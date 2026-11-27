const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, setEditorJson, renameSurvey, SURVEY_JSON } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const TEMPLATES_URL = "/wp-admin/admin.php?page=surveyjs-templates";
const GUIDE_URL = "https://surveyjs.io/form-library/documentation/design-survey/create-a-simple-survey";

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/onboarding.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

test.describe("hint in the editor of an empty form", () => {
  let formId;

  test.beforeAll(() => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to delete the test form");
  });

  test.afterAll(() => {
    if (WP_CONTAINER && formId) fixture("delete", formId);
  });

  test("guides to the JSON docs and the UI Builder until the form has an element", async ({ page }) => {
    await page.goto(TEMPLATES_URL);
    await page.getByRole("button", { name: "Create blank form" }).click();
    await expect(page).toHaveURL(/page=surveyjs_editor/);
    await renameSurvey(page, uniqueName("E2E blank form hint"));
    await expect(page.locator(".monaco-editor")).toBeVisible();
    formId = Number(new URL(page.url()).searchParams.get("id"));

    const hint = page.locator("#wp-sjs-empty-hint");
    const preview = page.locator("#wp-sjs-preview");
    await expect(hint).toBeVisible();
    await expect(hint.getByRole("heading")).toHaveText("This form is empty");
    await expect(hint).toContainText("If you're new to SurveyJS JSON, read the Getting Started guide, or build the form visually in the free UI Builder.");
    const guide = hint.getByRole("link", { name: /Getting Started guide/ });
    await expect(guide).toHaveAttribute("href", GUIDE_URL);
    await expect(guide).toHaveAttribute("target", "_blank");
    await expect(preview).toBeHidden();

    const uiBuilderUrl = await page.evaluate(() => {
      window.e2eOpened = [];
      window.open = (url) => window.e2eOpened.push(url);
      return window.surveyjsEditor.config.uiBuilderUrl;
    });
    await hint.getByRole("button", { name: "Open in free UI Builder" }).click();
    expect(await page.evaluate(() => window.e2eOpened)).toEqual([uiBuilderUrl]);

    await page.screenshot({ path: test.info().outputPath("empty-form-base.png") });

    await setEditorJson(page, SURVEY_JSON);
    await expect(hint).toBeHidden();
    await expect(preview).toBeVisible();
    await expect(page.frameLocator("#wp-sjs-preview").getByText("What is your name?")).toBeVisible();

    await setEditorJson(page, { title: "Only a title", pages: [{ name: "page1" }] });
    await expect(hint).toBeVisible();

    await page.evaluate(() => window.monaco.editor.getEditors()[0].setValue("{"));
    await expect(hint).toBeHidden();
  });
});
