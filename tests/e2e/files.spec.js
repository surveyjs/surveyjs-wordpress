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

  test("are uploaded and stored as links, even when the JSON asks for text and in dynamic panels", async ({ page, browser }) => {
    name = uniqueName("E2E file links");
    const surveyId = await createSurvey(page, name, {
      elements: [
        { type: "file", name: "plain", title: "Plain" },
        { type: "file", name: "as_text", title: "As text", storeDataAsText: true },
        { type: "paneldynamic", name: "items", title: "Items", panelCount: 1, templateElements: [{ type: "file", name: "nested", title: "Nested" }] }
      ]
    });
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;

    const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
    await visitor.goto(post.link);
    const chooseButtons = visitor.locator(".wp-sjs-survey .sd-file__choose-btn");
    await expect(chooseButtons).toHaveCount(3);
    for (const [index, fileName] of ["plain.png", "as-text.png", "nested.png"].entries()) {
      const uploaded = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
      const fileChooser = visitor.waitForEvent("filechooser");
      await chooseButtons.nth(index).click();
      await (await fileChooser).setFiles({ name: fileName, mimeType: "image/png", buffer: PNG });
      expect((await uploaded).ok()).toBe(true);
    }

    const saved = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await visitor.getByRole("button", { name: "Complete" }).click();
    const response = await saved;
    expect(response.ok()).toBe(true);
    const data = JSON.parse(new URLSearchParams(response.request().postData()).get("Json"));
    const stored = /\/wp-content\/uploads\/surveyjs\/[a-z0-9]+\.png$/;
    expect(data.plain[0]).toMatchObject({ name: "plain.png", content: expect.stringMatching(stored) });
    expect(data.as_text[0]).toMatchObject({ name: "as-text.png", content: expect.stringMatching(stored) });
    expect(data.items[0].nested[0]).toMatchObject({ name: "nested.png", content: expect.stringMatching(stored) });
    await visitor.close();

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    for (const fileName of ["plain.png", "as-text.png"]) {
      const href = await page.locator("#the-list a", { hasText: fileName }).getAttribute("href");
      expect((await page.request.get(href)).headers()["content-type"]).toBe("image/png");
    }
  });

  test("keep showing and downloading answers stored as text before", async ({ page }) => {
    name = uniqueName("E2E file text");
    const surveyId = await createSurvey(page, name, { elements: [{ type: "file", name: "photo", title: "Photo" }] });
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;
    const html = await (await page.request.get(post.link)).text();
    const config = JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
    const dataUrl = `data:image/png;base64,${PNG.toString("base64")}`;
    const submitted = await page.request.post(config.saveResultUrl, {
      form: { SurveyId: String(config.id), Json: JSON.stringify({ photo: [{ name: "old.png", type: "image/png", content: dataUrl }] }), Website: "", _wpnonce: config.saveResultNonce }
    });
    expect(submitted.ok()).toBe(true);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    const row = page.locator("#the-list tr").first();
    await expect(row).toContainText("old.png");
    await row.hover();
    await row.getByRole("link", { name: "View", exact: true }).click();
    const popup = page.locator("#wp-sjs-result-popup");
    await expect(popup.locator(".sd-file img")).toHaveAttribute("src", dataUrl);
    await expect(popup.locator("a[download='old.png']").first()).toHaveAttribute("href", dataUrl);

    const csv = await page.request.get(await page.getByRole("link", { name: "Export CSV" }).getAttribute("href"));
    expect(csv.headers()["content-type"]).toContain("text/csv");
    expect(await csv.text()).toContain("old.png");
  });

  test("stay in the browser in the editor preview", async ({ page }) => {
    name = uniqueName("E2E file preview");
    await createSurvey(page, name, { elements: [{ type: "file", name: "photo", title: "Photo" }] });
    let uploads = 0;
    page.on("request", request => {
      if (request.url().includes("action=SurveyJS_UploadFiles")) uploads++;
    });
    const preview = page.frameLocator("#wp-sjs-preview");
    const fileChooser = page.waitForEvent("filechooser");
    await preview.locator(".sd-file__choose-btn").first().click();
    await (await fileChooser).setFiles({ name: "preview.png", mimeType: "image/png", buffer: PNG });
    await expect(preview.locator(".sd-file img")).toHaveAttribute("src", /^blob:/);
    await expect(preview.locator(".sd-question__erbox")).toHaveCount(0);
    expect(uploads).toBe(0);
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

  test("lists Media Library images to copy without changing the JSON", async ({ page, context, baseURL }) => {
    await context.grantPermissions(["clipboard-read", "clipboard-write"], { origin: new URL(baseURL).origin });
    name = uniqueName("E2E images");
    await createSurvey(page, name);
    const editorValue = () => page.evaluate(() => monaco.editor.getModels()[0].getValue());
    const before = await editorValue();
    const insertImage = page.getByRole("button", { name: "Insert image" });
    const list = page.locator("#wp-sjs-image-list");
    await expect(list).toBeHidden();

    const chooseImages = async (files) => {
      await insertImage.click();
      const modal = page.locator(".media-modal:visible");
      await expect(modal).toBeVisible();
      await modal.locator("input[type=file]").setInputFiles(files.map(file => ({ name: file, mimeType: "image/png", buffer: PNG })));
      await expect(modal.locator("li.attachment.selected")).toHaveCount(files.length);
      await modal.locator(".media-button-select").click();
      await expect(modal).toBeHidden();
    };

    await chooseImages(["e2e-image-a.png", "e2e-image-b.png"]);
    const items = list.locator("li");
    await expect(items).toHaveCount(2);
    await expect(items.first().locator("img")).toBeVisible();
    await expect(items.first().locator("input")).toHaveValue(/\/wp-content\/uploads\/.*e2e-image-a.*\.png$/);
    expect(await editorValue()).toBe(before);

    const url = await items.first().locator("input").inputValue();
    await items.first().getByRole("button", { name: "Copy" }).click();
    await expect(items.first().getByRole("button", { name: "Copied!" })).toBeVisible();
    expect(await page.evaluate(() => navigator.clipboard.readText())).toBe(url);

    await insertImage.click();
    const modal = page.locator(".media-modal:visible");
    await modal.locator("li.attachment", { has: page.locator("img[src*='e2e-image-a']") }).first().click();
    await modal.locator(".media-button-select").click();
    await expect(items).toHaveCount(2);

    await items.first().getByRole("button", { name: /Remove from the list/ }).click();
    await expect(items).toHaveCount(1);
    await expect(items.first().locator("input")).toHaveValue(/e2e-image-b/);

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
    await expect(insertImage).toBeEnabled();
    await expect(items).toHaveCount(1);
    expect(await editorValue()).toBe(before);
  });
});
