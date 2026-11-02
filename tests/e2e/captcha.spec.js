const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

const SETTINGS_URL = "/wp-admin/admin.php?page=surveyjs-settings";
const TURNSTILE_SITE_KEY = "1x00000000000000000000AA";
const TURNSTILE_PASS_SECRET = "1x0000000000000000000000000000000AA";
const TURNSTILE_FAIL_SECRET = "2x0000000000000000000000000000000AA";

async function setCaptcha(page, type, siteKey = "", secretKey = "") {
  await page.goto(SETTINGS_URL);
  await page.getByLabel("Type", { exact: true }).selectOption(type);
  await page.getByLabel("Site Key", { exact: true }).fill(siteKey);
  await page.getByLabel("Secret Key", { exact: true }).fill(secretKey);
  await page.getByRole("button", { name: "Save Changes" }).click();
  await expect(page).toHaveURL(/settings-updated=true/);
}

async function enableCaptcha(page, surveyId) {
  await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}&tab=spam`);
  await page.getByLabel("Enable CAPTCHA").check();
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveSurvey"));
  await page.getByRole("button", { name: "Save", exact: true }).click();
  await saved;
}

test.describe("CAPTCHA", () => {
  test.describe.configure({ mode: "serial" });
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
      postId = null;
    }
    await setCaptcha(page, "none");
    await deleteSurveys(page, name);
  });

  async function publish(page, surveyId) {
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[Survey id="${surveyId}"]` }
    })).json();
    postId = post.id;
    return post.link;
  }

  test("the form setting is disabled until the keys are set", async ({ page }) => {
    name = uniqueName("E2E captcha off");
    const surveyId = await createSurvey(page, name);
    await setCaptcha(page, "none");
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}&tab=spam`);
    await expect(page.getByLabel("Enable CAPTCHA")).toBeDisabled();
    await expect(page.getByRole("link", { name: "Set up CAPTCHA" })).toHaveAttribute("href", /page=surveyjs-settings/);
  });

  test("the secret key is not printed back", async ({ page }) => {
    name = uniqueName("E2E captcha secret");
    await setCaptcha(page, "turnstile", TURNSTILE_SITE_KEY, TURNSTILE_PASS_SECRET);
    await expect(page.getByLabel("Secret Key", { exact: true })).toHaveValue("");
    expect(await page.content()).not.toContain(TURNSTILE_PASS_SECRET);
  });

  test("saves the entry when Turnstile passes", async ({ page }) => {
    name = uniqueName("E2E captcha pass");
    const surveyId = await createSurvey(page, name);
    await setCaptcha(page, "turnstile", TURNSTILE_SITE_KEY, TURNSTILE_PASS_SECRET);
    await enableCaptcha(page, surveyId);

    await page.goto(await publish(page, surveyId));
    await expect(page.locator(".wp-sjs-captcha iframe")).toBeAttached();
    await page.getByLabel("What is your name?").fill("Human");
    await expect(page.locator(".wp-sjs-captcha input[name='cf-turnstile-response']")).not.toHaveValue("", { timeout: 15000 });
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    await expect(page.locator(".sd-completedpage")).toBeVisible();

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list")).toContainText("Human");
  });

  test("keeps the answers and shows the error when verification fails", async ({ page }) => {
    name = uniqueName("E2E captcha fail");
    const surveyId = await createSurvey(page, name);
    await setCaptcha(page, "turnstile", TURNSTILE_SITE_KEY, TURNSTILE_FAIL_SECRET);
    await enableCaptcha(page, surveyId);

    await page.goto(await publish(page, surveyId));
    await page.getByLabel("What is your name?").fill("Bot");
    await expect(page.locator(".wp-sjs-captcha input[name='cf-turnstile-response']")).not.toHaveValue("", { timeout: 15000 });
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).status()).toBe(403);
    await expect(page.getByText("CAPTCHA verification failed. Please try again.")).toBeVisible();
    await expect(page.getByLabel("What is your name?")).toHaveValue("Bot");

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });
});
