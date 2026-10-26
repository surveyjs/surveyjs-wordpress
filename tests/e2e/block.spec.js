const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

test.describe("Gutenberg block", () => {
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    await deleteSurveys(page, name);
  });

  test("inserts a survey and completes it on the site", async ({ page }) => {
    name = uniqueName("E2E block");
    const surveyId = await createSurvey(page, name);

    await page.goto("/wp-admin/post-new.php");
    await page.waitForFunction(() => window.wp && wp.data && wp.data.select("core/editor").getCurrentPostId());
    await page.evaluate(() => wp.data.dispatch("core/preferences").set("core/edit-post", "welcomeGuide", false));
    postId = await page.evaluate(() => wp.data.select("core/editor").getCurrentPostId());
    restNonce = await page.evaluate(() => window.wpApiSettings.nonce);

    const canvas = page.frameLocator("iframe[name='editor-canvas']");
    await canvas.getByRole("textbox", { name: "Add title" }).fill(name);
    await page.getByRole("button", { name: "Block Inserter" }).click();
    await page.getByRole("searchbox").fill("SurveyJS");
    await page.getByRole("option", { name: "SurveyJS" }).click();

    const block = canvas.locator("[data-type='sjs/gutenberg-block']");
    await expect(block.getByText("Choose survey to insert")).toBeVisible();
    await block.locator("select").selectOption(String(surveyId));

    await page.getByRole("button", { name: "Publish", exact: true }).click();
    await page.locator(".editor-post-publish-panel").getByRole("button", { name: "Publish", exact: true }).click();
    await expect(page.locator(".post-publish-panel__postpublish")).toBeVisible();
    const postUrl = await page.evaluate(() => wp.data.select("core/editor").getPermalink());

    await page.goto(postUrl);
    await page.getByLabel("What is your name?").fill("E2E answer");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    await expect(page.getByText("Thank you for completing the survey")).toBeVisible();

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}&name=${encodeURIComponent(name)}`);
    await expect(page.locator("#wpSjsResultsTable")).toContainText("E2E answer");
  });
});
