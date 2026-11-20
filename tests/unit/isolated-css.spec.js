const fs = require("fs");
const { test, expect } = require("@playwright/test");
const { buildIsolatedCss } = require("../../scripts/isolate-css.js");

const normalize = (text) => text.replace(/\r\n/g, "\n");

test("src/trunk/isolated is up to date; run npm run build:isolated-css", () => {
  for (const { file, name, css } of buildIsolatedCss()) {
    expect(fs.existsSync(file), name).toBe(true);
    expect(normalize(fs.readFileSync(file, "utf8")) === normalize(css), `${name} is stale; run npm run build:isolated-css`).toBe(true);
  }
});
