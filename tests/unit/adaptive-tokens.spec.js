const fs = require("fs");
const path = require("path");
const { test, expect } = require("@playwright/test");

const trunk = path.join(__dirname, "../../src/trunk");

function cssVariables(file) {
  const css = fs.readFileSync(path.join(trunk, file), "utf8");
  return new Set(css.match(/--(?:sjs2|sd)-[\w-]+/g));
}

test("the adaptive style uses only variables that survey-core knows", () => {
  const core = cssVariables("libs/library/survey-core.min.css");
  const unknown = [...cssVariables("themes/wordpress.css")].filter((name) => !core.has(name));
  expect(unknown).toEqual([]);
});

test("the AI theme prompt lists only variables that survey-core knows", () => {
  const core = cssVariables("libs/library/survey-core.min.css");
  const editor = fs.readFileSync(path.join(trunk, "editor/editor.js"), "utf8");
  const promptTokens = /const PROMPT_TOKENS = \[([\s\S]*?)\n\s*\];/.exec(editor)[1].match(/^\s*\["(--sjs2-[\w-]+)"/gm).map((line) => line.trim().slice(2, -1));
  expect(promptTokens.length).toBeGreaterThan(0);
  expect(promptTokens.filter((name) => !core.has(name))).toEqual([]);
});
