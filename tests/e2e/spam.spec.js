const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

test.describe("spam protection", () => {
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    await deleteSurveys(page, name);
  });

  test("drops the entry when the honeypot field is filled", async ({ page }) => {
    name = uniqueName("E2E honeypot");
    const surveyId = await createSurvey(page, name);

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[Survey id="${surveyId}"]` }
    })).json();
    postId = post.id;

    await page.goto(post.link);
    await page.locator(".wp-sjs-hp input").fill("https://spam.example.com", { force: true });
    await page.getByLabel("What is your name?").fill("Bot");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });
});
