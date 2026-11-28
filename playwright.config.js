const path = require("path");
const { defineConfig } = require("@playwright/test");

const channel = process.env.PW_CHANNEL ?? "chrome";

module.exports = defineConfig({
  testDir: "tests/e2e",
  timeout: 180000,
  expect: { timeout: 30000 },
  workers: 1,
  retries: process.env.CI ? 2 : 0,
  reporter: process.env.CI ? [["github"], ["list"], ["blob"]] : "list",
  use: {
    baseURL: process.env.WP_BASE_URL || "http://localhost:9876",
    ...(channel && { channel }),
    viewport: { width: 1400, height: 900 },
    trace: "retain-on-failure",
    actionTimeout: 30000,
    navigationTimeout: 60000
  },
  projects: [
    { name: "unit", testDir: "tests/unit" },
    { name: "setup", testMatch: /auth\.setup\.js/ },
    {
      name: "e2e",
      dependencies: ["setup"],
      use: { storageState: path.join(__dirname, "tests/e2e/.auth/admin.json") }
    }
  ]
});
