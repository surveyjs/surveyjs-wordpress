const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, saveSurvey, deleteSurveys } = require("./helpers");

const DRAFT_JSON = {
  pages: [
    { name: "first", elements: [{ type: "text", name: "visitor_name", title: "Your name" }] },
    { name: "second", elements: [{ type: "text", name: "visitor_city", title: "Your city" }] }
  ]
};

test.describe("save & resume", () => {
  let name;
  let postId;
  let restNonce;
  let postLink;

  test.beforeEach(async ({ page }) => {
    name = uniqueName("E2E save resume");
    const surveyId = await createSurvey(page, name, DRAFT_JSON);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Advanced", exact: true }).click();
    await page.getByLabel("Let respondents continue later").check();
    await saveSurvey(page);

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;
    postLink = post.link;
  });

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    await deleteSurveys(page, name);
  });

  test("restores a visitor's answers from the browser and clears them on Start over", async ({ browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await page.goto(postLink);
    await page.getByLabel("Your name").fill("Jane");
    await page.getByRole("button", { name: "Next" }).click();
    await expect(page.getByLabel("Your city")).toBeVisible();
    await expect.poll(() => page.evaluate(() => Object.keys(localStorage).filter(key => key.startsWith("surveyjs-draft:")).length)).toBe(1);

    await page.reload();
    await expect(page.getByText("We restored your previous answers.")).toBeVisible();
    await expect(page.getByLabel("Your city")).toBeVisible();
    await page.getByRole("button", { name: "Previous" }).click();
    await expect(page.getByLabel("Your name")).toHaveValue("Jane");

    await page.getByRole("button", { name: "Start over" }).click();
    await expect(page.getByText("We restored your previous answers.")).toBeHidden();
    await expect(page.getByLabel("Your name")).toHaveValue("");
    await page.reload();
    await expect(page.getByLabel("Your name")).toBeVisible();
    await expect(page.getByText("We restored your previous answers.")).toBeHidden();
    await context.close();
  });

  test("continues a logged-in user's answers on another device and deletes them on submit", async ({ page, browser }) => {
    await page.goto(postLink);
    await page.getByLabel("Your name").fill("Jane");
    const draftSaved = page.waitForResponse(r => r.request().postData()?.includes("action=SurveyJS_SaveDraft"));
    await page.getByRole("button", { name: "Next" }).click();
    expect((await draftSaved).ok()).toBe(true);

    const otherDevice = await browser.newContext({ storageState: test.info().project.use.storageState });
    const otherPage = await otherDevice.newPage();
    await otherPage.goto(postLink);
    await expect(otherPage.getByText("We restored your previous answers.")).toBeVisible();
    await otherPage.getByLabel("Your city").fill("Riga");
    const resultSaved = otherPage.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await otherPage.getByRole("button", { name: "Complete" }).click();
    expect((await resultSaved).ok()).toBe(true);
    await otherDevice.close();

    await page.reload();
    await expect(page.getByLabel("Your name")).toHaveValue("");
    await expect(page.getByText("We restored your previous answers.")).toBeHidden();
  });
});
