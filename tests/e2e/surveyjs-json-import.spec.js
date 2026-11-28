const fs = require("fs");
const path = require("path");
const zlib = require("zlib");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, deleteSurveys } = require("./helpers");

const IMPORT_URL = "/wp-admin/admin.php?page=surveyjs_import&source=surveyjs";

function savedForms(name) {
  const output = execFileSync("docker", ["exec", "-i", process.env.WP_CONTAINER, "php", "--", name], { input: fs.readFileSync(path.join(__dirname, "fixtures/surveyjs-json.php")), stdio: ["pipe", "pipe", "ignore"] }).toString();
  return JSON.parse(output.trim().split("\n").pop());
}

function crc32(buffer) {
  let crc = -1;
  for (const byte of buffer) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit++) {
      crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
    }
  }
  return (crc ^ -1) >>> 0;
}

function zip(files) {
  const locals = [];
  const central = [];
  let offset = 0;
  for (const [name, text, deflate] of files) {
    const data = Buffer.from(text);
    const stored = deflate ? zlib.deflateRawSync(data) : data;
    const fileName = Buffer.from(name);
    const header = Buffer.alloc(30);
    header.writeUInt32LE(0x04034b50, 0);
    header.writeUInt16LE(20, 4);
    header.writeUInt16LE(deflate ? 8 : 0, 8);
    header.writeUInt32LE(crc32(data), 14);
    header.writeUInt32LE(stored.length, 18);
    header.writeUInt32LE(data.length, 22);
    header.writeUInt16LE(fileName.length, 26);
    const entry = Buffer.alloc(46);
    entry.writeUInt32LE(0x02014b50, 0);
    entry.writeUInt16LE(20, 4);
    entry.writeUInt16LE(20, 6);
    entry.writeUInt16LE(deflate ? 8 : 0, 10);
    entry.writeUInt32LE(crc32(data), 16);
    entry.writeUInt32LE(stored.length, 20);
    entry.writeUInt32LE(data.length, 24);
    entry.writeUInt16LE(fileName.length, 28);
    entry.writeUInt32LE(offset, 42);
    locals.push(header, fileName, stored);
    central.push(entry, fileName);
    offset += header.length + fileName.length + stored.length;
  }
  const directory = Buffer.concat(central);
  const end = Buffer.alloc(22);
  end.writeUInt32LE(0x06054b50, 0);
  end.writeUInt16LE(files.length, 8);
  end.writeUInt16LE(files.length, 10);
  end.writeUInt32LE(directory.length, 12);
  end.writeUInt32LE(offset, 16);
  return Buffer.concat([...locals, directory, end]);
}

function jsonFile(name, value) {
  return { name, mimeType: "application/json", buffer: Buffer.from(typeof value === "string" ? value : JSON.stringify(value)) };
}

function rowFor(page, name) {
  return page.locator(".wp-sjs-import-json tbody tr", { hasText: name });
}

test.describe("import SurveyJS JSON", () => {
  let uid;

  test.beforeEach(() => {
    uid = uniqueName("E2E JSON");
  });

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, uid);
  });

  test("imports form JSON files, a My Surveys survey, an export file and a .zip file", async ({ page }) => {
    test.slow();
    const theme = { themeName: "contrast", cssVariables: { "--sjs-primary-backcolor": "rgba(255, 0, 0, 1)" } };
    const files = [
      jsonFile("contact.json", { title: `${uid} Contact`, pages: [{ name: "page1", elements: [{ type: "text", name: "name" }] }] }),
      jsonFile("my-surveys.json", { id: "8d1c2f43-0000-4000-8000-000000000000", name: `${uid} Studio`, definition: { elements: [{ type: "rating", name: "score" }] }, theme }),
      jsonFile("export.json", {
        type: "surveyjs-forms",
        version: 1,
        forms: [
          { name: `${uid} Bundle A`, json: { elements: [{ type: "text", name: "email" }] }, settings: { notificationEnabled: true, notificationTo: "team@example.com", webhooks: [{ url: "https://example.com/hook" }] } },
          { name: `${uid} Bundle B`, json: { questions: [{ type: "comment", name: "note" }] } }
        ]
      }),
      jsonFile("broken.json", "{ not json"),
      jsonFile("other.json", { title: "Not a form" }),
      {
        name: "forms.zip",
        mimeType: "application/zip",
        buffer: zip([
          ["forms/one.json", JSON.stringify({ pages: [{ elements: [{ type: "boolean", name: "agree" }] }] }), true],
          ["forms/two.json", JSON.stringify({ title: { default: `${uid} Zip Two`, de: "Zwei" }, elements: [{ type: "text", name: "city" }] }), false],
          ["__MACOSX/forms/._one.json", "binary", false],
          ["forms/readme.txt", "skip me", true]
        ])
      }
    ];

    await page.goto(IMPORT_URL);
    await expect(page.locator(".nav-tab-active")).toHaveText("SurveyJS JSON");
    await page.setInputFiles("#wp-sjs-import-json-files", files);
    await expect(page.locator("[data-sjs-import-json-summary]")).toHaveText("Forms ready to import: 6. Skipped: 2.");
    await expect(page.locator(".wp-sjs-import-json tbody tr")).toHaveCount(8);
    await expect(rowFor(page, `${uid} Studio`)).toContainText("New form · with its theme");
    await expect(rowFor(page, "broken")).toContainText("Not valid JSON");
    await expect(rowFor(page, "broken").locator("input[type=checkbox]")).toBeDisabled();
    await expect(rowFor(page, "other.json")).toContainText("Not a SurveyJS form JSON");
    await expect(rowFor(page, "forms/one.json")).toContainText("one");
    await expect(rowFor(page, `${uid} Zip Two`)).toContainText("forms.zip › forms/two.json");

    await page.locator(".wp-sjs-import-json tbody tr", { hasText: "forms/one.json" }).locator("label").click();
    await page.getByRole("button", { name: "Import", exact: true }).click();
    await expect(page.locator(".wp-sjs-import-status")).toContainText("Import finished. Forms processed: 5.");
    for (const name of ["Contact", "Studio", "Bundle A", "Bundle B", "Zip Two"]) {
      await expect(page.locator(".wp-sjs-import-results li", { hasText: `${uid} ${name}` })).toContainText("imported");
    }
    await expect(page.locator(".wp-sjs-import-results li", { hasText: `${uid} Bundle A` })).toContainText("Webhooks and integrations were not imported");
    await expect(page.locator(".wp-sjs-import-results li", { hasText: `${uid} Bundle A` })).toContainText("The email notifications are on");

    const [studio] = savedForms(`${uid} Studio`);
    expect(studio.json).toEqual({ elements: [{ type: "rating", name: "score" }] });
    expect(studio.theme).toEqual(theme);
    expect(studio.settings.themeMode).toBe("surveyjs");
    const [bundle] = savedForms(`${uid} Bundle A`);
    expect(bundle.settings).toMatchObject({ notificationEnabled: true, notificationTo: "team@example.com", webhooks: [] });
    expect(savedForms(`${uid} Contact`)[0].settings).toMatchObject({ themeMode: "adaptive", minSubmitTimeEnabled: true });
    expect(savedForms("one")).toEqual([]);
  });

  test("adds pasted JSON and marks forms whose names exist", async ({ page }) => {
    const json = { title: `${uid} Pasted`, elements: [{ type: "text", name: "q1" }] };
    await page.goto(IMPORT_URL);
    await page.getByText("Paste JSON").click();
    await page.fill("#wp-sjs-import-json-text", JSON.stringify(json));
    await page.getByRole("button", { name: "Add to the list" }).click();
    await expect(rowFor(page, `${uid} Pasted`)).toContainText("New form");
    await page.fill("#wp-sjs-import-json-text", "[1, 2");
    await page.getByRole("button", { name: "Add to the list" }).click();
    await expect(page.locator("[data-sjs-import-json-summary]")).toHaveText("Forms ready to import: 1. Skipped: 1.");
    await page.getByRole("button", { name: "Clear the list" }).click();
    await expect(page.locator("[data-sjs-import-json-list]")).toBeHidden();

    await page.fill("#wp-sjs-import-json-text", JSON.stringify(json));
    await page.getByRole("button", { name: "Add to the list" }).click();
    await page.getByRole("button", { name: "Import", exact: true }).click();
    await expect(page.locator(".wp-sjs-import-status")).toContainText("Import finished. Forms processed: 1.");

    await page.goto(IMPORT_URL);
    await page.getByText("Paste JSON").click();
    await page.fill("#wp-sjs-import-json-text", JSON.stringify(json));
    await page.getByRole("button", { name: "Add to the list" }).click();
    await expect(rowFor(page, `${uid} Pasted`)).toContainText("A form with this name exists, a copy will be added");
  });

  test("exports forms from All Forms and imports the export file", async ({ page }) => {
    test.slow();
    const theme = { themeName: "flat", cssVariables: { "--sjs-general-backcolor": "rgba(250, 250, 250, 1)" } };
    await page.goto(IMPORT_URL);
    await page.setInputFiles("#wp-sjs-import-json-files", [
      jsonFile("first.json", { name: `${uid} First`, definition: { elements: [{ type: "text", name: "a", title: "Ünïcode / slash" }] }, theme }),
      jsonFile("second.json", { title: `${uid} Second`, pages: [{ name: "p", elements: [{ type: "checkbox", name: "b", choices: ["x", "y"] }] }] })
    ]);
    await page.getByRole("button", { name: "Import", exact: true }).click();
    await expect(page.locator(".wp-sjs-import-status")).toContainText("Import finished. Forms processed: 2.");

    await page.goto(listUrl(uid));
    await expect(page.locator("#bulk-action-selector-top option[value=export]")).toHaveText("Export");
    // A real download keeps the browser context from closing in Edge, so the bulk action URL is requested directly.
    const query = new URLSearchParams({ page: "surveyjs-main-menu", action: "export", _wpnonce: await page.locator("#wp-sjs-forms-list input[name=_wpnonce]").inputValue() });
    for (const id of await page.locator("#the-list input[name='survey[]']").evaluateAll(inputs => inputs.map(input => input.value))) {
      query.append("survey[]", id);
    }
    const response = await page.request.get(`/wp-admin/admin.php?${query}`);
    expect(response.headers()["content-disposition"]).toMatch(/attachment; filename="surveyjs-forms-.+\.json"/);
    const exported = await response.json();
    expect(exported).toMatchObject({ type: "surveyjs-forms", version: 1 });
    expect(exported.forms.map(form => form.name).sort()).toEqual([`${uid} First`, `${uid} Second`]);
    const first = exported.forms.find(form => form.name === `${uid} First`);
    expect(first.json).toEqual({ elements: [{ type: "text", name: "a", title: "Ünïcode / slash" }] });
    expect(first.theme).toEqual(theme);
    expect(first.settings).not.toHaveProperty("webhooks");

    await page.goto(IMPORT_URL);
    await page.setInputFiles("#wp-sjs-import-json-files", jsonFile("export.json", exported));
    await expect(rowFor(page, `${uid} First`)).toContainText("A form with this name exists, a copy will be added · with its theme");
    await page.getByRole("button", { name: "Import", exact: true }).click();
    await expect(page.locator(".wp-sjs-import-status")).toContainText("Import finished. Forms processed: 2.");
    const copies = savedForms(`${uid} First`);
    expect(copies).toHaveLength(2);
    expect(copies[0].json).toEqual(copies[1].json);
    expect(copies[0].theme).toEqual(theme);
  });
});
