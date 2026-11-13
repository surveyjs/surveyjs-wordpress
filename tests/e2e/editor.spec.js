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
    await expect(page.frameLocator("#wp-sjs-preview").getByText("What is your name?")).toBeVisible();
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

  test("toggles the GDPR agreement with a checkbox", async ({ page }) => {
    name = uniqueName("E2E GDPR");
    await createSurvey(page, name, { pages: [{ name: "page1", elements: [{ type: "text", name: "q1" }] }, { name: "page2", elements: [{ type: "text", name: "q2" }] }] });
    const gdpr = page.getByRole("checkbox", { name: "GDPR agreement" });
    const pageElements = async () => JSON.parse(await editorValue(page)).pages.map(item => item.elements.map(element => element.name));
    await expect(gdpr).not.toBeChecked();

    await gdpr.check();
    expect(await pageElements()).toEqual([["q1"], ["q2", "gdpr_agreement"]]);
    const question = JSON.parse(await editorValue(page)).pages[1].elements[1];
    expect(question).toMatchObject({ type: "boolean", displayMode: "checkbox", isRequired: true, validators: [{ type: "expression", expression: "{gdpr_agreement} = true" }] });
    expect(question.title).toContain("I consent");
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("Press Save to keep it");
    const preview = page.frameLocator("#wp-sjs-preview");
    await expect(preview.getByText("I consent")).toBeVisible();
    const privacyLink = question.title.match(/\[Privacy Policy\]\(([^)]+)\)/);
    if (privacyLink) {
      await expect(preview.locator("a", { hasText: "Privacy Policy" })).toHaveAttribute("href", privacyLink[1]);
    } else {
      await expect(preview.getByText("See our Privacy Policy.")).toBeVisible();
    }

    await page.locator(".monaco-editor textarea").focus();
    await page.keyboard.press("ControlOrMeta+z");
    expect(await pageElements()).toEqual([["q1"], ["q2"]]);
    await expect(gdpr).not.toBeChecked();

    await setEditorJson(page, { pages: [{ name: "page1", elements: [{ type: "panel", name: "p", elements: [{ type: "boolean", name: "gdpr_agreement" }] }] }, { name: "page2", elements: [{ type: "text", name: "q2" }] }] });
    await expect(gdpr).toBeChecked();
    await gdpr.uncheck();
    const json = JSON.parse(await editorValue(page));
    expect(json.pages[0].elements[0].elements).toEqual([]);
    expect(json.pages[1].elements.map(element => element.name)).toEqual(["q2"]);
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("removed");

    await page.evaluate(() => window.monaco.editor.getEditors()[0].setValue("{ \"elements\": ["));
    await expect(gdpr).toBeDisabled();

    await setEditorJson(page, {});
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    await expect(gdpr).toBeHidden();
  });

  test("does not warn about unsaved changes when nothing changed", async ({ page }) => {
    name = uniqueName("E2E unsaved");
    const id = await createSurvey(page, name);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${id}`);
    await expect(page.locator(".monaco-editor")).toBeVisible();
    let warned = false;
    const onDialog = dialog => {
      warned = true;
      dialog.accept();
    };
    page.on("dialog", onDialog);
    await page.getByRole("link", { name: "Back" }).click();
    await expect(page).toHaveURL(/page=surveyjs-main-menu/);
    page.off("dialog", onDialog);
    expect(warned).toBe(false);
  });
});

test.describe("admin menu", () => {
  let name;

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
  });

  test("opens All Forms from the top-level item and stays open on form pages", async ({ page }) => {
    name = uniqueName("E2E menu");
    const id = await createSurvey(page, name);
    const menu = page.locator("#toplevel_page_surveyjs-main-menu");
    const pages = [
      [`page=surveyjs_editor&id=${id}`, "All Forms"],
      [`page=surveyjs_results&id=${id}`, "All Forms"],
      ["page=surveyjs_import&source=cf7", "All Forms"],
      [`page=surveyjs-templates&form=${id}`, "Add New Form"],
      ["page=surveyjs-getting-started", "Getting Started"]
    ];
    for (const [query, current] of pages) {
      await page.goto(`/wp-admin/admin.php?${query}`);
      await expect(menu).toHaveClass(/wp-has-current-submenu/);
      await expect(menu).toHaveClass(/wp-menu-open/);
      await expect(menu.locator(".wp-submenu li.current")).toHaveText(current);
    }

    await expect(menu.locator(".wp-submenu li:not(.wp-submenu-head) a").first()).toHaveText("All Forms");
    await menu.locator("> a").click();
    await expect(page).toHaveURL(/page=surveyjs-main-menu$/);
    await expect(menu.locator(".wp-submenu li.current")).toHaveText("All Forms");
  });
});
