const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, addSurvey, renameSurvey, createSurvey, deleteSurveys } = require("./helpers");

test.describe("survey list", () => {
  let name;

  test.afterEach(async ({ page }) => {
    await deleteSurveys(page, name);
  });

  test("adds a survey", async ({ page }) => {
    name = uniqueName("E2E add");
    await addSurvey(page);
    await renameSurvey(page, name);

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .row-title")).toHaveText([name]);
  });

  test("saves survey changes made in the editor", async ({ page }) => {
    name = uniqueName("E2E edit");
    await createSurvey(page, name);

    await page.reload();
    await expect(page.locator(".svc-creator").getByText("What is your name?")).toBeVisible();
  });

  test("clones a survey", async ({ page }) => {
    name = uniqueName("E2E clone");
    await createSurvey(page, name);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    await row.hover();
    await row.getByRole("link", { name: "Clone", exact: true }).click();
    await expect(page.locator(".notice-success")).toContainText("Survey cloned.");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .row-title")).toHaveText([name, `Copy of ${name}`]);

    await page.locator("#the-list .row-title", { hasText: `Copy of ${name}` }).click();
    await expect(page.locator(".svc-creator").getByText("What is your name?")).toBeVisible();
  });

  test("deletes a survey", async ({ page }) => {
    name = uniqueName("E2E delete");
    await createSurvey(page, name);

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    await row.hover();
    page.once("dialog", dialog => dialog.accept());
    await row.getByRole("link", { name: "Delete", exact: true }).click();
    await expect(page.locator(".notice-success")).toContainText("1 survey deleted.");

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });
});
