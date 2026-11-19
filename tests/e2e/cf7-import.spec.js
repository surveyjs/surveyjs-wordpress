const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, deleteSurveys, setEditorJson, saveSurvey, currentUser, lintIssues } = require("./helpers");

const MAILPIT_URL = process.env.MAILPIT_URL || "http://localhost:8025";

const FORM = `<h3>Before you start</h3>
<label> Your name [text* your-name minlength:2] </label>
<label> Topic [select* recipient "Sales|sales@example.com" "Support|support@example.com"] </label>
<label> Message [textarea message 40x4] </label>
[hidden source "e2e"]
<p>[quiz robot "2+2=?|4"]</p>
<p>[acceptance terms default:on] I accept the terms. [/acceptance]</p>
[submit "Send"]`;

async function findMail(request, subject) {
  let id = null;
  await expect.poll(async () => {
    const response = await request.get(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`subject:"${subject}"`)}`);
    id = (await response.json()).messages[0]?.ID ?? null;
    return id;
  }, { timeout: 30000 }).not.toBeNull();
  return (await request.get(`${MAILPIT_URL}/api/v1/message/${id}`)).json();
}

async function editorJson(page) {
  await expect(page.locator(".monaco-editor")).toBeVisible();
  return JSON.parse(await page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue()));
}

async function importForm(page, cf7Id) {
  await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=cf7");
  await expect(page.getByText("Contact Form 7 does not keep entries")).toBeVisible();
  await expect(page.getByLabel("Import entries")).toHaveCount(0);
  for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
    await checkbox.setChecked(Number(await checkbox.getAttribute("value")) === cf7Id);
  }
  await page.getByRole("button", { name: "Import" }).click();
}

test.describe("import from Contact Form 7", () => {
  let name;
  let cf7Id;
  let postId;
  let restNonce;

  test.beforeEach(async ({ page }) => {
    await page.goto("/wp-admin/");
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
  });

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/index.php?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    if (cf7Id) {
      await page.request.delete(`/index.php?rest_route=/contact-form-7/v1/contact-forms/${cf7Id}`, { headers: { "X-WP-Nonce": restNonce } });
    }
    if (name) {
      await deleteSurveys(page, name);
    }
    name = postId = cf7Id = null;
  });

  test("imports text between fields, pipes, quiz and hidden fields, and re-import updates the form", async ({ page }) => {
    name = uniqueName("E2E CF7");
    const created = await page.request.post("/index.php?rest_route=/contact-form-7/v1/contact-forms", {
      headers: { "X-WP-Nonce": restNonce },
      data: {
        context: "save",
        title: name,
        locale: "en_US",
        form: FORM,
        mail: {
          recipient: "[recipient]",
          subject: `${name} from [your-name]`,
          sender: "[_site_title] <[_site_admin_email]>",
          body: "Topic: [_raw_recipient]\nMessage: [message]\nTerms: [terms]",
          additional_headers: "",
          attachments: "",
          use_html: false,
          exclude_blank: false
        }
      }
    });
    test.skip(!created.ok(), "Contact Form 7 is not active");
    cf7Id = (await created.json()).id;

    await importForm(page, cf7Id);
    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("imported");
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    const json = await editorJson(page);
    expect(await lintIssues(page, json)).toEqual([]);
    expect(json.elements.map(element => element.type)).toEqual(["html", "text", "dropdown", "comment", "text", "text", "boolean"]);
    expect(json.elements[0].html).toContain("Before you start");
    expect(json.elements[1].validators).toEqual([{ type: "text", minLength: 2 }]);
    expect(json.elements[2].choices).toEqual([
      { value: "sales@example.com", text: "Sales" },
      { value: "support@example.com", text: "Support" }
    ]);
    expect(json.elements[3].rows).toBe(4);
    expect(json.elements[4]).toMatchObject({ name: "source", visible: false, defaultValue: "e2e" });
    expect(json.elements[5]).toMatchObject({ name: "robot", title: "2+2=?", isRequired: true });
    expect(json.elements[6]).toEqual({
      type: "boolean",
      displayMode: "checkbox",
      name: "terms",
      title: "I accept the terms.",
      isRequired: true,
      defaultValue: true,
      validators: [{ type: "expression", expression: "{terms} = true", text: expect.any(String) }]
    });

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/index.php?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;
    await page.goto(post.link);
    await expect(page.getByText("Before you start")).toBeVisible();
    await page.getByLabel("Your name").fill("Jane");
    const topic = page.locator(".sd-question", { hasText: "Topic" }).locator(".sd-dropdown");
    // The list opens as a fixed popup: with the dropdown at the viewport bottom, clicking an option scrolls the page, and the list moves or closes.
    await topic.evaluate(element => element.scrollIntoView({ block: "center" }));
    await topic.click();
    await page.getByRole("option", { name: "Sales" }).click();
    await page.getByLabel("2+2=?").fill("5");
    await page.getByRole("button", { name: "Send" }).click();
    await expect(page.getByText("The answer to the quiz is incorrect.")).toBeVisible();
    await page.getByLabel("2+2=?").fill("4");
    await expect(page.getByRole("checkbox", { name: "I accept the terms." })).toBeChecked();
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Send" }).click();
    expect((await saved).ok()).toBe(true);
    const mail = await findMail(page.request, `${name} from Jane`);
    expect(mail.To.map(to => to.Address)).toEqual(["sales@example.com"]);
    expect(mail.Text).toContain("Topic: Sales");
    expect(mail.Text).toContain("Terms: Yes");

    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}`);
    await setEditorJson(page, { elements: [{ type: "text", name: "edited" }] });
    await saveSurvey(page);
    const updated = await page.request.post(`/index.php?rest_route=/contact-form-7/v1/contact-forms/${cf7Id}`, {
      headers: { "X-WP-Nonce": restNonce },
      data: { context: "save", form: FORM.replace("Your name", "Full name") }
    });
    expect(updated.ok()).toBe(true);

    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=cf7");
    await expect(page.locator("p", { has: page.locator(`input[value="${cf7Id}"]`) })).toContainText("imported before, will be updated");
    await importForm(page, cf7Id);
    await expect(result).toContainText("updated");
    await result.getByRole("link", { name: "Edit" }).click();
    expect(Number(new URL(page.url()).searchParams.get("id"))).toBe(surveyId);
    const reimported = await editorJson(page);
    expect(reimported.elements.map(element => element.name)).toEqual(["html1", "your-name", "recipient", "message", "source", "robot", "terms"]);
    expect(reimported.elements[1].title).toBe("Full name");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list tr")).toHaveCount(1);
  });

  test("imports fields on one line, default:get and default:user_* values, and CC and BCC of both emails", async ({ page }) => {
    name = uniqueName("E2E CF7 defaults");
    const created = await page.request.post("/?rest_route=/contact-form-7/v1/contact-forms", {
      headers: { "X-WP-Nonce": restNonce },
      data: {
        context: "save",
        title: name,
        locale: "en_US",
        form: `<label> First name [text first-name] </label> <label> Last name [text last-name] </label>
<div class="row"><div class="col-6">[email your-email default:user_email]</div><div class="col-6">[tel phone]</div></div>
<label> Reference [text ref default:get default:user_login] </label>
[hidden utm default:get "direct"]
[submit "Send"]`,
        mail: {
          recipient: "owner@example.com",
          subject: `${name} [first-name]`,
          sender: "[_site_title] <[_site_admin_email]>",
          body: "Email: [your-email]\nRef: [ref]\nUTM: [utm]",
          additional_headers: "Cc: boss@example.com\nBcc: audit@example.com, [your-email]",
          attachments: "",
          use_html: false,
          exclude_blank: false
        },
        mail_2: {
          active: true,
          recipient: "[your-email]",
          subject: `${name} auto-reply`,
          sender: "[_site_title] <[_site_admin_email]>",
          body: "Thanks",
          additional_headers: "Cc: [_site_admin_email]\nBcc: archive@example.com",
          attachments: "",
          use_html: false,
          exclude_blank: false
        }
      }
    });
    test.skip(!created.ok(), "Contact Form 7 is not active");
    cf7Id = (await created.json()).id;

    await importForm(page, cf7Id);
    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("imported");
    await expect(result).not.toContainText("CC");
    await expect(result).not.toContainText("The default value source");
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    const json = await editorJson(page);
    expect(await lintIssues(page, json)).toEqual([]);
    expect(json.elements.map(element => [element.name, element.startWithNewLine])).toEqual([
      ["first-name", undefined],
      ["last-name", false],
      ["your-email", undefined],
      ["phone", false],
      ["ref", undefined],
      ["utm", undefined]
    ]);
    expect(json.elements[2].defaultValueExpression).toBe("{#wpUser.email}");
    expect(json.elements[4].defaultValueExpression).toBe("iif({#urlParams.ref} notempty, {#urlParams.ref}, {#wpUser.login})");
    expect(json.elements[5]).toMatchObject({ defaultValue: "direct", defaultValueExpression: "{#urlParams.utm}" });
    await expect(page.locator("#wp-sjs-notification-cc")).toHaveValue("boss@example.com");
    await expect(page.locator("#wp-sjs-notification-bcc")).toHaveValue("audit@example.com, {field:your-email}");
    await expect(page.locator("#wp-sjs-auto-reply-cc")).toHaveValue("{admin_email}");
    await expect(page.locator("#wp-sjs-auto-reply-bcc")).toHaveValue("archive@example.com");

    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;
    const url = new URL(post.link);
    url.searchParams.set("utm", "0042");
    const user = await currentUser(page);
    await page.goto(url.toString());
    await expect(page.getByLabel("Email")).toHaveValue(user.email);
    await expect(page.getByLabel("Reference")).toHaveValue(user.login);
    await page.getByLabel("First name").fill("Withparams");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Send" }).click();
    expect((await saved).ok()).toBe(true);
    const mail = await findMail(page.request, `${name} Withparams`);
    expect(mail.Cc.map(cc => cc.Address)).toEqual(["boss@example.com"]);
    expect(mail.Bcc.map(bcc => bcc.Address)).toEqual(["audit@example.com", user.email]);
    const autoReply = await findMail(page.request, `${name} auto-reply`);
    expect(autoReply.To.map(to => to.Address)).toEqual([user.email]);
    expect(autoReply.Bcc.map(bcc => bcc.Address)).toEqual(["archive@example.com"]);
    expect(mail.Text).toContain(`Email: ${user.email}`);
    expect(mail.Text).toContain(`Ref: ${user.login}`);
    expect(mail.Text).toContain("UTM: 0042");

    url.searchParams.delete("utm");
    url.searchParams.set("ref", "R-1");
    await page.goto(url.toString());
    await expect(page.getByLabel("Reference")).toHaveValue("R-1");
    await page.getByLabel("First name").fill("Withoututm");
    await page.getByRole("button", { name: "Send" }).click();
    const fallback = await findMail(page.request, `${name} Withoututm`);
    expect(fallback.Text).toContain("UTM: direct");
  });

  test("the Getting Started page offers the Contact Form 7 import", async ({ page }) => {
    await page.goto("/wp-admin/admin.php?page=surveyjs-getting-started");
    const card = page.locator(".wp-sjs-welcome-import", { has: page.getByRole("heading", { name: "Import from Contact Form 7" }) });
    test.skip(!(await card.count()), "Contact Form 7 has no forms");
    await expect(card.getByRole("heading", { name: "Import from Contact Form 7" })).toBeVisible();
    await expect(card.getByText("Importing a form again updates the SurveyJS form")).toBeVisible();
    await expect(card.getByRole("button", { name: "Import" })).toBeVisible();
  });
});
