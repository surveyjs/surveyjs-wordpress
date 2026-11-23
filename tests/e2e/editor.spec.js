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

  test("lists the issues after Save and opens the first one in the JSON editor", async ({ page }) => {
    name = uniqueName("E2E save issues");
    await createSurvey(page, name);

    await setEditorJson(page, { elements: [
      { type: "text", name: "q1" },
      { type: "checkbox", name: "methods", choices: ["Medication", "Other"], showOtherItem: true }
    ] });
    await page.getByRole("button", { name: "Save", exact: true }).click();
    const notice = page.locator("#wp-sjs-editor-notice");
    await expect(notice).toContainText("Form saved with 1 issue.");
    await expect(notice.locator(".wp-sjs-editor__issues li")).toHaveText(/The choices of "methods" contain "Other" while showOtherItem is on/);

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    await notice.getByRole("button", { name: "Review and fix" }).click();
    await expect(page.locator(".wp-sjs-editor__tabs .nav-tab-active")).toHaveText("Config");
    await expect(page.locator(".wp-sjs-lint__finding.is-selected")).toContainText("elements[1].choices[1]");
    const line = await page.evaluate(() => window.monaco.editor.getEditors()[0].getPosition().lineNumber);
    const text = await page.evaluate((number) => window.monaco.editor.getEditors()[0].getModel().getLineContent(number), line);
    expect(text).toContain("\"Other\"");
  });

  test("sends the linter findings with Save for AI tools", async ({ page }) => {
    name = uniqueName("E2E save issues meta");
    await createSurvey(page, name);
    await setEditorJson(page, { elements: [{ type: "checkbox", name: "methods", choices: ["Other"], showOtherItem: true }] });
    const request = page.waitForRequest((item) => item.method() === "POST" && (item.postData() || "").includes("Issues="));
    await page.getByRole("button", { name: "Save", exact: true }).click();
    const issues = JSON.parse(new URLSearchParams((await request).postData()).get("Issues"));
    expect(issues).toEqual([expect.objectContaining({ ruleId: "choices/duplicate", path: "elements[0].choices[0]" })]);
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

  test("keeps WordPress button and input styles out of the find widget", async ({ page }) => {
    name = uniqueName("E2E find");
    await createSurvey(page, name);

    await page.locator("#wp-sjs-monaco .view-lines").click();
    await page.keyboard.press("Control+h");
    const widget = page.locator("#wp-sjs-monaco .find-widget.visible");
    await expect(widget).toBeVisible();

    const leaks = await widget.evaluate(node => {
      const color = getComputedStyle(node).color;
      return [...node.querySelectorAll(".button, textarea")].flatMap(element => {
        const style = getComputedStyle(element);
        const found = [];
        if (style.borderTopStyle !== "none") found.push("border");
        if (style.minHeight !== "auto" && style.minHeight !== "0px") found.push("min-height " + style.minHeight);
        if (style.boxShadow !== "none") found.push("box-shadow");
        if (element.matches(".button") && style.color !== color) found.push("color");
        return found.map(leak => element.className + ": " + leak);
      });
    });
    expect(leaks).toEqual([]);
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

  test("renders the Config tab or the requested tab from the server", async ({ page }) => {
    name = uniqueName("E2E first tab");
    const id = await createSurvey(page, name);
    const editorUrl = `/wp-admin/admin.php?page=surveyjs_editor&id=${id}`;

    const config = await (await page.request.get(editorUrl)).text();
    expect(config).toContain('data-active-tab="survey"');
    expect(config).toMatch(/id="wp-sjs-json-pane">/);
    expect(config).toContain('id="wp-sjs-set-logo"');
    expect(config).toContain('id="wp-sjs-lint"');
    expect(config).toMatch(/data-panel="email" hidden>/);

    const email = await (await page.request.get(`${editorUrl}&tab=email`)).text();
    expect(email).toContain('data-active-tab="email"');
    expect(email).toMatch(/id="wp-sjs-json-pane" hidden>/);
    expect(email).toMatch(/data-panel="email">/);

    await page.goto(editorUrl);
    await expect(page.locator(".wp-sjs-editor__tabs .nav-tab-active")).toHaveAttribute("data-tab", "survey");
    await expect(page.locator("#wp-sjs-json-pane .monaco-editor")).toBeVisible();
    await expect(page.locator("#wp-sjs-set-logo")).toBeVisible();
    await expect(page.locator("#wp-sjs-gdpr-agreement-label")).toBeVisible();
  });

  test("closes the Embed dialog back to the editor in every usual way", async ({ page }) => {
    name = uniqueName("E2E embed close");
    const id = await createSurvey(page, name);
    const editorUrl = `/wp-admin/admin.php?page=surveyjs_editor&id=${id}`;
    await page.goto(editorUrl);
    await expect(page.locator(".monaco-editor")).toBeVisible();

    const embedButton = page.locator("#wp-sjs-embed");
    const dialog = page.getByRole("dialog", { name: "Embed the form" });
    const open = async () => {
      await embedButton.click();
      await expect(dialog).toBeVisible();
      await expect(embedButton).toHaveAttribute("aria-expanded", "true");
      await expect(page.locator("#wp-sjs-embed-shortcode")).toBeFocused();
    };
    const expectClosed = async () => {
      await expect(dialog).toBeHidden();
      await expect(embedButton).toHaveAttribute("aria-expanded", "false");
      await expect(embedButton).toBeFocused();
      await expect(page).toHaveURL(new RegExp(`page=surveyjs_editor&id=${id}$`));
      await expect(page.locator(".monaco-editor")).toBeVisible();
    };

    await open();
    await dialog.locator(".wp-sjs-embed__header").getByRole("button", { name: "Close" }).click();
    await expectClosed();

    await open();
    await dialog.locator(".wp-sjs-embed__footer").getByRole("button", { name: "Close" }).click();
    await expectClosed();

    await open();
    await page.keyboard.press("Escape");
    await expectClosed();

    await open();
    const shortcodeBox = await page.locator("#wp-sjs-embed-shortcode").boundingBox();
    await page.mouse.move(shortcodeBox.x + 10, shortcodeBox.y + shortcodeBox.height / 2);
    await page.mouse.down();
    await page.mouse.move(5, 5);
    await page.mouse.up();
    await expect(dialog).toBeVisible();
    await page.mouse.click(5, 5);
    await expectClosed();

    await open();
    await page.goBack();
    await expectClosed();
    await page.goForward();
    await expect(dialog).toBeVisible();
    await dialog.locator(".wp-sjs-embed__footer").getByRole("button", { name: "Close" }).click();
    await expect(dialog).toBeHidden();
    await expect(page).toHaveURL(new RegExp(`page=surveyjs_editor&id=${id}$`));

    await page.setViewportSize({ width: 360, height: 640 });
    await open();
    const box = await dialog.boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(360);
    expect(box.y + box.height).toBeLessThanOrEqual(640);
    await expect(dialog.locator(".wp-sjs-embed__header").getByRole("button", { name: "Close" })).toBeInViewport();
    await expect(dialog.locator(".wp-sjs-embed__footer").getByRole("button", { name: "Close" })).toBeInViewport();
    await page.keyboard.press("Escape");
    await expectClosed();
    await page.setViewportSize({ width: 1400, height: 900 });
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
      ["page=surveyjs-getting-started", /^Getting Started/]
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
