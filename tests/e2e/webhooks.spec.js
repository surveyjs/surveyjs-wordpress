const fs = require("fs");
const path = require("path");
const http = require("http");
const { execFile, execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, saveSurvey, createSurvey, deleteSurveys, submitTimeFields } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const MU_PLUGIN = "/var/www/html/wp-content/mu-plugins/surveyjs-e2e-webhooks.php";
const FIXTURE = path.join(__dirname, "fixtures/webhooks.php");

const FORM_JSON = {
  elements: [
    { type: "text", name: "visitor_name", title: "Your name" },
    { type: "radiogroup", name: "department", title: "Department", choices: [{ value: "sales", text: "Sales" }, { value: "support", text: "Support" }, { value: "R&D < 5", text: "Research" }] }
  ]
};

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: ["pipe", "pipe", "ignore"] }).toString();
}

function fixture(...args) {
  const output = docker(["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], fs.readFileSync(FIXTURE));
  return JSON.parse(output.trim().split("\n").pop());
}

// Deliveries call the endpoint served by this process, so the event loop must stay free while they run.
function runWebhooks(...args) {
  return new Promise((resolve, reject) => {
    const child = execFile("docker", ["exec", "-i", WP_CONTAINER, "php", "--", "run-webhooks", ...args], (error, stdout) => {
      if (error) reject(error);
      else resolve(JSON.parse(stdout.trim().split("\n").pop()));
    });
    child.stdin.end(fs.readFileSync(FIXTURE));
  });
}

async function surveyConfig(page, link) {
  const html = await (await page.request.get(link)).text();
  return JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
}

async function submitEntry(page, config, data) {
  const response = await page.request.post(config.saveResultUrl, {
    form: { SurveyId: String(config.id), Json: JSON.stringify(data), Website: "", _wpnonce: config.saveResultNonce, ...(await submitTimeFields(config)) }
  });
  expect(response.ok()).toBe(true);
}

async function openWebhooksTab(page) {
  await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Integrations", exact: true }).click();
}

async function addWebhook(page, url) {
  await openWebhooksTab(page);
  await page.getByRole("button", { name: "Add webhook" }).click();
  const webhook = page.locator("#wp-sjs-webhooks .wp-sjs-item").last();
  await webhook.getByLabel("Webhook URL").fill(url);
  return webhook;
}

test.describe("webhooks", () => {
  test.skip(!WP_CONTAINER, "Set WP_CONTAINER to reach the test endpoint and run WP-Cron");

  let server;
  let endpoint;
  let port;
  let requests = [];
  let statuses = [];
  let name;
  let post;
  let restNonce;

  test.beforeAll(async () => {
    server = http.createServer((request, response) => {
      let body = "";
      request.on("data", (chunk) => { body += chunk; });
      request.on("end", () => {
        requests.push({ url: request.url, headers: request.headers, body });
        response.writeHead(statuses.shift() ?? 200, { "Content-Type": "application/json" });
        response.end("{}");
      });
    });
    await new Promise((resolve) => server.listen(0, "0.0.0.0", resolve));
    port = server.address().port;
    endpoint = `http://host.docker.internal:${port}/hook`;
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${MU_PLUGIN}`], fs.readFileSync(path.join(__dirname, "fixtures/webhook-endpoint.php"), "utf8").replace("{{PORT}}", String(port)));
  });

  test.afterAll(async () => {
    docker(["exec", WP_CONTAINER, "rm", "-f", MU_PLUGIN]);
    await new Promise((resolve) => server.close(resolve));
  });

  test.beforeEach(() => {
    requests = [];
    statuses = [];
    fixture("clear-webhooks");
  });

  test.afterEach(async ({ page }) => {
    if (post) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
      post = null;
    }
    if (name) await deleteSurveys(page, name);
    name = null;
  });

  async function publish(page, surveyId) {
    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    return surveyConfig(page, post.link);
  }

  test("sends a test request with custom headers, logs it and blocks local addresses", async ({ page }) => {
    name = uniqueName("E2E webhook test");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    const webhook = await addWebhook(page, endpoint);
    await expect(webhook.locator("[data-summary]")).toHaveText(new URL(endpoint).host);
    await webhook.getByRole("button", { name: "Add header" }).click();
    await webhook.getByLabel("Header name").fill("Authorization");
    await webhook.getByLabel("Header value").fill("Bearer e2e-token");

    await webhook.getByRole("button", { name: "Send test request" }).click();
    await expect(webhook.locator("[data-test-result]")).toContainText("Test sent. The URL answered with HTTP 200.");
    expect(requests).toHaveLength(1);
    const [request] = requests;
    expect(request.url).toBe("/hook");
    expect(request.headers["content-type"]).toBe("application/json; charset=utf-8");
    expect(request.headers["x-surveyjs-event"]).toBe("test");
    expect(request.headers["x-surveyjs-signature"]).toBeUndefined();
    expect(request.headers.authorization).toBe("Bearer e2e-token");
    const body = JSON.parse(request.body);
    expect(body).toMatchObject({ event: "test", form: { id: surveyId, name: name }, entry: { id: null, url: null }, data: { visitor_name: "Sample answer" } });
    expect(body.answers).toEqual({ visitor_name: "Sample answer", department: "Sample answer" });
    expect(body.site.url).toMatch(/^http:\/\/localhost:\d+\/$/);
    expect(body.fields.map((field) => field.title)).toEqual(["Your name", "Department"]);
    await expect(webhook.locator("[data-log] tbody tr")).toHaveCount(1);
    await expect(webhook.locator("[data-log] tbody tr").first()).toContainText("Delivered (HTTP 200)");

    for (const blocked of ["http://169.254.169.254/latest/meta-data/", "http://127.0.0.1/hook", "http://[::1]/hook"]) {
      await webhook.getByLabel("Webhook URL").fill(blocked);
      await webhook.getByRole("button", { name: "Send test request" }).click();
      await expect(webhook.locator("[data-test-result]")).toContainText("The URL is not allowed.");
    }
    expect(requests).toHaveLength(1);

    await webhook.getByLabel("Webhook URL").fill(endpoint);
    await saveSurvey(page);
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${surveyId}&tab=webhooks`);
    await expect(page.locator(".wp-sjs-editor__tabs .nav-tab[data-tab='integrations']")).toHaveClass(/nav-tab-active/);
    const saved = page.locator("#wp-sjs-webhooks .wp-sjs-item");
    await expect(saved).toHaveCount(1);
    await saved.locator('[data-action="toggle"]').click();
    await expect(saved.getByLabel("Header name")).toHaveValue("Authorization");
    await expect(saved.getByLabel("Header value")).toHaveValue("Bearer e2e-token");
    await expect(saved.locator("[data-log] tbody tr")).toHaveCount(4);
  });

  test("queues matching entries and retries a failed delivery", async ({ page }) => {
    name = uniqueName("E2E webhook entries");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    const webhook = await addWebhook(page, endpoint);
    await webhook.getByRole("button", { name: "Add condition" }).click();
    await webhook.getByLabel("Question", { exact: true }).selectOption("department");
    await webhook.locator('select[data-rule="choice"]').selectOption("sales");
    await saveSurvey(page);
    const config = await publish(page, surveyId);

    await submitEntry(page, config, { visitor_name: "Bob", department: "support" });
    expect(fixture("webhook-state", surveyId)).toMatchObject({ queued: 0, scheduled: [] });

    statuses = [500];
    await submitEntry(page, config, { visitor_name: "Jane", department: "sales" });
    expect(fixture("webhook-state", surveyId).queued).toBe(1);
    expect(await runWebhooks()).toEqual({ ran: 1 });
    expect(requests).toHaveLength(1);
    let state = fixture("webhook-state", surveyId);
    expect(state.queued).toBe(1);
    expect(state.scheduled).toHaveLength(1);
    expect(state.scheduled[0]).toBeGreaterThan(240);
    expect(state.log[0]).toMatchObject({ event: "entry.submitted", attempt: 1, ok: false, code: 500, retry: true });

    expect(await runWebhooks()).toEqual({ ran: 0 });
    expect(await runWebhooks("all")).toEqual({ ran: 1 });
    expect(requests).toHaveLength(2);
    state = fixture("webhook-state", surveyId);
    expect(state).toMatchObject({ queued: 0, scheduled: [] });
    expect(state.log[0]).toMatchObject({ attempt: 2, ok: true, code: 200, retry: false });

    const [first, second] = requests;
    expect(second.body).toBe(first.body);
    expect(second.headers["x-surveyjs-delivery"]).toBe(first.headers["x-surveyjs-delivery"]);
    expect(second.headers["x-surveyjs-event"]).toBe("entry.submitted");
    const body = JSON.parse(second.body);
    expect(body).toMatchObject({ event: "entry.submitted", form: { id: surveyId }, data: { visitor_name: "Jane", department: "sales" } });
    expect(body.entry.id).toBe(state.log[0].entry);
    expect(body.entry.id).toBeGreaterThan(0);
    expect(body.fields).toEqual([
      { name: "visitor_name", title: "Your name", text: "Jane" },
      { name: "department", title: "Department", text: "Sales" }
    ]);
    expect(body.answers).toEqual({ visitor_name: "Jane", department: "Sales" });
    expect(body.entry.url).toContain(`page=surveyjs_results&id=${surveyId}&entry=${body.entry.id}`);
    expect(Math.abs(Date.parse(body.entry.submitted_at) - Date.now())).toBeLessThan(10 * 60 * 1000);

    await page.reload();
    await openWebhooksTab(page);
    const saved = page.locator("#wp-sjs-webhooks .wp-sjs-item");
    await saved.locator('[data-action="toggle"]').click();
    const rows = saved.locator("[data-log] tbody tr");
    await expect(rows.first()).toContainText(`Entry #${body.entry.id} (retry 1)`);
    await expect(rows.first()).toContainText("Delivered (HTTP 200)");
    await expect(rows.nth(1)).toContainText("500 Internal Server Error Will retry.");
  });

  test("matches conditions on values with &, < and >", async ({ page }) => {
    name = uniqueName("E2E webhook entities");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    const webhook = await addWebhook(page, endpoint);
    await webhook.getByRole("button", { name: "Add condition" }).click();
    await webhook.getByLabel("Question", { exact: true }).selectOption("department");
    await webhook.locator('select[data-rule="choice"]').selectOption("R&D < 5");
    await saveSurvey(page);
    const config = await publish(page, surveyId);

    await submitEntry(page, config, { visitor_name: "Bob", department: "support" });
    expect(fixture("webhook-state", surveyId).queued).toBe(0);
    await submitEntry(page, config, { visitor_name: "Ann", department: "R&D < 5" });
    expect(fixture("webhook-state", surveyId).queued).toBe(1);

    const rule = (operator, value) => JSON.stringify({ logic: "all", rules: [{ question: "q", operator, value }] });
    expect(fixture("conditions", rule("equal", "Sales & Marketing"), JSON.stringify({ q: "Sales &amp; Marketing" }))).toBe(true);
    expect(fixture("conditions", rule("notequal", "Sales & Marketing"), JSON.stringify({ q: "Sales &amp; Marketing" }))).toBe(false);
    expect(fixture("conditions", rule("contains", "a<b"), JSON.stringify({ q: ["x", "A&lt;B"] }))).toBe(true);
    expect(fixture("conditions", rule("equal", "x > 1"), JSON.stringify({ q: "x &gt; 1" }))).toBe(true);
  });

  test("checks every address of the host and pins the request to a checked one", () => {
    expect(fixture("addresses", JSON.stringify([
      "8.8.8.8", "2606:4700:4700::1111",
      "169.254.169.254", "100.64.0.1", "10.0.0.1", "0.0.0.0",
      "::1", "::", "::ffff:127.0.0.1", "::ffff:169.254.169.254", "64:ff9b::a9fe:a9fe", "2002:7f00:1::", "fd00::1", "fe80::1"
    ]))).toEqual({
      "8.8.8.8": true, "2606:4700:4700::1111": true,
      "169.254.169.254": false, "100.64.0.1": false, "10.0.0.1": false, "0.0.0.0": false,
      "::1": false, "::": false, "::ffff:127.0.0.1": false, "::ffff:169.254.169.254": false, "64:ff9b::a9fe:a9fe": false, "2002:7f00:1::": false, "fd00::1": false, "fe80::1": false
    });
    const resolved = fixture("resolve", JSON.stringify(["http://localhost/hook", "http://169.254.169.254/", "http://8.8.8.8/hook", `http://host.docker.internal:${port}/hook`]));
    expect(resolved["http://localhost/hook"]).toBeNull();
    expect(resolved["http://169.254.169.254/"]).toBeNull();
    expect(resolved["http://8.8.8.8/hook"]).toBe("");
    expect(resolved[`http://host.docker.internal:${port}/hook`]).toMatch(new RegExp(`^host\\.docker\\.internal:${port}:(\\d+\\.){3}\\d+$`));
  });

  test("drops a request WP-Cron cannot schedule, and deactivation deletes every queued request", async ({ page }) => {
    name = uniqueName("E2E webhook queue");
    const surveyId = await createSurvey(page, name, FORM_JSON);
    await addWebhook(page, endpoint);
    await saveSurvey(page);

    const unschedulable = fixture("unschedulable", surveyId);
    expect(unschedulable.queued).toBe(0);
    expect(unschedulable.log[0]).toMatchObject({ ok: false, retry: false, error: "WP-Cron could not schedule the request, so it was not sent." });

    expect(fixture("deactivate")).toEqual({ before: 1, after: 0 });
  });
});
