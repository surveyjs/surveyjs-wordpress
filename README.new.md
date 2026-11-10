# SurveyJS for WordPress — draft

Working draft of what the Base plugin does. Source for the future `readme.txt` and docs.

## Requirements

- WordPress 6.4+, PHP 8.2+.

## Surveys

- **SurveyJS** menu: list of surveys (WordPress-style table) with search, sorting, bulk delete.
- Row actions: Edit, Results, Clone, Save as Template, Delete. Clone copies the form, theme and settings.

## Form templates

- **Add New Form** (menu item, list button, admin bar, Getting Started) opens `admin.php?page=surveyjs-templates`: optional form name, search, categories (All Templates, My Templates, Contact Forms, then A–Z) and cards: Blank Form first, then templates; Preview opens the template in its theme in a modal on the site preview page.
- **Use template** creates the form named after the template unless a name is entered, and opens the editor. The form gets the template JSON and theme with the SurveyJS theme mode (not Adaptive). Every new form, Blank included, has the email notification on; Reply-To = the first email question, attachments = the first file question. Blank Form is named "New Form" and keeps the Adaptive theme.
- **Save as Template** (forms list row action, editor header) saves the stored JSON, theme and theme mode of a form as a user template with a name and description (`sjs_template` post type, not public, `manage_options`, nonces). User templates are listed under My Templates, work like the built-in ones and can be deleted there.
- Templates come from [surveyjs/templates](https://github.com/surveyjs/templates) and ship in `src/trunk/templates/`; the plugin never downloads them. A template listed in several categories is shown once in All Templates. Cards show the template's `thumbnail.webp`; without one (user templates) they show "No preview".

## Editor

- Header: Back, survey name with inline Edit, Save.
- Tabs:
  - **Config** — survey JSON in a code editor with validation (linter), Format button and live preview.
  - **Theme** — theme JSON, same editor and preview.
  - **Email** — see [Email](#email).
  - **Entries** — "Save submitted entries in the database" (on by default).
- **Open in free UI Builder** — opens the survey in the free online visual builder on surveyjs.io; the edited JSON comes back to the editor. The plugin works without it.

## Adding a survey to a page

- Gutenberg block **SurveyJS**: pick the survey right in the block.
- Shortcode: `[surveyjs id="1"]`; the old `[Survey id="1"]` still works.

## Entries

- Results page per survey: one column per question, View (read-only form), Delete, Export CSV.

## Email

- **Email notification** to the site owner (off by default):
  - Send To (comma-separated), From Name, From Email, Reply-To, Subject, Message;
  - Reply-To: an email question of the form; replying to the email goes to the respondent. A checkbox adds a ready email question to the form.
- **Auto-reply** to the respondent (off by default), sent to the address from the Reply-To question. Works without the email notification.
- **Tags** in Subject and Message: `{all_fields}`, `{field:question_name}`, `{form_name}`, `{site_name}`, `{admin_email}`. The tags of the current form are listed under Message.
- **Send test email** — sends a test email to Send To with the current (even unsaved) settings and shows the delivery error, if any.
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

- `npm run update-templates [-- <local checkout>]` — replaces `src/trunk/templates/` with the JSON files and `thumbnail.webp` previews (600×400) of the templates repository (latest commit, or a local checkout); `source.txt` records the commit. Only `SurveyJS_Templates::load()` knows the repository layout.

- `SURVEYJS_SITE_URL` constant / `surveyjs_site_url` filter — surveyjs.io address, default `https://surveyjs.io`; the UI Builder (`/wordpress-ui-builder`) and Pro page (`/wordpress-pro`) links are built from it.
- `surveyjs_allowed_upload_extensions`, `surveyjs_max_upload_size` filters — upload rules.

## External services

- UI Builder on surveyjs.io — only when the user clicks Open in free UI Builder; the survey JSON is sent there. Terms: https://surveyjs.io/terms-use, privacy: https://surveyjs.io/privacy-policy.
