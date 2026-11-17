const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const FORMS_URL = "/wp-admin/admin.php?page=surveyjs-main-menu";
const GETTING_STARTED_URL = "/wp-admin/admin.php?page=surveyjs-getting-started";

function onboardingFixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/onboarding.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

function indicators(page) {
  return {
    topLevel: page.locator("#toplevel_page_surveyjs-main-menu > a"),
    menuProgress: page.locator("#toplevel_page_surveyjs-main-menu .wp-submenu a[href*='surveyjs-getting-started'] .wp-sjs-menu-progress"),
    barGettingStarted: page.locator("#wp-admin-bar-surveyjs-getting-started"),
    barNext: page.locator("#wp-admin-bar-surveyjs-next-step"),
    compact: page.locator(".wp-sjs-checklist-compact")
  };
}

test.describe("getting-started progress", () => {
  let savedState;
  let name;
  let postId;
  let restNonce;

  test.beforeAll(() => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to change the checklist state");
    savedState = onboardingFixture("get").state;
  });

  test.afterAll(() => {
    if (WP_CONTAINER) {
      onboardingFixture("set", JSON.stringify(savedState));
    }
  });

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
      postId = null;
    }
    if (name) {
      await deleteSurveys(page, name);
      name = null;
    }
  });

  test("shows the progress in the menu, the admin bar and the forms list, and Dismiss hides it", async ({ page }) => {
    onboardingFixture("progress", 1, "placement");
    const { topLevel, menuProgress, barGettingStarted, barNext, compact } = indicators(page);

    await page.goto(FORMS_URL);
    await expect(topLevel.locator(".wp-menu-name")).toHaveText("SurveyJS Forms");
    await expect(menuProgress.locator(".wp-sjs-menu-progress__text")).toHaveText("1 of 3");
    await expect(menuProgress.locator(".screen-reader-text")).toHaveText("1 of 3 steps completed");
    const bar = menuProgress.locator("progress");
    await expect(bar).toHaveAttribute("value", "1");
    await expect(bar).toHaveAttribute("max", "3");
    expect((await bar.boundingBox()).height).toBe(4);
    await expect(barGettingStarted.locator(".wp-sjs-menu-progress__text")).toHaveText("1 of 3");
    await expect(barGettingStarted.locator(".screen-reader-text")).toHaveText("1 of 3 steps completed");
    await expect(barGettingStarted.locator("progress")).toHaveAttribute("value", "1");
    await expect(barNext.locator("a")).toHaveText("Next: Add the form to a page");
    await expect(barNext.locator("a")).toHaveAttribute("href", /post-new\.php\?post_type=page$/);
    await expect(compact).toContainText("1 of 3 steps completed");
    await expect(compact.getByRole("link", { name: "Add the form to a page" })).toBeVisible();

    await page.goto("/");
    await expect(page.locator("#wp-admin-bar-surveyjs-next-step a")).toHaveText("Next: Add the form to a page");
    await page.locator("#wp-admin-bar-surveyjs").hover();
    const frontBar = page.locator("#wp-admin-bar-surveyjs-getting-started progress");
    await expect(frontBar).toBeVisible();
    expect((await frontBar.boundingBox()).height).toBe(4);

    await page.goto(FORMS_URL);
    const dismissed = page.waitForResponse(r => r.url().includes("admin-ajax.php") && r.request().postData()?.includes("action=surveyjs_checklist"));
    await compact.getByRole("button", { name: "Dismiss" }).click();
    await dismissed;
    await expect(compact).toBeHidden();
    await expect(menuProgress).toBeHidden();
    await expect(barNext).toBeHidden();

    await page.reload();
    await expect(page.locator(".wp-sjs-checklist-progress")).toHaveCount(0);
    await expect(barGettingStarted.locator("a")).toHaveText("Getting Started");

    await page.goto(GETTING_STARTED_URL);
    await page.getByRole("button", { name: "Show checklist" }).click();
    await expect(page.getByRole("button", { name: "Hide checklist" })).toBeVisible();
  });

  test("keeps the cached steps between pages and counts them again after a form is created", async ({ page }) => {
    onboardingFixture("progress", 0, "form");
    const { menuProgress, barNext } = indicators(page);

    await page.goto(FORMS_URL);
    await expect(barNext.locator("a")).toHaveText("Next: Create a form");
    await page.goto("/wp-admin/index.php");
    await expect(barNext.locator("a")).toHaveText("Next: Create a form");
    await expect(menuProgress.locator(".wp-sjs-menu-progress__text")).toHaveText("0 of 3");

    name = uniqueName("E2E onboarding");
    await createSurvey(page, name);
    await page.goto(FORMS_URL);
    await expect(page.locator("#wpadminbar")).toBeVisible();
    await expect(barNext.filter({ hasText: "Create a form" })).toHaveCount(0);
    await expect(menuProgress.filter({ hasText: "0 of 3" })).toHaveCount(0);
  });

  test("counts the first entry only for a submission of the published form, not the editor preview", async ({ page }) => {
    name = uniqueName("E2E onboarding entry");
    const surveyId = await createSurvey(page, name);
    expect(onboardingFixture("entry", surveyId).received).toBe(false);

    const preview = page.frameLocator("#wp-sjs-preview");
    await preview.getByLabel("What is your name?").fill("Preview answer");
    await preview.getByRole("button", { name: "Complete" }).click();
    await expect(preview.getByRole("button", { name: "Run again" })).toBeVisible();
    expect(onboardingFixture("entry", surveyId).received).toBe(false);

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;
    await page.goto(post.link);
    await page.getByLabel("What is your name?").fill("Real answer");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    expect(onboardingFixture("entry", surveyId).received).toBe(true);
  });

  test("shows no progress once the checklist is completed and lists the same items as the menu", async ({ page }) => {
    onboardingFixture("set", JSON.stringify({ dismissed: false, completed: true }));

    await page.goto(FORMS_URL);
    await expect(page.locator(".wp-sjs-checklist-progress")).toHaveCount(0);
    await expect(page.locator(".wp-sjs-checklist-compact")).toHaveCount(0);

    const menuItems = await page.locator("#toplevel_page_surveyjs-main-menu .wp-submenu li:not(.wp-submenu-head) a").allInnerTexts();
    const barItems = await page.locator("#wp-admin-bar-surveyjs-default > li > .ab-item").evaluateAll(items => items.map(item => item.textContent.trim()));
    expect(barItems.filter(item => !/unread|deleted soon/.test(item)).sort()).toEqual(menuItems.map(item => item.trim()).sort());
  });
});
