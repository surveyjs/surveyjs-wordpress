const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, deleteSurveys } = require("./helpers");

const LIST_URL = "/wp-admin/admin.php?page=surveyjs-main-menu";
const TEMPLATES_URL = "/wp-admin/admin.php?page=surveyjs-templates";
const MY_TEMPLATES_URL = `${TEMPLATES_URL}&category=my-templates`;
const ROI_BRAND_COLOR = "#8B5CF6";

async function useTemplate(page, id, name) {
  await page.goto(TEMPLATES_URL);
  await page.getByLabel("Form name").fill(name);
  await page.locator(`.wp-sjs-template[data-template="${id}"]`).getByRole("button", { name: "Use template" }).click();
  await expect(page).toHaveURL(/page=surveyjs_editor/);
  await expect(page.locator("#wp-sjs-name-text")).toHaveText(name);
}

async function openEmailTab(page) {
  await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true }).click();
  return page.locator("[data-setting=notificationEnabled]");
}

async function openThemeTab(page) {
  await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Theme", exact: true }).click();
  return page.locator("#wp-sjs-theme-mode-adaptive");
}

async function expectBrandColor(root, color) {
  await expect(root).toBeVisible();
  await expect.poll(() => root.evaluate(element => getComputedStyle(element).getPropertyValue("--sjs2-color-project-brand-600").trim())).toBe(color);
}

async function deleteTemplates(page, name) {
  await page.goto(MY_TEMPLATES_URL);
  const cards = page.locator(".wp-sjs-template--mine").filter({ hasText: name });
  while (await cards.count()) {
    page.once("dialog", dialog => dialog.accept());
    await cards.first().getByRole("link", { name: "Delete" }).click();
    await expect(page.locator(".notice-success")).toContainText("Template deleted");
  }
}

test.describe("form templates", () => {
  let name;

  test.beforeEach(() => {
    name = uniqueName("E2E template");
  });

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
    await deleteTemplates(page, name);
  });

  test("Add New Form opens the templates with Blank Form and Contact Forms first", async ({ page }) => {
    await page.goto(LIST_URL);
    await page.locator("#wpbody-content").getByRole("link", { name: "Add New Form" }).click();
    await expect(page).toHaveURL(/page=surveyjs-templates/);
    await expect(page.locator(".wp-sjs-template").first()).toContainText("Blank Form");
    await expect(page.locator(".wp-sjs-template[data-template]").first()).toHaveAttribute("data-template", "contact-form");
    const categories = page.locator(".wp-sjs-templates__category");
    await expect(categories.nth(0)).toContainText("All Templates");
    await expect(categories.nth(1)).toContainText("My Templates");
    await expect(categories.nth(2)).toContainText("Contact Forms");
    await expect(page.locator("#toplevel_page_surveyjs-main-menu").getByRole("link", { name: "Add New Form" })).toBeVisible();
  });

  test("filters by category and search and lists a template of two categories once", async ({ page }) => {
    await page.goto(TEMPLATES_URL);
    const cards = page.locator(".wp-sjs-template[data-template]");
    const visible = page.locator(".wp-sjs-template[data-template]:visible");
    const ids = await cards.evaluateAll(items => items.map(item => item.dataset.template));
    expect(new Set(ids).size).toBe(ids.length);
    await expect(page.locator(".wp-sjs-templates__category[data-category=''] .count")).toHaveText(String(ids.length));
    await expect(page.locator(".wp-sjs-template[data-template=business-inquiry-form]")).toHaveCount(1);

    await page.getByRole("button", { name: /^Contact Forms/ }).click();
    await expect(visible.first()).toBeVisible();
    for (const categories of await visible.evaluateAll(items => items.map(item => item.dataset.categories))) {
      expect(categories.split(" ")).toContain("contact");
    }
    await expect(page.locator(".wp-sjs-template[data-template=business-inquiry-form]")).toBeVisible();
    await page.getByRole("button", { name: /^Lead Generation/ }).click();
    await expect(page.locator(".wp-sjs-template[data-template=business-inquiry-form]")).toBeVisible();

    await page.getByRole("button", { name: /^All Templates/ }).click();
    await page.getByLabel("Search templates").fill("pizza order");
    await expect(visible).toHaveCount(1);
    await expect(visible).toHaveAttribute("data-template", "pizza-order-form");
    await page.getByLabel("Search templates").press("Enter");
    await expect(page).toHaveURL(/page=surveyjs-templates/);

    await page.getByLabel("Search templates").fill("zzzz-no-match");
    await expect(visible).toHaveCount(0);
    await expect(page.locator(".wp-sjs-templates__empty")).toBeVisible();
    await expect(page.locator(".wp-sjs-template--blank")).toBeVisible();
  });

  test("previews a template in its theme and creates the form from the preview", async ({ page }) => {
    await page.goto(TEMPLATES_URL);
    await page.getByLabel("Form name").fill(name);
    await page.locator(".wp-sjs-template[data-template=roi-calculator]").getByRole("button", { name: "Preview" }).click();
    const dialog = page.locator("#wp-sjs-template-preview");
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole("heading", { name: "ROI Calculator" })).toBeVisible();
    const frame = page.frameLocator(".wp-sjs-templates__dialog-frame");
    await expectBrandColor(frame.locator(".sd-root-modern"), ROI_BRAND_COLOR);
    await expect(frame.locator(".sd-root-modern")).not.toHaveClass(/wp-sjs-adaptive/);
    await dialog.getByRole("button", { name: "Close", exact: true }).click();
    await expect(dialog).toBeHidden();

    await page.locator(".wp-sjs-template[data-template=contact-form]").getByRole("button", { name: "Preview" }).click();
    await expect(frame.locator(".sd-question__title").first()).toContainText("Your name");
    await dialog.getByRole("button", { name: "Use template" }).click();
    await expect(page).toHaveURL(/page=surveyjs_editor/);
    await expect(page.locator("#wp-sjs-name-text")).toHaveText(name);
  });

  test("a Contact Forms template turns on the email notification with Reply-To and attachments", async ({ page }) => {
    await useTemplate(page, "contact-form-with-file-upload", name);
    await expect(await openEmailTab(page)).toBeChecked();
    await expect(page.locator("#wp-sjs-notification-reply-to")).toHaveValue("email");
    await expect(page.locator("[data-setting=notificationAttachments]")).toHaveValue("attachments");
  });

  test("a template of another category turns on the email notification and applies its theme", async ({ page }) => {
    await useTemplate(page, "roi-calculator", name);
    await expect(await openEmailTab(page)).toBeChecked();
    await expect(page.locator("#wp-sjs-notification-reply-to")).toHaveValue("email");
    await expect(await openThemeTab(page)).not.toBeChecked();
    const preview = page.frameLocator("#wp-sjs-preview").locator(".sd-root-modern");
    await expectBrandColor(preview, ROI_BRAND_COLOR);
    await expect(preview).not.toHaveClass(/wp-sjs-adaptive/);
  });

  test("Blank Form creates an empty form with the email notification on and the Adaptive theme", async ({ page }) => {
    await page.goto(TEMPLATES_URL);
    await page.getByLabel("Form name").fill(name);
    await page.getByRole("button", { name: "Create blank form" }).click();
    await expect(page).toHaveURL(/page=surveyjs_editor/);
    await expect(page.locator("#wp-sjs-name-text")).toHaveText(name);
    await expect(await openEmailTab(page)).toBeChecked();
    await expect(await openThemeTab(page)).toBeChecked();
  });

  test("saves a form as a template, creates a form from it and deletes the template", async ({ page }) => {
    const templateName = `${name} template`;
    await useTemplate(page, "roi-calculator", name);
    const formId = new URL(page.url()).searchParams.get("id");

    await page.getByRole("link", { name: "Save as Template" }).click();
    await expect(page.getByRole("heading", { name: "Save as Template" })).toBeVisible();
    await expect(page.getByLabel("Template name")).toHaveValue(name);
    await page.getByLabel("Template name").fill(templateName);
    await page.getByLabel("Description").fill("E2E description");
    await page.getByRole("button", { name: "Save template" }).click();

    await expect(page).toHaveURL(/category=my-templates/);
    await expect(page.locator(".notice-success")).toContainText("Template saved");
    await expect(page.getByRole("button", { name: /^My Templates/ })).toHaveAttribute("aria-pressed", "true");
    const card = page.locator(".wp-sjs-template--mine").filter({ hasText: templateName });
    await expect(card).toBeVisible();
    await expect(card).toContainText("E2E description");
    await expect(page.locator(".wp-sjs-template[data-template]:visible")).toHaveCount(await page.locator(".wp-sjs-template--mine").count());

    await card.getByRole("button", { name: "Preview" }).click();
    await expectBrandColor(page.frameLocator(".wp-sjs-templates__dialog-frame").locator(".sd-root-modern"), ROI_BRAND_COLOR);
    await page.locator("#wp-sjs-template-preview").getByRole("button", { name: "Close", exact: true }).click();

    await page.getByLabel("Form name").fill(`${name} copy`);
    await card.getByRole("button", { name: "Use template" }).click();
    await expect(page).toHaveURL(/page=surveyjs_editor/);
    await expect(page.locator("#wp-sjs-name-text")).toHaveText(`${name} copy`);
    await expect(await openEmailTab(page)).toBeChecked();
    await expect(page.locator("#wp-sjs-notification-reply-to")).toHaveValue("email");
    await expect(await openThemeTab(page)).not.toBeChecked();
    await expectBrandColor(page.frameLocator("#wp-sjs-preview").locator(".sd-root-modern"), ROI_BRAND_COLOR);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr").filter({ has: page.getByRole("link", { name, exact: true }) });
    await expect(row.getByRole("link", { name: "Save as Template" })).toHaveAttribute("href", new RegExp(`page=surveyjs-templates&(amp;)?form=${formId}$`));

    await page.goto(MY_TEMPLATES_URL);
    page.once("dialog", dialog => dialog.accept());
    await card.getByRole("link", { name: "Delete" }).click();
    await expect(page.locator(".notice-success")).toContainText("Template deleted");
    await expect(card).toHaveCount(0);
  });

  test("My Templates explains how to save a template when there are none", async ({ page }) => {
    await page.goto(MY_TEMPLATES_URL);
    test.skip(await page.locator(".wp-sjs-template--mine").count() > 0, "the site already has user templates");
    await expect(page.locator(".wp-sjs-templates__empty-mine")).toBeVisible();
    await expect(page.locator(".wp-sjs-templates__empty")).toBeHidden();
  });
});
