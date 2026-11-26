const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, saveSurvey, createSurvey, deleteSurveys, submitTimeFields } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const MU_PLUGIN = "/var/www/html/wp-content/mu-plugins/surveyjs-e2e-integrations-mock.php";
const MAILCHIMP_KEY = "0123456789abcdef".repeat(2) + "-us0";
const REVOKED_KEY = "f".repeat(32) + "-us0";
const SETTINGS_URL = "/wp-admin/admin.php?page=surveyjs-settings&tab=integrations";

const FORM_JSON = {
  elements: [
    { type: "text", name: "first_name", title: "First name" },
    { type: "text", name: "last_name", title: "Last name" },
    { type: "text", name: "email", title: "Email", inputType: "email" },
    { type: "text", name: "company", title: "Company" },
    { type: "boolean", name: "newsletter", title: "Subscribe me", renderAs: "checkbox" }
  ]
};

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: ["pipe", "pipe", "ignore"] }).toString();
}

function run(file, ...args) {
  const output = docker(["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], fs.readFileSync(path.join(__dirname, "fixtures", file)));
  return JSON.parse(output.trim().split("\n").pop());
}

const fixture = (...args) => run("integrations.php", ...args);
const runQueue = (...args) => run("webhooks.php", "run-webhooks", ...args);
const md5 = (text) => require("crypto").createHash("md5").update(text).digest("hex");

async function connect(page, service, label, field, value) {
  await page.goto(SETTINGS_URL);
  const card = page.locator(`#sjs-service-${service}`);
  const details = card.locator("details.sjs-integrations__add");
  if (!(await details.getAttribute("open"))) await details.locator("summary").click();
  const form = details.locator("form");
  await form.getByLabel("Name").fill(label);
  await form.getByLabel(field).fill(value);
  await form.getByRole("button", { name: "Connect" }).click();
  return card;
}

let post = null;
let restNonce = "";

async function publish(page, surveyId) {
  restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
  post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
    headers: { "X-WP-Nonce": restNonce },
    data: { title: "E2E integrations " + surveyId, status: "publish", content: `[surveyjs id="${surveyId}"]` }
  })).json();
}

async function submitEntry(page, surveyId, data) {
  if (!post) await publish(page, surveyId);
  const html = await (await page.request.get(post.link)).text();
  const config = JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
  const response = await page.request.post(config.saveResultUrl, {
    form: { SurveyId: String(config.id), Json: JSON.stringify(data), Website: "", _wpnonce: config.saveResultNonce, ...(await submitTimeFields(config)) }
  });
  expect(response.ok()).toBe(true);
}

async function openIntegrations(page) {
  await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Integrations", exact: true }).click();
}

async function addIntegration(page, serviceLabel, connectionLabel) {
  await page.locator("#wp-sjs-integration-service").selectOption({ label: serviceLabel });
  await page.getByRole("button", { name: "Add integration" }).click();
  const card = page.locator("#wp-sjs-integrations .wp-sjs-item").last();
  await expect(card.locator("[data-connections] + [data-single-choice]")).toHaveText(connectionLabel);
  return card;
}

test.describe("integrations", () => {
  test.skip(!WP_CONTAINER, "Set WP_CONTAINER to install the Mailchimp test double and run WP-Cron");

  let name;

  test.beforeAll(() => {
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${MU_PLUGIN}`], fs.readFileSync(path.join(__dirname, "fixtures/integrations-mock.php"), "utf8"));
  });

  test.afterAll(() => {
    fixture("clear");
    docker(["exec", WP_CONTAINER, "rm", "-f", MU_PLUGIN]);
  });

  test.beforeEach(() => {
    fixture("clear");
    run("webhooks.php", "clear-webhooks");
  });

  test.afterEach(async ({ page }) => {
    if (post) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
      post = null;
    }
    if (name) await deleteSurveys(page, name);
    name = null;
  });

  test("connects Mailchimp, stores the key encrypted and never shows it", async ({ page }) => {
    await connect(page, "mailchimp", "E2E Mailchimp", "API key", "not-a-key");
    await expect(page.locator(".notice-error")).toContainText("does not look like a Mailchimp API key");
    await connect(page, "mailchimp", "E2E Mailchimp", "API key", REVOKED_KEY);
    await expect(page.locator(".notice-error")).toContainText("401 API Key Invalid");
    const card = await connect(page, "mailchimp", "E2E Mailchimp", "API key", MAILCHIMP_KEY);
    await expect(card.locator(".sjs-integrations__connections")).toContainText("Connected");
    await expect(card.locator(".sjs-integrations__connections")).toContainText("Sandbox Mailchimp (mock) (owner@example.com) · us0");

    const html = await page.content();
    expect(html).not.toContain(MAILCHIMP_KEY.slice(0, 32));
    const state = fixture("state");
    expect(state.connections.map((connection) => [connection.service, connection.status, connection.encrypted])).toEqual([["mailchimp", "ok", true]]);
    expect(state.stored).not.toContain(MAILCHIMP_KEY.slice(0, 32));

    await card.getByRole("button", { name: "Test" }).click();
    await expect(page.locator(".notice-success")).toContainText("E2E Mailchimp works");
  });

  test("sends entries to Mailchimp with mapping, conditions, retries and Send again", async ({ page }) => {
    await connect(page, "mailchimp", "E2E Mailchimp", "API key", MAILCHIMP_KEY);
    name = uniqueName("E2E integrations");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    await openIntegrations(page);

    const mailchimp = await addIntegration(page, "Mailchimp", "E2E Mailchimp");
    await expect(mailchimp.locator("[data-summary]")).toHaveText("Mailchimp · E2E Mailchimp");
    await expect(mailchimp.locator("[data-target-list] + [data-single-choice]")).toContainText("Newsletter (mock)");
    await expect(mailchimp.locator("[data-target-list]")).toHaveValue("demo01");
    const mergeFields = mailchimp.locator("[data-field-map] tbody tr");
    await expect(mergeFields).toHaveCount(5);
    await expect(mergeFields.filter({ hasText: "FNAME" }).locator("select")).toHaveValue("first_name");
    await expect(mergeFields.filter({ hasText: "COMPANY" }).locator("select")).toHaveValue("company");
    await expect(mergeFields.filter({ hasText: "ADDRESS" })).toContainText("not supported yet");
    await expect(mergeFields.filter({ hasText: "BIRTHDAY" })).toContainText("not supported yet");
    await mergeFields.filter({ hasText: "LNAME" }).locator("select").selectOption("__custom__");
    await mergeFields.filter({ hasText: "LNAME" }).locator("input").fill("{field:last_name} (web)");
    await expect(mailchimp.getByLabel("Email question")).toHaveValue("email");
    await mailchimp.getByLabel("Tags").fill("website, e2e");
    await mailchimp.getByLabel("Double opt-in").uncheck();
    await expect(mailchimp.locator("[data-consent-status]")).toContainText("without their consent");
    await mailchimp.getByRole("button", { name: "Add condition" }).click();
    await mailchimp.getByLabel("Question", { exact: true }).selectOption("newsletter");
    await mailchimp.locator('[data-rule="choice"]').selectOption("true");
    await expect(mailchimp.locator("[data-consent-status]")).toBeEmpty();
    await mailchimp.getByRole("button", { name: "Send test" }).click();
    await expect(mailchimp.locator("[data-test-result]")).toContainText('"Newsletter (mock)" is reachable. No contact was added');
    expect(fixture("state", surveyId).log[0]).toMatchObject({ type: "integration", event: "test", ok: true });
    await saveSurvey(page);

    await submitEntry(page, surveyId, { first_name: "Anna", last_name: "Smith", email: "Anna@Example.com", company: "Acme & Co", newsletter: true });
    await submitEntry(page, surveyId, { first_name: "Bob", email: "bob@example.com", newsletter: false });
    await runQueue();
    let state = fixture("state", surveyId);
    const anna = state.mock.members[md5("anna@example.com")];
    expect(anna).toMatchObject({ email_address: "Anna@Example.com", status: "subscribed", merge_fields: { FNAME: "Anna", LNAME: "Smith (web)", COMPANY: "Acme & Co" } });
    expect(state.mock.members[md5("bob@example.com")]).toBeUndefined();
    expect(state.mock.tags[md5("anna@example.com")]).toEqual(["website", "e2e"]);

    fixture("set-key", REVOKED_KEY);
    await submitEntry(page, surveyId, { first_name: "Cleo", email: "cleo@example.com", newsletter: true });
    await runQueue();
    state = fixture("state", surveyId);
    expect(state.log[0]).toMatchObject({ ok: false, code: 401, retry: true });
    expect(state.connections.find((connection) => connection.service === "mailchimp")).toMatchObject({ status: "error" });
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}`);
    await expect(page.locator(".notice-warning")).toContainText("E2E Mailchimp could not send entries");

    fixture("set-key", MAILCHIMP_KEY);
    await runQueue("all");
    state = fixture("state", surveyId);
    expect(state.mock.members[md5("cleo@example.com")]).toBeDefined();
    expect(state.connections.find((connection) => connection.service === "mailchimp")).toMatchObject({ status: "ok" });
    expect(state.log[0]).toMatchObject({ attempt: 2, ok: true, retry: false });

    await submitEntry(page, surveyId, { first_name: "Dan", email: "dan@compliance.test", newsletter: true });
    await runQueue();
    expect(fixture("state", surveyId).log[0]).toMatchObject({ item: expect.stringMatching(/^i/), ok: false, code: 400, retry: false });
    await page.reload();
    await openIntegrations(page);
    const savedMailchimp = page.locator("#wp-sjs-integrations .wp-sjs-item").first();
    await savedMailchimp.locator('[data-action="toggle"]').click();
    await savedMailchimp.getByRole("button", { name: "Send again" }).first().click();
    await expect(savedMailchimp.locator("[data-test-result]")).toContainText("Member In Compliance State");
  });

  test("a Mailchimp integration is saved only with consent", async ({ page }) => {
    await connect(page, "mailchimp", "E2E Mailchimp", "API key", MAILCHIMP_KEY);
    name = uniqueName("E2E integrations consent");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    await openIntegrations(page);
    const mailchimp = await addIntegration(page, "Mailchimp", "E2E Mailchimp");
    await expect(mailchimp.getByLabel("Double opt-in")).toBeChecked();
    await expect(mailchimp.getByLabel("Update existing contacts")).toBeChecked();

    await page.getByRole("button", { name: "Save", exact: true }).click();
    await expect(page.locator("#wp-sjs-editor-notice")).toContainText("without their consent");
    const refused = await page.evaluate(async () => {
      const api = window.surveyjsEditor;
      const body = new URLSearchParams({ Id: api.config.id, Json: JSON.stringify(api.getSurvey()), Theme: "{}", Settings: JSON.stringify(api.getSettings()), Issues: "[]", _wpnonce: api.config.saveNonce });
      return (await fetch(api.config.saveUrl, { method: "POST", body })).json();
    });
    expect(refused).toMatchObject({ IsSuccess: 0, Error: expect.stringContaining("without their consent") });

    await mailchimp.getByRole("button", { name: 'Add "Subscribe me to the newsletter" question' }).click();
    await expect(mailchimp.locator('[data-rule="question"]')).toHaveValue("newsletter_consent");
    await expect(mailchimp.locator("[data-consent-status]")).toContainText("was added at the end of the form");
    await saveSurvey(page);

    await submitEntry(page, surveyId, { email: "yes@example.com", newsletter_consent: true });
    await submitEntry(page, surveyId, { email: "no@example.com" });
    await runQueue();
    const state = fixture("state", surveyId);
    expect(state.mock.members[md5("yes@example.com")]).toMatchObject({ status: "pending" });
    expect(state.mock.members[md5("no@example.com")]).toBeUndefined();
  });

  test("the AI sees connections and integrations but never the keys", async ({ page }) => {
    await connect(page, "mailchimp", "E2E Mailchimp", "API key", MAILCHIMP_KEY);
    name = uniqueName("E2E integrations AI");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    await openIntegrations(page);
    const mailchimp = await addIntegration(page, "Mailchimp", "E2E Mailchimp");
    await mailchimp.getByLabel("This form is a newsletter signup (submitting it means consent)").check();
    await saveSurvey(page);

    const ai = fixture("ai", surveyId, `key ${MAILCHIMP_KEY} or ${MAILCHIMP_KEY.slice(0, 32)}`);
    expect(ai.redacted).toBe("key [hidden] or [hidden]");
    for (const connection of ai.site.connections) expect(Object.keys(connection).sort()).toEqual(["id", "label", "service", "status"]);
    expect(JSON.stringify(ai.site)).not.toContain(MAILCHIMP_KEY.slice(0, 32));
    expect(ai.schema).toContain('"mailchimp": Mailchimp fields');
    expect(ai.settings[0]).toMatchObject({ service: "mailchimp", signupForm: true, connection: ai.site.connections.find((connection) => connection.label === "E2E Mailchimp").id });

    expect(fixture("keeps-unknown", surveyId)).toEqual({ kept: true, dropped: true });
  });
});
