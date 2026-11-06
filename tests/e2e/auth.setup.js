const path = require("path");
const { test: setup, expect } = require("@playwright/test");

setup("log in as admin", async ({ page }) => {
  await page.goto("/wp-login.php");
  await page.fill("#user_login", process.env.WP_USERNAME || "admin");
  await page.fill("#user_pass", process.env.WP_PASSWORD || "");
  await page.click("#wp-submit");
  await expect(page.locator("#wpadminbar")).toBeVisible();
  await page.context().storageState({ path: path.join(__dirname, ".auth/admin.json") });
});
