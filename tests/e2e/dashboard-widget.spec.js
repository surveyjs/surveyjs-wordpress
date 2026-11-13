const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const DASHBOARD_URL = "/wp-admin/index.php";

function storageFixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/storage.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

async function restNonce(page) {
  return (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
}

async function surveyConfig(page, link) {
  const html = await (await page.request.get(link)).text();
  return JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
}

async function submitEntry(page, config, data) {
  const response = await page.request.post(config.saveResultUrl, {
    form: { SurveyId: String(config.id), Json: JSON.stringify(data), Website: "", _wpnonce: config.saveResultNonce }
  });
  expect(response.ok()).toBe(true);
}

async function entryId(page, surveyId, text) {
  await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
  return Number(await page.locator("#the-list tr", { hasText: text }).locator('input[name="result[]"]').inputValue());
}

function widget(page) {
  return page.locator("#surveyjs_dashboard_widget");
}

async function selectPeriod(page, label) {
  const periods = widget(page).locator(".wp-sjs-dw__periods");
  const link = periods.getByRole("link", { name: label, exact: true });
  if (await link.count()) {
    await link.click();
  }
  await expect(periods.locator(".current")).toHaveText(label);
}

async function formRow(page, name) {
  const row = widget(page).locator("li", { hasText: name });
  if (!(await row.isVisible())) {
    await widget(page).locator(".wp-sjs-dw__more summary").click();
  }
  await expect(row).toBeVisible();
  return row;
}

test.describe("dashboard widget", () => {
  let name;
  let post;
  let period;

  test.beforeEach(() => {
    name = null;
    post = null;
    period = null;
  });

  test.afterEach(async ({ page }) => {
    if (period) {
      await page.goto(DASHBOARD_URL);
      await selectPeriod(page, period);
    }
    if (post) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
    }
    if (name) {
      await deleteSurveys(page, name);
    }
  });

  test("lists a form's entries for the selected period, remembers the period and links to the results", async ({ page }) => {
    name = uniqueName("E2E dashboard widget");
    const surveyId = await createSurvey(page, name);
    const nonce = await restNonce(page);
    post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": nonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    post.nonce = nonce;

    const config = await surveyConfig(page, post.link);
    for (const answer of ["E2E today", "E2E 20 days ago", "E2E 40 days ago"]) {
      await submitEntry(page, config, { e2e_question: answer });
    }
    storageFixture("age-entry", await entryId(page, surveyId, "E2E 20 days ago"), 20);
    storageFixture("age-entry", await entryId(page, surveyId, "E2E 40 days ago"), 40);

    await page.goto(DASHBOARD_URL);
    await expect(widget(page).locator(".postbox-header h2")).toHaveText("SurveyJS Forms");
    period = await widget(page).locator(".wp-sjs-dw__periods .current").textContent();

    await selectPeriod(page, "Last 7 days");
    let row = await formRow(page, name);
    await expect(row.locator(".wp-sjs-dw__count")).toHaveText("1 entry");
    await expect(row.locator(".wp-sjs-dw__unread")).toHaveText("3 unread");
    await expect(row.locator(".wp-sjs-dw__unread")).toHaveAttribute("href", new RegExp(`page=surveyjs_results&id=${surveyId}&status=unread$`));

    await selectPeriod(page, "Last 30 days");
    row = await formRow(page, name);
    await expect(row.locator(".wp-sjs-dw__count")).toHaveText("2 entries");

    await selectPeriod(page, "All time");
    row = await formRow(page, name);
    await expect(row.locator(".wp-sjs-dw__count")).toHaveText("3 entries");

    await page.reload();
    await expect(widget(page).locator(".wp-sjs-dw__periods .current")).toHaveText("All time");
    await expect(widget(page).getByRole("link", { name: "All Forms" })).toHaveAttribute("href", /page=surveyjs-main-menu$/);
    await expect(widget(page).getByRole("link", { name: "Create a new form" })).toHaveAttribute("href", /page=surveyjs-templates$/);

    row = await formRow(page, name);
    await row.getByRole("link", { name, exact: true }).click();
    await expect(page).toHaveURL(new RegExp(`page=surveyjs_results&id=${surveyId}$`));
    await expect(page.locator("#the-list tr")).toHaveCount(3);
  });
});
