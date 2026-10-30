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
