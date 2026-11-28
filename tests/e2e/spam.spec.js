const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const MU_PLUGINS = "/var/www/html/wp-content/mu-plugins";
const AKISMET_STUB = `${MU_PLUGINS}/surveyjs-e2e-akismet-stub.php`;
const CONTACT_JSON = {
  elements: [
    { type: "text", name: "your_name", title: "Your name" },
    { type: "text", name: "your_email", title: "Email", inputType: "email" },
    { type: "comment", name: "message", title: "Message" }
  ]
};

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: ["pipe", "pipe", "ignore"] }).toString();
}

function spamFixture(...args) {
  const output = docker(["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], fs.readFileSync(path.join(__dirname, "fixtures/spam.php")));
  return JSON.parse(output.trim().split("\n").pop());
}

async function surveyConfig(request, link) {
  const html = await (await request.get(link)).text();
  return JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
}

async function saveSpamSettings(page, surveyId, change) {
  await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}&tab=spam`);
  await change();
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveSurvey"));
  await page.getByRole("button", { name: "Save", exact: true }).click();
  await saved;
}

test.describe("spam protection", () => {
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
      postId = null;
    }
    await deleteSurveys(page, name);
  });

  async function publish(page, surveyId) {
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[Survey id="${surveyId}"]` }
    })).json();
    postId = post.id;
    return post.link;
  }

  test("drops the entry when the honeypot field is filled", async ({ page }) => {
    name = uniqueName("E2E honeypot");
    const surveyId = await createSurvey(page, name);

    await page.goto(await publish(page, surveyId));
    await page.locator(".wp-sjs-hp input").fill("https://spam.example.com", { force: true });
    await page.getByLabel("What is your name?").fill("Bot");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });

  test("turns the minimum time on for new forms only", () => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name");
    expect(spamFixture("existing-form")).toEqual({ enabled: false, newForm: true });
  });

  test("rejects an entry posted sooner than the minimum time, while the form waits for a person", async ({ page }) => {
    name = uniqueName("E2E min time");
    const surveyId = await createSurvey(page, name);
    await saveSpamSettings(page, surveyId, async () => {
      await expect(page.getByLabel("Reject entries sent too soon after the form opens")).toBeChecked();
      await expect(page.getByLabel("Minimum time")).toHaveValue("3");
      await page.getByLabel("Minimum time").fill("4");
    });
    const link = await publish(page, surveyId);

    const config = await surveyConfig(page.request, link);
    expect(config.submitTime.wait).toBe(4000);
    const post = (fields) => page.request.post(config.saveResultUrl, {
      form: Object.assign({ SurveyId: String(surveyId), Json: JSON.stringify({ e2e_name: "Bot" }), _wpnonce: config.saveResultNonce }, fields)
    });
    const tooSoon = await post({ SubmitToken: config.submitTime.token });
    expect(tooSoon.status()).toBe(403);
    expect((await tooSoon.json()).data.message).toContain("submitted too quickly");
    expect((await post({})).status()).toBe(403);
    expect((await post({ SubmitToken: `${Math.floor(Date.now() / 1000) - 60}.forged` })).status()).toBe(403);

    await page.goto(link);
    const shown = Date.now();
    await page.getByLabel("What is your name?").fill("Human");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    expect(Date.now() - shown).toBeGreaterThanOrEqual(3500);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list")).toContainText("Human");
  });

  test.describe("Akismet", () => {
    test.describe.configure({ mode: "serial" });
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name");
    let wasActive = true;

    test.beforeAll(() => {
      docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${AKISMET_STUB}`], fs.readFileSync(path.join(__dirname, "fixtures/akismet-stub.php")));
      const result = spamFixture("activate");
      expect(result.error).toBeNull();
      wasActive = result.wasActive;
    });

    test.afterAll(() => {
      spamFixture("restore", wasActive);
      docker(["exec", WP_CONTAINER, "rm", "-f", AKISMET_STUB]);
    });

    test("keeps an entry Akismet flags as spam and sends the respondent's fields", async ({ page }) => {
      name = uniqueName("E2E akismet");
      const surveyId = await createSurvey(page, name, CONTACT_JSON);
      await saveSpamSettings(page, surveyId, () => page.getByLabel("Check entries with Akismet").check());
      const link = await publish(page, surveyId);

      await page.goto(link);
      await page.getByLabel("Your name").fill("akismet-guaranteed-spam");
      await page.getByLabel("Email").fill("spammer@example.com");
      await page.getByLabel("Message").fill("Cheap pills");
      let saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
      await page.getByRole("button", { name: "Complete" }).click();
      expect((await saved).ok()).toBe(true);
      await expect(page.locator(".sd-completedpage")).toBeVisible();

      const request = spamFixture("last-request");
      expect(request).toMatchObject({
        comment_type: "contact-form",
        comment_author: "akismet-guaranteed-spam",
        comment_author_email: "spammer@example.com",
        comment_content: "Cheap pills"
      });
      expect(request.user_ip).toBeTruthy();
      expect(request.user_agent).toBeTruthy();
      expect(request.permalink).toBe(link);

      await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
      await expect(page.locator("#the-list .no-items")).toBeVisible();
      await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}&status=spam`);
      await expect(page.locator("#wp-sjs-entry-panel")).toContainText("Cheap pills");

      await page.goto(link);
      await page.getByLabel("Your name").fill("Jane Doe");
      await page.getByLabel("Message").fill("Hello there");
      saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
      await page.getByRole("button", { name: "Complete" }).click();
      expect((await saved).ok()).toBe(true);
      await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
      await expect(page.locator("#wp-sjs-entry-panel")).toContainText("Hello there");
      await expect(page.locator("#wp-sjs-entry-panel")).not.toContainText("Cheap pills");
    });

    test("hides the Akismet key from the AI", () => {
      const result = spamFixture("redact");
      expect(result.key).not.toBe("");
      expect(result.redacted).not.toContain(result.key);
    });
  });
});
