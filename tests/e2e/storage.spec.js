const fs = require("fs");
const path = require("path");
const { execFileSync } = require("child_process");
const { test, expect } = require("@playwright/test");
const { uniqueName, listUrl, createSurvey, deleteSurveys } = require("./helpers");

const WP_CONTAINER = process.env.WP_CONTAINER;
const FORMS_URL = "/wp-admin/admin.php?page=surveyjs-main-menu";
const SETTINGS_URL = "/wp-admin/admin.php?page=surveyjs-settings";
const STORE_IP_LABEL = "Store the IP address of the person who submits a form";

const FILE_JSON = { elements: [{ type: "file", name: "document", title: "Your document" }] };
const CONTACT_JSON = {
  elements: [
    { type: "text", name: "visitor_name", title: "Your name" },
    { type: "text", name: "visitor_email", title: "Your email", inputType: "email" }
  ]
};

function storageFixture(...args) {
  const output = execFileSync("docker", ["exec", "-i", WP_CONTAINER, "php", "--", ...args.map(String)], {
    input: fs.readFileSync(path.join(__dirname, "fixtures/storage.php")),
    stdio: ["pipe", "pipe", "ignore"]
  });
  return JSON.parse(output.toString().trim().split("\n").pop());
}

function resultsUrl(surveyId, status) {
  return `/wp-admin/admin.php?page=surveyjs_results&id=${surveyId}${status ? `&status=${status}` : ""}`;
}

async function restNonce(page) {
  return (await page.request.get("/wp-admin/admin-ajax.php?action=rest-nonce")).text();
}

async function surveyConfig(page, link) {
  const html = await (await page.request.get(link)).text();
  return JSON.parse(html.match(/<script type="application\/json" class="wp-sjs-survey-config">([\s\S]*?)<\/script>/)[1]);
}

async function submitEntry(page, config, data) {
  const response = await page.request.post(config.saveResultUrl, {
    form: { SurveyId: String(config.id), Json: JSON.stringify(data), Website: "", _wpnonce: config.saveResultNonce }
  });
  expect(response.ok()).toBe(true);
}

async function submitWithFile(page, link, fileName) {
  await page.goto(link);
  const uploaded = page.waitForResponse(r => r.url().includes("action=SurveyJS_UploadFiles"));
  const fileChooser = page.waitForEvent("filechooser");
  await page.locator(".wp-sjs-survey .sd-file__choose-btn").first().click();
  await (await fileChooser).setFiles({ name: fileName, mimeType: "text/plain", buffer: Buffer.from(`E2E storage ${fileName}`) });
  expect((await uploaded).ok()).toBe(true);
  const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
  await page.getByRole("button", { name: "Complete" }).click();
  expect((await saved).ok()).toBe(true);
}

function entryRow(page, text) {
  return page.locator("#the-list tr", { hasText: text });
}

async function rowAction(page, text, action) {
  const row = entryRow(page, text);
  await row.hover();
  await row.getByRole("link", { name: action, exact: true }).click();
}

async function bulkAction(page, texts, action) {
  for (const text of texts) {
    await entryRow(page, text).locator('input[name="result[]"]').check();
  }
  await page.selectOption("#bulk-action-selector-top", action);
  await page.click("#doaction");
}

async function expectCounts(page, counts) {
  for (const [view, count] of Object.entries(counts)) {
    await expect(page.locator(`.subsubsub .${view} .count`)).toHaveText(`(${count})`);
  }
}

async function unreadTotal(page) {
  const bubble = page.locator("#wp-admin-bar-surveyjs .wp-sjs-ab-count:not(.wp-sjs-ab-count--expiring)");
  return (await bubble.count()) ? Number((await bubble.textContent()).replace(/\D/g, "")) : 0;
}

async function setStoreIp(page, checked) {
  await page.goto(SETTINGS_URL);
  await page.getByLabel(STORE_IP_LABEL).setChecked(checked);
  await page.getByRole("button", { name: "Save Changes" }).click();
  await expect(page.getByText("Successfully updated!")).toBeVisible();
  await expect(page.getByLabel(STORE_IP_LABEL)).toBeChecked({ checked });
}

async function addPrivacyRequest(page, screen, email) {
  await page.goto(`/wp-admin/${screen}`);
  await page.fill("#username_or_email_for_privacy_request", email);
  await page.uncheck("#send_confirmation_email");
  await page.getByRole("button", { name: "Send Request" }).click();
  const row = page.locator("#the-list tr", { hasText: email });
  await expect(row).toHaveClass(/status-request-confirmed/);
  return row;
}

async function deletePrivacyRequests(page, email) {
  for (const screen of ["export-personal-data.php", "erase-personal-data.php"]) {
    const url = `/wp-admin/${screen}?s=${encodeURIComponent(email)}`;
    await page.goto(url);
    if (await page.locator("#the-list .no-items").count()) {
      continue;
    }
    await page.check("#cb-select-all-1");
    await page.selectOption("#bulk-action-selector-top", "delete");
    await page.click("#doaction");
    await page.goto(url);
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  }
}

function collectAjax(page, action) {
  const bodies = [];
  const listener = response => {
    if (response.url().includes("admin-ajax.php") && (response.request().postData() || "").includes(`action=${action}`)) {
      bodies.push(response.json());
    }
  };
  page.on("response", listener);
  return async () => {
    page.off("response", listener);
    return Promise.all(bodies);
  };
}

test.describe("entry storage", () => {
  let name;
  let posts;
  let legacyId;
  let legacyRowId;
  let privacyEmail;
  let storeIp;

  async function createPost(page, title, content) {
    const nonce = await restNonce(page);
    const post = await (await page.request.post("/?rest_route=/wp/v2/posts", {
      headers: { "X-WP-Nonce": nonce },
      data: { title, status: "publish", content }
    })).json();
    posts.push({ id: post.id, nonce });
    return post;
  }

  test.beforeEach(() => {
    name = null;
    posts = [];
    legacyId = null;
    legacyRowId = null;
    privacyEmail = null;
    storeIp = null;
  });

  test.afterEach(async ({ page }) => {
    if (legacyId) {
      storageFixture("legacy-cleanup", legacyId);
    }
    if (legacyRowId) {
      storageFixture("legacy-row-cleanup", legacyRowId);
    }
    if (privacyEmail) {
      await deletePrivacyRequests(page, privacyEmail);
    }
    if (storeIp !== null) {
      await setStoreIp(page, storeIp);
    }
    for (const post of posts) {
      await page.request.delete(`/?rest_route=/wp/v2/posts/${post.id}&force=true`, { headers: { "X-WP-Nonce": post.nonce } });
    }
    if (name) {
      await deleteSurveys(page, name);
    }
  });

  test("stores a submitted entry as unread with its date and user and marks it read when viewed", async ({ page }) => {
    name = uniqueName("E2E entry");
    const surveyId = await createSurvey(page, name);
    const post = await createPost(page, name, `[surveyjs id="${surveyId}"]`);
    const me = await (await page.request.get("/?rest_route=/wp/v2/users/me", { headers: { "X-WP-Nonce": await restNonce(page) } })).json();

    await page.goto(post.link);
    await page.getByLabel("What is your name?").fill("Jane");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    await page.goto(resultsUrl(surveyId));
    const header = page.locator("table.wp-list-table thead");
    await expect(header.locator("th.column-created_at")).toContainText("Date");
    await expect(header.locator("th.column-status")).toHaveText("Status");
    await expect(header.locator("th.column-submitter")).toHaveText("Submitted by");

    const row = entryRow(page, "Jane");
    const status = row.locator("td.column-status");
    await expect(row).toHaveClass(/wp-sjs-entry-unread/);
    await expect(status).toHaveText("Unread");
    await expect(status).toHaveCSS("font-weight", "600");
    await expect(row.locator("td.column-created_at")).toHaveText(/\d{4}/);
    await expect(row.locator("td.column-submitter a")).toHaveText(me.name);
    await expectCounts(page, { all: 1, unread: 1, read: 0, spam: 0 });

    const markedRead = page.waitForResponse(r => r.url().includes("admin-ajax.php") && (r.request().postData() || "").includes("action=surveyjs_entry_read"));
    await row.hover();
    await row.getByRole("link", { name: "View", exact: true }).click();
    await expect(page.locator("#wp-sjs-result-popup").getByLabel("What is your name?")).toHaveValue("Jane");
    expect((await markedRead).ok()).toBe(true);
    await expect(row).not.toHaveClass(/wp-sjs-entry-unread/);
    await expect(status).toHaveText("Read");
    await expect(status).toHaveCSS("font-weight", "400");

    await page.reload();
    await expect(status).toHaveText("Read");
    await expect(row).not.toHaveClass(/wp-sjs-entry-unread/);
    await expectCounts(page, { all: 1, unread: 0, read: 1, spam: 0 });
  });

  test("changes entry statuses, keeps spam out of All and the CSV, and deletes entries", async ({ page }) => {
    name = uniqueName("E2E statuses");
    const surveyId = await createSurvey(page, name);
    const post = await createPost(page, name, `[surveyjs id="${surveyId}"]`);
    const config = await surveyConfig(page, post.link);
    for (const answer of ["E2E Alpha", "E2E Bravo", "E2E Charlie"]) {
      await submitEntry(page, config, { e2e_question: answer });
    }

    await page.goto(resultsUrl(surveyId));
    await expectCounts(page, { all: 3, unread: 3, read: 0, spam: 0 });

    await rowAction(page, "E2E Alpha", "Mark as Read");
    await expect(page.locator(".notice-success")).toContainText("1 result updated.");
    await expect(entryRow(page, "E2E Alpha").locator("td.column-status")).toHaveText("Read");
    await expectCounts(page, { all: 3, unread: 2, read: 1, spam: 0 });

    await rowAction(page, "E2E Alpha", "Mark as Unread");
    await expect(entryRow(page, "E2E Alpha").locator("td.column-status")).toHaveText("Unread");
    await expectCounts(page, { all: 3, unread: 3, read: 0, spam: 0 });

    await rowAction(page, "E2E Bravo", "Spam");
    await expect(page.locator(".notice-success")).toContainText("1 result updated.");
    await expect(entryRow(page, "E2E Bravo")).toHaveCount(0);
    await expectCounts(page, { all: 2, unread: 2, read: 0, spam: 1 });

    await page.locator(".subsubsub .spam a").click();
    await expect(page).toHaveURL(/status=spam/);
    await expect(page.locator("#the-list tr")).toHaveCount(1);
    await expect(entryRow(page, "E2E Bravo").locator("td.column-status")).toHaveText("Spam");
    await rowAction(page, "E2E Bravo", "Not Spam");
    await expect(page.locator(".notice-success")).toContainText("1 result updated.");
    await expect(page.locator("#the-list .no-items")).toBeVisible();
    await expectCounts(page, { all: 3, unread: 2, read: 1, spam: 0 });

    await page.goto(resultsUrl(surveyId));
    await bulkAction(page, ["E2E Alpha", "E2E Charlie"], "read");
    await expect(page.locator(".notice-success")).toContainText("2 results updated.");
    await expectCounts(page, { all: 3, unread: 0, read: 3, spam: 0 });

    await bulkAction(page, ["E2E Charlie"], "spam");
    await expect(page.locator(".notice-success")).toContainText("1 result updated.");
    await expect(entryRow(page, "E2E Charlie")).toHaveCount(0);
    await expectCounts(page, { all: 2, unread: 0, read: 2, spam: 1 });

    const csvResponse = await page.request.get(await page.getByRole("link", { name: "Export CSV" }).getAttribute("href"));
    expect(csvResponse.headers()["content-type"]).toContain("text/csv");
    const lines = (await csvResponse.text()).replace(/^﻿/, "").split(/\r?\n/).filter(Boolean);
    expect(lines[0]).toBe('ID,Date,Status,"What is your name?"');
    expect(lines).toHaveLength(3);
    expect(lines.find(line => line.includes("E2E Alpha"))).toMatch(/^\d+,"[^"]+",Read,"E2E Alpha"$/);
    expect(lines.find(line => line.includes("E2E Bravo"))).toMatch(/^\d+,"[^"]+",Read,"E2E Bravo"$/);
    expect(lines.some(line => line.includes("E2E Charlie"))).toBe(false);

    await page.goto(listUrl(name));
    await expect(page.locator("#the-list tr", { hasText: name }).locator("td.column-results")).toHaveText("2");

    await page.goto(resultsUrl(surveyId));
    let message;
    page.once("dialog", dialog => {
      message = dialog.message();
      dialog.accept();
    });
    await rowAction(page, "E2E Alpha", "Delete");
    await expect(page.locator(".notice-success")).toContainText("1 result deleted.");
    expect(message).toBe("This action CANNOT be undone! Are you ABSOLUTELY sure?");
    await expect(entryRow(page, "E2E Alpha")).toHaveCount(0);

    await page.goto(resultsUrl(surveyId, "spam"));
    page.once("dialog", dialog => {
      message = dialog.message();
      dialog.accept();
    });
    await bulkAction(page, ["E2E Charlie"], "delete");
    expect(message).toBe("Delete the selected entries? Files uploaded with them will be deleted too. This cannot be undone.");
    await expect(page.locator(".notice-success")).toContainText("1 result deleted.");
    await expectCounts(page, { all: 1, unread: 0, read: 1, spam: 0 });
  });

  test("deletes the form's entries and their uploaded files with the form", async ({ page }) => {
    name = uniqueName("E2E form with entries");
    const surveyId = await createSurvey(page, name, FILE_JSON);
    const post = await createPost(page, name, `[surveyjs id="${surveyId}"]`);
    const files = ["first.txt", "second.txt"];
    for (const file of files) {
      await submitWithFile(page, post.link, file);
    }

    await page.goto(resultsUrl(surveyId));
    const hrefs = [];
    for (const file of files) {
      const href = await page.locator("#the-list a", { hasText: file }).getAttribute("href");
      const download = await page.request.get(href);
      expect(download.status()).toBe(200);
      expect(await download.text()).toBe(`E2E storage ${file}`);
      hrefs.push(href);
    }

    await page.goto(listUrl(name));
    const row = page.locator("#the-list tr", { hasText: name });
    let message;
    page.once("dialog", dialog => {
      message = dialog.message();
      dialog.accept();
    });
    await row.hover();
    await row.getByRole("link", { name: "Delete", exact: true }).click();
    await expect(page.locator(".notice-success")).toContainText("1 form deleted.");
    expect(message).toBe("Delete this form and its 2 entries? Files uploaded with these entries will be deleted too. This cannot be undone.");

    for (const href of hrefs) {
      expect((await page.request.get(href)).status()).toBe(404);
    }
    if (WP_CONTAINER) {
      expect(storageFixture("entries", surveyId).count).toBe(0);
    }
    await page.goto(resultsUrl(surveyId));
    await expect(page.locator("body")).toContainText("Form not found.");
  });

  test("shows unread entries and the forms of the current page in the admin bar", async ({ page }) => {
    name = uniqueName("E2E admin bar");
    const surveyId = await createSurvey(page, name);
    const post = await createPost(page, name, `[surveyjs id="${surveyId}"]`);
    const menu = page.locator("#wp-admin-bar-surveyjs");
    const editItem = page.locator(`#wp-admin-bar-surveyjs-edit-${surveyId}`);

    await page.goto(FORMS_URL);
    const before = await unreadTotal(page);
    await submitEntry(page, await surveyConfig(page, post.link), { e2e_question: "E2E admin bar answer" });

    await page.goto(FORMS_URL);
    await expect(page.locator("#wp-admin-bar-surveyjs > .ab-item .ab-label")).toHaveText("SurveyJS Forms");
    expect(await unreadTotal(page)).toBe(before + 1);
    await expect(page.locator("#wp-admin-bar-surveyjs > .ab-item .screen-reader-text").first()).toHaveText(/^[\d,.\s]+ unread entr(y|ies)$/);
    await expect(page.locator("#wp-admin-bar-surveyjs-unread")).toHaveText(/^[\d,.\s]+ unread entr(y|ies)$/);
    await expect(page.locator("#wp-admin-bar-surveyjs-forms a")).toHaveAttribute("href", /page=surveyjs-main-menu/);
    await expect(editItem).toHaveCount(0);

    await page.goto(post.link);
    await expect(editItem).toHaveText(`Edit form: ${name}`);
    await menu.hover();
    await editItem.locator("a").click();
    await expect(page).toHaveURL(new RegExp(`page=surveyjs_editor&id=${surveyId}(&|$)`));
    await expect(page.locator("#wp-sjs-name-text")).toHaveText(name);

    await page.goto(resultsUrl(surveyId));
    await rowAction(page, "E2E admin bar answer", "Mark as Read");
    await expect(page.locator(".notice-success")).toContainText("1 result updated.");
    expect(await unreadTotal(page)).toBe(before);
  });

  test("stores the IP address only when the setting is on", async ({ page }) => {
    name = uniqueName("E2E IP");
    await page.goto(SETTINGS_URL);
    storeIp = await page.getByLabel(STORE_IP_LABEL).isChecked();
    const surveyId = await createSurvey(page, name);
    const post = await createPost(page, name, `[surveyjs id="${surveyId}"]`);
    const config = await surveyConfig(page, post.link);

    await setStoreIp(page, false);
    await submitEntry(page, config, { e2e_question: "E2E without IP" });
    await setStoreIp(page, true);
    await submitEntry(page, config, { e2e_question: "E2E with IP" });

    await page.goto(resultsUrl(surveyId));
    await expect(entryRow(page, "E2E without IP").locator("td.column-submitter .description")).toHaveCount(0);
    await expect(entryRow(page, "E2E with IP").locator("td.column-submitter .description")).toHaveText(/^[0-9a-f.:]+$/i);
  });

  test("renders a form by its legacy ID in a shortcode and a block", async ({ page }) => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to create a legacy form ID");
    name = uniqueName("E2E legacy ID");
    const surveyId = await createSurvey(page, name);
    legacyId = storageFixture("legacy", surveyId).legacyId;
    expect(legacyId).not.toBe(surveyId);

    const shortcodePost = await createPost(page, `${name} shortcode`, `[surveyjs id="${legacyId}"]`);
    const blockPost = await createPost(page, `${name} block`, `<!-- wp:sjs/gutenberg-block {"surveyid":"${legacyId}","shortcode":"[surveyjs id=\\u0022${legacyId}\\u0022]"} /-->`);

    for (const post of [shortcodePost, blockPost]) {
      await page.goto(post.link);
      await expect(page.locator(".wp-sjs-plugin")).toHaveAttribute("id", `surveyContainer-${surveyId}`);
      await expect(page.getByLabel("What is your name?")).toBeVisible();
      await expect(page.locator(`#wp-admin-bar-surveyjs-edit-${surveyId}`)).toHaveText(`Edit form: ${name}`);
    }

    await page.getByLabel("What is your name?").fill("E2E legacy answer");
    const saved = page.waitForResponse(r => r.url().includes("action=SurveyJS_SaveResult"));
    await page.getByRole("button", { name: "Complete" }).click();
    expect((await saved).ok()).toBe(true);

    await page.goto(resultsUrl(surveyId));
    await expect(entryRow(page, "E2E legacy answer")).toBeVisible();
  });

  test("migrates a form saved by the old plugin after a rollback, even when its ID matches a newer form", async ({ page }) => {
    test.skip(!WP_CONTAINER, "Set WP_CONTAINER to the WordPress container name to add a row to the old forms table");
    name = uniqueName("E2E rollback");
    const surveyId = await createSurvey(page, name);
    const oldJson = { elements: [{ type: "text", name: "old_question", title: "Old plugin question" }] };
    storageFixture("legacy-row", surveyId, `${name} old`, JSON.stringify(oldJson));
    legacyRowId = surveyId;

    await page.goto(listUrl(`${name} old`));
    await expect(page.locator("#the-list tr")).toHaveCount(1);

    const legacyShortcode = `[Survey id=\\u0022${surveyId}\\u0022 name=\\u0022${name} old\\u0022] `;
    const legacyPosts = [
      await createPost(page, `${name} old shortcode`, `[Survey id="${surveyId}" name="${name} old"]`),
      await createPost(page, `${name} old block`, `<!-- wp:sjs/gutenberg-block {"surveyid":"${surveyId}","shortcode":"${legacyShortcode}"} /-->`)
    ];
    for (const post of legacyPosts) {
      await page.goto(post.link);
      await expect(page.getByLabel("Old plugin question")).toBeVisible();
      await expect(page.locator(".wp-sjs-plugin")).not.toHaveAttribute("id", `surveyContainer-${surveyId}`);
    }

    const newPost = await createPost(page, `${name} new shortcode`, `[surveyjs id="${surveyId}"]`);
    await page.goto(newPost.link);
    await expect(page.locator(".wp-sjs-plugin")).toHaveAttribute("id", `surveyContainer-${surveyId}`);
    await expect(page.getByLabel("What is your name?")).toBeVisible();
  });

  test("exports and erases the entries found by the email answer", async ({ page }) => {
    name = uniqueName("E2E privacy");
    privacyEmail = `e2e-privacy-${Date.now()}@example.com`;
    const surveyId = await createSurvey(page, name, CONTACT_JSON);
    const post = await createPost(page, name, `[surveyjs id="${surveyId}"]`);
    await submitEntry(page, await surveyConfig(page, post.link), { visitor_name: "E2E Privacy", visitor_email: privacyEmail });

    const exportRow = await addPrivacyRequest(page, "export-personal-data.php", privacyEmail);
    const exported = collectAjax(page, "wp-privacy-export-personal-data");
    const download = page.waitForEvent("download");
    await exportRow.hover();
    await exportRow.getByRole("button", { name: "Download personal data", exact: true }).click();
    await (await download).delete();
    const items = (await exported()).flatMap(body => body.data?.data ?? []).filter(item => item.group_id === "surveyjs-entries");
    expect(items).toHaveLength(1);
    expect(items[0].data).toEqual(expect.arrayContaining([
      { name: "Form", value: name },
      { name: "Your name", value: "E2E Privacy" },
      { name: "Your email", value: privacyEmail }
    ]));

    const eraseRow = await addPrivacyRequest(page, "erase-personal-data.php", privacyEmail);
    const erased = collectAjax(page, "wp-privacy-erase-personal-data");
    await eraseRow.getByRole("button", { name: "Erase personal data", exact: true }).click();
    await expect(eraseRow.locator(".remove-personal-data-success")).toBeVisible();
    expect((await erased()).some(body => body.data?.items_removed)).toBe(true);

    await page.goto(resultsUrl(surveyId));
    await expect(page.locator("#the-list .no-items")).toBeVisible();
  });
});
