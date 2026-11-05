const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, deleteSurveys, setEditorJson, saveSurvey } = require("./helpers");

const MAILPIT_URL = process.env.MAILPIT_URL || "http://localhost:8025";

async function findMail(request, subject) {
  let id = null;
  await expect.poll(async () => {
    const response = await request.get(`${MAILPIT_URL}/api/v1/search?query=${encodeURIComponent(`subject:"${subject}"`)}`);
    id = (await response.json()).messages[0]?.ID ?? null;
    return id;
  }, { timeout: 30000 }).not.toBeNull();
  return (await request.get(`${MAILPIT_URL}/api/v1/message/${id}`)).json();
}

test.describe("import from WPForms", () => {
  let name;
  let wpformsId;
  let postId;
  let restNonce;

  test.afterEach(async ({ page }) => {
    if (postId) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${postId}&force=true`, { headers: { "X-WP-Nonce": restNonce } });
    }
    if (wpformsId) {
      await page.goto("/wp-admin/admin.php?page=wpforms-overview");
      const trash = page.locator(`a[href*="action=trash"][href*="form_id=${wpformsId}&"]`).first();
      await page.goto(await trash.getAttribute("href"));
    }
    await deleteSurveys(page, name);
    postId = null;
    wpformsId = null;
  });

  async function createWpForm(page) {
    await page.goto("/wp-admin/admin.php?page=wpforms-builder&view=setup");
    const builderNonce = await page.evaluate(() => window.wpforms_builder?.nonce);
    test.skip(!builderNonce, "WPForms is not active");

    name = uniqueName("E2E WPForms");
    const created = await (await page.request.post("/wp-admin/admin-ajax.php", {
      form: { action: "wpforms_new_form", title: name, template: "simple-contact-form-template", nonce: builderNonce }
    })).json();
    wpformsId = Number(created.data.id);
  }

  async function importWpForm(page) {
    for (const checkbox of await page.locator('input[name="forms[]"]').all()) {
      await checkbox.setChecked(Number(await checkbox.getAttribute("value")) === wpformsId);
    }
    await page.getByRole("button", { name: "Import" }).click();
  }

  test("imports a WPForms form with its notification and confirmation", async ({ page }) => {
    await createWpForm(page);

    await page.goto("/wp-admin/admin.php?page=surveyjs-main-menu");
    await page.locator("#wpbody-content").getByRole("link", { name: "Import from WPForms" }).click();
    await expect(page).toHaveURL(/page=surveyjs_import&source=wpforms/);
    await importWpForm(page);

    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("imported");
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    await expect(page.locator(".monaco-editor")).toBeVisible();
    const json = JSON.parse(await page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue()));
    expect(json.elements.map(element => element.name)).toEqual(["name_first", "name_last", "email", "comment_or_message"]);
    expect(json.elements[1].startWithNewLine).toBe(false);
    expect(json.completedHtml).toContain("Thanks for contacting us!");

    await page.locator(".wp-sjs-editor__tabs").getByRole("link", { name: "Email", exact: true }).click();
    await expect(page.getByLabel("Send an email when the form is submitted")).toBeChecked();
    await expect(page.getByLabel("Send To")).toHaveValue("{admin_email}");
    await expect(page.getByLabel("Reply-To", { exact: true })).toHaveValue("email");

    restNonce = await (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": restNonce },
      data: { title: name, status: "publish", content: `[surveyjs id="${surveyId}"]` }
    })).json();
    postId = post.id;

    await page.goto(post.link);
    await page.getByLabel("First Name").fill("Jane");
    await page.getByLabel("Last Name").fill("Roe");
    await page.getByLabel("Email").fill("jane@example.com");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Submit" }).click();
    expect((await saved).ok()).toBe(true);
    await expect(page.locator(".sd-completedpage")).toContainText("Thanks for contacting us!");

    const mail = await findMail(page.request, `New entry: ${name}`);
    expect(mail.ReplyTo.map(to => to.Address)).toEqual(["jane@example.com"]);
    expect(mail.Text).toContain("First Name: Jane");
  });

  test("re-import updates the imported form instead of adding a copy", async ({ page }) => {
    await createWpForm(page);
    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=wpforms");
    await expect(page.getByLabel("Import entries").or(page.getByText("WPForms Lite does not keep entries on your site"))).toBeVisible();
    await importWpForm(page);
    const result = page.locator(".wrap li", { hasText: name });
    await expect(result).toContainText("imported");
    await result.getByRole("link", { name: "Edit" }).click();
    const surveyId = Number(new URL(page.url()).searchParams.get("id"));
    await expect(page.locator(".monaco-editor")).toBeVisible();
    await setEditorJson(page, { elements: [{ type: "text", name: "edited" }] });
    await saveSurvey(page);

    await page.goto("/wp-admin/admin.php?page=surveyjs_import&source=wpforms");
    await expect(page.locator("p", { has: page.locator(`input[value="${wpformsId}"]`) })).toContainText("imported before, will be updated");
    await importWpForm(page);
    await expect(result).toContainText("updated");
    await result.getByRole("link", { name: "Edit" }).click();
    expect(Number(new URL(page.url()).searchParams.get("id"))).toBe(surveyId);
    await expect(page.locator(".monaco-editor")).toBeVisible();
    const json = JSON.parse(await page.evaluate(() => window.monaco.editor.getEditors()[0].getModel().getValue()));
    expect(json.elements.map(element => element.name)).toEqual(["name_first", "name_last", "email", "comment_or_message"]);

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list tr")).toHaveCount(1);
  });
});
