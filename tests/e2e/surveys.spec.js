const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, addSurvey, renameSurvey, setEditorJson, saveSurvey, createSurvey, deleteSurveys } = require("./helpers");

test.describe("survey list", () => {
  let name;

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
  });

  test("adds a survey", async ({ page }) => {
    name = uniqueName("E2E add");
    await addSurvey(page);
    await renameSurvey(page, name);

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .row-title")).toHaveText([name]);
  });

  test("clones a survey", async ({ page }) => {
    name = uniqueName("E2E clone");
    await createSurvey(page, name);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    await setEditorJson(page, { themeName: "e2e-theme" });
    await saveSurvey(page);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    await row.hover();
    await row.getByRole("link", { name: "Clone", exact: true }).click();
    await expect(page.locator(".notice-success")).toContainText("Survey cloned.");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .row-title")).toHaveText([name, `Copy of ${name}`]);

    await page.locator("#the-list .row-title", { hasText: `Copy of ${name}` }).click();
    await expect(page.locator("#wp-sjs-preview").getByText("What is your name?")).toBeVisible();
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    expect(await page.evaluate(() => window.monaco.editor.getEditors()[0].getValue())).toContain("e2e-theme");
  });

  test("deletes a survey", async ({ page }) => {
    name = uniqueName("E2E delete");
    await createSurvey(page, name);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    await row.hover();
    page.once("dialog", dialog => dialog.accept());
    await row.getByRole("link", { name: "Delete", exact: true }).click();
    await expect(page.locator(".notice-success")).toContainText("1 survey deleted.");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });
});
