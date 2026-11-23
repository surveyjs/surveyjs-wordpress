const path = require("path");
const { test, expect } = require("@playwright/test");

const trunk = path.join(__dirname, "../../src/trunk");
const SITE_BUTTON = "rgb(4, 107, 210)";

for (const [where, markup] of [
  ["on the isolation container (front end)", "<div class=\"wp-sjs-survey\" id=\"target\"></div>"],
  ["inside the isolation container (editor previews)", "<div class=\"wp-sjs-survey\"><div id=\"target\"></div></div>"]
]) {
  test(`the adaptive style reads the site's button ${where}`, async ({ page }) => {
    await page.setContent(`<main class="entry-content">${markup}</main>`);
    await page.addStyleTag({ content: `.wp-block-button__link{background-color:${SITE_BUTTON};padding:12px 24px}` });
    await page.addStyleTag({ path: path.join(trunk, "isolated/survey-core.min.css") });
    await page.addScriptTag({ path: path.join(trunk, "themes/wordpress.js") });
    const styles = await page.evaluate(() => SurveyWordPress.getWordPressStyles(document.getElementById("target")));
    expect(styles["--sjs-wp-primary"]).toBe(SITE_BUTTON);
    expect(styles["--sjs-wp-button-padding-horizontal"]).toBe("24px");
  });
}
