const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const WP_USERNAME = process.env.WP_USERNAME || "admin";
const COLLIDING_JSON = {
  elements: [
    { type: "text", name: "full_name" },
    { type: "checkbox", name: "methods", choices: ["Medication", "Other"], showOtherItem: true },
    { type: "radiogroup", name: "pain", choices: ["Mild", { value: "none", text: "No pain" }], showNoneItem: true }
  ]
};

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], { input: fs.readFileSync(path.join(__dirname, "fixtures/ai.php")), stdio: ["pipe", "pipe", "ignore"] }).toString();
  return JSON.parse(output.trim().split("\n").pop());
}

function ability(name, input) {
  return fixture("exec", WP_USERNAME, name, JSON.stringify(input));
}

test.describe("JSON issues for AI", () => {
  const formIds = [];

  test.beforeAll(() => {
    test.skip(!WP_CONTAINER, "WP_CONTAINER is not set");
  });

  test.afterAll(() => {
    formIds.forEach((id) => fixture("delete-form", id));
  });

  test("removes choices that repeat a built-in item and keeps their text", () => {
    const created = ability("surveyjs/create-form", { name: uniqueName("E2E AI issues"), json: COLLIDING_JSON });
    formIds.push(created.id);
    expect(created.fixed).toHaveLength(2);
    expect(created.fixed[0]).toContain('Removed the choice "Other" of "methods"');
    expect(created.warnings).toBeUndefined();

    const form = ability("surveyjs/get-form", { id: created.id });
    const [, methods, pain] = form.json.elements;
    expect(methods.choices).toEqual(["Medication"]);
    expect(methods.showOtherItem).toBe(true);
    expect(pain.choices).toEqual(["Mild"]);
    expect(pain.noneText).toBe("No pain");
    expect(form.issues).toBeUndefined();
  });

  test("asks the AI to fix problems it cannot fix itself", () => {
    const created = ability("surveyjs/create-form", { name: uniqueName("E2E AI issues dup"), json: { elements: [{ type: "text", name: "q" }, { type: "comment", name: "q" }] } });
    formIds.push(created.id);
    expect(created.warnings[0]).toContain("names must be unique");
    expect(created.warnings_note).toContain("Fix them now with surveyjs-update-form");
  });

  test("returns the issues the form editor found while the JSON is unchanged", async ({ page }) => {
    const id = fixture("create-form", uniqueName("E2E AI issues live"), JSON.stringify(COLLIDING_JSON)).id;
    formIds.push(id);

    const basic = ability("surveyjs/get-form", { id });
    expect(basic.issues).toHaveLength(2);
    expect(basic.issues_note).toContain("basic checks of the plugin");

    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${id}`);
    const saved = page.waitForResponse((response) => response.url().includes("action=SurveyJS_SaveSurvey"));
    await page.locator("#wp-sjs-save").click();
    await saved;
    await expect(page.locator(".wp-sjs-snackbars")).toContainText("Form saved with 2 issues.");

    const checked = ability("surveyjs/get-form", { id });
    expect(checked.issues_note).toContain("JSON linter of the form editor");
    expect(checked.issues.join("\n")).toContain('The choices of "methods" contain "Other" while showOtherItem is on');
    expect(checked.issues.join("\n")).toContain("(choices/duplicate, warning)");

    const updated = ability("surveyjs/update-form", { id, revision: checked.revision, json: checked.json });
    expect(updated.fixed).toHaveLength(2);
    expect(ability("surveyjs/get-form", { id }).issues).toBeUndefined();
  });

  test("lists the checks of the form editor in the JSON guide", () => {
    const guide = ability("surveyjs/get-json-guide", null).guide;
    expect(guide).toContain("# Checks of the form editor's JSON linter");
    expect(guide).toContain("## choices/duplicate");
    expect(guide).toContain("collides with the built-in");
    expect(guide).toContain("`showOtherItem`");
  });
});
