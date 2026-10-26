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
  await page.getByRole("link", { name: "Add New Survey" }).click();
  await expect(page).toHaveURL(/page=surveyjs_editor/);
  await expect(page.locator(".svc-creator")).toBeVisible();
  return Number(new URL(page.url()).searchParams.get("id"));
}

async function renameSurvey(page, name) {
  await page.locator(".edit-survey-name").click();
  await page.locator("#sjs_editor_title_edit input").fill(name);
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_RenameSurvey"));
  await page.locator("#sjs_editor_title_edit").getByText("Update").click();
  await saved;
}

async function setSurveyJson(page, json) {
  await page.getByText("JSON Editor", { exact: true }).click();
  await page.locator(".svc-creator textarea").fill(JSON.stringify(json, null, 2));
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveSurvey"));
  await page.getByText("Designer", { exact: true }).click();
  await saved;
}

async function createSurvey(page, name, json = SURVEY_JSON) {
  const id = await addSurvey(page);
  await renameSurvey(page, name);
  await setSurveyJson(page, json);
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

module.exports = { SURVEY_JSON, uniqueName, listUrl, addSurvey, renameSurvey, setSurveyJson, createSurvey, deleteSurveys };
