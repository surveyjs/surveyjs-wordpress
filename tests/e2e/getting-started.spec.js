const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, setEditorJson, saveSurvey, SURVEY_JSON } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const TEMPLATES_URL = "/wp-admin/admin.php?page=surveyjs-templates";
const GETTING_STARTED_URL = "/wp-admin/admin.php?page=surveyjs-getting-started";
const LIST_URL = "/wp-admin/admin.php?page=surveyjs-main-menu";

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/onboarding.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

function editorUrl(id) {
  return `/wp-admin/admin.php?page=surveyjs_editor&id=${id}`;
}

function step(page, title) {
  return page.locator(".wp-sjs-checklist__steps li").filter({ has: page.locator("strong").getByText(title, { exact: true }) });
}

async function restNonce(page) {
  return (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
}

async function restPage(page, id, data) {
  const nonce = await restNonce(page);
  const response = data
    ? await page.request.post(`/index.php?rest_route=/wp/v2/pages/${id}`, { headers: { "X-WP-Nonce": nonce }, data })
    : await page.request.get(`/index.php?rest_route=/wp/v2/pages/${id}&context=edit`, { headers: { "X-WP-Nonce": nonce } });
  return response.json();
}

async function openEditor(page, id) {
  await page.goto(editorUrl(id));
  await expect(page.locator(".monaco-editor")).toBeVisible();
}

// One new site walked through the checklist in order, so the tests share its state and run serially.
test.describe.serial("getting started on a site without forms", () => {
  const name = uniqueName("E2E first form");
  const pageIds = [];
  let formId;

  test.beforeAll(() => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to a throwaway sandbox container: the test deletes every form");
    fixture("fresh");
  });

  test.afterAll(() => {
    if (WP_CONTAINER && pageIds.length) fixture("delete", ...pageIds);
  });

  test("talks about the JSON editor and the UI Builder", async ({ page }) => {
    const blankText = page.locator(".wp-sjs-template--blank .wp-sjs-template__description");
    await page.goto(TEMPLATES_URL);
    await expect(blankText).toHaveText("Start from scratch and add questions in the JSON editor or the visual UI Builder.");
    await page.goto(GETTING_STARTED_URL);
    await expect(step(page, "Create a form")).toContainText("JSON editor or the visual UI Builder");
  });

  test("shows the next steps after the first save of a new form until dismissed", async ({ page }) => {
    await page.goto(TEMPLATES_URL);
    await page.getByLabel("Form name").fill(name);
    await page.getByRole("button", { name: "Create blank form" }).click();
    await expect(page).toHaveURL(/page=surveyjs_editor/);
    await expect(page.locator(".monaco-editor")).toBeVisible();
    await expect(page).not.toHaveURL(/[?&]new=/);
    formId = Number(new URL(page.url()).searchParams.get("id"));

    const hint = page.locator("#wp-sjs-editor-notice .wp-sjs-editor-next");
    await expect(hint).toHaveCount(0);
    await setEditorJson(page, SURVEY_JSON);
    await saveSurvey(page);
    await expect(hint).toHaveText(/^Next: Embed the form · Turn on email notifications/);

    await hint.getByRole("button", { name: "Turn on email notifications" }).click();
    await expect(page.locator(".wp-sjs-editor__tabs .nav-tab-active")).toHaveText("Email");
    await expect(page.locator("input[data-setting=notificationEnabled]")).toBeFocused();
    await expect(page.locator("#wp-sjs-send-test-email")).toHaveClass(/wp-sjs-editor__highlight/);

    await hint.getByRole("button", { name: "Embed the form" }).click();
    await expect(page.locator("#wp-sjs-embed-panel")).toBeVisible();
    await expect(page.locator("#wp-sjs-embed")).toHaveAttribute("aria-expanded", "true");

    await hint.getByRole("button", { name: "Dismiss this notice." }).click();
    await expect(hint).toHaveCount(0);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Config", exact: true }).click();
    await saveSurvey(page);
    await expect(hint).toHaveCount(0);

    await page.goto(GETTING_STARTED_URL);
    await expect(step(page, "Create a form").locator(".dashicons-yes-alt")).toBeVisible();
  });

  test("copies the shortcode and creates a draft page with the block, saving unsaved changes first", async ({ page }) => {
    await page.context().grantPermissions(["clipboard-read", "clipboard-write"]);
    await openEditor(page, formId);
    await page.locator("#wp-sjs-embed").click();
    const shortcode = page.locator("#wp-sjs-embed-shortcode");
    await expect(shortcode).toHaveValue(`[surveyjs id="${formId}"]`);
    await page.locator("#wp-sjs-embed-copy").click();
    await expect(page.locator("#wp-sjs-embed-copy")).toHaveText("Copied!");
    expect(await page.evaluate(() => navigator.clipboard.readText())).toBe(`[surveyjs id="${formId}"]`);

    await setEditorJson(page, { ...SURVEY_JSON, title: "Saved before embedding" });
    await page.locator("#wp-sjs-embed").click();
    await page.locator("#wp-sjs-embed").click();
    await expect(page.locator(".wp-sjs-embed__unsaved")).toBeVisible();
    await page.getByRole("link", { name: "Create a page with this form" }).click();
    await expect(page).toHaveURL(/post\.php\?post=\d+&action=edit/);
    const pageId = Number(new URL(page.url()).searchParams.get("post"));
    pageIds.push(pageId);

    const created = await restPage(page, pageId);
    expect(created.status).toBe("draft");
    expect(created.title.raw).toBe(name);
    expect(created.content.raw).toContain("<!-- wp:sjs/gutenberg-block");
    expect(created.content.raw).toContain(`"surveyid":"${formId}"`);

    await openEditor(page, formId);
    expect(await page.evaluate(() => window.monaco.editor.getEditors()[0].getValue())).toContain("Saved before embedding");

    await page.goto(GETTING_STARTED_URL);
    await expect(step(page, "Add the form to a page").locator(".dashicons-yes-alt")).toHaveCount(0);
    await expect(step(page, "Add the form to a page").getByRole("link", { name: "Create a page with this form" })).toHaveAttribute("href", new RegExp(`action=surveyjs_embed_page&(amp;)?form=${formId}&`));
    await restPage(page, pageId, { status: "publish" });
    await page.reload();
    await expect(step(page, "Add the form to a page").locator(".dashicons-yes-alt")).toBeVisible();

    await page.goto(LIST_URL);
    const row = page.locator("#the-list tr").filter({ has: page.getByRole("link", { name, exact: true }) });
    await expect(row.getByRole("link", { name: "Create a page", exact: true })).toHaveAttribute("href", new RegExp(`action=surveyjs_embed_page&(amp;)?form=${formId}&`));
  });

  test("adds the block to an existing page in the page editor without saving the page", async ({ page }) => {
    const nonce = await restNonce(page);
    const existing = await (await page.request.post("/index.php?rest_route=/wp/v2/pages", {
      headers: { "X-WP-Nonce": nonce },
      data: { title: `${name} existing`, status: "publish", content: "<!-- wp:paragraph --><p>Existing text</p><!-- /wp:paragraph -->" }
    })).json();
    pageIds.push(existing.id);

    await openEditor(page, formId);
    await page.locator("#wp-sjs-embed").click();
    await page.getByLabel("Add to an existing page").selectOption(String(existing.id));
    await page.getByRole("button", { name: "Add to page" }).click();
    await expect(page).toHaveURL(new RegExp(`post\\.php\\?post=${existing.id}&action=edit`));
    await expect(page.locator(".components-notice__content", { hasText: `The form "${name}" was added at the end of the page.` })).toBeVisible();
    await expect(page).not.toHaveURL(/surveyjs_embed=/);
    const blocks = await page.evaluate(() => window.wp.data.select("core/block-editor").getBlocks().map((block) => [block.name, block.attributes.surveyid || ""]));
    expect(blocks.at(-1)).toEqual(["sjs/gutenberg-block", String(formId)]);
    expect(blocks[0][0]).toBe("core/paragraph");
    expect((await restPage(page, existing.id)).content.raw).not.toContain("sjs/gutenberg-block");
  });

  test("the optional email step opens the Email tab, ticks once the notification is on and is not counted", async ({ page }) => {
    await page.goto("/wp-admin/index.php");
    const dashboardProgress = page.locator("#surveyjs_dashboard_widget .wp-sjs-dw__progress");
    await expect(dashboardProgress).toContainText("2 of 3 steps completed");
    await expect(dashboardProgress.getByRole("link", { name: "Receive your first entry" })).toBeVisible();
    await expect(dashboardProgress.locator("progress.wp-sjs-progress")).toHaveAttribute("value", "2");
    await expect(dashboardProgress.locator("progress.wp-sjs-progress")).toHaveAttribute("max", "3");
    await expect(page.locator("#wp-admin-bar-surveyjs-next-step a")).toHaveText("Next: Receive your first entry");

    await page.goto(GETTING_STARTED_URL);
    const emailStep = step(page, "Set up an email notification");
    await expect(emailStep.locator(".wp-sjs-checklist__optional")).toHaveText("Optional");
    await expect(step(page, "Receive your first entry").locator(".wp-sjs-checklist__optional")).toHaveCount(0);
    await expect(emailStep).toContainText("if they don't, set up an SMTP plugin");
    await emailStep.getByRole("link", { name: "Set up notification" }).click();
    await expect(page).toHaveURL(/tab=email&focus=notification/);
    const enable = page.locator("input[data-setting=notificationEnabled]");
    await expect(enable).toBeFocused();
    await expect(page.locator("#wp-sjs-send-test-email")).toHaveClass(/wp-sjs-editor__highlight/);
    await expect(enable).not.toBeChecked();
    await enable.check();
    await saveSurvey(page);

    await page.goto(GETTING_STARTED_URL);
    await expect(emailStep.locator(".dashicons-yes-alt")).toBeVisible();
    await expect(page.locator(".wp-sjs-checklist")).toContainText("2 of 3 steps completed");

    await page.goto(`${editorUrl(formId)}&tab=email`);
    await enable.uncheck();
    await saveSurvey(page);
  });

  test("a real entry completes the checklist without the email step and offers the review link once", async ({ page }) => {
    const placed = await restPage(page, pageIds[0]);
    await page.goto(placed.link);
    await page.getByLabel("What is your name?").fill("Jane");
    const saved = page.waitForResponse((response) => response.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    await page.goto(GETTING_STARTED_URL);
    const card = page.locator(".wp-sjs-checklist");
    await expect(card.getByRole("heading", { name: "You're all set!" })).toBeVisible();
    await expect(card.locator("progress")).toHaveCount(0);
    const suggestion = card.locator(".wp-sjs-checklist__suggestion");
    await expect(suggestion).toContainText("Optional");
    await expect(suggestion.getByRole("link", { name: "Set up an email notification" })).toHaveAttribute("href", /tab=email&(amp;)?focus=notification/);
    await expect(card.getByRole("link", { name: /^Rate SurveyJS Forms on WordPress\.org/ })).toHaveAttribute("href", "https://wordpress.org/support/plugin/surveyjs/reviews/#new-post");
    await expect(page.getByRole("link", { name: /Rate SurveyJS Forms/ })).toHaveCount(1);

    await page.goto("/wp-admin/index.php");
    await expect(page.locator("#surveyjs_dashboard_widget")).toBeVisible();
    await expect(page.locator(".wp-sjs-dw__progress")).toHaveCount(0);
  });
});
