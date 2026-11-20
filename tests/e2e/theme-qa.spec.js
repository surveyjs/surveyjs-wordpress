const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { inspectSurvey } = require("./theme-qa-inspect");

const WP_CONTAINER = process.env.WP_CONTAINER;
const KITCHEN_SINK = JSON.parse(fs.readFileSync(path.join(__dirname, "fixtures/kitchen-sink.json"), "utf8"));
const WANTED_THEMES = (process.env.THEME_QA_THEMES || "twentytwentyfive,astra").split(",").map((slug) => slug.trim()).filter(Boolean);
const MODES = ["adaptive", "surveyjs"];
const MOBILE = { width: 390, height: 844 };
const EDITOR_VIEWPORT = { width: 1400, height: 2400 };
const CONTAINER_JSON = "/tmp/sjs-theme-qa-kitchen-sink.json";
const INFO_CHECKS = ["h-scroll"];
const SHOT_MARGIN = 8;
const ACCEPTED = [
  [/^theme-leak \S+: (max-width|min-width|min-height|vertical-align):/, "size limits and alignment from theme resets (img { max-width: 100% }, table { min-width }) that leave the controls as they are"],
  [/^theme-leak fieldset\S*: (margin-\w+|display):/, "the browser's 2px fieldset margin and Twenty Twenty-One's display: grid on fieldsets that hold a single child"],
  [/^theme-leak label\.sd-item\.sd-radio: /, "labels of matrix cells, which show no text"],
  [/^theme-leak span\.sd-item__decorator\.sd-radio__decorator: margin-top:/, "survey-core derives it from the label font size"],
  [/^clipped div\.sd-slider__tooltip-panel: /, "the value tooltip of a slider thumb at the minimum or maximum sticks out of a survey without side padding (open question)"]
];
const READ_ONLY_CONTRAST = "survey-core dims read-only captions; the default SurveyJS theme does the same";

const VARIANTS = [
  { name: "steps-numbers-only", props: { progressBarShowPageTitles: false } },
  { name: "steps-titles-only", props: { progressBarShowPageNumbers: false } },
  { name: "progress-pages-bottom", props: { progressBarShowPageTitles: false, progressBarShowPageNumbers: false, progressBarLocation: "bottom" } },
  { name: "progress-questions-topbottom", props: { progressBarType: "questions", progressBarLocation: "topbottom" } },
  { name: "toc-left", props: { showTOC: true } },
  { name: "toc-right-nav-top", props: { showTOC: true, tocLocation: "right", navigationButtonsLocation: "topBottom" } }
];

function fixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/theme-qa.php"))
  }).toString();
  return JSON.parse(output.slice(output.indexOf("{")));
}

function copyKitchenSink() {
  execFileSync("docker", ["exec", "-i", WP_CONTAINER, "sh", "-c", `cat > ${CONTAINER_JSON}`], { input: JSON.stringify(KITCHEN_SINK.survey) });
}

function galleryDir(testInfo) {
  return path.join(testInfo.project.outputDir, "theme-qa");
}

function themesToCheck() {
  const { active, installed } = fixture("themes");
  const themes = WANTED_THEMES.filter((slug) => installed.includes(slug));
  return { active, themes: themes.length ? themes : [active] };
}

function writeGallery(dir) {
  if (!fs.existsSync(dir)) return;
  const images = [];
  const walk = (folder) => fs.readdirSync(folder, { withFileTypes: true }).forEach((entry) => {
    const file = path.join(folder, entry.name);
    if (entry.isDirectory()) walk(file);
    else if (entry.name.endsWith(".png")) images.push(path.relative(dir, file).split(path.sep).join("/"));
  });
  walk(dir);
  const escape = (text) => String(text).replace(/[&<>"]/g, (char) => `&#${char.charCodeAt(0)};`);
  const items = images.sort().map((image) => {
    const key = image.replace(/\.png$/, "");
    const json = path.join(dir, `${key}.json`);
    const list = (fs.existsSync(json) ? JSON.parse(fs.readFileSync(json, "utf8")) : []).map((finding) => `<li${finding.accepted || INFO_CHECKS.includes(finding.check) ? ' class="minor"' : ""}><b>${escape(finding.check)}</b> ${escape(finding.element)}: ${escape(finding.detail)}</li>`).join("");
    return `<figure><figcaption>${escape(key)}</figcaption>${list ? `<ul>${list}</ul>` : ""}<a href="${image}"><img loading="lazy" src="${image}"></a></figure>`;
  });
  fs.writeFileSync(path.join(dir, "index.html"), `<!doctype html><meta charset="utf-8"><title>Theme QA</title><style>body{font:14px sans-serif;margin:16px}figure{margin:0 0 32px}img{max-width:100%;border:1px solid #ccc}ul{color:#b00}.minor{color:#777}</style>${items.join("")}`);
}

test.describe("theme QA: the kitchen sink form under WordPress themes", () => {
  test.skip(!WP_CONTAINER, "Set WP_CONTAINER to switch the site theme and create the kitchen sink form");

  let site;
  let originalTheme;

  test.beforeAll(() => {
    const { active, themes } = themesToCheck();
    originalTheme = active;
    copyKitchenSink();
    site = { themes, ...fixture("setup", `Theme QA ${Date.now()}`, CONTAINER_JSON) };
  });

  test.afterAll(async ({}, testInfo) => {
    if (!site) return;
    fixture("cleanup", site.formId, site.pageId);
    fixture("activate", originalTheme);
    writeGallery(galleryDir(testInfo));
  });

  async function capture(testInfo, target, frame, rootSelector, key) {
    await frame.evaluate(() => document.fonts.ready);
    await frame.waitForTimeout(300);
    const file = path.join(galleryDir(testInfo), `${key}.png`);
    const box = await target.boundingBox();
    const scroll = await target.page().evaluate(() => ({ x: window.scrollX, y: window.scrollY }));
    await target.page().screenshot({
      path: file,
      fullPage: true,
      clip: { x: Math.max(0, box.x + scroll.x - SHOT_MARGIN), y: Math.max(0, box.y + scroll.y - SHOT_MARGIN), width: box.width + SHOT_MARGIN * 2, height: box.height + SHOT_MARGIN * 2 },
      animations: "disabled",
      caret: "hide"
    });
    await testInfo.attach(key, { path: file, contentType: "image/png" });
    const findings = (await frame.evaluate(inspectSurvey, rootSelector)).map((finding) => {
      const text = `${finding.check} ${finding.element}: ${finding.detail}`;
      const accepted = ACCEPTED.find(([pattern]) => pattern.test(text));
      const dimmedReadOnly = finding.check === "low-contrast" && key.endsWith("/read-only") ? READ_ONLY_CONTRAST : undefined;
      return { ...finding, text, accepted: accepted ? accepted[1] : dimmedReadOnly };
    });
    fs.writeFileSync(file.replace(/\.png$/, ".json"), JSON.stringify(findings, null, 2));
    expect.soft(findings.filter((finding) => !finding.accepted && !INFO_CHECKS.includes(finding.check)).map((finding) => finding.text), key).toEqual([]);
  }

  for (const mode of MODES) {
    test(`front end, ${mode} theme`, async ({ page }, testInfo) => {
      const errors = [];
      page.on("pageerror", (error) => errors.push(error.message));
      fixture("mode", site.formId, mode);
      const surveyVar = `survey${site.formId}`;
      const survey = page.locator(".wp-sjs-survey");

      async function open() {
        await page.goto(site.url);
        await page.waitForFunction((name) => window[name] && document.querySelector(".sd-root-modern"), surveyVar);
        if (mode === "adaptive") await expect(page.locator(".wp-sjs-adaptive .sd-root-modern")).toBeVisible();
        else await expect(page.locator(".wp-sjs-adaptive")).toHaveCount(0);
      }

      async function apply(change, value) {
        await page.evaluate(({ name, change: source, value: arg }) => {
          const survey = window[name];
          const changes = {
            page: () => { survey.currentPageNo = arg; },
            props: () => Object.assign(survey, arg),
            filled: () => { survey.questionsOnPageMode = "singlePage"; survey.data = arg; },
            errors: () => { survey.questionsOnPageMode = "singlePage"; survey.validate(true, false); },
            readOnly: () => { survey.questionsOnPageMode = "singlePage"; survey.data = arg; survey.readOnly = true; },
            preview: () => { survey.showPreviewBeforeComplete = true; survey.data = arg; survey.currentPageNo = survey.visiblePages.length - 1; survey.showPreview(); }
          };
          changes[source]();
        }, { name: surveyVar, change, value });
        await page.waitForTimeout(200);
      }

      for (const wpTheme of site.themes) {
        fixture("activate", wpTheme);
        const prefix = `${wpTheme}/${mode}/front`;
        await page.setViewportSize({ width: 1400, height: 900 });
        await open();
        const pageCount = await page.evaluate((name) => window[name].visiblePages.length, surveyVar);
        for (let index = 0; index < pageCount; index++) {
          await apply("page", index);
          await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/page-${index + 1}`);
        }

        for (const variant of VARIANTS) {
          await open();
          await apply("props", variant.props);
          await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/${variant.name}`);
        }

        await open();
        await apply("filled", KITCHEN_SINK.data);
        await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/filled`);

        await open();
        await apply("errors");
        await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/errors`);

        await open();
        await apply("readOnly", KITCHEN_SINK.data);
        await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/read-only`);

        await open();
        await apply("preview", KITCHEN_SINK.data);
        await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/answers-preview`);

        await page.setViewportSize(MOBILE);
        await open();
        await apply("filled", KITCHEN_SINK.data);
        await capture(testInfo, survey, page, ".wp-sjs-survey .sd-root-modern", `${prefix}/mobile-filled`);
      }
      expect.soft(errors, "page errors").toEqual([]);
    });

    test(`editor preview, ${mode} theme`, async ({ page }, testInfo) => {
      page.on("dialog", (dialog) => dialog.accept());
      await page.setViewportSize(EDITOR_VIEWPORT);
      for (const wpTheme of site.themes) {
        fixture("activate", wpTheme);
        const prefix = `${wpTheme}/${mode}/editor`;
        await page.goto(`/wp-admin/admin.php?page=surveyjs_editor&id=${site.formId}`);
        await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
        await page.locator("#wp-sjs-theme-mode-adaptive").setChecked(mode === "adaptive");
        const preview = page.frameLocator("#wp-sjs-preview");
        if (mode === "adaptive") await expect(preview.locator(".wp-sjs-adaptive .sd-root-modern")).toBeVisible();
        else await expect(preview.locator(".sd-root-modern:not(.wp-sjs-adaptive .sd-root-modern)")).toBeVisible();
        const frame = page.frames().find((candidate) => candidate.url().includes("surveyjs_preview=1"));
        const panel = page.locator("#wp-sjs-preview-panel");

        const pageCount = KITCHEN_SINK.survey.pages.length;
        for (let index = 0; index < pageCount; index++) {
          await capture(testInfo, panel, frame, ".sd-root-modern", `${prefix}/page-${index + 1}`);
          if (index < pageCount - 1) await preview.locator(".sd-navigation__next-btn").click();
        }
        await preview.locator(".sd-navigation__complete-btn").click();
        await expect(preview.locator(".sd-question__erbox").first()).toBeVisible();
        await capture(testInfo, panel, frame, ".sd-root-modern", `${prefix}/errors`);
      }
    });
  }
});
