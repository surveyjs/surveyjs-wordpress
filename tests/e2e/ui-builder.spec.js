const { test, expect } = require("@playwright/test");
const { uniqueName, saveSurvey, createSurvey, deleteSurveys } = require("./helpers");

const UI_BUILDER_URL = "https://surveyjs.io/wordpress-ui-builder";

const MOCK_UI_BUILDER = `<!doctype html>
<script>
  window.addEventListener("message", (event) => {
    if (event.data.type !== "load") return;
    const json = Object.assign({}, event.data.json, { title: "Edited in UI Builder" });
    const theme = Object.assign({}, event.data.theme, { themeName: "ui-builder-theme" });
    event.source.postMessage({ source: "surveyjs-ui-builder", type: "save", surveyId: event.data.surveyId, json, theme }, event.origin);
    window.close();
  });
  window.opener.postMessage({ source: "surveyjs-ui-builder", type: "ready" }, "*");
</script>`;

const modelValue = (page, index) => page.evaluate(i => window.monaco.editor.getModels()[i].getValue(), index);

test.describe("UI Builder", () => {
  let name;

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
  });

  test("sends the survey to the UI Builder and takes the edited JSON back", async ({ page, context }) => {
    name = uniqueName("E2E builder");
    const surveyId = await createSurvey(page, name);
    await context.route(`${UI_BUILDER_URL}**`, route => route.fulfill({ contentType: "text/html", body: MOCK_UI_BUILDER }));

    const popupEvent = page.waitForEvent("popup");
    await page.getByRole("button", { name: "Open in UI Builder" }).click();
    const popup = await popupEvent;

    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("Changes from the UI Builder are in the editor.");
    await expect.poll(() => popup.isClosed()).toBe(true);
    expect(await modelValue(page, 0)).toContain("Edited in UI Builder");
    expect(await modelValue(page, 0)).toContain("e2e_question");
    expect(await modelValue(page, 1)).toContain("ui-builder-theme");

    await saveSurvey(page);
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}`);
    await expect(page.locator(".monaco-editor")).toBeVisible();
    expect(await modelValue(page, 0)).toContain("Edited in UI Builder");
  });

  test("ignores messages from other origins", async ({ page }) => {
    name = uniqueName("E2E builder origin");
    const surveyId = await createSurvey(page, name);

    await page.evaluate(id => window.postMessage({ source: "surveyjs-ui-builder", type: "save", surveyId: id, json: { title: "Forged" }, theme: {} }, "*"), surveyId);
    await page.waitForTimeout(500);
    expect(await modelValue(page, 0)).not.toContain("Forged");
  });
});
