const path = require("path");
const { test: setup, expect } = require("@playwright/test");

setup("log in as admin", async ({ page }) => {
  await page.goto("/wp-login.php");
  // wp-login.php focuses and selects the username 200 ms after load; typing before that lands the password in the username.
  await expect(page.locator("#user_login")).toBeFocused();
  await page.fill("#user_login", process.env.WP_USERNAME || "admin");
  await page.fill("#user_pass", process.env.WP_PASSWORD || "");
  await page.click("#wp-submit");
  await expect(page.locator("#wpadminbar")).toBeVisible();
  await page.context().storageState({ path: path.join(__dirname, ".auth/admin.json") });
});
