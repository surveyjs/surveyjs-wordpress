const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, saveSurvey, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const FILE_JSON = { elements: [{ type: "file", name: "document", title: "Your document" }] };

function runFixture(file, args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures", file)),
    stdio: ["pipe", "pipe", "ignore"]
  }).toString();
  return JSON.parse(output.trim().split("\n").pop());
}

const fixture = (...args) => runFixture("restrictions.php", args);
const storageFixture = (...args) => runFixture("storage.php", args);

function entryIds(surveyId) {
  return fixture("entries", surveyId).map(entry => Number(entry.id));
}

test.describe("entry retention", () => {
  test.skip(!WP_CONTAINER, "needs WP_CONTAINER to age entries and run the cron job");

  let names;
  let post;

  test.beforeEach(() => {
    names = [];
    post = null;
    fixture("site-retention", 0);
    fixture("unschedule");
  });

  test.afterEach(async ({ page }) => {
    fixture("site-retention", 0);
    if (post) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
    }
    for (const name of names) {
      await deleteSurveys(page, name);
    }
    fixture("unschedule");
  });

  test("the site setting deletes old entries of forms that use it", async ({ page }) => {
    await page.goto("/wp-admin/admin.php?page=surveyjs-settings");
    await page.getByLabel("Delete old entries").fill("5");
    await page.getByLabel("Delete old entries").press("Enter"); // saved on Enter or when the field is left
    await expect(page.locator(".wp-sjs-snackbar.is-success")).toBeVisible();
    await expect(page.getByLabel("Delete old entries")).toHaveValue("5");
    expect(fixture("scheduled").next).toBeTruthy();

    const siteName = uniqueName("E2E retention site");
    names.push(siteName);
    const siteForm = await createSurvey(page, siteName);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Entries" }).click();
    await expect(page.locator("#wp-sjs-entry-retention option[value=default]")).toHaveText("Use the site setting: delete after 5 days");
    await expect(page.getByLabel("Days to keep entries")).toBeDisabled();

    const keptName = uniqueName("E2E retention forever");
    names.push(keptName);
    const keptForm = await createSurvey(page, keptName);
    fixture("settings", keptForm, JSON.stringify({ entryRetention: "forever" }));

    const [old] = fixture("insert", siteForm, 1, 6).ids;
    const [recent] = fixture("insert", siteForm, 1, 4).ids;
    const [oldSpam] = fixture("insert", siteForm, 1, 30, "spam").ids;
    const [keptOld] = fixture("insert", keptForm, 1, 30).ids;

    fixture("run-retention");
    expect(entryIds(siteForm)).toEqual([recent]);
    expect(entryIds(siteForm)).not.toContain(old);
    expect(entryIds(siteForm)).not.toContain(oldSpam);
    expect(entryIds(keptForm)).toEqual([keptOld]);

    fixture("site-retention", 0);
    fixture("run-retention");
    expect(fixture("scheduled").next).toBe(false);
  });

  test("a form's own period deletes old entries with their uploaded files", async ({ page, browser }) => {
    const name = uniqueName("E2E retention form");
    names.push(name);
    const surveyId = await createSurvey(page, name, FILE_JSON);
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Entries" }).click();
    const days = page.getByLabel("Days to keep entries");
    await expect(days).toBeDisabled();
    await page.getByLabel("Delete old entries").selectOption("days");
    await expect(days).toBeEnabled();
    await days.fill("7");
    await saveSurvey(page);
    expect(fixture("settings", surveyId, "{}")).toMatchObject({ entryRetention: "days", entryRetentionDays: 7 });
    expect(fixture("scheduled").next).toBeTruthy();

    const nonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    post = { ...(await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": nonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json()), nonce };

    const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
    await visitor.goto(post.link);
    const uploaded = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
    const fileChooser = visitor.waitForEvent("filechooser");
    await visitor.locator(".wp-sjs-survey .sd-file__choose-btn").first().click();
    await (await fileChooser).setFiles({ name: "old.txt", mimeType: "text/plain", buffer: Buffer.from("E2E old file") });
    const file = (await (await uploaded).json())["old.txt"];
    const saved = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await visitor.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    await visitor.close();

    const [submitted] = entryIds(surveyId);
    expect(storageFixture("file", file.file)).toMatchObject({ exists: true, record: { entry_id: String(submitted) } });
    storageFixture("age-entry", submitted, 8);
    const [recent] = fixture("insert", surveyId, 1, 6).ids;

    fixture("run-retention");
    expect(entryIds(surveyId)).toEqual([recent]);
    expect(storageFixture("file", file.file)).toMatchObject({ exists: false, record: null });
    expect(fixture("scheduled").next).toBeTruthy();
  });
});
