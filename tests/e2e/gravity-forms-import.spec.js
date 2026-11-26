const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, deleteSurveys, lintIssues } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const IMPORT_URL = "/wp-admin/admin.php?page=surveyjs_import&source=gravityforms";
const EXPORT_FILE = path.join(__dirname, "fixtures/gravity-forms-export.json");
const EVENT_FORM = "E2E GF Event Registration";
const CONTACT_FORM = "E2E GF Simple Contact Form";

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: ["pipe", "pipe", "ignore"] }).toString();
}

function fixture(...args) {
  const output = docker(["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], fs.readFileSync(path.join(__dirname, "fixtures/gravity-forms.php")));
  return JSON.parse(output.trim().split("\n").pop());
}

function findElement(elements, name) {
  for (const element of elements) {
    if (element.name === name) {
      return element;
    }
    const nested = findElement(element.elements || [], name);
    if (nested) {
      return nested;
    }
  }
  return null;
}

async function uploadExport(page, file = EXPORT_FILE) {
  await page.goto(IMPORT_URL);
  await page.setInputFiles("#wp-sjs-import-file", file);
  await page.getByRole("button", { name: "Upload and import" }).click();
  await expect(page).toHaveURL(/imported=1/);
}

test.describe("import from Gravity Forms", () => {
  let tableForms = [];
  let names = [];
  let postId;

  test.afterEach(async ({ page }) => {
    if (postId) {
      const restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    if (tableForms.length) {
      fixture("cleanup", ...tableForms);
    }
    for (const name of names) {
      await deleteSurveys(page, name);
    }
    tableForms = [];
    names = [];
    postId = null;
  });

  test("imports the forms of an uploaded export file without Gravity Forms", async ({ page }) => {
    test.slow();
    names = [EVENT_FORM, CONTACT_FORM];
    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=gravityforms");
    await expect(page.getByText("does not need to be installed on this site")).toBeVisible();

    await uploadExport(page);
    const event = page.locator(".wrap li", { hasText: EVENT_FORM });
    await expect(event).toContainText("imported");
    await expect(event).toContainText("Not imported (no equivalent field): Ticket price (product)");
    await expect(event).toContainText('The notification "User Notification" was imported as the auto-reply.');
    await expect(event).toContainText('The confirmation "Thank-you page" was not imported');
    await expect(event).toContainText('The URL parameter "utm_source" of "Source" was not imported.');
    await expect(event).toContainText("Custom CSS classes were not imported: badge-field.");
    await expect(page.locator(".wrap li", { hasText: CONTACT_FORM })).toContainText("imported");

    const survey = fixture("survey", EVENT_FORM);
    const { json, settings } = survey;
    expect(json.pages.map(page => page.navigationTitle)).toEqual(["About you", "Event options", "Uploads"]);
    expect(json.pages.map(page => page.title)).toEqual([undefined, undefined, undefined]);
    expect(json.questionDescriptionLocation).toBe("underInput");
    expect(json.pages[0].elements.map(element => element.name)).toEqual(["your_name", "email", "phone", "address", "ticket_type", "student_id"]);
    const yourName = findElement(json.pages, "your_name");
    expect(yourName).toMatchObject({ type: "panel", title: "Your Name", description: "As on your ID.", isRequired: true, questionTitleLocation: "bottom" });
    expect(yourName.elements).toEqual([
      { type: "text", name: "your_name_first", title: "First name", isRequired: true },
      { type: "text", name: "your_name_last", title: "Last name", isRequired: true, startWithNewLine: false }
    ]);
    const address = findElement(json.pages, "address");
    expect(address).toMatchObject({ type: "panel", title: "Address" });
    expect(address.isRequired).toBeUndefined();
    expect(address.elements.map(({ name, startWithNewLine }) => [name, startWithNewLine])).toEqual([
      ["address_street", undefined], ["address_city", undefined], ["address_state", false], ["address_zip", undefined], ["address_country", false]
    ]);
    const country = findElement(json.pages, "address_country");
    expect(country).toMatchObject({ type: "dropdown", title: "Country", defaultValue: "DE" });
    expect(country.choices).toContainEqual({ value: "DE", text: "Germany" });
    expect(country.choices.length).toBeGreaterThan(200);
    expect(findElement(json.pages, "phone")).toMatchObject({ startWithNewLine: false, width: "50%", minWidth: "auto", inputType: "tel" });
    expect(findElement(json.pages, "student_id").visibleIf).toBe("{ticket_type} = 'student'");
    expect(findElement(json.pages, "badge_name")).toMatchObject({ visibleIf: "substring({#your_name_first}, 0, 1) = 'A'", defaultValueExpression: "{#wpUser.display_name}" });
    expect(json.calculatedValues).toContainEqual({ name: "wpUser" });
    expect(findElement(json.pages, "workshops")).toMatchObject({ type: "panel", title: "Workshops", visibleIf: "!({ticket_type} = 'standard')" });
    expect(findElement(json.pages, "workshops_2")).toMatchObject({ type: "checkbox", showSelectAllItem: true, minSelectedChoices: 1, maxSelectedChoices: 2 });
    expect(findElement(json.pages, "rate_the_topics").type).toBe("matrix");
    expect(findElement(json.pages, "dietary_needs").columns.map(column => column.name)).toEqual(["person", "need"]);
    expect(findElement(json.pages, "event_rules")).toMatchObject({ type: "boolean", title: "I agree to the event rules.", isRequired: true });
    expect(json.pages[2].visibleIf).toBe("{guests} < 5");
    expect(json).toMatchObject({ completeText: "Register", pageNextText: "Continue", pagePrevText: "Back", showProgressBar: true, progressBarShowNavigationText: true });
    expect(json.completedHtml).toContain("Thanks {your_name_first}!");
    expect(json.navigateToUrlOnCondition).toEqual([{ expression: "({ticket_type} = 'vip')", url: "https://example.com/vip?ticket={ticket_type}" }]);
    expect(settings).toMatchObject({
      notificationEnabled: true,
      notificationTo: "{admin_email}",
      notificationCc: "boss@example.com",
      notificationBcc: "audit@example.com, {field:email}",
      notificationSubject: "New registration: {form_name}",
      notificationReplyTo: "email",
      notificationAttachments: "documents",
      autoReplyEnabled: true,
      autoReplyCc: "",
      autoReplyBcc: "archive@example.com",
      autoReplySubject: "Your {form_name} registration",
      autoReplyHtml: true
    });
    expect(settings.autoReplyMessage).toContain("Hi {field:your_name_first},");
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${survey.id}`);
    expect(await lintIssues(page, json)).toEqual([]);
    expect(await lintIssues(page, fixture("survey", CONTACT_FORM).json)).toEqual([]);

    await uploadExport(page);
    await expect(page.locator(".wrap li", { hasText: EVENT_FORM })).toContainText("updated");
    expect(fixture("survey", EVENT_FORM).id).toBe(survey.id);
    await page.goto(listUrl(EVENT_FORM));
    await expect(page.locator("#the-list tr")).toHaveCount(1);

    const restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: EVENT_FORM, status: "publish", content: `[surveyjs id="${survey.id}"]` }
    })).json();
    postId = post.id;
    await page.goto(post.link);
    await expect(page.getByText("Student ID")).toBeHidden();
    await page.getByText("Student", { exact: true }).click();
    await expect(page.getByText("Student ID")).toBeVisible();
  });

  test("reports a file that is not a Gravity Forms export", async ({ page }) => {
    const file = { name: "forms.json", mimeType: "application/json", buffer: Buffer.from(JSON.stringify({ version: "2.9.20", title: "Not a form" })) };
    await uploadExport(page, file);
    await expect(page.locator(".wrap li", { hasText: "forms.json" })).toContainText("This file is not a Gravity Forms export.");
    await uploadExport(page, { name: "forms.xml", mimeType: "text/xml", buffer: Buffer.from("<forms/>") });
    await expect(page.locator(".wrap li", { hasText: "forms.xml" })).toContainText("The file is not a .json file.");
  });

  test("imports forms and entries from the Gravity Forms tables", async ({ page }) => {
    test.slow();
    const name = uniqueName("E2E GF tables");
    names = [name];
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", "cat > /tmp/surveyjs-e2e-gravity-forms-export.json"], fs.readFileSync(EXPORT_FILE));
    const created = fixture("create", name);
    tableForms = [created.formId, created.trashedId];

    await page.goto(listUrl(""));
    await expect(page.getByRole("link", { name: "Import from Gravity Forms" })).toBeVisible();
    await page.goto(IMPORT_URL);
    await expect(page.getByLabel(`${name} (trashed)`)).toHaveCount(0);
    await expect(page.getByRole("heading", { name: "Import from an export file" })).toBeVisible();
    for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
      await checkbox.setChecked(Number(await checkbox.getAttribute("value")) === created.formId);
    }
    await page.getByLabel("Import entries").check();
    await page.getByRole("button", { name: "Import", exact: true }).click();
    const result = page.locator(".wp-sjs-import-results li", { hasText: name });
    await expect(result).toContainText("Entries: 2 added, 0 updated.");
    await expect(result).toContainText("2 entry files copied to the SurveyJS uploads folder.");
    await expect(result).toContainText("1 entry file was not found");

    const survey = fixture("survey", name);
    const entries = fixture("results", survey.id);
    expect(entries.map(entry => entry.createdAt)).toEqual(["2026-01-15 10:00:00", "2026-01-16 08:30:00"]);
    const [ann, bob] = entries.map(entry => entry.data);
    expect(ann).toMatchObject({
      your_name_first: "Ann",
      your_name_last: "Lee",
      email: "ann@example.com",
      phone: "(555) 123-4567",
      address_street: "Main St 1",
      address_city: "Berlin",
      address_zip: "10115",
      address_country: "DE",
      ticket_type: "student",
      student_id: "S-123",
      workshops_2: ["Workshop A", "Workshop C"],
      arrival_date: "2026-12-01",
      arrival_time: "14:30",
      guests: 2,
      rate_the_topics: { glikertrow13r1: "glikertcol13a3", glikertrow13r2: "glikertcol13a1" },
      how_excited_are_you: "grating14c2",
      dietary_needs: [{ person: "Ann", need: "Vegan" }, { person: "Max", need: "No nuts" }],
      interests: ["music", "art"],
      event_rules: true,
      source: "gf-e2e"
    });
    expect(ann.documents.map(file => file.name)).toEqual(["ticket.txt", "id card.txt"]);
    for (const file of ann.documents) {
      expect(file.content).not.toContain("/gravity_forms/");
    }
    expect(bob).toMatchObject({ your_name_first: "Bob", address_country: "US", ticket_type: "other", "ticket_type-Comment": "Press pass", arrival_time: "00:05", guests: 0, event_rules: true });
    expect(bob.documents[0].content).toContain("/gravity_forms/");

    await page.goto(IMPORT_URL);
    for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
      await checkbox.setChecked(Number(await checkbox.getAttribute("value")) === created.formId);
    }
    await page.getByLabel("Import entries").check();
    await page.getByRole("button", { name: "Import", exact: true }).click();
    await expect(page.locator(".wp-sjs-import-results li", { hasText: name })).toContainText("Entries: 0 added, 2 updated.");
    expect(fixture("results", survey.id)).toHaveLength(2);
  });
});
