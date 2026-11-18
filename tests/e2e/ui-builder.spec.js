const { test, expect } = require("@playwright/test");
const { uniqueName, saveSurvey, createSurvey, deleteSurveys } = require("./helpers");

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

const mockUiBuilder = (onLoad) => `<!doctype html>
<script>
  window.received = [];
  window.addEventListener("message", (event) => {
    window.received.push(event.data);
    if (event.data.type === "load") (${onLoad})(event);
  });
  if (window.opener) window.opener.postMessage({ source: "surveyjs-ui-builder", type: "ready" }, "*");
</script>`;

const modelValue = (page, index) => page.evaluate(i => window.monaco.editor.getModels()[i].getValue(), index);
const uiBuilderUrl = page => page.evaluate(() => JSON.parse(document.getElementById("wp-sjs-editor-config").textContent).uiBuilderUrl);
const status = page => page.locator("#wp-sjs-ui-builder-status");
const manualPanel = page => page.locator("#wp-sjs-ui-builder-manual");

async function routeUiBuilder(page, context, body, status = 200) {
  await context.route(`${await uiBuilderUrl(page)}**`, route => route.fulfill({ status, contentType: "text/html", body }));
}

async function openUiBuilder(page) {
  const popupEvent = page.waitForEvent("popup");
  await page.getByRole("button", { name: "Open in free UI Builder" }).click();
  return popupEvent;
}

test.describe("UI Builder", () => {
  let name;

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
  });

  test("sends the survey to the UI Builder and takes the edited JSON back", async ({ page, context }) => {
    name = uniqueName("E2E builder");
    const surveyId = await createSurvey(page, name);
    await routeUiBuilder(page, context, MOCK_UI_BUILDER);

    const popup = await openUiBuilder(page);

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

  test("acknowledges a save so the UI Builder knows the JSON arrived", async ({ page, context }) => {
    name = uniqueName("E2E builder ack");
    const surveyId = await createSurvey(page, name);
    await routeUiBuilder(page, context, mockUiBuilder(`(event) => {
      const json = Object.assign({}, event.data.json, { title: "Acknowledged" });
      event.source.postMessage({ source: "surveyjs-ui-builder", type: "save", requestId: "save-1", surveyId: event.data.surveyId, json }, event.origin);
    }`));

    const popup = await openUiBuilder(page);

    await expect.poll(() => popup.evaluate(() => window.received.map(message => message.type))).toEqual(["load", "saved"]);
    const [load, saved] = await popup.evaluate(() => window.received);
    expect(load).toMatchObject({ source: "surveyjs-wordpress", protocolVersion: 2, surveyId, name });
    expect(saved).toEqual({ source: "surveyjs-wordpress", type: "saved", surveyId, requestId: "save-1" });
    await expect(status(page)).toContainText("Changes from the UI Builder arrived at");
    expect(await modelValue(page, 0)).toContain("Acknowledged");
  });

  test("rejects a save for another form and tells the UI Builder why", async ({ page, context }) => {
    name = uniqueName("E2E builder mismatch");
    const surveyId = await createSurvey(page, name);
    await routeUiBuilder(page, context, mockUiBuilder(`(event) => {
      event.source.postMessage({ source: "surveyjs-ui-builder", type: "save", requestId: 7, surveyId: event.data.surveyId + 1, json: { title: "Other form" } }, event.origin);
    }`));

    const popup = await openUiBuilder(page);

    await expect.poll(() => popup.evaluate(() => window.received.length)).toBe(2);
    expect((await popup.evaluate(() => window.received))[1]).toEqual({ source: "surveyjs-wordpress", type: "save-failed", reason: "survey-mismatch", surveyId, requestId: 7 });
    await expect(status(page)).toContainText("changes for another form");
    expect(await modelValue(page, 0)).not.toContain("Other form");
  });

  test("offers the manual path when the browser blocks the popup", async ({ page }) => {
    name = uniqueName("E2E builder blocked");
    await createSurvey(page, name);
    await page.evaluate(() => { window.open = () => null; });

    await page.getByRole("button", { name: "Open in free UI Builder" }).click();

    await expect(status(page)).toContainText("The browser blocked the UI Builder window.");
    await expect(manualPanel(page)).toBeVisible();
    await expect(page.locator("#wp-sjs-ui-builder-manual-toggle")).toHaveAttribute("aria-expanded", "true");
  });

  test("detects the lost connection when Cross-Origin-Opener-Policy cuts the popup off", async ({ page, context }) => {
    name = uniqueName("E2E builder coop");
    const surveyId = await createSurvey(page, name);
    await routeUiBuilder(page, context, MOCK_UI_BUILDER.replace("window.opener.postMessage", "window.opener && window.opener.postMessage"));
    await page.route(url => url.pathname.endsWith("/wp-admin/admin.php") && url.searchParams.get("page") === "surveyjs_editor", async route => {
      const response = await route.fetch();
      await route.fulfill({ response, headers: Object.assign({}, response.headers(), { "cross-origin-opener-policy": "same-origin" }) });
    });
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}`);
    await expect(page.locator(".monaco-editor")).toBeVisible();

    await openUiBuilder(page);

    await expect(status(page)).toContainText("The connection to the UI Builder was lost");
    await expect(manualPanel(page)).toBeVisible();
    expect(await modelValue(page, 0)).not.toContain("Edited in UI Builder");
  });

  test("warns when the UI Builder page does not respond", async ({ page, context }) => {
    name = uniqueName("E2E builder timeout");
    await createSurvey(page, name);
    await routeUiBuilder(page, context, "<!doctype html><h1>Not Found</h1>", 404);

    await openUiBuilder(page);

    await expect(status(page)).toContainText("Opening the UI Builder");
    await expect(status(page)).toContainText("has not connected to this page yet", { timeout: 25000 });
    await expect(manualPanel(page)).toBeVisible();
  });

  test("copies the form JSON and applies form or theme JSON pasted back", async ({ page, context }) => {
    name = uniqueName("E2E builder manual");
    await createSurvey(page, name);
    await context.grantPermissions(["clipboard-read", "clipboard-write"]);

    await page.getByRole("button", { name: "Copy and paste JSON manually" }).click();
    await page.getByRole("button", { name: "Copy form JSON" }).click();
    await expect(page.locator("#wp-sjs-ui-builder-manual-notice")).toContainText("Form JSON copied.");
    expect(await page.evaluate(() => navigator.clipboard.readText())).toContain("e2e_question");

    const paste = page.getByLabel("JSON from the UI Builder");
    await paste.fill("not json");
    await page.getByRole("button", { name: "Apply JSON" }).click();
    await expect(page.locator("#wp-sjs-ui-builder-manual-notice")).toContainText("The pasted text is not valid JSON");

    await paste.fill(JSON.stringify({ title: "Pasted form", elements: [{ type: "text", name: "pasted_question" }] }));
    await page.getByRole("button", { name: "Apply JSON" }).click();
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("The pasted form JSON is in the editor.");
    expect(await modelValue(page, 0)).toContain("pasted_question");
    await expect(paste).toHaveValue("");

    await paste.fill(JSON.stringify({ themeName: "pasted-theme", cssVariables: {} }));
    await page.getByRole("button", { name: "Apply JSON" }).click();
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("The pasted theme JSON is in the editor.");
    expect(await modelValue(page, 1)).toContain("pasted-theme");
    await expect(page.locator(".nav-tab-active")).toHaveAttribute("data-tab", "theme");
  });
});
