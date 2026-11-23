const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, saveSurvey, createSurvey, deleteSurveys, submitTimeFields } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const MAILPIT_URL = process.env.MAILPIT_URL || "http://localhost:8025";
const MU_PLUGIN = "/var/www/html/wp-content/mu-plugins/surveyjs-e2e-stream-uploads.php";
const FILE_JSON = { elements: [{ type: "file", name: "document", title: "Your document" }] };
const PNG = Buffer.from("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=", "base64");

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: ["pipe", "pipe", "ignore"] }).toString();
}

function fixture(...args) {
  const output = docker(["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], fs.readFileSync(path.join(__dirname, "fixtures/storage.php")));
  return JSON.parse(output.trim().split("\n").pop());
}

async function publish(page, name, surveyId) {
  const nonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
  const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
    headers: { "X-WP-Nonce": nonce },
    data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
  })).json();
  return { ...post, nonce };
}

async function surveyConfig(request, link) {
  const html = await (await request.get(link)).text();
  return JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
}

async function upload(page, fileName, body, mimeType = "text/plain") {
  const uploaded = page.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
  const fileChooser = page.waitForEvent("filechooser");
  await page.locator(".wp-sjs-survey .sd-file__choose-btn").first().click();
  await (await fileChooser).setFiles({ name: fileName, mimeType, buffer: body });
  const response = await uploaded;
  expect(response.ok()).toBe(true);
  return (await response.json())[fileName];
}

async function submit(page) {
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
  await page.getByRole("button", { name: "Complete" }).click();
  const response = await saved;
  expect(response.ok()).toBe(true);
  return JSON.parse(new URLSearchParams(response.request().postData()).get("Json"));
}

function deleteRequest(request, config, uploaded, overrides = {}) {
  return request.post(config.deleteFileUrl, {
    form: { _wpnonce: config.deleteFileNonce, file: uploaded.file, expires: String(uploaded.expires), token: uploaded.deleteToken, ...overrides }
  });
}

async function findMail(request, subject) {
  let id = null;
  await expect.poll(async () => {
    const result = await (await request.get(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`subject:"${subject}"`)}`)).json();
    id = result.messages[0]?.ID ?? null;
    return id;
  }, { timeout: 30000 }).not.toBeNull();
  return (await request.get(`${MAILPIT_URL}/api/v1/message/${id}`)).json();
}

function storageTests(label) {
  test.describe(label, () => {
    let names = [];
    let posts = [];

    test.afterEach(async ({ page }) => {
      for (const post of posts) {
        await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
      }
      for (const name of names) {
        await deleteSurveys(page, name);
      }
      names = [];
      posts = [];
    });

    test("stores an endpoint link, lets the respondent delete a file before submitting and keeps submitted files", async ({ page, browser }) => {
      const name = uniqueName("E2E file storage");
      names.push(name);
      const surveyId = await createSurvey(page, name, FILE_JSON);
      const post = await publish(page, name, surveyId);
      posts.push(post);

      const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
      await visitor.goto(post.link);
      const config = await surveyConfig(visitor.request, post.link);
      const first = await upload(visitor, "first.png", PNG, "image/png");
      expect(first.url).toContain(`action=SurveyJS_DownloadFile&file=${first.file}`);
      expect(first.url).not.toContain("/uploads/");
      await expect(visitor.locator(".wp-sjs-survey .sd-file img")).toHaveAttribute("src", first.preview);
      const preview = await visitor.request.get(first.preview);
      expect(preview.status()).toBe(200);
      expect(preview.headers()["content-type"]).toBe("image/png");
      expect((await visitor.request.get(first.url, { maxRedirects: 0 })).status()).toBe(302);
      if (WP_CONTAINER) {
        expect(fixture("file", first.file)).toMatchObject({ exists: true, record: { form_id: String(surveyId), entry_id: null, original_name: "first.png", mime: "image/png" } });
      }

      expect((await deleteRequest(visitor.request, config, first, { token: "0".repeat(64) })).status()).toBe(403);
      expect((await deleteRequest(visitor.request, config, first, { expires: String(first.expires + 1) })).status()).toBe(403);
      const deleted = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_DeleteFile"));
      await visitor.locator(".wp-sjs-survey .sd-file").getByRole("button", { name: "Clear" }).click();
      await visitor.locator(".sv-popup").getByRole("button", { name: /^(ok|yes|apply)$/i }).first().click();
      expect((await deleted).status()).toBe(200);
      expect((await page.request.get(first.url)).status()).toBe(404);
      if (WP_CONTAINER) {
        expect(fixture("file", first.file)).toMatchObject({ exists: false, record: null });
      }

      const second = await upload(visitor, "second.txt", Buffer.from("E2E second file"));
      const data = await submit(visitor);
      expect(data.document[0]).toMatchObject({ name: "second.txt", content: second.url });
      expect((await deleteRequest(visitor.request, config, second)).status()).toBe(409);
      await visitor.close();

      await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
      const href = await page.locator("#the-list a", { hasText: "second.txt" }).getAttribute("href");
      const download = await page.request.get(href);
      expect(download.status()).toBe(200);
      expect(await download.text()).toBe("E2E second file");
      expect(download.headers()["content-disposition"]).toContain('attachment; filename="second.txt"');
      expect((await page.request.get(second.url)).status()).toBe(200);
      if (WP_CONTAINER) {
        const stored = fixture("entry-values", surveyId);
        expect(stored[0].document[0].content).toBe(second.url);
        expect(fixture("file", second.file).record.entry_id).not.toBeNull();
      }

      page.once("dialog", dialog => dialog.accept());
      await page.locator("#the-list tr").first().hover();
      await page.locator("#the-list tr").first().locator("a.submitdelete").click();
      await expect(page.locator(".notice-success")).toContainText("1 result deleted.");
      expect((await page.request.get(href)).status()).toBe(404);
      if (WP_CONTAINER) {
        expect(fixture("file", second.file)).toMatchObject({ exists: false, record: null });
      }
    });

    test("drops files uploaded to another form from a submitted entry", async ({ page, browser }) => {
      test.skip(!WP_CONTAINER, "Set WP_CONTAINER to read the stored entry");
      const name = uniqueName("E2E foreign file");
      names.push(name);
      const sourceId = await createSurvey(page, `${name} source`, FILE_JSON);
      const targetId = await createSurvey(page, `${name} target`, { elements: [...FILE_JSON.elements, { type: "text", name: "note", title: "Note" }] });
      const source = await publish(page, `${name} source`, sourceId);
      const target = await publish(page, `${name} target`, targetId);
      posts.push(source, target);

      const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
      await visitor.goto(source.link);
      const uploaded = await upload(visitor, "foreign.txt", Buffer.from("E2E foreign"));
      const config = await surveyConfig(visitor.request, target.link);
      const response = await visitor.request.post(config.saveResultUrl, {
        form: { SurveyId: String(config.id), Json: JSON.stringify({ note: "kept", document: [{ name: "foreign.txt", type: "text/plain", content: uploaded.url }] }), Website: "", _wpnonce: config.saveResultNonce, ...(await submitTimeFields(config)) }
      });
      expect(response.ok()).toBe(true);
      await visitor.close();

      expect(fixture("entry-values", targetId)).toEqual([{ note: "kept", document: [] }]);
      expect(fixture("file", uploaded.file)).toMatchObject({ exists: true, record: { form_id: String(sourceId), entry_id: null } });
    });

    test("attaches files to the notification and removes abandoned uploads with the daily cleanup", async ({ page, browser }) => {
      test.skip(!WP_CONTAINER, "Set WP_CONTAINER to run the cleanup");
      const name = uniqueName("E2E file mail");
      const subject = `${name} subject`;
      names.push(name);
      const surveyId = await createSurvey(page, name, FILE_JSON);
      await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true }).click();
      await page.getByLabel("Send an email when the form is submitted").check();
      await page.getByLabel("Send To").fill("owner@example.com");
      await page.locator("#wp-sjs-notification-subject").fill(subject);
      await page.locator("#wp-sjs-notification-attachments").selectOption("document");
      await saveSurvey(page);
      const post = await publish(page, name, surveyId);
      posts.push(post);

      const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
      await visitor.goto(post.link);
      const abandoned = await upload(visitor, "abandoned.txt", Buffer.from("E2E abandoned"));
      await visitor.goto(post.link);
      const sent = await upload(visitor, "report.txt", Buffer.from("E2E attachment"));
      await submit(visitor);
      await visitor.close();

      const mail = await findMail(page.request, subject);
      expect(mail.Attachments.map(attachment => attachment.FileName)).toEqual(["report.txt"]);
      const attachment = await page.request.get(`${MAILPIT_URL}/api/v1/message/${mail.ID}/part/${mail.Attachments[0].PartID}`);
      expect(await attachment.text()).toBe("E2E attachment");

      fixture("age-file", abandoned.file, 2);
      fixture("age-file", sent.file, 2);
      fixture("cleanup-files");
      expect(fixture("file", abandoned.file)).toMatchObject({ exists: false, record: null });
      expect(fixture("file", sent.file)).toMatchObject({ exists: true });
    });
  });
}

storageTests("file storage in the uploads folder");

test.describe("with upload checks from other plugins", () => {
  const PREFILTER_PLUGIN = "/var/www/html/wp-content/mu-plugins/surveyjs-e2e-upload-prefilter.php";
  test.skip(!WP_CONTAINER, "Set WP_CONTAINER to install the upload check mu-plugin");

  test.beforeAll(() => {
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${PREFILTER_PLUGIN}`], fs.readFileSync(path.join(__dirname, "fixtures/upload-prefilter.php")));
  });

  test.afterAll(() => {
    docker(["exec", WP_CONTAINER, "rm", "-f", PREFILTER_PLUGIN]);
  });

  test("refuses files rejected by wp_handle_upload_prefilter or the wp_handle_upload_overrides types", async ({ page, browser }) => {
    const name = uniqueName("E2E upload checks");
    const surveyId = await createSurvey(page, name, FILE_JSON);
    const post = await publish(page, name, surveyId);
    try {
      const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
      await visitor.goto(post.link);
      const refused = async (fileName, mimeType) => {
        const uploaded = visitor.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
        const fileChooser = visitor.waitForEvent("filechooser");
        await visitor.locator(".wp-sjs-survey .sd-file__choose-btn").first().click();
        await (await fileChooser).setFiles({ name: fileName, mimeType, buffer: Buffer.from("E2E checked file") });
        const response = await uploaded;
        expect(response.status()).toBe(400);
        return response.json();
      };

      expect(await refused("infected.txt", "text/plain")).toEqual({ error: "file_rejected", message: "E2E scanner: this file is infected." });
      await expect(visitor.locator(".wp-sjs-survey .sd-question__erbox")).toContainText("E2E scanner: this file is infected.");
      expect(await refused("table.csv", "text/csv")).toEqual({ error: "file_type_mismatch" });
      const clean = await upload(visitor, "clean.txt", Buffer.from("E2E clean file"));
      expect(fixture("file", clean.file).exists).toBe(true);
      await visitor.close();
    } finally {
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
      await deleteSurveys(page, name);
    }
  });
});

test.describe("on a stream wrapper without folder listing (like vip://)", () => {
  test.skip(!WP_CONTAINER, "Set WP_CONTAINER to install the stream wrapper mu-plugin");

  test.beforeAll(() => {
    docker(["exec", WP_CONTAINER, "sh", "-c", "rm -rf /var/www/html/wp-content/sjstest-uploads /var/www/html/wp-content/sjstest-unsupported.log"]);
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${MU_PLUGIN}`], fs.readFileSync(path.join(__dirname, "fixtures/stream-uploads.php")));
  });

  test.afterAll(() => {
    docker(["exec", WP_CONTAINER, "sh", "-c", `rm -f ${MU_PLUGIN}; rm -rf /var/www/html/wp-content/sjstest-uploads /var/www/html/wp-content/sjstest-unsupported.log`]);
  });

  test("keeps files behind the stream wrapper, out of reach of their uploads URL", async ({ page, browser }) => {
    const name = uniqueName("E2E stream file");
    const surveyId = await createSurvey(page, name, FILE_JSON);
    const post = await publish(page, name, surveyId);
    try {
      const visitor = await browser.newPage({ storageState: { cookies: [], origins: [] } });
      await visitor.goto(post.link);
      const uploaded = await upload(visitor, "stream.txt", Buffer.from("E2E stream file"));
      const stored = fixture("file", uploaded.file);
      expect(stored.path).toBe(`sjstest://uploads/surveyjs/${uploaded.file}`);
      expect(stored.exists).toBe(true);
      expect((await visitor.request.get(`/wp-content/uploads/surveyjs/${uploaded.file}`)).status()).not.toBe(200);
      expect(await (await visitor.request.get(uploaded.preview)).text()).toBe("E2E stream file");
      await visitor.close();
    } finally {
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
      await deleteSurveys(page, name);
    }
  });

  storageTests("file storage on the stream wrapper");

  test("never lists, renames or changes modes in the SurveyJS folder", () => {
    expect(fixture("stream-log").lines.filter(line => line.includes("/surveyjs"))).toEqual([]);
  });
});
