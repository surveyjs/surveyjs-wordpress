const fs = require("fs");
const path = require("path");
const Module = require("module");

const root = path.resolve(__dirname, "..");
const library = path.join(root, "src/trunk/libs/library");
const creatorFile = path.resolve(process.argv[2] || path.join(root, "../surveyjs-wordpress-pro/libs/survey-creator-core/survey-creator-core.min.js"));
const output = path.join(root, "src/trunk/ai/json-checks.md");

// The UMD bundles of the linter and of Creator require "survey-core"; both must get the plugin's own copy.
const resolve = Module._resolveFilename;
Module._resolveFilename = function (request, ...rest) {
    return request === "survey-core" ? path.join(library, "survey.core.min.js") : resolve.call(this, request, ...rest);
};
const Survey = require(path.join(library, "survey.core.min.js"));
const linter = require(path.join(library, "survey.linter.min.js"));
const creator = require(creatorFile);

const strings = creator.editorLocalization.getLocaleStrings("en").linter;
const creatorRules = creator.getCreatorLintOptions({
    validateJsonPropertyValues: new creator.SurveyCreatorModel({}).validateJsonPropertyValues,
    variablePresets: undefined,
    onLintSurvey: { fire: () => {} }
}).rules;

const rules = linter.getRules().map((rule) => ({
    id: rule.id,
    base: rule.defaultSeverity,
    creator: creatorRules[rule.id] || rule.defaultSeverity,
    messages: Object.values(strings.messages[rule.id] || {})
}));

function severity(rule) {
    if (rule.creator === rule.base) return rule.creator;
    return `${rule.creator} in Survey Creator, ${rule.base} on the Config tab without the add-on`;
}

const lines = [
    "# Checks of the form editor's JSON linter",
    "",
    `Generated from survey-creator-core ${creator.Version} (the English messages and severities of Survey Creator's JSON Editor) and the SurveyJS linter of survey-core ${Survey.Version} by \`scripts/update-json-checks.js\`. Do not hand-edit: the next run overwrites this file.`,
    "",
    "The form editor checks the survey JSON with the SurveyJS linter: the JSON Editor tab of Survey Creator with SurveyJS Forms Add-on, the Static analysis panel of the Config tab without the add-on. Every finding is an issue in \"Form saved with N issues\". Write JSON that passes every check below. When the person asks to fix the issues, change the JSON so that none of these messages applies any more. Words in braces are placeholders filled in from the JSON. A rule marked off does not run in that editor.",
    ""
];
rules.forEach((rule) => {
    lines.push(`## ${rule.id} (${severity(rule)})`, "");
    rule.messages.forEach((message) => lines.push("- " + message.replace(/\s+/g, " ")));
    if (rule.messages.length) lines.push("");
});

fs.writeFileSync(output, lines.join("\n"));
console.log(`${path.relative(root, output)}: ${rules.length} rules`);
