# SurveyJS Forms for WordPress: how the plugin works

Use this guide to answer "how do I…" questions about the plugin. Tell the user where to click in the WordPress admin; when you can do the task yourself with your tools, offer to do it.

## Admin screens

- **SurveyJS Forms > All Forms**: the list of forms with entries count, views and conversion rate. Hover a form for Edit, Entries, Duplicate, Export and Delete. "AI changes to review" marks a form with an unsaved AI draft.
- **SurveyJS Forms > Add New Form**: start from a blank form or a template, or import forms from Contact Form 7, WPForms, Gravity Forms, Ninja Forms and Fluent Forms (Import button).
- **SurveyJS Forms > Support**: a short checklist for new users and links to help.
- **SurveyJS Forms > Settings**: General tab (IP addresses, form views, delete old entries, uninstall), Spam Protection tab (CAPTCHA keys), Integrations tab (connected Slack and Mailchimp accounts), AI MCP tab (connection for the user's own AI assistant) and AI Client tab (the AI chat with the user's own API key).
- **Form editor** (click a form): tabs Config (survey JSON), Theme, Email, Integrations (integrations and webhooks), Entries, Restrictions, Spam Protection, Advanced; a live preview on the right; buttons Full preview, Embed and Save. Nothing is published until the person clicks Save.

## Building a form

- Without SurveyJS Forms Add-on the form is edited as SurveyJS JSON on the Config tab (Monaco editor with error checking and a live preview), or in the free online UI Builder (link "Open in UI Builder" above the JSON): it opens the form in a new tab and sends the result back to the editor.
- With SurveyJS Forms Add-on (free add-on from surveyjs.io) the Config tab becomes the visual Survey Creator (drag and drop).
- "GDPR agreement" checkbox (above the JSON editor) adds a required consent checkbox with a link to the Privacy Policy page.
- "Save as Template" in the editor header saves the form as a template for new forms.

## JSON issues ("Form saved with N issues")

- The form editor checks the form JSON with the SurveyJS linter: in Survey Creator's JSON Editor tab with SurveyJS Forms Add-on, in the Static analysis panel under the JSON editor of the Config tab without the add-on. The form still saves; Save then says "Form saved with N issues", lists the first ones and offers "Review and fix" (opens the JSON editor at the first issue) and, when the AI chat is set up, "Fix with AI".
- The list of checks is at the end of the SurveyJS JSON guide (surveyjs-get-json-guide). surveyjs-get-form returns the issues of the current JSON in `issues`; in the form editor's chat they are also in the context. When the person asks to "fix the issue" or "fix the errors", fix these.
- A frequent one: a choice "Other" (or "None") while `showOtherItem` (`showNoneItem`) is on collides with the built-in item. Keep the built-in item and remove the choice; set `otherText` (`noneText`) to change its label.

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

## Integrations (Integrations tab)

- Sends every entry to a connected account: **Slack** (a message to a channel with the answers and a "View entry" button) and **Mailchimp** (adds or updates a contact in an audience, with contact fields, tags and double opt-in, on by default).
- First connect the account on SurveyJS Forms > Settings > Integrations: for Slack paste an incoming webhook URL (Slack app > Incoming Webhooks > Add New Webhook), for Mailchimp an API key (Profile > Extras > API keys). Keys are stored encrypted and never shown again, also not to AI. Several connections per service are allowed (two channels, two Mailchimp accounts).
- Then on the form's Integrations tab: choose the service, "Add integration", pick the connection (a single one is chosen for you), map the answers (Mailchimp: the email question, contact fields, tags), add conditions, "Send test", Save.
- Mailchimp needs consent: the form is saved only when the integration has a condition on a consent question or "This form is a newsletter signup (submitting it means consent)" is checked. The button "Add "Subscribe me to the newsletter" question" next to Conditions adds the checkbox to the form and the condition.
- Entries are sent by WP-Cron right after the submission; failed requests are retried after 5 minutes, 1 hour and 6 hours. "Recent requests" shows the log, and "Send again" resends a stored entry. When a key stops working the connection shows "Needs attention" in Settings > Integrations.

## Webhooks (Integrations tab, below the integrations)

- Sends every entry as JSON in a POST request to a URL: Zapier ("Catch Hook" in Webhooks by Zapier), Make ("Custom webhook"), n8n ("Webhook" node), a CRM or custom code. Several webhooks per form, each with conditions on the answers ("Send when all / any condition matches").
- The request has `answers` (question name: answer as text, easy to map in Zapier), `data` (answers as SurveyJS stores them), `fields` (with question titles), `form`, `entry` (id, date, link to the entry) and `site`. Files are download links.
- Optional headers (e.g. an API key), "Send test request" and a log of recent requests. Failed requests are retried after 5 minutes and after 1 hour.
- Requests are sent by WP-Cron right after the submission; only public addresses are allowed.

## Entries (Entries tab and the Entries screen)

- Entries are stored in WordPress unless "Save submitted entries in the database" is off for the form. The Entries screen has a column per question, an entry view and CSV export.
- Save & Resume lets respondents continue later; "Save partial entries" keeps unfinished multi-page forms (deleted after 30 days unless kept).
- Delete old entries: for the whole site in Settings, or per form on the Entries tab.
- AI assistants read entries only when the site owner allows it: "Allow AI apps to read entries" on the AI MCP tab and "Allow the AI chat to read entries" on the AI Client tab, both off by default. Even then entries are read-only and uploaded files, signatures and passwords are never included. The AI chat then counts entries per form and period, summarizes and compares answers (on the Entries screen, the Dashboard of SurveyJS Forms Add-on or anywhere else); without it, it helps with export, filters and PDF.

## Restrictions tab

Entry limit (total or per day/week/month/year), entries per person (by user account or IP address), schedule (open and close dates), require login. Each shows its own message instead of the form.

## Spam Protection tab

A hidden honeypot field is always on. CAPTCHA (Google reCAPTCHA v2, v3, Cloudflare Turnstile or hCaptcha) needs keys in Settings > Spam Protection first, then "Enable CAPTCHA" on this tab. "Check entries with Akismet" works when the Akismet plugin is active and connected to an Akismet account: entries Akismet flags are saved with the Spam status and send no emails. "Reject entries sent too soon after the form opens" blocks bots that post the form within the minimum time; it is on in new forms with 3 seconds, and forms created before keep it off until turned on. People don't notice it, because the form waits until the time has passed.

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
