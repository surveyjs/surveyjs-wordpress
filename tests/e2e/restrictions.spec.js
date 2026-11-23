const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, saveSurvey, deleteSurveys, submitTimeFields } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/restrictions.php")),
    stdio: ["pipe", "pipe", "ignore"]
  }).toString();
  return JSON.parse(output.trim().split("\n").pop());
}

function setSettings(surveyId, settings) {
  return fixture("settings", surveyId, JSON.stringify(settings));
}

async function publish(page, name, surveyId) {
  const nonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
  const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
    headers: { "X-WP-Nonce": nonce },
    data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
  })).json();
  return { ...post, nonce };
}

function surveyConfig(html) {
  const match = html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/);
  return match ? JSON.parse(match[1]) : null;
}

function isStatus(response) {
  return response.url().includes("action=SurveyJS_FormStatus");
}

async function openForm(page, link) {
  const status = page.waitForResponse(isStatus);
  await page.goto(link);
  expect((await (await status).json()).data.open).toBe(true);
  await expect(page.getByLabel("What is your name?")).toBeVisible();
}

async function fillAndComplete(page, answer) {
  await page.getByLabel("What is your name?").fill(answer);
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
  await page.getByRole("button", { name: "Complete" }).click();
  return saved;
}

async function postEntry(request, config, surveyId) {
  return request.post(config.saveResultUrl, {
    form: { SurveyId: String(surveyId), Json: JSON.stringify({ e2e_question: "Direct" }), Website: "", _wpnonce: config.saveResultNonce, ...(await submitTimeFields(config)) }
  });
}

test.describe("form restrictions", () => {
  test.skip(!WP_CONTAINER, "needs WP_CONTAINER to set up entries");

  let name;
  let surveyId;
  let post;
  let visitors = [];

  test.beforeEach(async ({ page }) => {
    name = uniqueName("E2E restrictions");
    surveyId = await createSurvey(page, name);
    post = null;
    visitors = [];
  });

  test.afterEach(async ({ page }) => {
    for (const visitor of visitors) {
      await visitor.close();
    }
    if (post) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
    }
    await deleteSurveys(page, name);
  });

  async function newVisitor(browser) {
    const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
    visitors.push(visitor);
    return visitor;
  }

  test("saves the restrictions from the Restrictions tab", async ({ page }) => {
    const tabs = page.locator(".wp-sjs-editor__tabs");
    await tabs.getByRole("link", { name: "Restrictions" }).click();
    const limit = page.getByLabel("Number of entries", { exact: true });
    await expect(limit).toBeDisabled();
    await page.getByLabel("Limit the number of entries").check();
    await expect(limit).toBeEnabled();
    await limit.fill("5");
    await page.getByLabel("Limit period").selectOption("week");
    await expect(page.locator("#wp-sjs-entry-limit-message")).toHaveValue("Sorry, this form is no longer accepting entries.");
    await page.getByLabel("Limit how many times one person can submit the form").check();
    await page.getByLabel("Identify people by").selectOption("ip");
    await page.getByLabel("Open and close the form at set times").check();
    await page.getByLabel("Opens", { exact: true }).fill("2030-01-02T09:30");
    await page.getByLabel("Only logged-in users can fill in the form").check();
    await page.locator("#wp-sjs-require-login-message").fill("<em>Members only</em>");

    const storeNotice = page.locator("#wp-sjs-restrictions-store-notice");
    await expect(storeNotice).toBeHidden();
    await tabs.getByRole("link", { name: "Entries" }).click();
    await page.getByLabel("Save submitted entries in the database").uncheck();
    await tabs.getByRole("link", { name: "Restrictions" }).click();
    await expect(storeNotice).toBeVisible();
    await tabs.getByRole("link", { name: "Entries" }).click();
    await page.getByLabel("Save submitted entries in the database").check();
    await saveSurvey(page);

    expect(fixture("settings", surveyId, "{}")).toMatchObject({
      limitEntries: true,
      entryLimit: 5,
      entryLimitPeriod: "week",
      limitPerRespondent: true,
      respondentBy: "ip",
      scheduleEnabled: true,
      scheduleStart: "2030-01-02T09:30",
      scheduleEnd: "",
      requireLogin: true,
      requireLoginMessage: "<em>Members only</em>"
    });

    await page.reload();
    await tabs.getByRole("link", { name: "Restrictions" }).click();
    await expect(page.getByLabel("Number of entries", { exact: true })).toHaveValue("5");
    await expect(page.getByLabel("Limit period")).toHaveValue("week");
    await expect(page.getByLabel("Identify people by")).toHaveValue("ip");
    await expect(page.getByLabel("Opens", { exact: true })).toHaveValue("2030-01-02T09:30");
    await expect(page.getByLabel("Only logged-in users can fill in the form")).toBeChecked();
  });

  test("closes the form at the entry limit, on render, on a cached page and on submit", async ({ page, browser }) => {
    setSettings(surveyId, { limitEntries: true, entryLimit: 2, entryLimitMessage: "<strong>E2E form is full</strong>" });
    post = await publish(page, name, surveyId);

    const first = await newVisitor(browser);
    await openForm(first, post.link);
    const cachedHtml = await (await first.request.get(post.link)).text();
    const config = surveyConfig(cachedHtml);
    expect(config.statusUrl).toContain("action=SurveyJS_FormStatus");
    expect((await fillAndComplete(first, "Ann")).ok()).toBe(true);

    const second = await newVisitor(browser);
    await openForm(second, post.link);
    fixture("insert", surveyId, 1, 0);
    expect((await fillAndComplete(second, "Bob")).status()).toBe(403);
    await expect(second.locator(".wp-sjs-restricted__message strong")).toHaveText("E2E form is full");
    await expect(second.locator(".wp-sjs-survey")).toBeHidden();

    const third = await newVisitor(browser);
    await third.goto(post.link);
    await expect(third.locator(".wp-sjs-restricted__message")).toContainText("E2E form is full");
    await expect(third.locator(".wp-sjs-survey-config")).toHaveCount(0);

    const stale = await newVisitor(browser);
    await stale.route(url => url.href === post.link, route => route.fulfill({ status: 200, contentType: "text/html", body: cachedHtml }));
    await stale.goto(post.link);
    await expect(stale.locator(".wp-sjs-restricted__message")).toContainText("E2E form is full");
    await expect(stale.locator(".wp-sjs-survey")).toBeHidden();

    const rejected = await postEntry(stale.request, config, surveyId);
    expect(rejected.status()).toBe(403);
    expect((await rejected.json()).data).toMatchObject({ restricted: true, reason: "entry_limit", message: "E2E form is full" });
    expect(fixture("entries", surveyId)).toHaveLength(2);
  });

  test("counts only submitted entries of the current period", async ({ page, browser }) => {
    setSettings(surveyId, { limitEntries: true, entryLimit: 1, entryLimitPeriod: "day" });
    post = await publish(page, name, surveyId);
    fixture("insert", surveyId, 1, 2);
    fixture("insert", surveyId, 1, 0, "spam");

    const visitor = await newVisitor(browser);
    await openForm(visitor, post.link);

    fixture("insert", surveyId, 1, 0);
    await visitor.goto(post.link);
    await expect(visitor.locator(".wp-sjs-restricted__message")).toHaveText("Sorry, this form is no longer accepting entries.");
  });

  test("limits entries per IP address without storing the address", async ({ page, browser }) => {
    setSettings(surveyId, { limitPerRespondent: true, respondentBy: "ip", respondentLimit: 1 });
    post = await publish(page, name, surveyId);

    const visitor = await newVisitor(browser);
    await openForm(visitor, post.link);
    const config = surveyConfig(await (await visitor.request.get(post.link)).text());
    expect((await fillAndComplete(visitor, "Ann")).ok()).toBe(true);
    const entries = fixture("entries", surveyId);
    expect(entries).toHaveLength(1);
    expect(entries[0].ip_hash).toMatch(/^[a-f0-9]{64}$/);
    expect(entries[0].ip_hash).not.toContain(".");

    const html = await (await visitor.request.get(post.link)).text();
    expect(surveyConfig(html)).not.toBeNull();
    await visitor.goto(post.link);
    await expect(visitor.locator(".wp-sjs-restricted__message")).toHaveText("You have already submitted this form. Thank you!");

    const rejected = await postEntry(visitor.request, config, surveyId);
    expect(rejected.status()).toBe(403);
    expect((await rejected.json()).data.reason).toBe("respondent_limit");
    expect(fixture("entries", surveyId)).toHaveLength(1);
  });

  test("limits entries per user account and asks visitors to log in", async ({ page, browser }) => {
    setSettings(surveyId, { limitPerRespondent: true, respondentBy: "user", respondentLimit: 1, requireLoginMessage: "E2E members only" });
    post = await publish(page, name, surveyId);

    const guest = await newVisitor(browser);
    await guest.goto(post.link);
    await expect(guest.locator(".wp-sjs-restricted__message")).toContainText("E2E members only");
    const login = guest.locator(".wp-sjs-restricted__message").getByRole("link", { name: "Log in" });
    await expect(login).toHaveAttribute("href", /wp-login\.php\?redirect_to=/);
    expect(decodeURIComponent(new URL(await login.getAttribute("href")).searchParams.get("redirect_to"))).toBe(post.link);

    await openForm(page, post.link);
    expect((await fillAndComplete(page, "Admin")).ok()).toBe(true);
    const entries = fixture("entries", surveyId);
    expect(Number(entries[0].user_id)).toBeGreaterThan(0);
    await page.goto(post.link);
    await expect(page.locator(".wp-sjs-restricted__message")).toHaveText("You have already submitted this form. Thank you!");
  });

  test("requires login", async ({ page, browser }) => {
    post = await publish(page, name, surveyId);
    const guest = await newVisitor(browser);
    await guest.goto(post.link);
    const config = surveyConfig(await (await guest.request.get(post.link)).text());
    expect(config.statusUrl).toBeUndefined();

    setSettings(surveyId, { requireLogin: true });
    await guest.goto(post.link);
    await expect(guest.locator(".wp-sjs-restricted__message")).toContainText("Please log in to fill in this form.");
    await expect(guest.locator(".wp-sjs-restricted__message").getByRole("link", { name: "Log in" })).toBeVisible();
    const rejected = await postEntry(guest.request, config, surveyId);
    expect(rejected.status()).toBe(403);
    expect((await rejected.json()).data.reason).toBe("login");

    await openForm(page, post.link);
  });

  test("opens and closes the form on schedule", async ({ page, browser }) => {
    post = await publish(page, name, surveyId);
    const visitor = await newVisitor(browser);

    const opens = fixture("site-time", 120);
    setSettings(surveyId, { scheduleEnabled: true, scheduleStart: opens.value });
    await visitor.goto(post.link);
    await expect(visitor.locator(".wp-sjs-restricted__message")).toHaveText(`This form opens on ${opens.formatted}. Please come back then.`);

    const closed = fixture("site-time", -60);
    setSettings(surveyId, { scheduleStart: "", scheduleEnd: closed.value, scheduleExpiredMessage: "E2E closed on {close_date}." });
    await visitor.goto(post.link);
    await expect(visitor.locator(".wp-sjs-restricted__message")).toHaveText(`E2E closed on ${closed.formatted}.`);

    setSettings(surveyId, { scheduleStart: fixture("site-time", -60).value, scheduleEnd: fixture("site-time", 60).value, scheduleExpiredMessage: "" });
    await openForm(visitor, post.link);
    const ended = fixture("site-time", -1);
    setSettings(surveyId, { scheduleEnd: ended.value });
    expect((await fillAndComplete(visitor, "Late")).status()).toBe(403);
    await expect(visitor.locator(".wp-sjs-restricted__message")).toHaveText(`Sorry, this form closed on ${ended.formatted} and is no longer accepting entries.`);
    expect(fixture("entries", surveyId)).toHaveLength(0);
  });
});
