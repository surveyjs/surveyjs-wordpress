const { test, expect } = require("@playwright/test");
const { uniqueName, createSurvey, deleteSurveys } = require("./helpers");

const PNG = Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=", "base64");

test.describe("uploaded files", () => {
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    await deleteSurveys(page, name);
  });

  test("are private, open from the entry and are deleted with it", async ({ page, browser }) => {
    name = uniqueName("E2E files");
    const surveyId = await createSurvey(page, name, { elements: [{ type: "file", name: "photo", title: "Photo" }] });
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;

    const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
    await visitor.goto(post.link);
    const uploaded = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
    const fileChooser = visitor.waitForEvent("filechooser");
    await visitor.locator(".wp-sjs-survey .sd-file__choose-btn").first().click();
    await (await fileChooser).setFiles({ name: "photo.png", mimeType: "image/png", buffer: PNG });
    const file = (await (await uploaded).json())["photo.png"];

    expect((await visitor.request.get(file.url, { maxRedirects: 0 })).status()).toBe(403);
    await expect(visitor.locator(".wp-sjs-survey .sd-file img")).toHaveAttribute("src", file.preview);
    expect((await visitor.request.get(file.preview)).status()).toBe(200);

    const saved = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await visitor.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    const link = page.locator("#the-list a", { hasText: "photo.png" });
    const href = await link.getAttribute("href");
    const download = await page.request.get(href);
    expect(download.status()).toBe(200);
    expect(download.headers()["content-type"]).toBe("image/png");
    expect((await visitor.request.get(href, { maxRedirects: 0 })).status()).toBe(302);
    await visitor.close();

    page.once("dialog", dialog => dialog.accept());
    await page.locator("#the-list tr").first().hover();
    await page.locator("#the-list tr").first().locator("a.submitdelete").click();
    await expect(page.locator(".notice-success")).toContainText("1 result deleted.");
    expect((await page.request.get(href)).status()).toBe(404);
  });

  test("inserts a Media Library image as the form logo", async ({ page }) => {
    name = uniqueName("E2E logo");
    await createSurvey(page, name);
    await expect(page.locator("#wp-sjs-set-logo")).toBeEnabled();
    await page.locator("#wp-sjs-set-logo").click();
    const modal = page.locator(".media-modal:visible");
    await expect(modal).toBeVisible();
    await modal.locator("input[type=file]").setInputFiles({ name: "e2e-logo.png", mimeType: "image/png", buffer: PNG });
    await expect(modal.locator("li.attachment.selected")).toBeVisible();
    await expect(modal.locator(".media-button-select")).toBeEnabled();
    await modal.locator(".media-button-select").click();
    const json = JSON.parse(await page.evaluate(() => monaco.editor.getModels()[0].getValue()));
    expect(json.logo).toMatch(/\/wp-content\/uploads\/.*e2e-logo.*\.png$/);
  });
});
