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
    menuProgress: page.locator("#toplevel_page_surveyjs-main-menu .wp-submenu a[href*='surveyjs-getting-started'] .wp-sjs-progress"),
    headerProgress: page.locator(".wp-sjs-header .wp-sjs-progress"),
    dashboardProgress: page.locator("#surveyjs_dashboard_widget .wp-sjs-dw__progress"),
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
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
      postId = null;
    }
    if (name) {
      await deleteSurveys(page, name);
      name = null;
    }
  });

  test("shows the progress on the forms list, not in the menu, the header or the admin bar, and Dismiss hides it", async ({ page }) => {
    onboardingFixture("progress", 1, "placement");
    const { topLevel, menuProgress, headerProgress, compact } = indicators(page);

    await page.goto(FORMS_URL);
    await expect(topLevel.locator(".wp-menu-name")).toHaveText("SurveyJS Forms");
    await expect(menuProgress).toHaveCount(0);
    await expect(headerProgress).toHaveCount(0);
    await expect(page.locator("#wp-admin-bar-surveyjs")).toHaveCount(0);
    await expect(page.locator(".wp-sjs-header__tab", { hasText: "Support" })).toHaveText("Support");
    await expect(compact).toContainText("1 of 3 steps completed");
    await expect(compact.getByRole("link", { name: "Add the form to a page" })).toHaveAttribute("href", /post-new\.php\?post_type=page$/);

    await page.goto("/");
    await expect(page.locator("#wpadminbar")).toBeVisible();
    await expect(page.locator("#wp-admin-bar-surveyjs")).toHaveCount(0);

    await page.goto(FORMS_URL);
    const dismissed = page.waitForResponse(r => r.url().includes("admin-ajax.php") && r.request().postData()?.includes("action=surveyjs_checklist"));
    await compact.getByRole("button", { name: "Dismiss" }).click();
    await dismissed;
    await expect(compact).toBeHidden();

    await page.reload();
    await expect(page.locator(".wp-sjs-checklist-compact")).toHaveCount(0);

    await page.goto(GETTING_STARTED_URL);
    await page.getByRole("button", { name: "Show checklist" }).click();
    await expect(page.getByRole("button", { name: "Hide checklist" })).toBeVisible();
  });

  test("keeps the cached steps between pages and counts them again after a form is created", async ({ page }) => {
    onboardingFixture("progress", 0, "form");
    const { compact, dashboardProgress } = indicators(page);

    await page.goto(FORMS_URL);
    await expect(compact.getByRole("link", { name: "Create a form" })).toBeVisible();
    await page.goto("/wp-admin/index.php");
    await expect(dashboardProgress.getByRole("link", { name: "Create a form" })).toBeVisible();

    name = uniqueName("E2E onboarding");
    await createSurvey(page, name);
    await page.goto(FORMS_URL);
    await expect(compact.getByRole("link", { name: "Create a form" })).toHaveCount(0);
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
    const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
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

  test("shows no progress once the checklist is completed and the header lists the same items as the menu", async ({ page }) => {
    onboardingFixture("set", JSON.stringify({ dismissed: false, completed: true }));

    await page.goto(FORMS_URL);
    await expect(page.locator(".wp-sjs-checklist-compact")).toHaveCount(0);

    const label = items => items.map(item => {
      const copy = item.cloneNode(true);
      copy.querySelectorAll(".wp-sjs-menu-count, .screen-reader-text").forEach(node => node.remove());
      return copy.textContent.trim();
    });
    const menuItems = await page.locator("#toplevel_page_surveyjs-main-menu .wp-submenu li:not(.wp-submenu-head):not(.hidden) a").evaluateAll(label);
    const headerItems = await page.locator(".wp-sjs-header__tabs > a.wp-sjs-header__tab, .wp-sjs-header__tab-link, .wp-sjs-header__actions > a").evaluateAll(label);
    expect(headerItems.sort()).toEqual(menuItems.sort());
  });
});
