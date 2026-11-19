const { test, expect } = require("@playwright/test");
const { uniqueName, saveSurvey, createSurvey, deleteSurveys } = require("./helpers");

const MAILPIT_URL = process.env.MAILPIT_URL || "http://localhost:8025";

const CONTACT_JSON = {
  elements: [
    { type: "text", name: "visitor_name", title: "Your name" },
    { type: "text", name: "visitor_email", title: "Your email", inputType: "email" }
  ]
};

async function findMail(request, subject) {
  let id = null;
  await expect.poll(async () => {
    const response = await request.get(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`subject:"${subject}"`)}`);
    const result = await response.json();
    id = result.messages[0]?.ID ?? null;
    return id;
  }, { timeout: 30000 }).not.toBeNull();
  return (await request.get(`${MAILPIT_URL}/api/v1/message/${id}`)).json();
}

test.describe("email notification", () => {
  let name;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    await deleteSurveys(page, name);
  });

  test("adds and removes the Reply-To question with the checkbox", async ({ page }) => {
    name = uniqueName("E2E reply-to");
    await createSurvey(page, name);
    const settingsTab = page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true });
    const addQuestion = page.getByLabel("Add a Reply-To question (email) to the form");
    const replyTo = page.getByLabel("Reply-To", { exact: true });
    const notification = page.getByLabel("Send an email when the form is submitted");
    const editorJson = async () => JSON.parse(await page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue()));

    await settingsTab.click();
    await expect(notification).not.toBeChecked();
    await expect(addQuestion).toBeDisabled();
    await notification.check();
    await expect(addQuestion).toBeEnabled();
    await addQuestion.check();
    await expect(replyTo).toHaveValue("reply_to_email");
    await expect(replyTo.locator("option:checked")).toHaveText("reply_to_email");
    await saveSurvey(page);

    await page.reload();
    await expect(page.locator(".monaco-editor")).toBeVisible();
    expect((await editorJson()).elements.at(-1)).toEqual({ type: "text", name: "reply_to_email", title: "Leave your email so we can get back to you", inputType: "email", placeholder: "john.doe@example.com" });
    await settingsTab.click();
    await expect(replyTo).toHaveValue("reply_to_email");
    await expect(addQuestion).toBeChecked();

    await addQuestion.uncheck();
    await expect(replyTo).toHaveValue("");
    expect((await editorJson()).elements.some(element => element.name === "reply_to_email")).toBe(false);
  });

  test("emails the submitted answers and skips storing them when entries are off", async ({ page }) => {
    name = uniqueName("E2E notify");
    const subject = `E2E notification ${Date.now()}`;
    const autoReplySubject = `E2E auto-reply ${Date.now()}`;
    const surveyId = await createSurvey(page, name, CONTACT_JSON);

    const emailTab = page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true });
    const entriesTab = page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Entries" });
    const notificationSubject = page.locator("#wp-sjs-notification-subject");
    const autoReply = page.getByRole("group", { name: "Auto-reply" });
    await entriesTab.click();
    await page.getByLabel("Save submitted entries in the database").uncheck();
    await emailTab.click();
    await page.getByLabel("Send an email when the form is submitted").check();
    await page.getByLabel("Send To").fill("owner@example.com");
    await page.locator("#wp-sjs-notification-cc").fill("copy@example.com, not-an-email");
    await page.locator("#wp-sjs-notification-bcc").fill("hidden@example.com");
    await page.getByLabel("Reply-To", { exact: true }).selectOption("visitor_email");
    await notificationSubject.fill(`${subject} from {field:visitor_name}`);
    await page.locator("#wp-sjs-notification-html").check();
    await autoReply.getByLabel("Send an auto-reply to the respondent").check();
    await autoReply.getByLabel("Subject").fill(autoReplySubject);
    await autoReply.getByLabel("CC", { exact: true }).fill("team@example.com");
    await autoReply.getByLabel("BCC", { exact: true }).fill("archive@example.com");
    await saveSurvey(page);

    await page.reload();
    await emailTab.click();
    await expect(notificationSubject).toHaveValue(`${subject} from {field:visitor_name}`);
    await expect(autoReply.getByLabel("Subject")).toHaveValue(autoReplySubject);
    await expect(page.locator("#wp-sjs-notification-cc")).toHaveValue("copy@example.com, not-an-email");
    await expect(page.locator("#wp-sjs-notification-bcc")).toHaveValue("hidden@example.com");
    await expect(autoReply.getByLabel("CC", { exact: true })).toHaveValue("team@example.com");
    await expect(autoReply.getByLabel("BCC", { exact: true })).toHaveValue("archive@example.com");
    await expect(page.getByLabel("Reply-To", { exact: true })).toHaveValue("visitor_email");
    await entriesTab.click();
    await expect(page.getByLabel("Save submitted entries in the database")).not.toBeChecked();

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;

    await page.goto(post.link);
    await page.getByLabel("Your name").fill("Jane");
    await page.getByLabel("Your email").fill("jane@example.com");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    const mail = await findMail(page.request, subject);
    expect(mail.Subject).toBe(`${subject} from Jane`);
    expect(mail.To.map(to => to.Address)).toEqual(["owner@example.com"]);
    expect(mail.ReplyTo.map(to => to.Address)).toEqual(["jane@example.com"]);
    expect(mail.Cc.map(cc => cc.Address)).toEqual(["copy@example.com"]);
    expect(mail.Bcc.map(bcc => bcc.Address)).toEqual(["hidden@example.com"]);
    expect(mail.HTML).toMatch(/Your name: Jane<br>\s*Your email: jane@example\.com/);

    const autoReplyMail = await findMail(page.request, autoReplySubject);
    expect(autoReplyMail.To.map(to => to.Address)).toEqual(["jane@example.com"]);
    expect(autoReplyMail.ReplyTo.map(to => to.Address)).toEqual(["owner@example.com"]);
    expect(autoReplyMail.Cc.map(cc => cc.Address)).toEqual(["team@example.com"]);
    expect(autoReplyMail.Bcc.map(bcc => bcc.Address)).toEqual(["archive@example.com"]);
    expect(autoReplyMail.Text).toContain("Thanks for your message.");
    expect(autoReplyMail.Text).toContain("Your name: Jane");

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });

  test("sends the test email to the CC and BCC addresses too", async ({ page }) => {
    name = uniqueName("E2E test email");
    const owner = `owner-${Date.now()}@example.com`;
    await createSurvey(page, name, CONTACT_JSON);

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true }).click();
    await page.getByLabel("Send an email when the form is submitted").check();
    await page.getByLabel("Send To").fill(owner);
    await page.locator("#wp-sjs-notification-cc").fill("{field:visitor_email}, test-copy@example.com");
    await page.locator("#wp-sjs-notification-bcc").fill("test-hidden@example.com");
    await page.getByRole("button", { name: "Send test email" }).click();
    await expect(page.locator("#wp-sjs-test-email-result")).toContainText(`Test email sent to ${owner}, test-copy@example.com, test-hidden@example.com.`);

    let id = null;
    await expect.poll(async () => {
      const response = await page.request.get(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`to:"${owner}"`)}`);
      id = (await response.json()).messages[0]?.ID ?? null;
      return id;
    }, { timeout: 30000 }).not.toBeNull();
    const mail = await (await page.request.get(`${MAILPIT_URL}/api/v1/message/${id}`)).json();
    expect(mail.Cc.map(cc => cc.Address)).toEqual(["test-copy@example.com"]);
    expect(mail.Bcc.map(bcc => bcc.Address)).toEqual(["test-hidden@example.com"]);
  });

  test("attaches uploaded files and deletes them when entries are off", async ({ page }) => {
    name = uniqueName("E2E attachments");
    const subject = `E2E attachments ${Date.now()}`;
    const surveyId = await createSurvey(page, name, { elements: [{ type: "file", name: "document", title: "Your document" }] });

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Entries" }).click();
    await page.getByLabel("Save submitted entries in the database").uncheck();
    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true }).click();
    await page.getByLabel("Send an email when the form is submitted").check();
    await page.getByLabel("Send To").fill("owner@example.com");
    await page.locator("#wp-sjs-notification-subject").fill(subject);
    await page.locator("#wp-sjs-notification-attachments").selectOption("document");
    await saveSurvey(page);

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;

    await page.goto(post.link);
    const uploaded = page.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
    const fileChooser = page.waitForEvent("filechooser");
    await page.locator(".wp-sjs-survey .sd-file__choose-btn").first().click();
    await (await fileChooser).setFiles({ name: "report.txt", mimeType: "text/plain", buffer: Buffer.from("E2E attachment") });
    const fileKey = (await (await uploaded).json())["report.txt"].file;
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    const mail = await findMail(page.request, subject);
    expect(mail.Attachments.map(attachment => attachment.FileName)).toEqual(["report.txt"]);
    expect((await page.request.get(`/wp-content/uploads/surveyjs/${fileKey}`, { maxRedirects: 0 })).status()).not.toBe(200);
  });
});
