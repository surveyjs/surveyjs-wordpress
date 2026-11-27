=== SurveyJS Forms - AI, Contact Forms, Form Builder, Surveys, Quizzes & More ===
Contributors: devsoftbaltic
Tags: contact form, form builder, ai, survey, quiz
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 3.2.4
Requires PHP: 8.2

Contact forms, surveys and quizzes powered by SurveyJS, with an AI assistant. Email notifications, Slack, Mailchimp, webhooks, file uploads and entries — free.

== Description ==

SurveyJS is a form builder for **contact forms, surveys, quizzes and multi-page forms** of any complexity: build a form yourself, start from a template or just ask AI to build it for you, then add it to any page with a block or a shortcode. Forms run on the open-source SurveyJS Form Library.

= Use SurveyJS with your favorite AI =
Build and change forms by simply asking: "Create a contact form with name, email and message", "Make the phone question required", "Send entries to sales@example.com", "Close the form after 100 entries", "How do I put this form on a page?". Two ways, each on its own tab of SurveyJS Forms > Settings:

* **AI MCP: your AI assistant**, free with the AI app you already use: Claude, ChatGPT, Cursor, Claude Code, Codex, GitHub Copilot in VS Code and other MCP apps.
* **AI Client: AI chat right in your WordPress admin**, with your own API key from Anthropic, OpenAI or Google, connected once in Settings > Connectors (WordPress 7.0 or later). Requests are billed by your AI provider, not by SurveyJS. The chat is on every SurveyJS Forms screen (create forms from a description or a template, change forms, ask how to do something, analyze entries when you allow it), with one conversation for all screens, and in the form editor, where changes show up right away, with Undo.

* **Safe by design:** the AI can't see form entries unless you allow it, and can't change, delete or export entries or delete forms or anything else. Every AI change is saved as a draft: your live form changes only when you open it in the form editor, review the changes and click Save. The editor shows what the AI changed, warns when it changed email recipients, webhooks or integrations, and warns when an AI app changes a form you have open. Webhook header values and the keys of your Slack and Mailchimp connections are never shown to the AI.
* **What the AI can do:** list forms, read and change form JSON, theme and settings (emails, restrictions, schedule, spam protection and more), create forms, look up the SurveyJS documentation and explain how the plugin works. With SurveyJS Forms Add-on it also changes PDF settings and the Pro admin settings.
* **Easy to connect your assistant:** turn the AI connection on and install the free MCP Adapter plugin by the WordPress team in one click. Then either let your assistant set itself up (download a connection file, copy one setup prompt into Claude Code, Codex, Cursor, GitHub Copilot in VS Code or another assistant that runs commands; it connects itself, tests the connection and deletes the file) or set it up manually with ready-made settings and step-by-step instructions for Claude Desktop, Cursor, GitHub Copilot (VS Code), Claude Code, Codex and other apps. A connection is a standard WordPress Application Password that works only with the SurveyJS MCP server; the list of connections shows the last activity of each, and you can revoke any of them at any time.
* **Read-only by default:** AI apps can read forms and settings until you turn on "Enable MCP write access".
* **AI chat:** click "AI assistant" at the top of any SurveyJS Forms screen. It knows which screen you are on and suggests matching questions. It creates forms from a description or a plugin template, changes forms and their settings and answers "where is…" questions with links; it never puts forms on pages. In the form editor it works on what you see in the editor, including unsaved edits, and shows every change with "Undo the last AI change". The AI Client tab checks step by step what is missing: an AI provider connected in Settings > Connectors and a provider whose models can call functions (Anthropic, OpenAI, Google with AI Provider for Google 1.2.1 or later, Mistral).
* The MCP connection is off by default and needs WordPress 6.9 or later and HTTPS (or a local development site). claude.ai and ChatGPT on the web will work when WordPress adds OAuth sign-in.
* **Entries only when you allow it:** reading entries is off by default and has two separate switches: "Allow AI apps to read entries" on the AI MCP tab and "Allow the AI chat to read entries" on the AI Client tab. With it on, the AI can count entries per form and period, list entries, read one entry and summarize answers, read-only; uploaded files, signatures and passwords are never included. On the Entries screen and the Pro Dashboard the chat then answers questions such as "Summarize entries for the last 30 days" or "Which form gets the most entries?". Entries are written by visitors, so the AI treats their text only as data, and in the chat changing forms is off for the rest of a turn in which entries were read.
* Your AI app or AI provider receives the forms and settings the AI reads, under its terms, and the entries it reads if you allowed that. Mention this in your privacy policy. With the MCP connection the plugin itself never sends forms to an AI service.

= Contact forms with email =
* **Email notification** to the site owner on every submission: recipients, sender, subject and message.
* **Reply-To** set to the respondent's email, so you can answer right from your inbox.
* **Auto-reply** to the respondent.
* **Tags** in the subject and message: all answers at once or the answer to a single question.
* **HTML emails**, **file attachments** and a **test email** button.
* **Spam protection** with a hidden honeypot field and optional **CAPTCHA**: Google reCAPTCHA v2 (checkbox), reCAPTCHA v3 (invisible), Cloudflare Turnstile or hCaptcha.
* **Akismet spam filter**: when the Akismet plugin is active, turn on **Check entries with Akismet** for a form. Entries that Akismet flags are kept with the Spam status, send no emails, and can be restored with **Not Spam**.
* **Minimum time to submit**: reject entries that bots send right after the page loads. On by default in new forms (3 seconds); people never notice it.

= Integrations: Slack and Mailchimp =
* **Slack**: post a message to a channel for each entry, with a heading, the answers you choose and a "View entry" button.
* **Mailchimp**: add or update a contact in an audience for each entry, with contact fields, tags and double opt-in (on by default). People who unsubscribed are never subscribed again.
* **Only people who agreed**: a Mailchimp integration is saved only with a condition on a consent question, or when you mark the form as a newsletter signup form. One click adds an optional "Subscribe me to the newsletter" checkbox to the form together with the condition.
* Connect each account once on SurveyJS Forms > Settings > Integrations (a Slack incoming webhook URL or a Mailchimp API key; several connections per service are fine). Keys are stored encrypted and never shown again.
* Add the integration to a form on its **Integrations** tab, map the answers, add conditions (for example only when a consent box is checked) and click **Send test**.
* Entries are sent in the background right after the submission; failed ones are retried after 5 minutes, 1 hour and 6 hours, and **Send again** in the request log resends a stored entry.

= Webhooks: Zapier, Make, n8n and any app =
* **Webhooks** (on the Integrations tab of the form editor): send every entry to Zapier ("Catch Hook"), Make, n8n, a CRM or your own code as JSON. Several webhooks per form, each with its own conditions on the answers.
* Answers come both as a flat list of question name and answer text, easy to map in Zapier, and as raw SurveyJS data for code. File answers are download links.
* **Custom headers** for API keys, a **Send test request** button and a log of recent requests.
* Requests are sent in the background right after the submission, and failed ones are retried after 5 minutes and after 1 hour. Only public addresses are allowed.

= Entries =
* Submitted entries are stored in WordPress; you can turn this off for any form.
* Entries table with a column per question, entry view and CSV export.
* **Trash**: a deleted form goes to the Trash first. It is no longer shown on your site and does not accept entries, but its entries stay until the form is deleted permanently. Restore it or delete it permanently from the Trash view of the Forms list; WordPress empties the Trash after 30 days (`EMPTY_TRASH_DAYS`).
* **Partial entries**: turn on **Save partial entries** on the Entries tab of the form editor to see who started a multi-page form but did not submit it. The answers are saved each time the respondent goes to the next page and are listed under **Partial**, with the page they reached. When the respondent submits the form, the partial entry becomes a regular entry. Partial entries are not counted as unread and are not in the CSV export of all entries; export them from the Partial view. Unfinished entries are deleted after 30 days (filter `surveyjs_partial_entry_lifetime`); 5 days before that they are marked "Will be deleted on …" and the entries page shows a warning, so you can click **Keep** on the ones you need. The WPForms importer brings partial and abandoned WPForms entries in as Partial. Off by default.

= Form restrictions =
Set them on the Restrictions tab of the form editor. All restrictions are off by default.

* **Entry limit**: close the form after a number of entries in total or per day, week, month or year.
* **Entries per person**: limit how many times one person can submit the form, by user account or IP address.
* **Schedule**: open and close the form on set dates, with a message before and after.
* **Require login**: show the form only to logged-in users, with a Log in link for everyone else.
* Each restriction shows its own message instead of the form. The restrictions are checked when the page opens and again when the form is submitted, so they also work on cached pages.
* **Delete old entries**: entries older than a number of days are deleted once a day, for the whole site or for one form.

= Privacy and GDPR =
* **Delete old entries**: set SurveyJS Forms > Settings > Delete old entries, or a period for one form on its Entries tab, and entries older than that are deleted once a day together with their uploaded files. Off by default.
* **GDPR agreement**: turn on the **GDPR agreement** checkbox in the form editor to add a required consent checkbox to the end of the form; turn it off to remove it. Its text links to your site's Privacy Policy page (Settings > Privacy); without a published Privacy Policy page the text has no link. The form is not submitted until the respondent checks it. Entries, the CSV export and emails show the consent text the respondent accepted.
* **Personal data export and erasure**: entries work with the WordPress tools Tools > Export Personal Data and Tools > Erase Personal Data. An entry belongs to a person when one of its answers is their email address or when they submitted it while logged in. The eraser deletes these entries together with their uploaded files.
* **Partial entries** store answers the respondent has not submitted yet. Turn them on only if your privacy policy mentions it. Partial entries that are not submitted are deleted after 30 days, together with their uploaded files, unless you keep them. They work with the personal data export and erasure tools like other entries.
* **Form views**: to show the conversion rate in the Forms list, the plugin counts how many times visitors see each form and how many of them submit it. It stores only one number per form per day: no cookies, no IP addresses, nothing that identifies a visitor. Views by logged-in users who can edit posts and by known bots are not counted. Turn it off in SurveyJS Forms > Settings > Form views.
* **IP addresses** are not stored by default. Turn on SurveyJS Forms > Settings > IP addresses only if you need them and your privacy policy mentions it. Only when a form limits entries per person by IP address, each entry keeps a hash of the address (HMAC with the site salt), not the address itself.
* Files uploaded by respondents are stored on your site and are not public: only administrators can open them from the entries table, the entry view and the CSV export. The email notification to the site owner attaches the files and links to them; the links open only for logged-in administrators. The auto-reply to the respondent lists only the file names.
* A respondent can remove an uploaded file before submitting the form, and the file is deleted right away. Files are deleted together with their entry, or with their form when it is deleted permanently. Files uploaded to forms that were never submitted are deleted by a daily cleanup once they are a day old (30 days for forms with Save & Resume). This also works on hosts where folders cannot be listed, such as WordPress VIP.
* The privacy export includes links to the person's files; the links work as long as the export file is kept.
* **Uninstall**: by default, deleting the plugin keeps your forms, entries and files. Turn on SurveyJS Forms > Settings > Uninstall to delete all forms, entries, uploaded files and settings when the plugin is deleted.
* Form answers stay on your site and are sent only in the emails, to the webhook URLs and to the Slack and Mailchimp accounts you set up, and to AI only if you allow it. The optional CAPTCHA services and the free online UI Builder never receive form answers. Only when you turn on the Akismet check for a form, the respondent's name, email, website and text answers are sent to Akismet (see External services below).

= Form builder =
* **Form templates**: start a new form from a ready-made template, your own template or a blank form. Templates are grouped by category, with search and a live preview, and the new form gets the template's theme. The email notification is off in a new form until you turn it on on the Email tab; Reply-To is already set to the respondent's email.
* **Save as Template**: save any form as your own template and reuse it from My Templates.
* **Import from other form plugins**: forms (and entries, where available) from WPForms, Contact Form 7, Ninja Forms, Fluent Forms and Gravity Forms. A Gravity Forms export file (.json) can be imported without Gravity Forms installed.
* Describe your form in JSON with validation hints, formatting and a live preview.
* Add a logo and images to the form from the WordPress Media Library.
* **Prefill from the URL or the user account**: use `{urlParams.name}` for a parameter from the page URL and `{wpUser.email}` (also `id`, `login`, `url`, `display_name`, `first_name`, `last_name`, `full_name`, `nickname`) for the logged-in user in default values, conditions and calculations.
* **Adaptive theme**: by default the form takes the colors, fonts, buttons and fields of your site's theme.
* **AI themes**: copy a prompt with your site's styles into your own AI assistant and paste back a SurveyJS theme that matches your site. The plugin checks the theme and warns about hard-to-read text.
* Prefer a visual editor? Open the form in the **free online SurveyJS UI Builder** with drag & drop, a logic editor and a theme editor. When you save there, the form comes back to WordPress. Optional: the plugin works fully without it (see External services below).
* **Survey Creator**, the visual form designer, is now in the free SurveyJS Forms Add-on.

= Flexible Configuration Options =
**Every single element of your form is fully customizable**, from titles and helper texts to placeholder texts and error messages&mdash;all to make sure you can provide your respondents with tailored guides and instructions and ensure high-quality user experience.

= Diverse Question types =
Unlike the vast majority of form builders, SurveyJS goes far beyond common question types such as Single-line input (date, email, name, surname etc.), Long text, Rating, and Radio button group. Among some more advanced form elements that you can use are the following:

* [**Single-Select Dropdown**](https://surveyjs.io/form-library/examples/create-dropdown-menu-in-javascript/)
* [**Multi-Select Dropdown**](https://surveyjs.io/form-library/examples/how-to-create-multiselect-tag-box/) **(Tag Box)**
* [**Image Picker**](https://surveyjs.io/form-library/examples/image-picker-question/)
* [**Multiple Textboxes**](https://surveyjs.io/form-library/examples/multiple-text-box-question/)
* [**Ranking**](https://surveyjs.io/form-library/examples/add-ranking-question-to-form/)
* [**Select Items to Rank**](https://surveyjs.io/form-library/examples/select-items-to-rank/)
* [**HTML**](https://surveyjs.io/form-library/examples/questiontype-html/)
* [**Image Capture**](https://surveyjs.io/form-library/examples/photo-capture/)
* [**Single-Select Matrix**](https://surveyjs.io/form-library/examples/single-selection-matrix-table-question/)
* [**Multi-Select Matrix**](https://surveyjs.io/form-library/examples/questiontype-matrixdropdown/)
* [**Dynamic Matrix**](https://surveyjs.io/form-library/examples/questiontype-matrixdynamic/)

= Features =
In addition to offering a wide variety of question elements, SurveyJS enables you to implement the following advanced features:

* [**Calculations**](https://surveyjs.io/form-library/examples/questiontype-matrixdynamic-totals/) - Dynamically calculate values based on user input or predefined variables and display the calculated values in your form or survey.
* [**Carry Forward Responses**](https://surveyjs.io/form-library/examples/survey-carry-forward/) - Copy choices from one question (the source) to another (the target). 
* [**Side Navigation**](https://surveyjs.io/form-library/examples/table-of-contents/) **(Table of Contents)** - Help users to quickly navigate through longer surveys.
* [**Progress bar**](https://surveyjs.io/form-library/examples/configure-form-navigation-with-progress-indicators/)  - Show users how many completed pages, answered questions, or valid answers have been given.
* [**Input validation**](https://surveyjs.io/form-library/examples/javascript-form-validation/) - Ensure that respondents fill out all required form fields and the format of values is correct before they are submitted.
* [**Panels**](https://surveyjs.io/form-library/examples/set-properties-on-multiple-questions-using-panel/) - Group questions and configure settings for several questions at once. 
* [**Duplicate group option**](https://surveyjs.io/form-library/examples/dynamic-matrix-table-question-with-dropdown-cell-type/) - Save time and effort by allowing respondents to quickly clone a set of questions or elements without having to recreate them manually.
* [**Multi-file upload**](https://surveyjs.io/form-library/examples/file-upload/) - Enable respondents to upload multiple files of a required format within one file upload field. Once uploaded, files can be managed in preview mode.
* [**Join identifier**](https://surveyjs.io/form-library/examples/merge-question-values/) - Link several questions within a form together and sync their values.

= Display and Skip Logic =
Show or hide questions, skip pages and change texts based on the answers. For example, you can set the [visibility of the next question](https://surveyjs.io/form-library/examples/conditional-logic-and-branching-in-surveys/) based on a given answer, or [customize the text of the Thank you page](https://surveyjs.io/form-library/examples/nps-question/) based on how the respondent answered your questions.

= Styling =
By default, forms use the **Adaptive theme**: they take the colors, fonts, buttons and fields of your WordPress theme and look like a native part of your site.

Want a SurveyJS theme in your site's style? Use **AI themes**: click **Copy prompt for AI** on the Theme tab, paste the prompt into the AI assistant you already use, and paste its answer back. The prompt contains your site's colors, fonts, buttons and fields and the list of SurveyJS design tokens. The plugin keeps only valid tokens, shows the result in the live preview right away and warns if text contrast is too low. The plugin itself never sends anything to an AI service.

Or start from one of the [predefined themes](https://surveyjs.io/form-library/documentation/manage-default-themes-and-styles#add-surveyjs-themes-to-your-application) and adjust colors, fonts and layout on the Theme tab with a live preview. The predefined themes prioritize accessibility: dark mode and a contrast theme are particularly suited for individuals with visual impairments.

Please also refer to the collection of demos specially designed for various real-life use cases: [View Featured Demos](https://surveyjs.io/form-library/examples/overview).

= E-signature capture field =
SurveyJS offers a simple way to add digital signatures to your e-forms with a build-it input field. This [signature field](https://surveyjs.io/form-library/examples/signature-pad-widget-javascript/) enables respondents to sign a form with their mouse pointer or even a finger by simply drawing a signature within the signature pad area.

== External services ==
This plugin can open a form in the SurveyJS UI Builder, a free online visual form editor provided by Devsoft Baltic OÜ at [surveyjs.io/wordpress-ui-builder](https://surveyjs.io/wordpress-ui-builder).

The plugin connects to this service only when you click **Open in free UI Builder** in the form editor. The UI Builder then opens in a new browser tab, and the plugin sends it the form ID, the form name, the form JSON, the theme JSON currently open in the editor, and the address of your site's Privacy Policy page. When you click **Save and back to WordPress**, the UI Builder sends the edited form JSON and theme JSON back to the editor, where you can review them before saving. Form entries and other site data are never sent. If the browser blocks the connection between the two tabs, the editor offers to copy the form JSON and paste the edited JSON back by hand; in that case the plugin sends nothing on its own.

The plugin works without this service: you can create and edit forms in the built-in JSON editor.

This service is provided by Devsoft Baltic OÜ: [Terms of Use](https://surveyjs.io/terms-use), [Privacy Policy](https://surveyjs.io/privacy-policy).

= SurveyJS documentation search (AI connection) =
When you connect an AI app on the SurveyJS Forms > Settings > AI MCP tab (off by default) and the AI app asks to search the SurveyJS documentation, your site sends the search words chosen by the AI app (and, for API lookups, a SurveyJS class and property name) to the SurveyJS documentation service at [mcp.surveyjs.io](https://mcp.surveyjs.io/mcp), provided by Devsoft Baltic OÜ, and passes the found articles back to the AI app. Your forms, entries, settings and site address are never sent. Nothing is sent while the AI connection is off.

This service is provided by Devsoft Baltic OÜ: [Terms of Use](https://surveyjs.io/terms-use), [Privacy Policy](https://surveyjs.io/privacy-policy).

= Entries and AI apps (MCP) =
Only when you turn on "Allow AI apps to read entries" on the SurveyJS Forms > Settings > AI MCP tab (off by default), AI apps connected through MCP can read form entries with the tools surveyjs-count-entries, surveyjs-list-entries, surveyjs-get-entry and surveyjs-summarize-entries. The entries they read go to that AI app and its AI provider under their terms. Uploaded files, signatures and passwords are never included, and entries can't be changed, deleted or exported this way.

= AI provider you connect (AI chat) =
The AI chat (WordPress 7.0 or later) sends requests only when you send a chat message. It uses the WordPress AI Client and the AI provider you connected in Settings > Connectors with your own API key, for example Anthropic, OpenAI or Google. Each request contains your message, earlier messages of the same chat, the plugin's instructions and what the AI reads with its tools: the form JSON, theme and settings, the plugin guide and SurveyJS documentation search results. Files you attach to a message (images, PDFs or audio, for example a photo of a paper form) are sent to the provider with that message and are not stored on your site. When you turn on "Search the web" for a message, the provider searches the web for it with its own search service. Form entries are sent only after you turn on "Allow the AI chat to read entries" on the AI Client tab, and only the entries the chat reads to answer (never uploaded files, signatures or passwords). The provider bills the requests under your account, and its terms and privacy policy apply: [Anthropic](https://www.anthropic.com/legal/privacy), [OpenAI](https://openai.com/policies/privacy-policy/), [Google](https://policies.google.com/privacy). Nothing is sent until you connect a provider and send a message; you can turn the chat off in SurveyJS Forms > Settings > AI Client.

= Voice input in the AI chat (your browser's speech recognition) =
The microphone button in the AI chat uses the speech recognition built into your browser (Web Speech API). It is shown only when your browser supports it, and it works only after you click it and allow the microphone. The browser turns your speech into text in the message box; you review the text and send it yourself. Depending on the browser, the audio is processed on your device or sent to the browser maker's speech service: Google in Chrome ([Privacy Policy](https://policies.google.com/privacy)), Microsoft in Edge ([Privacy Statement](https://privacy.microsoft.com/privacystatement)), Apple in Safari ([Privacy Policy](https://www.apple.com/legal/privacy/)). When the browser can recognize your language on the device, the plugin asks it to do so. SurveyJS Forms never receives or stores the audio; only the text you send goes to your AI provider.

= SurveyJS Forms Add-on download page =
The **Get the Free Add-on** links in the admin menu and the admin bar, and the SurveyJS Forms Add-on links on the Support page, the dashboard widget and in the form editor open the SurveyJS Forms Add-on download page at [surveyjs.io/wordpress-pro](https://surveyjs.io/wordpress-pro) in your browser. The link carries the address of your site's admin area (`wp_admin_url`) and labels of where in the plugin it was clicked (`utm_source`, `utm_medium`). Nothing is sent until you click a link.

This service is provided by Devsoft Baltic OÜ: [Terms of Use](https://surveyjs.io/terms-use), [Privacy Policy](https://surveyjs.io/privacy-policy).

= Google reCAPTCHA =
The plugin can protect forms from spam with Google reCAPTCHA v2 (checkbox) or reCAPTCHA v3 (invisible). It is off by default and is used only when you choose reCAPTCHA in SurveyJS Forms > Settings, enter your keys, and turn on CAPTCHA for a form.

On pages with such a form, the visitor's browser loads the reCAPTCHA script from google.com. Google receives the visitor's IP address, browser and device data, and the visitor's interaction with the page and the widget. When the form is submitted, your site sends the reCAPTCHA token and your secret key to [https://www.google.com/recaptcha/api/siteverify](https://www.google.com/recaptcha/api/siteverify) to check that the respondent is not a bot. Form answers are never sent to Google.

This service is provided by Google LLC: [Terms of Service](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).

= Cloudflare Turnstile =
The plugin can protect forms from spam with Cloudflare Turnstile. It is off by default and is used only when you choose Turnstile in SurveyJS Forms > Settings, enter your keys, and turn on CAPTCHA for a form.

On pages with such a form, the visitor's browser loads the Turnstile script from challenges.cloudflare.com. Cloudflare receives the visitor's IP address, browser and device data, and the results of the browser challenge. When the form is submitted, your site sends the Turnstile token and your secret key to [https://challenges.cloudflare.com/turnstile/v0/siteverify](https://challenges.cloudflare.com/turnstile/v0/siteverify) to check that the respondent is not a bot. Form answers are never sent to Cloudflare.

This service is provided by Cloudflare, Inc.: [Terms of Use](https://www.cloudflare.com/website-terms/), [Turnstile Privacy Addendum](https://www.cloudflare.com/turnstile-privacy-policy/), [Privacy Policy](https://www.cloudflare.com/privacypolicy/).

= hCaptcha =
The plugin can protect forms from spam with hCaptcha. It is off by default and is used only when you choose hCaptcha in SurveyJS Forms > Settings, enter your keys, and turn on CAPTCHA for a form.

On pages with such a form, the visitor's browser loads the hCaptcha script from js.hcaptcha.com. hCaptcha receives the visitor's IP address, browser and device data, and the visitor's interaction with the widget. When the form is submitted, your site sends the hCaptcha token, your site key and your secret key to [https://api.hcaptcha.com/siteverify](https://api.hcaptcha.com/siteverify) to check that the respondent is not a bot. Form answers are never sent to hCaptcha.

This service is provided by Intuition Machines, Inc.: [Terms of Service](https://www.hcaptcha.com/terms), [Privacy Policy](https://www.hcaptcha.com/privacy).

= Akismet =
The plugin can check entries for spam with Akismet. It is off by default and is used only when the Akismet plugin is active and connected to your Akismet account and you turn on "Check entries with Akismet" for a form.

When such a form is submitted, your site sends to Akismet through the Akismet plugin: the respondent's IP address, browser user agent, the address of the page with the form, the answers that look like the respondent's name, email and website, and the other text answers, together with your site address, language and the role of a logged-in respondent. Akismet answers whether the entry is spam. Choice, rating, file and other non-text answers are not sent.

This service is provided by Automattic Inc.: [Terms of Service](https://akismet.com/tos/), [Privacy Policy](https://automattic.com/privacy/).

= Webhooks you add =
When you add a webhook to a form (Integrations tab of the form editor, none by default), every entry of that form that matches the webhook's conditions is sent to the URL you enter: the form ID and name, the entry ID, date and admin link, the site name and address, and all answers, with download links to the uploaded files that work for 7 days. "Send test request" sends the latest entry of the form, or sample answers when there is none. The receiving service is chosen by you, for example Zapier, Make or n8n, and its terms and privacy policy apply; mention it in your privacy policy. Nothing is sent while a form has no active webhook.

= Slack =
The plugin can post a message to a Slack channel for each entry. It is off by default and is used only when you connect a Slack incoming webhook URL on SurveyJS Forms > Settings > Integrations and add a Slack integration to a form. Connecting and "Test" post a short "connected" message to the channel. For each entry of that form that matches the integration's conditions, your site sends to Slack the message you set up: its heading, the answers its text includes and a link to the entry in your WordPress admin. "Send test" in the form editor posts the latest entry, or sample answers when there is none.

This service is provided by Slack Technologies, LLC: [Terms of Service](https://slack.com/terms-of-service), [Privacy Policy](https://slack.com/trust/privacy/privacy-policy).

= Mailchimp =
The plugin can add or update a contact in a Mailchimp audience for each entry. It is off by default and is used only when you connect a Mailchimp API key on SurveyJS Forms > Settings > Integrations and add a Mailchimp integration to a form. Connecting reads your Mailchimp account name and email; the form editor reads your audiences and their merge fields. For each entry of that form that matches the integration's conditions, your site sends to Mailchimp the respondent's email address, the answers you mapped to merge fields and the tags you set. "Send test" sends the latest entry of the form; without entries it only checks that the audience can be reached.

This service is provided by The Rocket Science Group LLC d/b/a Mailchimp (Intuit): [Terms of Use](https://mailchimp.com/legal/terms/), [Privacy Policy](https://www.intuit.com/privacy/statement/).

== Installation ==

SurveyJS plugin is free. It works on [self-hosted](https://wordpress.com/go/website-building/wordpress-com-vs-wordpress-org/) WordPress and on WordPress.com plans that support plugins.

**Install the plugin from the WordPress directory**

1. Inside your admin area, go to Plugins > Add New Plugin.
2. Search for "surveyjs".
3. Click Add New Plugin and then click Install now.
4. Once installed, activate the plugin.

**Upload & Install the plugin using the WordPress admin plugin upload**

1. Go to the [WordPress.org plugin repository](https://wordpress.org/plugins/surveyjs/) and search for the SurveyJS plugin.
2. Download the zip file with the plugin.
3. Inside your admin area, go to Plugins > Add New Plugin.
4. Click Upload Plugin and then click Install now.
5. Once installed, activate the plugin.

**Protect uploaded files on Nginx**

Files that respondents upload are saved to `wp-content/uploads/surveyjs/`. On Apache the plugin blocks direct access to this folder with an `.htaccess` file. Nginx does not read `.htaccess`, so add this rule to the `server` block of your site and reload Nginx:

`location ^~ /wp-content/uploads/surveyjs/ { deny all; }`

In Local (localwp.com), which uses Nginx by default, add the rule to `conf/nginx/site.conf.hbs` in the site folder (not to `site.conf`, which Local overwrites) and restart the site.

Answers link to files through a permission check, so this matters for entries saved by earlier versions and for addresses copied elsewhere. Tools > Site Health warns when the files can be opened by their direct address. On WordPress VIP, use Access-Controlled Files: see "Protect uploaded files on WordPress VIP" in the plugin's GitHub readme.

Administrators still open the files through the plugin, so links in the entries, the CSV export and emails keep working.

== Frequently Asked Questions ==

= Where is the visual form designer? =
Survey Creator, the visual form designer, has moved to the free SurveyJS Forms Add-on, because WordPress.org hosts only GPL-licensed code and Survey Creator is a commercial SurveyJS component. Your forms and entries are not affected. Without the add-on, edit forms in the built-in JSON editor or click **Open in free UI Builder** in the form editor.

= Does it work on Nginx and Local (localwp.com)? =
Yes. Nginx does not read the `.htaccess` file that protects `wp-content/uploads/surveyjs/`, so add this rule to the `server` block of the site and reload Nginx:

`location ^~ /wp-content/uploads/surveyjs/ { deny all; }`

In Local, add it to `conf/nginx/site.conf.hbs` in the site folder and restart the site.

= My custom CSS does not change the form. Why? =
The plugin protects forms from the styles of your WordPress theme, so a form looks right in any theme. The same protection outweighs ordinary custom CSS. To change colors, fonts, corners and sizes, use the **Theme** tab of the form editor. For other changes, add `!important` to your rule, for example `.sd-btn { border-radius: 0 !important; }`.

= How do I make a form wider? =
By default a form is as wide as your theme's content column. Select the SurveyJS Forms block and choose **Wide width** or **Full width** in the Align menu of the block toolbar; the form then fills the wider area. The options appear when your theme supports wide alignment, as most current themes do.

= How do I connect Claude, Codex or Cursor? =
Open SurveyJS Forms > Settings > AI MCP, turn on the AI connection, install MCP Adapter with one click and confirm that a connection password may be created. Then either download the connection file and paste the setup prompt into your assistant (it adds the connection, tests it and deletes the file), or choose your app under "Set it up manually", click Create connection and follow the steps. To let the AI change forms, also click "Enable MCP write access". The site must use HTTPS; on a local development site add `define( 'WP_ENVIRONMENT_TYPE', 'local' );` to wp-config.php. If the AI app gets "401" errors on shared hosting, your server may drop the Authorization header; ask your host to pass it to PHP.

= Does the AI chat cost money? =
The chat uses your own API key from an AI provider, and the provider bills each request, usually a few cents per chat. Subscriptions such as Claude Pro or ChatGPT Plus don't include API credits. The MCP connection works with the AI app you already pay for and costs nothing extra.

= How do I send entries to Slack or Mailchimp? =
Open SurveyJS Forms > Settings > Integrations and connect the account: for Slack paste an incoming webhook URL (in Slack: Create New App > From scratch > Incoming Webhooks > Add New Webhook), for Mailchimp an API key (Profile > Extras > API keys). Then open your form, go to the Integrations tab, choose the service, click Add integration, choose what to send and click Send test (a single connection and a single Mailchimp audience are chosen for you). For Mailchimp, click Add "Subscribe me to the newsletter" question, so only people who check it are added. Save the form.

= How do I send entries to Zapier, Make or n8n? =
Create a webhook trigger in that service: "Catch Hook" in Webhooks by Zapier, "Custom webhook" in Make or the "Webhook" node in n8n (POST). Copy its URL, open your form, go to the Integrations tab, click Add webhook, paste the URL and click Send test request, so the service sees a sample entry. Then map the fields under "answers" (one per question, by question name) and save the form. Webhooks are sent by WP-Cron right after the submission; on a site without visitors and with a broken loopback request (see Tools > Site Health) they wait for the next page view.

== Screenshots ==

1. All Forms: your forms and the number of entries in each.
2. Add New Form: start from a ready-made template, your own template or a blank form, with categories, search and a live preview.
3. Form editor: the built-in JSON editor with validation hints and a live preview. Open in free UI Builder edits the form visually.
4. Theme tab: the Adaptive theme, predefined themes and AI themes made from your site's styles.
5. Email tab: the email notification with recipients, sender, Reply-To, subject and message.
6. Entries: a column per question, unread and read entries, search and CSV export.
7. A form on a page: questions appear based on the answers.

== Release Notes ==
Check out SurveyJS [Release Notes](https://surveyjs.io/stay-updated/release-notes) to keep yourself informed about our latest enhancements and new features introduced every week.

== Changelog ==

= 3.2.4 =
Survey Creator, the visual form designer, has moved to the free SurveyJS Forms Add-on. Your forms and entries are not affected. Without the add-on, edit forms in the new JSON editor or in the free online UI Builder.
Added: AI. Build and change forms by asking the AI chat right in your WordPress admin (your own API key, WordPress 7.0 or later) or your own AI assistant such as Claude, ChatGPT or Cursor through MCP. Off by default; AI changes are drafts until you click Save.
Added: CC and BCC fields for the email notification and the auto-reply; form importers now bring over CC and BCC addresses.
Added: webhooks. Send every entry as JSON to Zapier, Make, n8n or any URL, with conditions, custom headers, a test button, a request log and automatic retries.
Added: Trash for forms. A deleted form goes to the Trash with its entries, so you can restore it with Undo or from the Trash view; WordPress deletes it permanently with its entries after 30 days.
Added: integrations with Slack and Mailchimp. Connect accounts once on Settings > Integrations (keys are stored encrypted), then send each entry to a Slack channel or a Mailchimp audience from the form's new Integrations tab, with field mapping, tags, conditions, a test button, retries and Send again.

= 2.5.7 =
Fixed: a survey failed to load on a page when its JSON contained escaped double quotes, for example in an HTML link or an expression.

= 2.5.6 =
Security: added permission checks to the file upload and file delete handlers. Survey respondents can attach files again: an upload is only accepted when the survey really has a file question, only allowed file types go through, there is a size limit, and files are saved to a separate folder where PHP cannot run.

== Upgrade Notice ==

= 3.2.4 =
Survey Creator, the visual form designer, has moved to the free SurveyJS Forms Add-on. Your forms and entries are not affected. Without the add-on, edit forms in the new JSON editor or in the free online UI Builder.

== Support ==

* [View Support Forum](https://wordpress.org/support/plugin/surveyjs)
* [Visit SurveyJS Support Page](https://surveyjs.io/support)

== Our web sites ==

* [https://surveyjs.io/](https://surveyjs.io/)
* [https://devsoftbaltic.com/](https://devsoftbaltic.com/)

== Contact us ==
info@devsoftbaltic.com
