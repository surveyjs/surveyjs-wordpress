# SurveyJS for WordPress — draft

Working draft of what the Base plugin does. Source for the future `readme.txt` and docs.

## Requirements

- WordPress 6.4+, PHP 8.2+.

## Surveys

- **SurveyJS** menu: list of surveys (WordPress-style table) with search, sorting, bulk delete.
- Row actions: Edit, Results, Clone, Delete. Clone copies the form, theme and settings.

## Editor

- Header: Back, survey name with inline Edit, Save.
- Tabs:
  - **Config** — survey JSON in a code editor with validation (linter), Format button and live preview.
  - **Theme** — theme JSON, same editor and preview.
  - **Email Notification** — see [Email](#email).
  - **Entries** — "Save submitted entries in the database" (on by default).
- **Open in UI Builder** — opens the survey in the free online visual builder on surveyjs.io; the edited JSON comes back to the editor. The plugin works without it.

## Adding a survey to a page

- Gutenberg block **SurveyJS**: pick the survey right in the block.
- Shortcode: `[Survey id="1"]`.

## Entries

- Results page per survey: one column per question, View (read-only form), Delete, Export CSV.

## Email

- **Email notification** to the site owner (off by default):
  - Send To (comma-separated), From Name, From Email, Reply-To, Subject, Message;
  - Reply-To: an email question of the form; replying to the email goes to the respondent. A checkbox adds a ready email question to the form.
- **Auto-reply** to the respondent (off by default), sent to the address from the Reply-To question. Works without the email notification.
- **Tags** in Subject and Message: `{all_fields}`, `{field:question_name}`, `{form_name}`, `{site_name}`, `{admin_email}`.
- **Use HTML content type** — write the message in HTML. Dangerous tags are removed; answers are escaped.
- **File Attachments** — pick a File Upload question; files the respondent uploaded there are attached to the email, up to 18 MB in total (larger files are skipped). A checkbox adds a ready File Upload question to the form. Separate setting for the email notification and the auto-reply.
- Delivery depends on the site's mail setup (`wp_mail`); the plugin does not send mail itself.

## Spam protection

- Hidden honeypot field on every form; submissions with it filled are silently dropped. No settings.

## File uploads

- Files are always uploaded to `wp-content/uploads/surveyjs/` with random names, even if the question JSON says otherwise (`storeDataAsText`); PHP execution in the folder is blocked.
- Allowed types: images (jpg, png, gif, webp, bmp, tif), pdf, Office/OpenDocument files, txt, csv.
- Max size: 10 MB per file or the server upload limit, whichever is lower.
- When entries are not saved, files are deleted right after the emails are sent.
- WordPress.com: plugins work on every paid plan; uploads up to 2 GB per file within the plan storage; mail is sent by WordPress.com servers (SMTP plugins supported). No documented limit on email or attachment size.
- Email providers reject large emails: Gmail receives up to 50 MB, Outlook ~20–25 MB. Attachments grow ~33% when encoded, so the plugin attaches at most 18 MB of files per email.

## For developers

- `SURVEYJS_UI_BUILDER_URL` constant / `surveyjs_ui_builder_url` filter — UI Builder address.
- `surveyjs_allowed_upload_extensions`, `surveyjs_max_upload_size` filters — upload rules.

## External services

- UI Builder on surveyjs.io — only when the user clicks Open in UI Builder; the survey JSON is sent there. Terms: https://surveyjs.io/terms-use, privacy: https://surveyjs.io/privacy-policy.
