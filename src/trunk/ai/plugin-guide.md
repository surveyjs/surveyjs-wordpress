# SurveyJS Forms for WordPress: how the plugin works

Use this guide to answer "how do I…" questions about the plugin. Tell the user where to click in the WordPress admin; when you can do the task yourself with your tools, offer to do it.

## Admin screens

- **SurveyJS Forms > All Forms**: the list of forms with entries count, views and conversion rate. Hover a form for Edit, Entries, Duplicate, Export and Delete. "AI changes to review" marks a form with an unsaved AI draft.
- **SurveyJS Forms > Add New Form**: start from a blank form or a template, or import forms from Contact Form 7, WPForms, Gravity Forms, Ninja Forms and Fluent Forms (Import button).
- **SurveyJS Forms > Getting Started**: a short checklist for new users.
- **SurveyJS Forms > Settings**: General tab (CAPTCHA keys, IP addresses, form views, delete old entries, uninstall) AI MCP tab (connection for the user's own AI assistant) and AI Client tab (the AI chat with the user's own API key).
- **Form editor** (click a form): tabs Config (survey JSON), Theme, Email, Entries, Restrictions, Spam Protection, Advanced; a live preview on the right; buttons Open full preview, Embed and Save. Nothing is published until the person clicks Save.

## Building a form

- Without SurveyJS Forms Pro the form is edited as SurveyJS JSON on the Config tab (Monaco editor with error checking and a live preview), or in the free online UI Builder (button "Open in free UI Builder"): it opens the form in a new tab and sends the result back to the editor.
- With SurveyJS Forms Pro (free add-on from surveyjs.io) the Config tab becomes the visual Survey Creator (drag and drop).
- "GDPR agreement" checkbox (above the JSON editor) adds a required consent checkbox with a link to the Privacy Policy page.
- "Save as Template" in the editor header saves the form as a template for new forms.

## Showing a form on the site

- Block editor: add the "SurveyJS Forms" block and pick the form.
- Anywhere else: the shortcode `[surveyjs id="FORM_ID"]` (the Embed button in the editor copies it).
- The form takes the look of the WordPress theme (adaptive theme mode). To use a SurveyJS theme instead, turn off "Adaptive: use your site's theme styles" on the Theme tab and choose a preset or edit theme JSON.

## Emails (Email tab)

- Notification to the site owner on every submission: recipients (comma-separated), sender name and email, subject and message.
- Reply-To: pick the email question; its answer becomes Reply-To and the recipient of the auto-reply.
- Auto-reply to the respondent, HTML emails, file attachments from a file question, and a "Send test email" button.
- Tags: `{all_fields}`, `{field:question_name}`, `{form_name}`, `{site_name}`, `{admin_email}`.
- Emails not arriving: send a test email first; most sites need an SMTP plugin, because many hosts block PHP mail. Use a sender address on the site's own domain.

## Entries (Entries tab and the Entries screen)

- Entries are stored in WordPress unless "Save submitted entries in the database" is off for the form. The Entries screen has a column per question, an entry view and CSV export.
- Save & Resume lets respondents continue later; "Save partial entries" keeps unfinished multi-page forms (deleted after 30 days unless kept).
- Delete old entries: for the whole site in Settings, or per form on the Entries tab.
- AI assistants read entries only when the site owner allows it: "Allow AI apps to read entries" on the AI MCP tab and "Allow the AI chat to read entries" on the AI Client tab, both off by default. Even then entries are read-only and uploaded files, signatures and passwords are never included. The AI chat then counts entries per form and period, summarizes and compares answers (on the Entries screen, the Dashboard of SurveyJS Forms Pro or anywhere else); without it, it helps with export, filters and PDF.

## Restrictions tab

Entry limit (total or per day/week/month/year), entries per person (by user account or IP address), schedule (open and close dates), require login. Each shows its own message instead of the form.

## Spam Protection tab

A hidden honeypot field is always on. CAPTCHA (Google reCAPTCHA v2, v3 or Cloudflare Turnstile) needs keys in Settings > General first, then "Enable CAPTCHA" on this tab.

## Advanced tab

Markdown in texts, prefill from the URL (`{urlParams.name}`) and from the logged-in user (`{wpUser.email}` and others), and other options.

## Privacy

Entries work with Tools > Export Personal Data and Erase Personal Data. IP addresses are not stored by default. Uploaded files are private and open only for administrators.

## AI features

- **MCP connection** (Settings > AI MCP): connects the user's own AI assistant (Claude, ChatGPT, Cursor, Claude Code, Codex, GitHub Copilot in VS Code and others) to the site through the free MCP Adapter plugin. Off by default; read-only until "Enable MCP write access" is on.
- **AI chat** (set up in Settings > AI Client; the "AI assistant" button at the top of every SurveyJS Forms screen, shown once the chat is ready: one conversation for all screens, and a separate one per form in the form editor): needs WordPress 7.0 or later and an AI provider connected in Settings > Connectors with the user's own API key. Requests are billed by the AI provider, not by SurveyJS. In the chat you can attach images, PDFs or audio (when the chosen model reads them, for example a photo of a paper form to turn into a form), turn on "Search the web" (when the model supports it), dictate with the microphone (speech recognition of the browser) and stop an answer with Stop.
- Every AI change is a draft: the live form changes only when the person reviews it in the editor and clicks Save. "Undo" in the chat reverts the last AI change; "Discard AI changes" drops the whole draft.

## Getting help

Documentation: https://surveyjs.io/form-library/documentation/overview. WordPress plugin support forum: https://wordpress.org/support/plugin/surveyjs/.
