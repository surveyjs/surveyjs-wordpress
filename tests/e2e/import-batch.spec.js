const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const IMPORT_URL = "/wp-admin/admin.php?page=surveyjs_import&source=wpforms";
const IMPORT_TIMEOUT = { timeout: 300000 };

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/wpforms-entries.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

async function recordStatus(page) {
  await page.evaluate(() => {
    window.sjsStatusLog = [];
    new MutationObserver(() => {
      const status = document.querySelector(".wp-sjs-import-status [role=status]");
      const text = status ? status.textContent : "";
      if (text && window.sjsStatusLog[window.sjsStatusLog.length - 1] !== text) {
        window.sjsStatusLog.push(text);
      }
    }).observe(document.body, { childList: true, subtree: true, characterData: true });
  });
}

async function selectForms(page, ids) {
  for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
    await checkbox.setChecked(ids.includes(Number(await checkbox.getAttribute("value"))));
  }
}

test.describe("batch import", () => {
  test.describe.configure({ timeout: 900000 });

  let name;
  let wpformsIds = [];
  let entriesFormId;

  test.afterEach(async ({ page }) => {
    for (const id of wpformsIds) {
      await page.goto("/wp-admin/admin.php?page=wpforms-overview");
      const trash = page.locator(`a[href*="action=trash"][href*="form_id=${id}&"]`).first();
      await page.goto(await trash.getAttribute("href"));
    }
    if (entriesFormId) {
      fixture("cleanup", entriesFormId);
    }
    await deleteSurveys(page, name);
    wpformsIds = [];
    entriesFormId = null;
  });

  test("imports the selected forms one by one with progress and keeps going after an error", async ({ page }) => {
    await page.goto("/wp-admin/admin.php?page=wpforms-builder&view=setup");
    const builderNonce = await page.evaluate(() => window.wpforms_builder?.nonce);
    test.skip(!builderNonce, "WPForms is not active");

    name = uniqueName("E2E Batch");
    for (const suffix of ["A", "B", "C"]) {
      const created = await (await page.request.post("/wp-admin/admin-ajax.php", {
        form: { action: "wpforms_new_form", title: `${name} ${suffix}`, template: "simple-contact-form-template", nonce: builderNonce }
      })).json();
      wpformsIds.push(Number(created.data.id));
    }

    await page.goto(IMPORT_URL);
    await selectForms(page, wpformsIds);
    await page.evaluate(([title, afterId]) => {
      const line = document.createElement("p");
      line.innerHTML = '<label><input type="checkbox" name="forms[]" value="999999999" checked> </label>';
      line.querySelector("label").append(title);
      document.querySelector(`input[name="forms[]"][value="${afterId}"]`).closest("p").after(line);
    }, [`${name} missing`, wpformsIds[0]]);
    await recordStatus(page);
    await page.getByRole("button", { name: "Import" }).click();

    const status = page.locator(".wp-sjs-import-status");
    await expect(status).toContainText("Import finished. Forms processed: 4, with errors: 1.", IMPORT_TIMEOUT);
    await expect(status).toHaveClass(/notice-warning/);
    const lines = page.locator(".wp-sjs-import-results li");
    await expect(lines).toHaveCount(4);
    await expect(lines.nth(0)).toContainText(`${name} A — imported`);
    await expect(lines.nth(1)).toContainText("The WPForms form was not found.");
    await expect(lines.nth(2)).toContainText(`${name} B — imported`);
    await expect(lines.nth(3)).toContainText(`${name} C — imported`);
    await expect(lines.nth(3).getByRole("link", { name: "Edit" })).toHaveAttribute("href", /page=surveyjs_editor&id=\d+/);
    await expect(page.locator("form[data-sjs-import]")).toBeHidden();
    await expect(page.getByRole("link", { name: "Go to all forms" })).toBeVisible();

    const log = await page.evaluate(() => window.sjsStatusLog);
    expect(log.filter(text => text.startsWith("Importing"))).toEqual([
      `Importing 1 of 4: ${name} A…`,
      `Importing 2 of 4: ${name} missing…`,
      `Importing 3 of 4: ${name} B…`,
      `Importing 4 of 4: ${name} C…`
    ]);

    await page.goto(IMPORT_URL);
    await selectForms(page, wpformsIds.slice(0, 1));
    await page.getByRole("button", { name: "Import" }).click();
    await expect(page.locator(".wp-sjs-import-status")).toContainText("Import finished. Forms processed: 1.", IMPORT_TIMEOUT);
    await expect(page.locator(".wp-sjs-import-results li")).toContainText(`${name} A — updated`);
  });

  test("imports WPForms entries in chunks and copies their files once", async ({ page, baseURL }) => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to create WPForms entries");
    name = uniqueName("E2E Batch entries");
    const created = fixture("create", name, 5);
    entriesFormId = created.formId;

    await page.goto(IMPORT_URL);
    await selectForms(page, [entriesFormId]);
    await page.getByLabel("Import entries").check();
    await page.locator("form[data-sjs-import]").evaluate(form => form.setAttribute("data-entries-per-request", "2"));
    await recordStatus(page);
    await page.getByRole("button", { name: "Import" }).click();

    const line = page.locator(".wp-sjs-import-results li", { hasText: name });
    await expect(line).toContainText("Entries: 5 added, 0 updated.", IMPORT_TIMEOUT);
    await expect(line).toContainText("5 entry files copied to the SurveyJS uploads folder.");
    await expect(line).toContainText("1 entry file was not found or has a file type that is not allowed, so its entry keeps the link to the original file.");
    const log = await page.evaluate(() => window.sjsStatusLog);
    expect(log).toEqual(expect.arrayContaining([
      `Importing 1 of 1: ${name} — entries 2 of 5…`,
      `Importing 1 of 1: ${name} — entries 4 of 5…`
    ]));

    const surveyId = Number(new URL(await line.getByRole("link", { name: "Edit" }).getAttribute("href"), baseURL).searchParams.get("id"));
    const first = fixture("results", surveyId);
    expect(first.entries).toHaveLength(5);
    const values = first.entries.map(entry => entry.documents);
    const copiedFiles = values.flat().map(file => file.content).filter(url => url.includes("/uploads/surveyjs/"));
    expect(copiedFiles).toHaveLength(5);
    for (const [index, value] of values.entries()) {
      expect(value[0]).toMatchObject({ name: `Document ${index + 1}.txt`, type: "text/plain" });
      expect(value[0].content.startsWith(`${baseURL}/wp-content/uploads/surveyjs/`)).toBe(true);
      expect(value[0].content).toMatch(/\/surveyjs\/[\w.]+\.txt$/);
      expect(fixture("stored-file", value[0].content).content).toBe(`E2E file ${index + 1} of ${name}`);
    }
    expect(values[4][1].content).toBe(`${baseURL}/wp-content/uploads/wpforms/${entriesFormId}-e2e/missing.txt`);
    expect(fixture("originals", entriesFormId)).toEqual(["doc-1.txt", "doc-2.txt", "doc-3.txt", "doc-4.txt", "doc-5.txt"]);

    await page.goto(IMPORT_URL);
    await selectForms(page, [entriesFormId]);
    await page.getByLabel("Import entries").check();
    await page.getByRole("button", { name: "Import" }).click();
    await expect(line).toContainText("Entries: 0 added, 5 updated.", IMPORT_TIMEOUT);
    await expect(line).not.toContainText("copied to the SurveyJS uploads folder");
    const second = fixture("results", surveyId);
    expect(second.entries.map(entry => entry.documents)).toEqual(values);
    expect(second.storedFiles).toBe(first.storedFiles);
    expect(fixture("originals", entriesFormId)).toHaveLength(5);
  });
});

test.describe("import without JavaScript", () => {
  test.use({ javaScriptEnabled: false });
  test.describe.configure({ timeout: 900000 });

  let name;
  let entriesFormId;

  test.afterEach(async ({ browser, baseURL, storageState }) => {
    if (entriesFormId) {
      fixture("cleanup", entriesFormId);
    }
    const context = await browser.newContext({ baseURL, storageState, javaScriptEnabled: true });
    await deleteSurveys(await context.newPage(), name);
    await context.close();
    entriesFormId = null;
  });

  test("imports the form and all its entries in one request", async ({ page }) => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to create WPForms entries");
    name = uniqueName("E2E No JS");
    entriesFormId = fixture("create", name, 3).formId;

    await page.goto(IMPORT_URL);
    await selectForms(page, [entriesFormId]);
    await page.getByLabel("Import entries").check();
    await page.getByRole("button", { name: "Import" }).click();

    await expect(page).toHaveURL(/imported=1/, IMPORT_TIMEOUT);
    const line = page.locator(".wrap li", { hasText: name });
    await expect(line).toContainText("Entries: 3 added, 0 updated.");
    await expect(line).toContainText("3 entry files copied to the SurveyJS uploads folder.");
    await expect(line).toContainText("1 entry file was not found");
    await expect(page.locator(".wp-sjs-import-status")).toHaveCount(0);
  });
});
