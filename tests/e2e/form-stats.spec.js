const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { listUrl } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const VIEW_ACTION = "action=surveyjs_form_view";
const BROWSER_UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36";

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/form-stats.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

async function surveyConfig(request, link) {
  const html = await (await request.get(link)).text();
  return JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
}

// Playwright's Chromium reports navigator.webdriver, which survey.js treats as a bot.
async function visitor(browser) {
  const context = await browser.newContext({ storageState: { cookies: [], origins: [] }, userAgent: BROWSER_UA });
  await context.addInitScript(() => Object.defineProperty(Navigator.prototype, "webdriver", { get: () => false }));
  return context;
}

function guestRequest(playwright, baseURL) {
  return playwright.request.newContext({ baseURL, storageState: { cookies: [], origins: [] } });
}

async function formRow(page, name) {
  await page.goto(listUrl(name));
  return page.locator("#the-list tr", { hasText: name });
}

test.describe("form views and conversion", () => {
  test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name");

  let created = null;
  let previousSetting = null;

  test.beforeEach(() => {
    previousSetting = fixture("setting", "default").previous;
    created = fixture("create");
  });

  test.afterEach(() => {
    if (created) {
      fixture("cleanup", created.form, ...created.pageIds);
    }
    fixture("setting", previousSetting);
  });

  test("counts a visitor's view once the form is on screen, and a submission, and shows the conversion", async ({ browser, page }) => {
    const context = await visitor(browser);
    const guest = await context.newPage();

    const topView = guest.waitForRequest((request) => request.url().includes(VIEW_ACTION) && request.method() === "POST");
    await guest.goto(created.pages.top);
    expect((await topView).postData()).toBe(`id=${created.form}`);
    await expect.poll(() => fixture("stats", created.form).views).toBe(1);

    let belowViews = 0;
    guest.on("request", (request) => {
      if (request.url().includes(VIEW_ACTION)) belowViews++;
    });
    await guest.goto(created.pages.below);
    await expect(guest.getByText("What is your name?")).toBeAttached();
    await guest.waitForTimeout(1500);
    expect(belowViews).toBe(0);
    const belowView = guest.waitForRequest((request) => request.url().includes(VIEW_ACTION));
    await guest.getByText("What is your name?").scrollIntoViewIfNeeded();
    await belowView;
    await expect.poll(() => fixture("stats", created.form).views).toBe(2);

    await guest.getByRole("textbox", { name: "What is your name?" }).fill("Ann");
    const saved = guest.waitForResponse((response) => response.url().includes("action=SurveyJS_SaveResult"));
    await guest.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    await expect.poll(() => fixture("stats", created.form)).toEqual({ views: 2, submissions: 1 });
    await context.close();

    const row = await formRow(page, created.name);
    await expect(row.locator("td.column-results")).toHaveText("1");
    await expect(row.locator("td.column-views")).toHaveText("2");
    await expect(row.locator("td.column-conversion")).toContainText("50%");
    await expect(row.locator("td.column-conversion span")).toHaveAttribute("title", "1 submission from 2 views");
  });

  test("does not count administrators, bots or GET requests", async ({ page, playwright, baseURL }) => {
    const request = await guestRequest(playwright, baseURL);
    const adminConfig = await surveyConfig(page.request, created.pages.top);
    expect(adminConfig.viewUrl).toBeUndefined();

    const guestConfig = await surveyConfig(request, created.pages.top);
    expect(guestConfig.viewUrl).toContain(VIEW_ACTION);

    const admin = await page.request.post(guestConfig.viewUrl, { form: { id: String(created.form) }, headers: { "User-Agent": BROWSER_UA } });
    expect(admin.status()).toBe(204);
    for (const userAgent of ["Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)", "curl/8.4.0", ""]) {
      const bot = await request.post(guestConfig.viewUrl, { form: { id: String(created.form) }, headers: { "User-Agent": userAgent } });
      expect(bot.status()).toBe(204);
    }
    await request.get(`${guestConfig.viewUrl}&id=${created.form}`, { headers: { "User-Agent": BROWSER_UA } });
    expect(fixture("stats", created.form).views).toBe(0);

    await request.post(guestConfig.viewUrl, { form: { id: String(created.form) }, headers: { "User-Agent": BROWSER_UA } });
    expect(fixture("stats", created.form).views).toBe(1);

    const row = await formRow(page, created.name);
    await expect(row.locator("td.column-conversion")).toContainText("0%");
  });

  test("the Form views setting turns counting and the columns off", async ({ page, playwright, baseURL }) => {
    const request = await guestRequest(playwright, baseURL);
    const row = await formRow(page, created.name);
    await expect(row.locator("td.column-conversion")).toHaveText("—");

    await page.goto("/wp-admin/admin.php?page=surveyjs-settings");
    const setting = page.getByLabel("Count form views to show conversion rates");
    await expect(setting).toBeChecked();
    await setting.uncheck();
    await page.getByRole("button", { name: "Save Changes" }).click();
    await expect(page.getByLabel("Count form views to show conversion rates")).not.toBeChecked();

    const guestConfig = await surveyConfig(request, created.pages.top);
    expect(guestConfig.viewUrl).toBeUndefined();
    await request.post(`/wp-admin/admin-ajax.php?${VIEW_ACTION}`, { form: { id: String(created.form) }, headers: { "User-Agent": BROWSER_UA } });
    expect(fixture("stats", created.form).views).toBe(0);

    await page.goto(listUrl(created.name));
    await expect(page.locator("th#views")).toHaveCount(0);
    await expect(page.locator("th#conversion")).toHaveCount(0);
  });
});
