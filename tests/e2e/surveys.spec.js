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

  test("opens the full preview from the list", async ({ page }) => {
    name = uniqueName("E2E preview");
    const id = await createSurvey(page, name);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    await row.hover();
    const link = row.getByRole("link", { name: "Preview", exact: true });
    await expect(link).toHaveAttribute("target", "_blank");
    const href = new URL(await link.getAttribute("href"), page.url());
    expect(href.searchParams.get("page")).toBe("surveyjs_editor");
    expect(href.searchParams.get("id")).toBe(String(id));

    const [preview] = await Promise.all([page.waitForEvent("popup"), link.click()]);
    await expect(preview.locator(".wp-sjs-editor")).toHaveClass(/wp-sjs-editor--full-preview/);
    const frame = preview.locator("#wp-sjs-full-preview-frame");
    await expect(frame).toHaveAttribute("src", await preview.evaluate(() => window.surveyjsEditor.config.fullPreviewUrl));
    await expect(preview.frameLocator("#wp-sjs-full-preview-frame").getByText("What is your name?")).toBeVisible();
    await preview.close();
  });

  test("duplicates a survey", async ({ page }) => {
    name = uniqueName("E2E duplicate");
    await createSurvey(page, name);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    await setEditorJson(page, { themeName: "e2e-theme" });
    await saveSurvey(page);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    await row.hover();
    await row.getByRole("link", { name: "Duplicate", exact: true }).click();
    await expect(page.locator(".notice-success")).toContainText("Form duplicated.");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .row-title")).toHaveText([name, `Copy of ${name}`]);

    await page.locator("#the-list .row-title", { hasText: `Copy of ${name}` }).click();
    await expect(page.frameLocator("#wp-sjs-preview").getByText("What is your name?")).toBeVisible();
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
    await expect(page.locator(".notice-success")).toContainText("1 form deleted.");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });
});
