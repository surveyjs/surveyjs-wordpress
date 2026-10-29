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
  await page.getByRole("link", { name: "Add New Form" }).click();
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
  await expect(page.locator("#wp-sjs-editor-notice")).toContainText("Form saved");
}

async function renameSurvey(page, name) {
  await page.getByRole("button", { name: "Edit form name" }).click();
  await page.getByLabel("Form name", { exact: true }).fill(name);
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveSurvey"));
  await page.getByRole("button", { name: "Save name" }).click();
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
  await page.click("#doaction");
  await expect(page.locator(".notice-success")).toContainText("deleted");
}

module.exports = { SURVEY_JSON, uniqueName, listUrl, addSurvey, renameSurvey, setEditorJson, saveSurvey, createSurvey, deleteSurveys };
