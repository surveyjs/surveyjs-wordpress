const path = require("path");
const { execSync } = require("child_process");
const { test, expect } = require("@playwright/test");

const root = path.join(__dirname, "../..");
const php = process.env.PHP_BIN || "php";

function run(script, name) {
  const output = execSync(`${php} tests/unit/php/${script} ${name}`, { cwd: root, encoding: "utf8", stdio: ["ignore", "pipe", "pipe"] });
  return JSON.parse(output);
}

test.describe("hook guard", () => {
  test("an add-on admin bar item that uses a class SurveyJS Forms removed no longer breaks wp-admin", () => {
    const { result, output, log } = run("guard-cases.php", "old-addon-admin-bar");
    expect(result.nodes).toEqual(["core-after-addon", "core-after-addon"]);
    expect(result.failures).toEqual([{ hook: "admin_bar_menu", callback: "SurveyJS_Addon_Dashboard::add_admin_bar_item", add_on: true }]);
    expect(log).toContain('Class "SurveyJS_Admin_Bar" not found');
    expect(log.match(/SurveyJS Forms: /g)).toHaveLength(1);
    expect(output).toContain("skipped part of SurveyJS Forms Add-on");
  });

  test("a failing filter of the plugin returns the value unchanged and later filters still run", () => {
    const { result, log } = run("guard-cases.php", "filter-keeps-value");
    expect(result).toBe("links+base");
    expect(log).toContain("SurveyJS_Test_Base::broken_filter");
    expect(log).toContain("TypeError");
  });

  test("a closure from the add-on folder is guarded", () => {
    const { result } = run("guard-cases.php", "add-on-closure");
    expect(result.value).toEqual(["deactivate"]);
    expect(result.failures[0].add_on).toBe(true);
  });

  test("plugin functions are guarded", () => {
    const { result } = run("guard-cases.php", "base-function");
    expect(result).toEqual([{ hook: "admin_init", callback: "wps_test_broken_function", add_on: false }]);
  });

  test("errors of other plugins are not swallowed", () => {
    expect(run("guard-cases.php", "foreign-callback").result).toBe("other plugin error");
  });

  test("hooks outside the list keep failing loudly", () => {
    expect(run("guard-cases.php", "unlisted-hook").result).toBe("sanitize failed");
  });

  test("remove_filter() with the original callback still works after the callback is guarded", () => {
    expect(run("guard-cases.php", "remove-after-wrap").result).toEqual(["body+base", true, "body"]);
  });

  test("the notice is shown only to people who manage plugins", () => {
    const { result, output } = run("guard-cases.php", "notice-hidden-without-capability");
    expect(result).toBe(1);
    expect(output).toBe("");
  });
});

test.describe("stored data of unexpected shape", () => {
  test("form settings of the wrong type fall back to the defaults", () => {
    expect(run("data-cases.php", "settings-wrong-types").result).toEqual({
      notificationReplyTo: "",
      notificationAttachments: "",
      notificationTo: "owner@example.com",
      webhooks: [],
      integrations: [{ service: "slack" }],
      storeEntries: false
    });
  });

  test("settings JSON that is not an object gives the defaults", () => {
    expect(run("data-cases.php", "settings-not-an-object").result).toBe(true);
  });

  test("questions whose name is not a string are skipped", () => {
    expect(run("data-cases.php", "question-name-not-scalar").result).toEqual(["a@example.com"]);
  });
});
