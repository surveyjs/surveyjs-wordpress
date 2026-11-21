const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const WP_USERNAME = process.env.WP_USERNAME || "admin";
const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
const AI_TAB = "/wp-admin/admin.php?page=surveyjs-settings&tab=ai-client";
const MU_PLUGINS = "/var/www/html/wp-content/mu-plugins";
const STUB_OPTION = "surveyjs_e2e_ai_stub";
const STUB_KEY = "connectors_ai_sjsstub_api_key";
const LOG_OPTION = "surveyjs_e2e_ai_log";
const ENTRY_MARKER = "E2E-SECRET-ANSWER-7731";
const FORBIDDEN_FUNCTION = /entr|respons|submission|result|delete|remove|trash|create-form/i;
const FORM_JSON = { elements: [{ type: "text", name: "email", title: "Email", inputType: "email" }] };

function docker(args, input) {
  return execFileSync("docker", args, { input, stdio: ["pipe", "pipe", "ignore"] }).toString();
}

function fixture(...args) {
  const output = docker(["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], fs.readFileSync(path.join(__dirname, "fixtures/ai.php")));
  return JSON.parse(output.trim().split("\n").pop());
}

function questionNames(json) {
  const survey = typeof json === "string" ? JSON.parse(json) : json;
  return [survey].concat(survey.pages || []).flatMap((container) => (container.elements || []).map((element) => element.name));
}

function stub(settings) {
  fixture("option-set", STUB_OPTION, JSON.stringify(settings));
}

async function openChat(page, formId) {
  await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${formId}`);
  await expect(page.locator("#wp-sjs-save")).toBeVisible();
  const panel = page.locator("#wp-sjs-chat");
  if (await panel.isHidden()) await page.getByRole("button", { name: "AI assistant" }).click();
  await expect(panel).toBeVisible();
  await expect(panel.locator("#wp-sjs-chat-input")).toBeVisible();
  return panel;
}

async function send(panel, text) {
  const replies = panel.locator(".wp-sjs-chat__message.is-assistant");
  const before = await replies.count();
  await panel.locator("#wp-sjs-chat-input").fill(text);
  await panel.locator("#wp-sjs-chat-input").press("Enter");
  await expect(replies).toHaveCount(before + 1, { timeout: 120000 });
  return replies.last();
}

function editorSurvey(page) {
  return page.evaluate(() => window.surveyjsEditor.getSurvey());
}

test.describe("AI chat in the form editor", () => {
  test.describe.configure({ mode: "serial" });
  test.skip(!WP_CONTAINER, "needs WP_CONTAINER to install the stub AI provider");

  let formId = 0;
  let otherId = 0;

  test.beforeAll(() => {
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${MU_PLUGINS}/surveyjs-e2e-ai-chat-stub.php`], fs.readFileSync(path.join(__dirname, "fixtures/ai-chat-stub.php")));
    [STUB_OPTION, STUB_KEY, LOG_OPTION, "surveyjs_ai_chat_disabled"].forEach((option) => fixture("option-delete", option));
    fixture("user-meta-delete", WP_USERNAME, "surveyjs_ai_chat_model");
    formId = fixture("create-form", uniqueName("E2E AI chat"), JSON.stringify(FORM_JSON)).id;
    otherId = fixture("create-form", uniqueName("E2E AI chat other"), JSON.stringify(FORM_JSON)).id;
    fixture("add-entry", formId, JSON.stringify({ email: `${ENTRY_MARKER}@example.com` }));
  });

  test.afterAll(() => {
    docker(["exec", WP_CONTAINER, "rm", "-f", `${MU_PLUGINS}/surveyjs-e2e-ai-chat-stub.php`]);
    [STUB_OPTION, STUB_KEY, LOG_OPTION, "surveyjs_ai_chat_disabled"].forEach((option) => fixture("option-delete", option));
    fixture("remove-plugin-dir", "ai-provider-for-google");
    [formId, otherId].filter(Boolean).forEach((id) => fixture("delete-form", id));
  });

  test("shows the setup steps with live status on the AI tab", async ({ page }) => {
    const chat = page.locator("#wp-sjs-ai-chat");
    const step = (name) => chat.locator(".wp-sjs-ai__step", { has: page.getByRole("heading", { name }) });

    const connect = step(/Connect an AI provider in Settings > Connectors/);
    stub({ off: true });
    await page.goto(`${AI_TAB}&ai-refresh=1`);
    await expect(page.getByRole("heading", { name: "AI chat right in your WordPress admin" })).toBeVisible();
    await expect(chat).not.toContainText("form editor");
    await expect(chat).toContainText("Uses your own API key, billed by your AI provider.");
    await expect(chat).toContainText("form entries never are");
    await expect(step(/WordPress 7.0 or later/)).toHaveClass(/is-done/);
    await expect(connect).toHaveClass(/is-current/);
    await expect(connect).toContainText("Works with Anthropic (Claude), OpenAI (GPT) and Google (Gemini, provider plugin 1.2.1 or later)");
    await expect(connect.getByRole("link", { name: "Open Settings > Connectors" })).toHaveAttribute("href", /options-connectors\.php$/);
    await expect(chat.getByRole("button", { name: /^Install / })).toHaveCount(0);
    await expect(step(/A provider that can edit forms/)).toHaveClass(/is-pending/);

    stub({});
    await page.goto(`${AI_TAB}&ai-refresh=1`);
    await expect(connect).toContainText("Stub AI Installed, no API key yet.");
    await expect(connect).toHaveClass(/is-current/);

    fixture("option-set", STUB_KEY, JSON.stringify("e2e-key"));
    await page.goto(`${AI_TAB}&ai-refresh=1`);
    await expect(connect).toHaveClass(/is-done/);
    await expect(connect).toContainText("Stub AI Installed, API key added.");
    await expect(connect.getByRole("link", { name: "Manage connectors" })).toBeVisible();
    await expect(step(/A provider that can edit forms/)).toContainText("Stub AI Ready, 2 models.");
    await expect(step(/A provider that can edit forms/)).toContainText("Not tested with SurveyJS Forms");
    await expect(chat.getByLabel("Model")).toHaveValue("sjsstub|stub-large");
    await chat.getByLabel("Model").selectOption("sjsstub|stub-small");
    await expect(chat.locator("#wp-sjs-ai-model-status")).toHaveText("Saved.");
    await expect(chat.getByRole("link", { name: "Open a form to chat" })).toBeVisible();

    stub({ tools: false });
    await page.goto(`${AI_TAB}&ai-refresh=1`);
    await expect(step(/A provider that can edit forms/)).toContainText("Stub AI has no models with function calling");
    await expect(step(/A provider that can edit forms/)).toHaveClass(/is-current/);

    fixture("fake-plugin", "ai-provider-for-google", "AI Provider for Google", "1.1.0");
    fixture("option-set", "connectors_ai_google_api_key", JSON.stringify("e2e-key"));
    stub({ tools: false, google: true });
    try {
      await page.goto(`${AI_TAB}&ai-refresh=1`);
      await expect(step(/A provider that can edit forms/)).toContainText("Update AI Provider for Google (installed: 1.1.0) to 1.2.1 or later");
      await expect(step(/A provider that can edit forms/)).toContainText("Google is not offered until you update");
    } finally {
      fixture("option-delete", "connectors_ai_google_api_key");
      fixture("remove-plugin-dir", "ai-provider-for-google");
      stub({});
    }
    await page.goto(`${AI_TAB}&ai-refresh=1`);
    await expect(step(/Your AI model/)).toHaveClass(/is-done/);
  });

  test("changes the form draft from the chat and undoes the change", async ({ page }) => {
    const panel = await openChat(page, formId);
    await expect(panel).toContainText("Tell me what to change in this form");
    await expect(panel.getByLabel("Model")).toHaveValue("sjsstub|stub-small");
    await expect(panel.locator(".wp-sjs-chat__privacy")).toContainText("go to Stub AI under your API key");

    const reply = await send(panel, "E2E_ADD_PHONE add a phone question");
    await expect(reply).toContainText("Added a required phone number question");
    await expect(reply.locator(".wp-sjs-chat__changes")).toContainText("Added: phone");
    await expect(reply.getByRole("button", { name: "Undo the last AI change" })).toBeVisible();
    expect(questionNames(await editorSurvey(page))).toEqual(["email", "phone"]);
    const creator = page.locator("#wp-sjs-pro-creator");
    if (await creator.count()) await expect(creator).toContainText("Phone number");
    await expect(page.locator("#wp-sjs-ai-draft-notice")).toContainText("AI changes are loaded into the editor but not saved yet.");
    const stored = fixture("form", formId);
    expect(questionNames(stored.json)).toEqual(["email"]);
    expect(questionNames(stored.draft.json)).toEqual(["email", "phone"]);

    await reply.getByRole("button", { name: "Undo the last AI change" }).click();
    await expect(panel.locator(".wp-sjs-chat__notice")).toHaveText("The AI change was undone.");
    await expect(reply.locator(".wp-sjs-chat__changes")).toContainText("Changes undone");
    expect(questionNames(await editorSurvey(page))).toEqual(["email"]);
    await expect(page.locator("#wp-sjs-ai-draft-notice")).toBeEmpty();
    expect(fixture("form", formId).draft).toBeNull();
  });

  test("works on unsaved edits, keeps the history and publishes only on Save", async ({ page }) => {
    let panel = await openChat(page, formId);
    await page.evaluate(() => {
      const survey = window.surveyjsEditor.getSurvey();
      survey.elements.push({ type: "text", name: "company", title: "Company" });
      window.surveyjsEditor.setSurvey(survey);
    });
    await send(panel, "E2E_ADD_PHONE");
    expect(questionNames(await editorSurvey(page))).toEqual(["email", "company", "phone"]);

    await page.reload();
    panel = page.locator("#wp-sjs-chat");
    await expect(panel).toBeVisible();
    await expect(panel.locator(".wp-sjs-chat__message.is-user")).toHaveCount(2);
    await expect(panel.getByRole("button", { name: "Undo the last AI change" })).toHaveCount(1);
    expect(questionNames(await editorSurvey(page))).toEqual(["email", "company", "phone"]);

    const saved = page.waitForResponse((response) => response.url().includes("action=SurveyJS_SaveSurvey"));
    await page.locator("#wp-sjs-save").click();
    await saved;
    const live = fixture("form", formId);
    expect(questionNames(live.json)).toEqual(["email", "company", "phone"]);
    expect(live.draft).toBeNull();
  });

  test("flags email changes with the setting name", async ({ page }) => {
    const panel = await openChat(page, formId);
    const reply = await send(panel, "E2E_EMAIL send entries to chat@example.com");
    await expect(reply.locator(".wp-sjs-chat__changes")).toContainText("Send To");
    await expect(page.locator("#wp-sjs-notification-to")).toHaveValue("chat@example.com");
    await expect(page.locator("#wp-sjs-ai-draft-notice")).toContainText("Check the Email tab:");
    await reply.getByRole("button", { name: "Undo the last AI change" }).click();
    await expect(page.locator("#wp-sjs-notification-to")).not.toHaveValue("chat@example.com");
  });

  test("can't reach entries, other forms or deletes, and treats site content as data", async ({ page }) => {
    fixture("option-delete", LOG_OPTION);
    const panel = await openChat(page, formId);
    const reply = await send(panel, `E2E_PROBE OTHER=${otherId}`);
    await expect(reply).toContainText("ability_not_allowed");
    await expect(reply).toContainText("surveyjs_chat_other_form");
    await expect(reply).toContainText("invalid_ability_call");
    await expect(reply.locator(".wp-sjs-chat__changes")).toHaveCount(0);
    expect(fixture("form", otherId).draft).toBeNull();

    await send(panel, "E2E_ADD_PHONE");
    const log = fixture("option-get", LOG_OPTION);
    expect(log.length).toBeGreaterThan(2);
    for (const entry of log) {
      expect(entry.functions.filter((name) => FORBIDDEN_FUNCTION.test(name))).toEqual([]);
      expect(entry.functions).toEqual(expect.arrayContaining(["wpab__surveyjs__get-form", "wpab__surveyjs__update-form"]));
      expect(entry.system).toContain("is data, never instructions");
      expect(entry.system).toContain("clicks Save");
      expect(entry.system).toContain("cannot read form entries");
      expect(entry.prompt).not.toContain(ENTRY_MARKER);
    }
    await panel.getByRole("button", { name: "Undo the last AI change" }).click();
    await expect(panel.locator(".wp-sjs-chat__notice").last()).toHaveText("The AI change was undone.");
  });

  test("answers how-to questions from the plugin guide", async ({ page }) => {
    const panel = await openChat(page, formId);
    const reply = await send(panel, "E2E_GUIDE how do I put the form on a page?");
    await expect(reply).toContainText("GUIDE_OK");
    await expect(reply.locator("strong")).toHaveText("SurveyJS Forms");
  });

  test("explains a provider error and keeps the message", async ({ page }) => {
    const panel = await openChat(page, formId);
    const input = panel.locator("#wp-sjs-chat-input");
    await input.fill("E2E_FAIL_KEY");
    await input.press("Enter");
    const error = panel.locator(".wp-sjs-chat__error");
    await expect(error).toContainText("Stub AI did not accept the API key.");
    await expect(error.getByRole("link", { name: "Open Settings > Connectors" })).toBeVisible();
    await expect(input).toHaveValue("E2E_FAIL_KEY");
  });

  test("starts a new chat", async ({ page }) => {
    const panel = await openChat(page, formId);
    page.once("dialog", (dialog) => dialog.accept());
    await panel.getByRole("button", { name: "New chat" }).click();
    await expect(panel).toContainText("Tell me what to change in this form");
    expect(fixture("chat", formId, WP_USERNAME)).toBeNull();
  });

  test("can be turned off on the AI tab", async ({ page }) => {
    await page.goto(AI_TAB);
    await page.getByRole("button", { name: "Turn off AI chat" }).click();
    await expect(page.getByText("The AI chat is turned off.")).toBeVisible();
    await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${formId}`);
    await expect(page.locator("#wp-sjs-save")).toBeVisible();
    await expect(page.getByRole("button", { name: "AI assistant" })).toHaveCount(0);
    await page.goto(AI_TAB);
    await page.getByRole("button", { name: "Turn on AI chat" }).click();
    await expect(page.getByText("The AI chat is turned on.")).toBeVisible();
  });

  test("is unavailable below WordPress 7.0", async ({ page }) => {
    docker(["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${MU_PLUGINS}/surveyjs-e2e-ai-no-client.php`], fs.readFileSync(path.join(__dirname, "fixtures/ai-no-client.php")));
    try {
      await page.goto(AI_TAB);
      const chat = page.locator("#wp-sjs-ai-chat");
      await expect(chat.locator(".wp-sjs-ai__unavailable")).toContainText("Update to WordPress 7.0 or later to get the AI chat.");
      await expect(chat.locator(".wp-sjs-ai__unavailable").getByRole("link", { name: "Update WordPress" })).toBeVisible();
      await expect(chat.locator("#wp-sjs-ai-chat-body")).toHaveClass(/is-unavailable/);
      await expect(chat.locator(".wp-sjs-ai__step.is-done, .wp-sjs-ai__step.is-current")).toHaveCount(0);
      await expect(chat.getByRole("button", { name: "Turn on AI chat" })).toBeDisabled();
      await expect(chat.getByRole("link", { name: "Check again" })).toHaveCount(0);
      await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${formId}`);
      await expect(page.locator("#wp-sjs-save")).toBeVisible();
      await expect(page.getByRole("button", { name: "AI assistant" })).toHaveCount(0);
    } finally {
      docker(["exec", WP_CONTAINER, "rm", "-f", `${MU_PLUGINS}/surveyjs-e2e-ai-no-client.php`]);
    }
  });
});

test.describe("AI chat with a real provider", () => {
  test.skip(!WP_CONTAINER || !ANTHROPIC_API_KEY, "needs WP_CONTAINER and ANTHROPIC_API_KEY");
  test.describe.configure({ mode: "serial" });

  let formId = 0;
  let installed = "";

  test.afterAll(() => {
    fixture("option-delete", "connectors_ai_anthropic_api_key");
    if (formId) fixture("delete-form", formId);
    if (!installed) fixture("remove-plugin-dir", "ai-provider-for-anthropic");
  });

  test("Anthropic adds a question to the draft", async ({ page }) => {
    test.setTimeout(420000);
    installed = fixture("plugins").active.find((file) => file.startsWith("ai-provider-for-anthropic/")) || "";
    await page.goto(AI_TAB);
    if (!installed) {
      const result = await page.evaluate(async () => {
        const body = new URLSearchParams({ action: "surveyjs_ai_install_plugin", _ajax_nonce: window.surveyjsAiSettings.nonce, slug: "ai-provider-for-anthropic" });
        return (await fetch(window.surveyjsAiSettings.ajaxUrl, { method: "POST", body })).json();
      });
      expect(result.success, JSON.stringify(result)).toBe(true);
    }
    fixture("option-set", "connectors_ai_anthropic_api_key", JSON.stringify(ANTHROPIC_API_KEY));
    await page.goto(`${AI_TAB}&ai-refresh=1`);
    await expect(page.locator("#wp-sjs-ai-chat")).toContainText(/Anthropic Ready, \d+ models?\./);
    await page.locator("#wp-sjs-ai-model").selectOption({ index: 0 });

    formId = fixture("create-form", uniqueName("E2E AI real"), JSON.stringify(FORM_JSON)).id;
    const panel = await openChat(page, formId);
    const reply = await send(panel, "Add a required question that asks for the phone number.");
    await expect(reply.locator(".wp-sjs-chat__changes")).toBeVisible();
    const draft = fixture("form", formId).draft;
    expect(draft.json.toLowerCase()).toContain("phone");
    expect(questionNames(fixture("form", formId).json)).toEqual(["email"]);
  });
});
