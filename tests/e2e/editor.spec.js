const { test, expect } = require("@playwright/test");
const { uniqueName, setEditorJson, saveSurvey, createSurvey, deleteSurveys } = require("./helpers");

const editorValue = page => page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue(window.monaco.editor.EndOfLinePreference.LF));

test.describe("JSON editor", () => {
  let name;

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
  });

  test("saves the survey and theme JSON", async ({ page }) => {
    name = uniqueName("E2E edit");
    await createSurvey(page, name);

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    await setEditorJson(page, { themeName: "e2e-theme" });
    await saveSurvey(page);

    await page.reload();
    await expect(page.locator(".monaco-editor")).toBeVisible();
    expect(await editorValue(page)).toContain("e2e_question");
    await expect(page.locator("#wp-sjs-preview").getByText("What is your name?")).toBeVisible();
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    expect(await editorValue(page)).toContain("e2e-theme");
  });

  test("shows linter findings and still saves", async ({ page }) => {
    name = uniqueName("E2E lint");
    await createSurvey(page, name);

    await setEditorJson(page, { elements: [{ type: "text", name: "q1", visibleIf: "{unknown} = 1" }] });
    await expect(page.locator(".wp-sjs-lint__bar")).toContainText("Static analysis: 1 issue");
    await expect(page.locator(".wp-sjs-lint-glyph-error")).toBeVisible();
    await page.locator(".wp-sjs-lint__finding").click();
    await expect(page.locator(".wp-sjs-lint__finding")).toHaveClass(/is-selected/);

    await page.getByRole("button", { name: "Save", exact: true }).click();
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("Form saved with 1 issue.");
  });

  test("blocks saving invalid JSON and formats valid JSON", async ({ page }) => {
    name = uniqueName("E2E format");
    await createSurvey(page, name);

    await page.evaluate(() => window.monaco.editor.getEditors()[0].setValue("{ \"elements\": ["));
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("Config:");
    await expect(page.getByRole("button", { name: "Save", exact: true })).toBeDisabled();
    await expect(page.locator(".wp-sjs-lint__bar")).toContainText("waiting for valid JSON");

    await page.evaluate(() => window.monaco.editor.getEditors()[0].setValue("{\"title\":\"Compact\"}"));
    await page.getByRole("button", { name: "Format" }).click();
    expect(await editorValue(page)).toBe("{\n  \"title\": \"Compact\"\n}");
    await expect(page.getByRole("button", { name: "Save", exact: true })).toBeEnabled();
  });

  test("does not warn about unsaved changes when nothing changed", async ({ page }) => {
    name = uniqueName("E2E unsaved");
    const id = await createSurvey(page, name);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${id}`);
    await expect(page.locator(".monaco-editor")).toBeVisible();
    let warned = false;
    page.on("dialog", dialog => {
      warned = true;
      dialog.accept();
    });
    await page.getByRole("link", { name: "Back" }).click();
    await expect(page).toHaveURL(/page=surveyjs-main-menu/);
    expect(warned).toBe(false);
  });
});
