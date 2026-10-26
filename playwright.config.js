const path = require("path");
const { defineConfig } = require("@playwright/test");

module.exports = defineConfig({
  testDir: "tests/e2e",
  timeout: 180000,
  expect: { timeout: 30000 },
  workers: 1,
  use: {
    baseURL: process.env.WP_BASE_URL || "http://localhost:9876",
    channel: "chrome",
    viewport: { width: 1400, height: 900 },
    trace: "retain-on-failure"
  },
  projects: [
    { name: "setup", testMatch: /auth\.setup\.js/ },
    {
      name: "e2e",
      dependencies: ["setup"],
      use: { storageState: path.join(__dirname, "tests/e2e/.auth/admin.json") }
    }
  ]
});
