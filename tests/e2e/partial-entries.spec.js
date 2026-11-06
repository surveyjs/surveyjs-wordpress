const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, createSurvey, saveSurvey, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;

function storageFixture(...args) {
  execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/storage.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
}

const PARTIAL_JSON = {
  pages: [
    { name: "first", elements: [{ type: "text", name: "visitor_name", title: "Your name" }] },
    { name: "second", elements: [{ type: "text", name: "visitor_city", title: "Your city" }] },
    { name: "third", elements: [{ type: "comment", name: "visitor_message", title: "Your message" }] }
  ]
};

function resultsUrl(surveyId, status) {
  return `/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}${status ? `&status=${status}` : ""}`;
}

function isPartialSave(response) {
  return response.url().includes("action=SurveyJS_SavePartialResult");
}

test.describe("partial entries", () => {
  let name;
  let surveyId;
  let postId;
  let restNonce;
  let postLink;

  test.beforeEach(async ({ page }) => {
    name = uniqueName("E2E partial entries");
    surveyId = await createSurvey(page, name, PARTIAL_JSON);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Entries" }).click();
    await page.getByLabel("Save partial entries").check();
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

  test("the setting is disabled while entries are not stored", async ({ page }) => {
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}`);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Entries" }).click();
    await expect(page.getByLabel("Save partial entries")).toBeEnabled();
    await page.getByLabel("Save submitted entries in the database").uncheck();
    await expect(page.getByLabel("Save partial entries")).toBeDisabled();
  });

  test("keeps one partial entry per respondent and turns it into an entry on submit", async ({ page, browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const visitor = await context.newPage();
    await visitor.goto(postLink);
    await visitor.getByLabel("Your name").fill("Jane");
    const firstSave = visitor.waitForResponse(isPartialSave);
    await visitor.getByRole("button", { name: "Next" }).click();
    const created = await (await firstSave).json();
    expect(created.data.id).toBeGreaterThan(0);
    expect(created.data.token).toMatch(/^[a-f0-9]{48}$/);

    await visitor.getByLabel("Your city").fill("Riga");
    const secondSave = visitor.waitForResponse(isPartialSave);
    await visitor.getByRole("button", { name: "Next" }).click();
    expect((await (await secondSave).json()).data.id).toBe(created.data.id);

    await page.goto(resultsUrl(surveyId));
    await expect(page.locator(".subsubsub .all .count")).toHaveText("(0)");
    await expect(page.locator(".subsubsub .partial .count")).toHaveText("(1)");
    await page.goto(resultsUrl(surveyId, "partial"));
    const row = page.locator("#the-list tr", { hasText: "Jane" });
    await expect(row).toHaveCount(1);
    await expect(row.locator(".column-status")).toHaveText("Partial, page 3");
    await expect(row).toContainText("Riga");

    await visitor.getByLabel("Your message").fill("Hello");
    const resultSaved = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await visitor.getByRole("button", { name: "Complete" }).click();
    expect((await resultSaved).ok()).toBe(true);
    await context.close();

    await page.goto(resultsUrl(surveyId));
    await expect(page.locator(".subsubsub .all .count")).toHaveText("(1)");
    await expect(page.locator(".subsubsub .partial")).toHaveCount(0);
    const entry = page.locator("#the-list tr", { hasText: "Hello" });
    await expect(entry.locator(".column-status")).toHaveText("Unread");
    await expect(entry).toContainText(`#${created.data.id}`);
  });

  test("ignores a partial save from the honeypot and replaces a wrong token with a new entry", async ({ page }) => {
    const html = await (await page.request.get(postLink)).text();
    const config = JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
    const send = (fields) => page.request.post(config.partial.url, {
      form: Object.assign({ SurveyId: String(config.id), Page: "2", Website: "", _wpnonce: config.saveResultNonce }, fields)
    });

    const bot = await (await send({ Json: JSON.stringify({ visitor_name: "Bot" }), Website: "spam" })).json();
    expect(bot).toEqual({ success: true });

    const first = await (await send({ Json: JSON.stringify({ visitor_name: "Ann" }) })).json();
    const forged = await (await send({ Json: JSON.stringify({ visitor_name: "Mallory" }), PartialId: String(first.data.id), PartialToken: "0".repeat(48) })).json();
    expect(forged.data.id).not.toBe(first.data.id);

    const tooLarge = await send({ Json: JSON.stringify({ visitor_name: "x".repeat(1100000) }) });
    expect(tooLarge.status()).toBe(413);

    await page.goto(resultsUrl(surveyId, "partial"));
    await expect(page.locator("#the-list tr", { hasText: "Ann" })).toHaveCount(1);
    await expect(page.locator("#the-list tr", { hasText: "Mallory" })).toHaveCount(1);
    await expect(page.locator("#the-list tr", { hasText: "Bot" })).toHaveCount(0);
  });

  test("marks a partial entry as spam and back", async ({ page, browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const visitor = await context.newPage();
    await visitor.goto(postLink);
    await visitor.getByLabel("Your name").fill("Spammy");
    const saved = visitor.waitForResponse(isPartialSave);
    await visitor.getByRole("button", { name: "Next" }).click();
    expect((await saved).ok()).toBe(true);
    await context.close();

    await page.goto(resultsUrl(surveyId, "partial"));
    await expect(page.locator("#bulk-action-selector-top option")).toHaveText(["Bulk actions", "Keep", "Stop Keeping", "Mark as Spam", "Delete"]);
    const row = page.locator("#the-list tr", { hasText: "Spammy" });
    await row.hover();
    await row.getByRole("link", { name: "Spam", exact: true }).click();
    await expect(page.locator(".subsubsub .spam .count")).toHaveText("(1)");

    await page.goto(resultsUrl(surveyId, "spam"));
    const spamRow = page.locator("#the-list tr", { hasText: "Spammy" });
    await spamRow.hover();
    await spamRow.getByRole("link", { name: "Not Spam", exact: true }).click();
    await page.goto(resultsUrl(surveyId, "partial"));
    await expect(page.locator("#the-list tr", { hasText: "Spammy" })).toHaveCount(1);
  });

  test("flags a partial entry before it is deleted and keeps it on request", async ({ page, browser }) => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to age a partial entry");
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const visitor = await context.newPage();
    await visitor.goto(postLink);
    await visitor.getByLabel("Your name").fill("Forgetful");
    const saved = visitor.waitForResponse(isPartialSave);
    await visitor.getByRole("button", { name: "Next" }).click();
    const entryId = (await (await saved).json()).data.id;
    await context.close();

    storageFixture("age-entry", entryId, 27);
    await page.goto(listUrl(name));
    await expect(page.locator("#wp-admin-bar-surveyjs-expiring")).toHaveText(/^[\d,.\s]+ partial entr(y|ies) will be deleted soon$/);
    await expect(page.locator("#the-list tr", { hasText: name })).toContainText("1 partial entry will be deleted soon");

    await page.goto(resultsUrl(surveyId, "partial"));
    await expect(page.locator(".notice-warning", { hasText: "will be deleted within 5 days" })).toContainText("1 partial entry will be deleted within 5 days.");
    const row = page.locator("#the-list tr", { hasText: "Forgetful" });
    await expect(row.locator(".column-status")).toContainText("Will be deleted on");
    await row.hover();
    await row.getByRole("link", { name: "Keep", exact: true }).click();
    await expect(row.locator(".column-status")).toContainText("Kept");
    await expect(row.locator(".column-status")).not.toContainText("Will be deleted on");
    await expect(page.locator(".notice-warning", { hasText: "will be deleted within 5 days" })).toHaveCount(0);

    await row.hover();
    await row.getByRole("link", { name: "Stop Keeping", exact: true }).click();
    await expect(row.locator(".column-status")).toContainText("Will be deleted on");
  });
});
