// Replaces the plugin files in the WordPress container and wipes every SurveyJS table, option and form there,
// so it runs only against a throwaway site whose plugins/surveyjs folder is not a bind mount (see the env vars below).
//   UPGRADE_E2E=1          opt in
//   WP_CONTAINER           WordPress container (php, sh and tar inside); WP_BASE_URL / WP_USERNAME / WP_PASSWORD as usual
//   UPGRADE_LEGACY_REF     git ref of the pre-3 plugin, archived from src/trunk (default: master)
//   UPGRADE_LEGACY_DIR     host folder with the pre-3 plugin instead, e.g. the unzipped wordpress.org package
//   UPGRADE_BASE_DIR       host folder with the plugin to upgrade to (default: this repo's src/trunk)
//   UPGRADE_PLUGIN_DIR     plugin folder in the container (default: /var/www/html/wp-content/plugins/surveyjs)
const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");

const WP_CONTAINER = process.env.WP_CONTAINER;
const REPO = path.join(__dirname, "../..");
const PLUGIN_DIR = process.env.UPGRADE_PLUGIN_DIR || "/var/www/html/wp-content/plugins/surveyjs";
const UPLOADS_DIR = path.posix.join(PLUGIN_DIR, "../../uploads/surveyjs");
const STAGE_DIR = "/tmp/sjs-upgrade-plugin";
const LEGACY_REF = process.env.UPGRADE_LEGACY_REF || "master";
const LEGACY_DIR = process.env.UPGRADE_LEGACY_DIR;
const BASE_DIR = process.env.UPGRADE_BASE_DIR || path.join(REPO, "src/trunk");
// PHP's opcache rechecks file timestamps every 2 s by default; WordPress's own updater invalidates them, a file copy does not.
const OPCACHE_REVALIDATE_MS = 2500;

const FORMS_URL = "/wp-admin/admin.php?page=surveyjs-main-menu";
const SETTINGS_URL = "/wp-admin/admin.php?page=surveyjs-settings";
const LICENSE_KEY = "E2E-LEGACY-LICENSE-KEY";

const CONTACT_NAME = `Customer's "VIP" café — опрос`;
const CONTACT_JSON = {
  title: `Say "hello" — привет 👋 日本語`,
  description: "Path C:\\temp\\file.txt",
  pages: [{
    name: "page1",
    elements: [
      { type: "text", name: "full_name", title: `Your "full" name`, isRequired: true },
      { type: "dropdown", name: "city", title: "Город", choices: ["Москва", "Zürich", "東京"] },
      { type: "comment", name: "note", title: "Note" }
    ]
  }],
  completedHtml: `<h3>Спасибо, "friend"!</h3>`
};
const CONTACT_THEME = {
  themeName: "custom",
  colorPalette: "light",
  isPanelless: true,
  cssVariables: { "--sjs-primary-backcolor": "rgba(214, 40, 40, 1)", "--sjs-general-backcolor-dim": "rgba(255, 250, 240, 1)" }
};
const UPLOAD_NAME = "Upload form";
const UPLOAD_JSON = {
  elements: [
    { type: "file", name: "uploaded_doc", title: "Uploaded document", storeDataAsText: false },
    { type: "file", name: "inline_doc", title: "Inline document" }
  ]
};
const DEFAULT_THEME = { themeName: "default", colorPalette: "light", isPanelless: false, cssVariables: {} };
const SLASHED_NAME = "Form saved before 2.5";
const SLASHED_JSON = { elements: [{ type: "text", name: "old_q", title: `Legacy "quoted" question` }] };
const ROLLBACK_NAME = "Added after rollback";
const ROLLBACK_JSON = { elements: [{ type: "text", name: "rollback_q", title: "Rollback question" }] };

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: [input ? "pipe" : "ignore", "pipe", "pipe"], maxBuffer: 256 * 1024 * 1024 }).toString();
}

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", "-u", "33:33", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/upgrade.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

function assertThrowawayPluginDir() {
  const mounts = docker(["exec", WP_CONTAINER, "cat", "/proc/self/mountinfo"]).split("\n").map(line => line.split(" ")[4]);
  if (mounts.includes(PLUGIN_DIR)) {
    throw new Error(`${PLUGIN_DIR} is a bind mount in ${WP_CONTAINER}; the upgrade test would overwrite its source`);
  }
}

function removePlugin() {
  docker(["exec", WP_CONTAINER, "rm", "-rf", PLUGIN_DIR, STAGE_DIR]);
}

async function installPlugin(source) {
  removePlugin();
  if (source.dir) {
    docker(["exec", WP_CONTAINER, "mkdir", "-p", STAGE_DIR]);
    docker(["cp", `${source.dir.replace(/\\/g, "/")}/.`, `${WP_CONTAINER}:${STAGE_DIR}`]);
  } else {
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `mkdir -p ${STAGE_DIR} && tar -x -C ${STAGE_DIR}`], source.tar);
  }
  docker(["exec", WP_CONTAINER, "sh", "-c", `mv ${STAGE_DIR} ${PLUGIN_DIR} && chown -R 33:33 ${PLUGIN_DIR}`]);
  await new Promise(resolve => setTimeout(resolve, OPCACHE_REVALIDATE_MS));
}

function legacyPlugin() {
  if (LEGACY_DIR) {
    return { dir: LEGACY_DIR };
  }
  return { tar: execFileSync("git", ["archive", "--format=tar", `${LEGACY_REF}:src/trunk`], { cwd: REPO, maxBuffer: 256 * 1024 * 1024 }) };
}

// Same escaping as serializeAttributes() in @wordpress/blocks, so the markup matches what the old block saved.
function blockMarkup(name, attributes) {
  const json = JSON.stringify(attributes)
    .replace(/--/g, "\\u002d\\u002d")
    .replace(/</g, "\\u003c")
    .replace(/>/g, "\\u003e")
    .replace(/&/g, "\\u0026")
    .replace(/\\"/g, "\\u0022");
  return `<!-- wp:${name} ${json} /-->`;
}

function legacyShortcode(form) {
  return `[Survey id="${form.id}" name="${form.name}"] `;
}

function legacyBlock(form) {
  return blockMarkup("sjs/gutenberg-block", { surveyid: String(form.id), shortcode: legacyShortcode(form) });
}

function createPage(title, content) {
  const page = fixture("post", title, Buffer.from(content).toString("base64"));
  expect(page.error).toBeUndefined();
  return page;
}

async function pageHtml(page, url) {
  return (await page.request.get(url)).text();
}

function nonceIn(html, pattern) {
  const match = html.match(pattern);
  expect(match, `nonce ${pattern} on the page`).not.toBeNull();
  return match[1];
}

async function legacyAjax(page, action, form) {
  const response = await page.request.post(`/wp-admin/admin-ajax.php?action=${action}`, { form });
  expect(response.ok()).toBe(true);
  return response;
}

// The same requests the 2.5.x "Add Survey" button, title editor and Creator autosave send.
async function legacyCreateForm(page, name, json, theme) {
  const list = await pageHtml(page, FORMS_URL);
  const created = await (await legacyAjax(page, "SurveyJS_AddSurvey", { Name: "New Survey", _wpnonce: nonceIn(list, /Name: "New Survey", _wpnonce: '(\w+)'/) })).json();
  const id = Number(created.Id);
  const editor = await pageHtml(page, `/wp-admin/admin.php?page=surveyjs_editor&id=${id}&name=New%20Survey`);
  await legacyAjax(page, "SurveyJS_RenameSurvey", { Id: String(id), Name: name, _wpnonce: nonceIn(editor, /Name: surveyName, _wpnonce: '(\w+)'/) });
  const saved = await (await legacyAjax(page, "SurveyJS_SaveSurvey", {
    Id: String(id),
    Json: JSON.stringify(json),
    Theme: JSON.stringify(theme),
    _wpnonce: nonceIn(editor, /Json: json, Theme: theme, _wpnonce: '(\w+)'/)
  })).json();
  expect(saved.IsSuccess).toBe(1);
  return id;
}

async function legacySubmit(page, link, data) {
  const html = await pageHtml(page, link);
  const nonce = nonceIn(html, /Json : JSON\.stringify\(sender\.data\), _wpnonce: '(\w+)'/);
  const surveyId = nonceIn(html, /SurveyId: '(\d+)'/);
  await legacyAjax(page, "SurveyJS_SaveResult", { SurveyId: surveyId, Json: JSON.stringify(data), _wpnonce: nonce });
}

async function chooseFile(page, index, name, text) {
  const fileChooser = page.waitForEvent("filechooser");
  await page.locator(".wp-sjs-plugin .sd-file__choose-btn").nth(index).click();
  await (await fileChooser).setFiles({ name, mimeType: "text/plain", buffer: Buffer.from(text) });
}

async function complete(page) {
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
  await page.getByRole("button", { name: "Complete" }).click();
  expect((await saved).ok()).toBe(true);
}

function legacyForm(state, name) {
  const row = state.legacyForms.find(form => form.name === name);
  expect(row, `pre-3 form "${name}"`).toBeTruthy();
  return { id: Number(row.id), name: row.name };
}

function migratedForm(state, legacyId) {
  const form = state.forms.find(f => f.legacyId === legacyId);
  expect(form, `form migrated from pre-3 ID ${legacyId}`).toBeTruthy();
  return form;
}

function entriesOf(state, formId) {
  return state.entries.filter(entry => Number(entry.form_id) === formId);
}

function pluginLogLines(offset) {
  return fixture("log", offset).lines.filter(line => /surveyjs|PHP Fatal/i.test(line));
}

async function expectForm(page, link, formId, text) {
  await page.goto(link);
  await expect(page.locator(".wp-sjs-plugin")).toHaveAttribute("id", `surveyContainer-${formId}`);
  await expect(page.locator(".wp-sjs-plugin").getByText(text, { exact: true })).toBeVisible();
}

async function expectEntry(page, formId, text) {
  await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${formId}`);
  await expect(page.locator("#the-list tr", { hasText: text })).toHaveCount(1);
}

test.describe.serial("upgrade from the pre-3 plugin", () => {
  test.skip(process.env.UPGRADE_E2E !== "1" || !WP_CONTAINER, "Set UPGRADE_E2E=1 and WP_CONTAINER to a throwaway WordPress container");
  test.setTimeout(600000);
  test.use({ actionTimeout: 60000 });

  const site = {};

  test("keeps forms, entries, files, pages and settings after replacing 2.5.x with the current plugin", async ({ page }) => {
    await test.step("install 2.5.x on a site without SurveyJS data", async () => {
      assertThrowawayPluginDir();
      removePlugin();
      fixture("reset");
      docker(["exec", WP_CONTAINER, "rm", "-rf", UPLOADS_DIR]);
      await installPlugin(legacyPlugin());
      expect(fixture("activate").error).toBeNull();
    });

    await test.step("save the license key on the 2.5.x Settings page", async () => {
      await page.goto(SETTINGS_URL);
      await page.locator("input[name='surveyjs-settings[license_key]']").fill(LICENSE_KEY);
      await page.getByRole("button", { name: "Save Changes" }).click();
      await expect(page.getByText("Successfully updated!")).toBeVisible();
    });

    await test.step("create forms the way 2.5.x does", async () => {
      const ids = fixture("legacy-ids");
      site.takenId = ids.taken;
      fixture("legacy-next-id", ids.taken);
      site.contactId = await legacyCreateForm(page, CONTACT_NAME, CONTACT_JSON, CONTACT_THEME);
      expect(site.contactId).toBe(ids.taken);
      fixture("legacy-next-id", ids.free);
      site.uploadId = await legacyCreateForm(page, UPLOAD_NAME, UPLOAD_JSON, DEFAULT_THEME);
      site.slashedId = fixture("legacy-slashed-row", SLASHED_NAME, JSON.stringify(SLASHED_JSON)).id;

      const state = fixture("state");
      site.contact = legacyForm(state, state.legacyForms.find(f => Number(f.id) === site.contactId).name);
      site.upload = legacyForm(state, UPLOAD_NAME);
      site.slashed = legacyForm(state, SLASHED_NAME);

      await page.goto(FORMS_URL);
      await expect(page.locator(".surveys-list tbody tr")).toHaveCount(3);
    });

    await test.step("embed the forms with [Survey] shortcodes and the old block", async () => {
      site.contactShortcodePage = createPage("Contact shortcode", legacyShortcode(site.contact));
      site.contactBlockPage = createPage("Contact block", legacyBlock(site.contact));
      site.uploadBlockPage = createPage("Upload block", legacyBlock(site.upload));
      site.slashedShortcodePage = createPage("Old form shortcode", legacyShortcode(site.slashed));
    });

    await test.step("submit entries with 2.5.x, including uploaded and inline files", async () => {
      await page.goto(site.contactShortcodePage.link);
      await page.getByLabel(`Your "full" name`).fill(`Ann "Quote" Ünïcödé 日本語 🎉 <b>bold</b>`);
      await page.getByLabel("Note").fill("Line with \\ backslash");
      await complete(page);
      await expect(page.getByText(`Спасибо, "friend"!`)).toBeVisible();

      await legacySubmit(page, site.contactBlockPage.link, { full_name: "Bob O'Brien", city: "東京" });

      await page.goto(site.uploadBlockPage.link);
      const uploaded = page.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
      await chooseFile(page, 0, "uploaded.txt", "E2E uploaded by 2.5.x");
      expect((await uploaded).ok()).toBe(true);
      await chooseFile(page, 1, "inline.txt", "E2E inline in 2.5.x");
      await expect(page.getByText("inline.txt").first()).toBeVisible();
      await complete(page);

      const entries = fixture("state").entries;
      expect(entries).toHaveLength(3);
      const files = JSON.parse(entries[2].json);
      expect(files.uploaded_doc[0].content).toMatch(/\/wp-content\/uploads\/surveyjs\/[^/]+\.txt$/);
      expect(files.inline_doc[0].content).toMatch(/^data:text\/plain;base64,/);
    });

    await test.step("replace the plugin files with the current version, as an update does", async () => {
      site.logOffset = fixture("log").size;
      await installPlugin({ dir: BASE_DIR });
      await page.goto(FORMS_URL);
      await expect(page.locator("#the-list tr")).toHaveCount(3);
    });

    const state = fixture("state");
    const contact = migratedForm(state, site.contactId);
    const upload = migratedForm(state, site.uploadId);
    const slashed = migratedForm(state, site.slashedId);
    Object.assign(site, { contactPost: contact.id, uploadPost: upload.id, slashedPost: slashed.id });

    await test.step("migrate forms with their names, JSON, theme and IDs", async () => {
      expect(state.forms).toHaveLength(3);
      expect(contact.id).not.toBe(site.contactId);
      expect(upload.id).toBe(site.uploadId);
      expect(slashed.id).toBe(site.slashedId);
      expect(state.options.legacyMap).toEqual({ [site.contactId]: contact.id });
      expect(contact.name).toBe(CONTACT_NAME);
      expect(JSON.parse(contact.json)).toEqual(CONTACT_JSON);
      expect(JSON.parse(contact.theme)).toEqual(CONTACT_THEME);
      expect(JSON.parse(upload.json)).toEqual(UPLOAD_JSON);

      await page.goto(FORMS_URL);
      expect((await page.locator("#the-list .row-title").allTextContents()).sort()).toEqual([CONTACT_NAME, UPLOAD_NAME, SLASHED_NAME].sort());
      await expect(page.locator("#the-list tr")).toHaveCount(3);
    });

    await test.step("keep the license key, also after saving the new Settings page", async () => {
      expect(state.options.settings).toMatchObject({ license_key: LICENSE_KEY });
      await page.goto(SETTINGS_URL);
      await page.getByRole("button", { name: "Save Changes" }).click();
      await expect(page.getByText("Successfully updated!")).toBeVisible();
      expect(fixture("state").options.settings).toMatchObject({ license_key: LICENSE_KEY });
    });

    await test.step("open migrated forms in the editor", async () => {
      await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${contact.id}`);
      await expect(page.locator("#wp-sjs-name-text")).toHaveText(CONTACT_NAME);
      await expect(page.locator(".monaco-editor")).toBeVisible();
      const text = await page.evaluate(() => window.monaco.editor.getEditors()[0].getValue());
      expect(JSON.parse(text)).toEqual(CONTACT_JSON);
      await expect(page.frameLocator("#wp-sjs-preview").getByText(`Your "full" name`)).toBeVisible();

      await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${slashed.id}`);
      await expect(page.frameLocator("#wp-sjs-preview").getByText(`Legacy "quoted" question`)).toBeVisible();
    });

    await test.step("show 2.5.x entries and files in Results", async () => {
      expect(entriesOf(state, contact.id).map(e => e.status)).toEqual(["read", "read"]);
      expect(JSON.parse(entriesOf(state, contact.id)[0].json).full_name).toBe(`Ann "Quote" Ünïcödé 日本語 🎉 bold`);
      await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${contact.id}`);
      await expect(page.locator("#the-list tr")).toHaveCount(2);
      // WordPress's emoji script replaces emoji with images, so page text is matched without them.
      await expect(page.locator("#the-list tr", { hasText: `Ann "Quote" Ünïcödé 日本語` })).toHaveCount(1);
      await expect(page.locator("#the-list tr", { hasText: "Bob O'Brien" })).toHaveCount(1);

      await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${upload.id}`);
      const row = page.locator("#the-list tr").first();
      await expect(row).toContainText("uploaded.txt");
      await expect(row).toContainText("inline.txt");
      const download = await page.request.get(await row.locator("a", { hasText: "uploaded.txt" }).getAttribute("href"));
      expect(download.ok()).toBe(true);
      expect(await download.text()).toBe("E2E uploaded by 2.5.x");
    });

    await test.step("render migrated forms through [Survey], [surveyjs] and the old block", async () => {
      await expectForm(page, site.contactShortcodePage.link, contact.id, `Your "full" name`);
      await expect(page.getByText(`Say "hello" — привет`)).toBeVisible();
      const primary = await page.locator(".sd-root-modern").first().evaluate(el => getComputedStyle(el).getPropertyValue("--sjs2-color-project-brand-600").trim());
      expect(primary).toBe("rgba(214, 40, 40, 1)");
      await expectForm(page, site.contactBlockPage.link, contact.id, `Your "full" name`);
      await expectForm(page, site.uploadBlockPage.link, upload.id, "Uploaded document");
      await expectForm(page, site.slashedShortcodePage.link, slashed.id, `Legacy "quoted" question`);
      const newShortcodePage = createPage("Contact new shortcode", `[surveyjs id="${contact.id}"]`);
      await expectForm(page, newShortcodePage.link, contact.id, `Your "full" name`);

      await page.goto(site.contactBlockPage.link);
      await page.getByLabel(`Your "full" name`).fill("Submitted after the upgrade");
      await complete(page);
      await expectEntry(page, contact.id, "Submitted after the upgrade");
    });

    await test.step("load the old block in the block editor", async () => {
      await page.goto(`/wp-admin/post.php?post=${site.contactBlockPage.id}&action=edit`);
      await page.waitForFunction(() => window.wp?.data?.select("core/block-editor")?.getBlocks().length > 0);
      const block = await page.evaluate(() => {
        const [first] = window.wp.data.select("core/block-editor").getBlocks();
        return { name: first.name, isValid: first.isValid };
      });
      expect(block).toEqual({ name: "sjs/gutenberg-block", isValid: true });
      await expect(page.frameLocator("iframe[name='editor-canvas']").locator(".wp-block-sjs-gutenberg-block input")).toHaveValue(CONTACT_NAME);
    });

    await test.step("log no PHP notices from the plugin", async () => {
      expect(pluginLogLines(site.logOffset)).toEqual([]);
    });
  });

  test("brings forms and entries added after a rollback to 2.5.x back to the current plugin", async ({ page }) => {
    await test.step("delete a migrated form", async () => {
      await page.goto(`${FORMS_URL}&s=${encodeURIComponent(SLASHED_NAME)}`);
      const row = page.locator("#the-list tr", { hasText: SLASHED_NAME });
      await row.hover();
      await row.getByRole("link", { name: "Trash", exact: true }).click();
      await expect(page.locator(".wp-sjs-snackbar.is-success")).toContainText("1 form moved to the Trash.");
      await page.goto(`${FORMS_URL}&post_status=trash&s=${encodeURIComponent(SLASHED_NAME)}`);
      const trashed = page.locator("#the-list tr", { hasText: SLASHED_NAME });
      await trashed.hover();
      page.once("dialog", dialog => dialog.accept());
      await trashed.getByRole("link", { name: "Delete Permanently", exact: true }).click();
      await expect(page.locator(".wp-sjs-snackbar.is-success")).toContainText("1 form permanently deleted.");
    });

    await test.step("roll back by replacing the files with 2.5.x and add a form and entries there", async () => {
      await installPlugin(legacyPlugin());
      site.rollbackId = await legacyCreateForm(page, ROLLBACK_NAME, ROLLBACK_JSON, DEFAULT_THEME);
      site.rollbackPage = createPage("Rollback form", legacyBlock({ id: site.rollbackId, name: ROLLBACK_NAME }));
      await page.goto(site.rollbackPage.link);
      await page.getByLabel("Rollback question").fill("Rollback answer");
      await complete(page);
      await legacySubmit(page, site.contactShortcodePage.link, { full_name: "Sent during the rollback" });
    });

    await test.step("update to the current plugin again", async () => {
      site.logOffset = fixture("log").size;
      await installPlugin({ dir: BASE_DIR });
      await page.goto(FORMS_URL);
    });

    const state = fixture("state");
    const rollback = migratedForm(state, site.rollbackId);

    await test.step("migrate the new form and its entries and keep the deleted form deleted", async () => {
      expect((await page.locator("#the-list .row-title").allTextContents()).sort()).toEqual([ROLLBACK_NAME, CONTACT_NAME, UPLOAD_NAME].sort());
      await expect(page.locator("#the-list tr")).toHaveCount(3);
      expect(state.forms.find(f => f.legacyId === site.slashedId)).toBeUndefined();
      await expectForm(page, site.rollbackPage.link, rollback.id, "Rollback question");
      await expectForm(page, site.contactShortcodePage.link, site.contactPost, `Your "full" name`);
      await expectEntry(page, rollback.id, "Rollback answer");
      await expectEntry(page, site.contactPost, "Sent during the rollback");
    });

    await test.step("roll back once more, reactivating 2.5.x, and only submit an entry", async () => {
      expect(pluginLogLines(site.logOffset)).toEqual([]);
      await installPlugin(legacyPlugin());
      expect(fixture("reactivate").error).toBeNull();
      await legacySubmit(page, site.uploadBlockPage.link, { inline_doc: [{ name: "second.txt", type: "text/plain", content: "data:text/plain;base64,c2Vjb25k" }] });
      site.logOffset = fixture("log").size;
      await installPlugin({ dir: BASE_DIR });
      await page.goto(FORMS_URL);
      await expectEntry(page, site.uploadPost, "second.txt");
    });

    await test.step("log no PHP notices from the plugin", async () => {
      expect(pluginLogLines(site.logOffset)).toEqual([]);
    });
  });
});
