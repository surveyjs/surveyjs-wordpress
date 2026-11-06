const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, deleteSurveys, setEditorJson, saveSurvey } = require("./helpers");

const MAILPIT_URL = process.env.MAILPIT_URL || "http://localhost:8025";

async function findMail(request, subject) {
  let id = null;
  await expect.poll(async () => {
    const response = await request.get(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`subject:"${subject}"`)}`);
    id = (await response.json()).messages[0]?.ID ?? null;
    return id;
  }, { timeout: 30000 }).not.toBeNull();
  return (await request.get(`${MAILPIT_URL}/api/v1/message/${id}`)).json();
}

function options(items) {
  return items.map(([label, value], order) => ({ label, value, calc: "", selected: 0, order }));
}

function ninjaForm(title) {
  const email = { active: 1, email_format: "html", cc: "", bcc: "", from_name: "", from_address: "" };
  return {
    settings: {
      title,
      formContentData: [
        { order: 0, cells: [{ order: 0, fields: ["first_name"], width: "50" }, { order: 1, fields: ["last_name"], width: "50" }] },
        "email", "topic", "products", "newsletter", "message", "submit"
      ]
    },
    fields: [
      { type: "firstname", key: "first_name", label: "First Name", required: 1, order: 1 },
      { type: "lastname", key: "last_name", label: "Last Name", required: 1, order: 2 },
      { type: "email", key: "email", label: "Email", required: 1, order: 3 },
      { type: "listselect", key: "topic", label: "Topic", order: 4, options: options([["- Select -", ""], ["Support", "support"], ["Billing", "billing"]]) },
      { type: "listcheckbox", key: "products", label: "Products", order: 5, options: options([["Form Library", "library"], ["Survey Creator", "creator"]]) },
      { type: "checkbox", key: "newsletter", label: "Subscribe to the newsletter", order: 6 },
      { type: "textarea", key: "message", label: "Message", order: 7, input_limit: 200, input_limit_type: "characters" },
      { type: "submit", key: "submit", label: "Send", order: 8 }
    ],
    actions: [
      { type: "save", label: "Record Submission", active: 1 },
      { ...email, type: "email", label: "Admin", to: "{system:admin_email}", reply_to: "{field:email}", email_subject: "Ninja: {form:title}", email_message: "<p>{all_fields_table}</p>" },
      { ...email, type: "email", label: "Copy to visitor", to: "{field:email}", email_subject: "Thanks {field:first_name}", email_message: "<p>We got it.</p>" },
      { type: "successmessage", label: "Success Message", active: 1, success_msg: "<p>Thanks, {field:first_name}!</p>" }
    ]
  };
}

test.describe("import from Ninja Forms", () => {
  let name;
  let ninjaId;
  let postIds = [];
  let restNonce;

  async function runAbility(page, ability, input) {
    const response = await page.request.post(`/?rest_route=/wp-abilities/v1/abilities/${ability}/run`, {
      headers: { "X-WP-Nonce": restNonce },
      data: { input }
    });
    return { status: response.status(), body: await response.json() };
  }

  async function publish(page, title, content) {
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title, status: "publish", content }
    })).json();
    postIds.push(post.id);
    return post.link;
  }

  test.afterEach(async ({ page }) => {
    for (const id of postIds) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${id}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    if (ninjaId) {
      await runAbility(page, "ninjaforms/delete-form", { form_id: ninjaId });
    }
    await deleteSurveys(page, name);
    postIds = [];
    ninjaId = null;
  });

  async function createNinjaForm(page) {
    test.slow();
    await page.goto("/wp-admin/");
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    name = uniqueName("E2E Ninja Forms");
    const created = await runAbility(page, "ninjaforms/import-form", { file_content: JSON.stringify(ninjaForm(name)) });
    test.skip(created.status === 404, "Ninja Forms is not active");
    expect(created.body.success).toBe(true);
    ninjaId = Number(created.body.form_id);
    const form = await runAbility(page, "ninjaforms/get-form", { form_id: ninjaId, include_actions: false, include_calculations: false });
    return Object.fromEntries(form.body.form.fields.map(field => [field.key, `#nf-field-${field.id}`]));
  }

  async function submitNinjaForm(page, fields) {
    await page.goto(await publish(page, `${name} (Ninja Forms)`, `[ninja_form id="${ninjaId}"]`));
    await page.locator(fields.first_name).fill("Jane");
    await page.locator(fields.last_name).fill("Roe");
    await page.locator(fields.email).fill("jane@example.com");
    await page.locator(fields.topic).selectOption("billing");
    await page.locator(`${fields.products}-wrap`).getByLabel("Survey Creator").check();
    await page.locator(fields.newsletter).check();
    await page.locator(fields.message).fill("Please resend the invoice.");
    await page.locator(fields.submit).click();
    await expect(page.locator(".nf-response-msg")).toContainText("Thanks, Jane!");
  }

  async function importNinjaForm(page, withEntries) {
    for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
      await checkbox.setChecked(Number(await checkbox.getAttribute("value")) === ninjaId);
    }
    await page.getByLabel("Import entries").setChecked(withEntries);
    await page.getByRole("button", { name: "Import" }).click();
  }

  async function editorJson(page) {
    await expect(page.locator(".monaco-editor")).toBeVisible();
    return JSON.parse(await page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue()));
  }

  test("imports a Ninja Forms form with its entries, emails and success message", async ({ page }) => {
    const fields = await createNinjaForm(page);
    await submitNinjaForm(page, fields);

    await page.goto("/wp-admin/admin.php?page=surveyjs-main-menu");
    await page.locator("#wpbody-content").getByRole("link", { name: "Import from Ninja Forms" }).click();
    await expect(page).toHaveURL(/page=surveyjs_import&source=ninja-forms/);
    await importNinjaForm(page, true);

    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("imported");
    await expect(result).toContainText("Entries: 1 added, 0 updated.");
    await expect(result).toContainText('The email action "Copy to visitor" was imported as the auto-reply.');
    const editUrl = await result.getByRole("link", { name: "Edit" }).getAttribute("href");
    await result.getByRole("link", { name: "View entries" }).click();
    await expect(page.locator("#the-list tr")).toHaveCount(1);
    await expect(page.locator("#the-list")).toContainText("Jane");
    await expect(page.locator("#the-list")).toContainText("Survey Creator");
    await expect(page.locator("#the-list td.column-question_5")).toHaveText("Yes");

    await page.goto(editUrl);
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    const json = await editorJson(page);
    expect(json.elements.map(element => element.name)).toEqual(["first_name", "last_name", "email", "topic", "products", "subscribe_to_the_newsletter", "message"]);
    expect(json.elements[1].startWithNewLine).toBe(false);
    expect(json.elements[3]).toMatchObject({ type: "dropdown", placeholder: "- Select -", choices: [{ value: "support", text: "Support" }, { value: "billing", text: "Billing" }] });
    expect(json.elements[4].type).toBe("checkbox");
    expect(json.elements[5]).toEqual({ type: "boolean", displayMode: "checkbox", name: "subscribe_to_the_newsletter", title: "Subscribe to the newsletter" });
    expect(json.elements[6]).toMatchObject({ type: "comment", maxLength: 200 });
    expect(json.completeText).toBe("Send");
    expect(json.completedHtml).toBe("<p>Thanks, {first_name}!</p>");

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true }).click();
    await expect(page.getByLabel("Send an email when the form is submitted")).toBeChecked();
    await expect(page.getByLabel("Send To")).toHaveValue("{admin_email}");
    await expect(page.getByLabel("Reply-To", { exact: true })).toHaveValue("email");

    await page.goto(await publish(page, name, `[surveyjs id="${surveyId}"]`));
    await page.getByLabel("First Name").fill("Ann");
    await page.getByLabel("Last Name").fill("Lee");
    await page.getByLabel("Email").fill("ann@example.com");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Send" }).click();
    expect((await saved).ok()).toBe(true);
    await expect(page.locator(".sd-completedpage")).toContainText("Thanks, Ann!");

    const mail = await findMail(page.request, `Ninja: ${name}`);
    expect(mail.ReplyTo.map(to => to.Address)).toEqual(["ann@example.com"]);
    expect(mail.HTML).toContain("First Name: Ann");
    const reply = await findMail(page.request, "Thanks Ann");
    expect(reply.To.map(to => to.Address)).toEqual(["ann@example.com"]);
  });

  test("re-import updates the imported form and its entries instead of adding copies", async ({ page }) => {
    const fields = await createNinjaForm(page);
    await submitNinjaForm(page, fields);

    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=ninja-forms");
    await importNinjaForm(page, true);
    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("Entries: 1 added, 0 updated.");
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    await editorJson(page);
    await setEditorJson(page, { elements: [{ type: "text", name: "edited" }] });
    await saveSurvey(page);

    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=ninja-forms");
    await expect(page.locator("p", { has: page.locator(`input[value="${ninjaId}"]`) })).toContainText("imported before, will be updated");
    await importNinjaForm(page, true);
    await expect(result).toContainText("updated");
    await expect(result).toContainText("Entries: 0 added, 1 updated.");
    await result.getByRole("link", { name: "Edit" }).click();
    expect(Number(new URL(page.url()).searchParams.get("id"))).toBe(surveyId);
    expect((await editorJson(page)).elements.map(element => element.name)).toContain("first_name");

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list tr")).toHaveCount(1);
    await page.goto(listUrl(name));
    await expect(page.locator("#the-list tr")).toHaveCount(1);
  });
});
