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

const rules = (required = false) => ({ required: { value: required, message: "This field is required", global: true } });

function field(element, name, settings = {}, attributes = {}) {
  return {
    element,
    attributes: { name, value: "", ...attributes },
    settings: { label: "", validation_rules: rules(), conditional_logics: [], ...settings },
    uniqElKey: `el_${name}`
  };
}

function nameField() {
  const part = (name, label, visible) => ({
    element: "input_text",
    attributes: { type: "text", name, value: "", placeholder: label },
    settings: { label, visible, validation_rules: rules(visible), conditional_logics: [] }
  });
  return {
    element: "input_name",
    attributes: { name: "names", "data-type": "name-element" },
    settings: { admin_field_label: "Name", conditional_logics: [] },
    fields: { first_name: part("first_name", "First Name", true), middle_name: part("middle_name", "Middle Name", false), last_name: part("last_name", "Last Name", true) },
    uniqElKey: "el_names"
  };
}

const options = items => Object.entries(items).map(([value, label]) => ({ label, value, calc_value: "", image: "" }));

const FORM_FIELDS = {
  fields: [
    nameField(),
    {
      element: "container",
      attributes: {},
      settings: { conditional_logics: [] },
      columns: [
        { width: 50, fields: [field("input_email", "email", { label: "Email", validation_rules: { ...rules(true), email: { value: true, message: "Invalid email" } } }, { type: "email" })] },
        { width: 50, fields: [field("input_text", "company", { label: "Company" }, { type: "text" })] }
      ],
      uniqElKey: "el_container"
    },
    { element: "form_step", attributes: { id: "", class: "" }, settings: { next_btn: { type: "default", text: "Continue", img_url: "" }, prev_btn: { type: "default", text: "Back", img_url: "" } }, uniqElKey: "el_step" },
    field("input_radio", "ticket_type", { label: "Ticket type", advanced_options: options({ standard: "Standard", student: "Student" }), validation_rules: rules(true) }, { type: "radio" }),
    field("input_text", "student_id", {
      label: "Student ID",
      conditional_logics: { type: "all", status: true, conditions: [{ field: "ticket_type", value: "student", operator: "=" }] }
    }, { type: "text" }),
    field("input_checkbox", "sessions", { label: "Sessions", advanced_options: options({ keynote: "Keynote", workshop: "Workshop" }) }, { type: "checkbox", value: [] }),
    { ...field("ratings", "excitement", { label: "Excitement", show_text: "no" }, { value: 0 }), options: { 1: "Nice", 2: "Good", 3: "Very Good", 4: "Awesome", 5: "Amazing" } },
    field("terms_and_condition", "terms", { admin_field_label: "Terms", tnc_html: "I accept the event rules.", has_checkbox: true, validation_rules: rules(true) }, { type: "checkbox", value: false }),
    field("input_text", "badge_name", {
      label: "Badge name",
      conditional_logics: { type: "all", status: true, conditions: [{ field: "terms", value: "on", operator: "=" }] }
    }, { type: "text" })
  ],
  submitButton: { element: "button", attributes: { type: "submit", class: "" }, settings: { button_ui: { type: "default", text: "Register", img_url: "" } } },
  stepsWrapper: {
    stepStart: { element: "step_start", attributes: { id: "", class: "" }, settings: { progress_indicator: "progress-bar", step_titles: ["Your details", "Event options"] } },
    stepEnd: { element: "step_end", attributes: { id: "", class: "" }, settings: { prev_btn: { type: "default", text: "Back", img_url: "" } } }
  }
};

const NO_CONDITIONS = { status: false, type: "all", conditions: [{ field: null, operator: "=", value: null }] };

test.describe("import from Fluent Forms", () => {
  let name;
  let fluentId;
  let postId;
  let restNonce;

  async function rest(page, method, route, form) {
    const response = await page.request.fetch(`/?rest_route=/fluentform/v1/${route}`, { method, headers: { "X-WP-Nonce": restNonce }, form });
    expect(response.ok(), await response.text()).toBe(true);
    return response.json();
  }

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    if (fluentId) {
      await rest(page, "DELETE", `forms/${fluentId}`);
    }
    await deleteSurveys(page, name);
    postId = null;
    fluentId = null;
  });

  async function createFluentForm(page) {
    test.slow();
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    name = uniqueName("E2E Fluent Forms");
    const created = await page.request.post("/?rest_route=/fluentform/v1/forms", { headers: { "X-WP-Nonce": restNonce }, form: { predefined: "blank_form", type: "form" } });
    test.skip(created.status() === 404, "Fluent Forms is not active");
    expect(created.ok(), await created.text()).toBe(true);
    fluentId = (await created.json()).formId;
    await rest(page, "POST", `forms/${fluentId}`, { title: name, status: "published", formFields: JSON.stringify(FORM_FIELDS) });

    const submitted = await page.request.post("/wp-admin/admin-ajax.php", {
      form: {
        action: "fluentform_submit",
        form_id: fluentId,
        data: new URLSearchParams([
          ["names[first_name]", "Ann"], ["names[last_name]", "Lee"], ["email", "ann@example.com"],
          ["ticket_type", "student"], ["student_id", "S-42"], ["sessions[]", "keynote"], ["sessions[]", "workshop"], ["excitement", "4"], ["terms", "on"], ["badge_name", "Annie"]
        ]).toString()
      }
    });
    expect(submitted.ok(), await submitted.text()).toBe(true);

    const [formSettings] = await rest(page, "GET", `settings/${fluentId}&meta_key=formSettings`);
    formSettings.value.confirmation.messageToShow = "<p>Thanks, {inputs.names.first_name}! See you there.</p>";
    await rest(page, "POST", `settings/${fluentId}`, { meta_key: "formSettings", meta_id: formSettings.id, value: JSON.stringify(formSettings.value) });

    const [notification] = await rest(page, "GET", `settings/${fluentId}&meta_key=notifications&is_multiple=1`);
    await rest(page, "POST", `settings/${fluentId}`, {
      meta_key: "notifications",
      meta_id: notification.id,
      value: JSON.stringify({
        ...notification.value,
        enabled: true,
        sendTo: { type: "email", email: "{wp.admin_email}", field: "", routing: [] },
        replyTo: "{inputs.email}",
        subject: "Registration: {form_title}",
        message: "<p>{inputs.names} registered.</p><p>{all_data}</p>",
        conditionals: NO_CONDITIONS
      })
    });
    await rest(page, "POST", `settings/${fluentId}`, {
      meta_key: "notifications",
      value: JSON.stringify({
        name: "Attendee confirmation",
        enabled: true,
        sendTo: { type: "field", email: "", field: "email", routing: [] },
        fromName: "",
        fromEmail: "",
        replyTo: "",
        bcc: "",
        subject: "You are registered: {form_title}",
        message: "<p>Hi {inputs.names.first_name}, see you there.</p>",
        conditionals: NO_CONDITIONS
      })
    });
  }

  async function importFluentForm(page, withEntries = true) {
    for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
      await checkbox.setChecked(Number(await checkbox.getAttribute("value")) === fluentId);
    }
    await page.getByLabel("Import entries").setChecked(withEntries);
    await page.getByRole("button", { name: "Import" }).click();
  }

  async function editorJson(page) {
    await expect(page.locator(".monaco-editor")).toBeVisible();
    return JSON.parse(await page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue()));
  }

  test("imports a Fluent Forms form with its steps, logic, emails and entries", async ({ page }) => {
    await createFluentForm(page);

    await page.goto("/wp-admin/admin.php?page=surveyjs-main-menu");
    await page.locator("#wpbody-content").getByRole("link", { name: "Import from Fluent Forms" }).click();
    await expect(page).toHaveURL(/page=surveyjs_import&source=fluentform/);
    await importFluentForm(page);

    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("imported");
    await expect(result).toContainText("Entries: 1 added, 0 updated.");
    await expect(result).toContainText('The notification "Attendee confirmation" was imported as the auto-reply.');
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    const json = await editorJson(page);
    expect(json.pages.map(p => p.title)).toEqual(["Your details", "Event options"]);
    expect(json.pages[0].elements.map(e => e.name)).toEqual(["name_first", "name_last", "email", "company"]);
    expect(json.pages[0].elements[1].startWithNewLine).toBe(false);
    expect(json.pages[0].elements[3].startWithNewLine).toBe(false);
    expect(json.pages[1].elements.map(e => [e.name, e.type])).toEqual([["ticket_type", "radiogroup"], ["student_id", "text"], ["sessions", "checkbox"], ["excitement", "rating"], ["terms", "boolean"], ["badge_name", "text"]]);
    expect(json.pages[1].elements[4]).toEqual({
      type: "boolean",
      displayMode: "checkbox",
      name: "terms",
      title: "I accept the event rules.",
      isRequired: true,
      validators: [{ type: "expression", expression: "{terms} = true", text: "Please give your consent to submit the form." }]
    });
    expect(json.pages[1].elements[5].visibleIf).toBe("{terms} = true");
    expect(json.pages[1].elements[1].visibleIf).toBe("{ticket_type} = 'student'");
    expect(json).toMatchObject({ completeText: "Register", pageNextText: "Continue", pagePrevText: "Back", showProgressBar: true });
    expect(json.completedHtml).toContain("Thanks, {name_first}!");

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email Notification" }).click();
    await expect(page.getByLabel("Send an email when the form is submitted")).toBeChecked();
    await expect(page.getByLabel("Send To")).toHaveValue("{admin_email}");
    await expect(page.getByLabel("Reply-To", { exact: true })).toHaveValue("email");

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list tr", { hasText: "ann@example.com" })).toBeVisible();
    await expect(page.locator("#the-list td[data-colname=\"I accept the event rules.\"]")).toHaveText("Yes");

    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;

    await page.goto(post.link);
    await page.getByLabel("First Name").fill("Jane");
    await page.getByLabel("Last Name").fill("Roe");
    await page.getByLabel("Email").fill("jane@example.com");
    await page.getByRole("button", { name: "Continue" }).click();
    await expect(page.getByLabel("Student ID")).toBeHidden();
    await page.getByText("Student", { exact: true }).click();
    await page.getByLabel("Student ID").fill("S-7");
    await expect(page.getByLabel("Badge name")).toBeHidden();
    await page.getByText("I accept the event rules.").click();
    await page.getByLabel("Badge name").fill("Janie");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Register" }).click();
    expect((await saved).ok()).toBe(true);
    await expect(page.locator(".sd-completedpage")).toContainText("Thanks, Jane!");

    const mail = await findMail(page.request, `Registration: ${name}`);
    expect(mail.ReplyTo.map(to => to.Address)).toEqual(["jane@example.com"]);
    expect(mail.HTML).toContain("Jane Roe registered.");
    const autoReply = await findMail(page.request, `You are registered: ${name}`);
    expect(autoReply.To.map(to => to.Address)).toEqual(["jane@example.com"]);
  });

  test("re-import updates the imported form and its entries instead of adding copies", async ({ page }) => {
    await createFluentForm(page);
    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=fluentform");
    await importFluentForm(page);
    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("Entries: 1 added, 0 updated.");
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    await editorJson(page);
    await setEditorJson(page, { elements: [{ type: "text", name: "edited" }] });
    await saveSurvey(page);

    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=fluentform");
    await expect(page.locator("p", { has: page.locator(`input[value="${fluentId}"]`) })).toContainText("imported before, will be updated");
    await importFluentForm(page);
    await expect(result).toContainText("updated");
    await expect(result).toContainText("Entries: 0 added, 1 updated.");
    await result.getByRole("link", { name: "Edit" }).click();
    expect(Number(new URL(page.url()).searchParams.get("id"))).toBe(surveyId);
    expect((await editorJson(page)).pages[1].elements.map(e => e.name)).toEqual(["ticket_type", "student_id", "sessions", "excitement", "terms", "badge_name"]);

    await page.goto(`/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}`);
    await expect(page.locator("#the-list tr", { hasText: "ann@example.com" })).toHaveCount(1);
    await page.goto(listUrl(name));
    await expect(page.locator("#the-list tr")).toHaveCount(1);
  });
});
