const { expect } = require("@playwright/test");

const LIST_URL = "/wp-admin/admin.php?page=surveyjs-main-menu";

const SURVEY_JSON = {
  elements: [{ type: "text", name: "e2e_question", title: "What is your name?" }]
};

function uniqueName(prefix) {
  return `${prefix} ${Date.now()}`;
}

function listUrl(search) {
  return `${LIST_URL}&s=${encodeURIComponent(search)}`;
}

async function addSurvey(page) {
  await page.goto(LIST_URL);
  await page.locator("#wpbody-content").getByRole("link", { name: "Add New Form" }).click();
  await page.getByRole("button", { name: "Create blank form" }).click();
  await expect(page).toHaveURL(/page=surveyjs_editor/);
  await expect(page.locator(".monaco-editor")).toBeVisible();
  return Number(new URL(page.url()).searchParams.get("id"));
}

async function setEditorJson(page, json) {
  await page.evaluate(text => window.monaco.editor.getEditors()[0].setValue(text), JSON.stringify(json, null, 2));
}

async function saveSurvey(page) {
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveSurvey"));
  await page.getByRole("button", { name: "Save", exact: true }).click();
  await saved;
  await expect(page.locator(".wp-sjs-snackbars")).toContainText("Form saved");
}

async function renameSurvey(page, name) {
  if (!(await page.getByLabel("Form name", { exact: true }).isVisible())) await page.locator("#wp-sjs-name-edit").click();
  await page.getByLabel("Form name", { exact: true }).fill(name);
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveSurvey"));
  await page.getByLabel("Form name", { exact: true }).press("Enter");
  await saved;
  await expect(page.locator("#wp-sjs-name-text")).toHaveText(name);
}

async function createSurvey(page, name, json = SURVEY_JSON) {
  const id = await addSurvey(page);
  await renameSurvey(page, name);
  await setEditorJson(page, json);
  await saveSurvey(page);
  return id;
}

async function deleteSurveys(page, search) {
  await page.goto(listUrl(search));
  if (await page.locator("#the-list .no-items").count()) {
    return;
  }
  await page.check("#cb-select-all-1");
  await page.selectOption("#bulk-action-selector-top", "delete");
  page.once("dialog", dialog => dialog.accept());
  await page.click("#doaction");
  await expect(page.locator(".wp-sjs-snackbar.is-success")).toContainText("deleted");
}

async function currentUser(page) {
  const nonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
  const user = await (await page.request.get("/index.php?rest_route=/wp/v2/users/me&context=edit", { headers: { "X-WP-Nonce": nonce } })).json();
  return { login: user.username, email: user.email };
}

async function lintIssues(page, json) {
  return page.evaluate(survey => window.SurveyLinter.lintSurvey(survey).findings.map(finding => `${finding.ruleId} at ${finding.path}: ${finding.message}`), json);
}

// New forms reject entries sent sooner than the minimum time after the form was rendered, so a direct POST waits like survey.js does.
async function submitTimeFields(config) {
  if (!config.submitTime) return {};
  const left = Number(config.submitTime.token.split(".")[0]) * 1000 + config.submitTime.wait + 1000 - Date.now();
  if (left > 0) await new Promise((resolve) => setTimeout(resolve, left));
  return { SubmitToken: config.submitTime.token };
}

module.exports = { SURVEY_JSON, uniqueName, listUrl, addSurvey, renameSurvey, setEditorJson, saveSurvey, createSurvey, deleteSurveys, currentUser, lintIssues, submitTimeFields };
