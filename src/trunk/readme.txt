=== SurveyJS Forms - Contact Forms, Survey & Form Builder & More ===
Contributors: DevSoft Baltic
Tags: contact form, form builder, survey, forms, quiz
Requires at least: 6.4
Tested up to: 7.1
Stable tag: 2.5.6
Requires PHP: 8.2

Contact forms, surveys and quizzes powered by SurveyJS. Email notifications, file uploads, entries and AI themes that match your site — free.

== Description ==

SurveyJS lets you build **contact forms, surveys, quizzes and multi-page forms** of any complexity and add them to any page with a block or a shortcode. Forms run on the open-source SurveyJS Form Library.

= Contact forms with email =
* **Email notification** to the site owner on every submission: recipients, sender, subject and message.
* **Reply-To** set to the respondent's email, so you can answer right from your inbox.
* **Auto-reply** to the respondent.
* **Tags** in the subject and message: all answers at once or the answer to a single question.
* **HTML emails**, **file attachments** and a **test email** button.
* **Spam protection** with a hidden honeypot field and optional **CAPTCHA**: Google reCAPTCHA v2 (checkbox), reCAPTCHA v3 (invisible) or Cloudflare Turnstile.

= Entries =
* Submitted entries are stored in WordPress; you can turn this off for any form.
* Entries table with a column per question, entry view and CSV export.

= Privacy and GDPR =
* **GDPR agreement**: turn on the **GDPR agreement** checkbox in the form editor to add a required consent checkbox to the end of the form; turn it off to remove it. Its text links to your site's Privacy Policy page (Settings > Privacy); without a published Privacy Policy page the text has no link. The form is not submitted until the respondent checks it. Entries, the CSV export and emails show the consent text the respondent accepted.
* **Personal data export and erasure**: entries work with the WordPress tools Tools > Export Personal Data and Tools > Erase Personal Data. An entry belongs to a person when one of its answers is their email address or when they submitted it while logged in. The eraser deletes these entries together with their uploaded files.
* **IP addresses** are not stored by default. Turn on SurveyJS Forms > Settings > IP addresses only if you need them and your privacy policy mentions it.
* Files uploaded by respondents are stored on your site and are not public: only administrators can open them from the entries table, the entry view and the CSV export. The email notification to the site owner attaches the files and links to them; the links open only for logged-in administrators. The auto-reply to the respondent lists only the file names.
* Files are deleted together with their entry or form. Files uploaded to forms that were never submitted are deleted by a daily cleanup once they are a day old.
* **Uninstall**: by default, deleting the plugin keeps your forms, entries and files. Turn on SurveyJS Forms > Settings > Uninstall to delete all forms, entries, uploaded files and settings when the plugin is deleted.
* Form answers stay on your site and are sent only in the emails you set up. The optional CAPTCHA services and the free online UI Builder never receive form answers (see External services below).

= Form builder =
* Describe your form in JSON with validation hints, formatting and a live preview.
* Add a logo and images to the form from the WordPress Media Library.
* **Adaptive theme**: by default the form takes the colors, fonts, buttons and fields of your site's theme.
* **AI themes**: copy a prompt with your site's styles into your own AI assistant and paste back a SurveyJS theme that matches your site. The plugin checks the theme and warns about hard-to-read text.
* Prefer a visual editor? Open the form in the **free online SurveyJS UI Builder** with drag & drop, a logic editor and a theme editor. When you save there, the form comes back to WordPress. Optional: the plugin works fully without it (see External services below).

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

The plugin connects to this service only when you click **Open in free UI Builder** in the form editor. The UI Builder then opens in a new browser tab, and the plugin sends it the form ID, the form name, the form JSON, and the theme JSON currently open in the editor. When you click **Save and back to WordPress**, the UI Builder sends the edited form JSON and theme JSON back to the editor, where you can review them before saving. Form entries and other site data are never sent.

The plugin works without this service: you can create and edit forms in the built-in JSON editor.

This service is provided by Devsoft Baltic OÜ: [Terms of Use](https://surveyjs.io/terms-use), [Privacy Policy](https://surveyjs.io/privacy-policy).

= Google reCAPTCHA =
The plugin can protect forms from spam with Google reCAPTCHA v2 (checkbox) or reCAPTCHA v3 (invisible). It is off by default and is used only when you choose reCAPTCHA in SurveyJS Forms > Settings, enter your keys, and turn on CAPTCHA for a form.

On pages with such a form, the visitor's browser loads the reCAPTCHA script from google.com. Google receives the visitor's IP address, browser and device data, and the visitor's interaction with the page and the widget. When the form is submitted, your site sends the reCAPTCHA token and your secret key to [https://www.google.com/recaptcha/api/siteverify](https://www.google.com/recaptcha/api/siteverify) to check that the respondent is not a bot. Form answers are never sent to Google.

This service is provided by Google LLC: [Terms of Service](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).

= Cloudflare Turnstile =
The plugin can protect forms from spam with Cloudflare Turnstile. It is off by default and is used only when you choose Turnstile in SurveyJS Forms > Settings, enter your keys, and turn on CAPTCHA for a form.

On pages with such a form, the visitor's browser loads the Turnstile script from challenges.cloudflare.com. Cloudflare receives the visitor's IP address, browser and device data, and the results of the browser challenge. When the form is submitted, your site sends the Turnstile token and your secret key to [https://challenges.cloudflare.com/turnstile/v0/siteverify](https://challenges.cloudflare.com/turnstile/v0/siteverify) to check that the respondent is not a bot. Form answers are never sent to Cloudflare.

This service is provided by Cloudflare, Inc.: [Terms of Use](https://www.cloudflare.com/website-terms/), [Turnstile Privacy Addendum](https://www.cloudflare.com/turnstile-privacy-policy/), [Privacy Policy](https://www.cloudflare.com/privacypolicy/).

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

Administrators still open the files through the plugin, so links in the entries, the CSV export and emails keep working.

== Release Notes ==
Check out SurveyJS [Release Notes](https://surveyjs.io/stay-updated/release-notes) to keep yourself informed about our latest enhancements and new features introduced every week.

== Changelog ==
Check out SurveyJS [Changelog](https://surveyjs.io/changelog).


= v2.5.6 =
Security: added permission checks to the file upload and file delete handlers. Survey respondents can attach files again: an upload is only accepted when the survey really has a file question, only allowed file types go through, there is a size limit, and files are saved to a separate folder where PHP cannot run.


= v2.5.5 =

== Support ==

* [View Support Forum](https://wordpress.org/support/plugin/surveyjs)
* [Visit SurveyJS Support Page](https://surveyjs.io/support)

== Our web sites ==

* [https://surveyjs.io/](https://surveyjs.io/)
* [http://devsoftbaltic.com/](http://devsoftbaltic.com/)

== Contact us ==
info@devsoftbaltic.com
