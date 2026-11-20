const fs = require("fs");
const path = require("path");
const vm = require("vm");
const { test, expect } = require("@playwright/test");

const trunk = path.join(__dirname, "../../src/trunk");

function loadLinter() {
  const context = { console, setTimeout, clearTimeout, navigator: { userAgent: "node" } };
  context.globalThis = context;
  context.self = context;
  vm.createContext(context);
  for (const file of ["survey.core.min.js", "survey.linter.min.js"]) {
    vm.runInContext(fs.readFileSync(path.join(trunk, "libs/library", file), "utf8"), context, { filename: file });
  }
  return context.SurveyLinter;
}

function templateForms(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const file = path.join(dir, entry.name);
    if (entry.isDirectory()) return templateForms(file);
    return entry.name === "form.json" ? [file] : [];
  });
}

test("the bundled templates have no linter findings", () => {
  const linter = loadLinter();
  const forms = templateForms(path.join(trunk, "templates"));
  expect(forms.length).toBeGreaterThan(0);
  const findings = forms.flatMap((file) => linter.lintSurvey(JSON.parse(fs.readFileSync(file, "utf8"))).findings
    .map((finding) => `${path.relative(trunk, file).split(path.sep).join("/")}: ${finding.ruleId} at ${finding.path}: ${finding.message}`));
  expect(findings).toEqual([]);
});

test("the theme QA kitchen sink has no linter findings", () => {
  const { survey } = JSON.parse(fs.readFileSync(path.join(__dirname, "../e2e/fixtures/kitchen-sink.json"), "utf8"));
  expect(loadLinter().lintSurvey(survey).findings.map((finding) => `${finding.ruleId} at ${finding.path}: ${finding.message}`)).toEqual([]);
});
