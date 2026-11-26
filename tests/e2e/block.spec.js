const fs = require("fs");
const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

test.describe("Gutenberg block", () => {
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    await deleteSurveys(page, name);
  });

  test("inserts a survey, completes it on the site and manages the result", async ({ page }) => {
    name = uniqueName("E2E block");
    const surveyId = await createSurvey(page, name);

    await page.goto("/wp-admin/post-new.php");
    await page.waitForFunction(() => window.wp && wp.data && wp.data.select("core/editor").getCurrentPostId());
    await page.evaluate(() => wp.data.dispatch("core/preferences").set("core/edit-post", "welcomeGuide", false));
    postId = await page.evaluate(() => wp.data.select("core/editor").getCurrentPostId());
    restNonce = await page.evaluate(() => window.wpApiSettings.nonce);

    const canvas = page.frameLocator("iframe[name='editor-canvas']");
    await canvas.getByRole("textbox", { name: "Add title" }).fill(name);
    await page.getByRole("button", { name: "Block Inserter" }).click();
    await page.getByRole("searchbox").fill("SurveyJS");
    await page.getByRole("option", { name: "SurveyJS" }).click();

    const block = canvas.locator("[data-type='sjs/gutenberg-block']");
    await expect(block.getByText("Choose form to insert")).toBeVisible();
    await block.getByRole("combobox").fill(name);
    await block.getByRole("option", { name, exact: true }).click();

    await page.getByRole("button", { name: "Publish", exact: true }).click();
    await page.locator(".editor-post-publish-panel").getByRole("button", { name: "Publish", exact: true }).click();
    await expect(page.locator(".post-publish-panel__postpublish")).toBeVisible();
    const postUrl = await page.evaluate(() => wp.data.select("core/editor").getPermalink());

    await page.goto(postUrl);
    await page.getByLabel("What is your name?").fill("E2E answer");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);
    await expect(page.getByText("Thank you for completing the survey")).toBeVisible();

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    const row = page.locator("#the-list tr", { hasText: "E2E answer" });
    await expect(row).toBeVisible();

    await row.hover();
    await row.getByRole("link", { name: "View", exact: true }).click();
    await expect(page.locator("#wp-sjs-result-popup").getByLabel("What is your name?")).toHaveValue("E2E answer");
    await page.reload();

    const downloadEvent = page.waitForEvent("download");
    await page.getByRole("link", { name: "Export CSV" }).click();
    const csv = fs.readFileSync(await (await downloadEvent).path(), "utf8");
    expect(csv).toContain("What is your name?");
    expect(csv).toContain("E2E answer");

    await row.hover();
    page.once("dialog", dialog => dialog.accept());
    await row.getByRole("link", { name: "Delete", exact: true }).click();
    await expect(page.locator(".wp-sjs-snackbar.is-success")).toContainText("1 entry deleted.");
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });

  test("wide and full alignment widen the form", async ({ page }) => {
    name = uniqueName("E2E block align");
    const surveyId = await createSurvey(page, name);
    const block = (align) => `<!-- wp:sjs/gutenberg-block ${JSON.stringify(Object.assign(
      { shortcode: `[surveyjs id="${surveyId}" title="${name}"] `, surveyid: String(surveyId) },
      align ? { align } : {}
    ))} /-->`;

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: [block(""), block("wide"), block("full")].join("\n\n") }
    })).json();
    postId = post.id;

    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(post.link);
    const blocks = page.locator(".wp-block-sjs-gutenberg-block");
    await expect(blocks).toHaveCount(3);
    await expect(blocks.nth(0)).not.toHaveClass(/\balign(wide|full)\b/);
    await expect(blocks.nth(1)).toHaveClass(/\balignwide\b/);
    await expect(blocks.nth(2)).toHaveClass(/\balignfull\b/);

    const bodies = blocks.locator(".sd-body");
    await expect(bodies.nth(0)).toHaveClass(/\bsd-body--static\b/);
    await expect(bodies.nth(1)).toHaveClass(/\bsd-body--responsive\b/);
    await expect(bodies.nth(2)).toHaveClass(/\bsd-body--responsive\b/);

    const [defaultWidth, fullWidth] = await Promise.all([0, 2].map(async (i) => (await bodies.nth(i).boundingBox()).width));
    expect(fullWidth).toBeGreaterThan(defaultWidth);
    const fullBox = await blocks.nth(2).boundingBox();
    const fullBody = await bodies.nth(2).boundingBox();
    expect(fullBody.x).toBeGreaterThan(fullBox.x);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  });
});
