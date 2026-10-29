=== SurveyJS Forms - Contact Forms, Survey & Form Builder & More ===
Contributors: DevSoft Baltic
Tags: contact form, form builder, survey, forms, quiz
Requires at least: 6.4
Tested up to: 7.1
Stable tag: 2.5.6
Requires PHP: 8.2

Contact forms, surveys and quizzes powered by SurveyJS. Email notifications, auto-replies, file uploads and entries stored in WordPress — free.

== Description ==

SurveyJS lets you build **contact forms, surveys, quizzes and multi-page forms** of any complexity and add them to any page with a block or a shortcode. Forms run on the open-source SurveyJS Form Library.

= Contact forms with email =
* **Email notification** to the site owner on every submission: recipients, sender, subject and message.
* **Reply-To** set to the respondent's email, so you can answer right from your inbox.
* **Auto-reply** to the respondent.
* **Tags** in the subject and message: all answers at once or the answer to a single question.
* **HTML emails**, **file attachments** and a **test email** button.
* **Spam protection** with a hidden honeypot field.

= Entries =
* Submitted entries are stored in WordPress; you can turn this off for any form.
* Entries table with a column per question, entry view and CSV export.
* Files uploaded by respondents are stored on your site.

= Form builder =
* Describe your form in JSON with validation hints, formatting and a live preview.
* Style the form on the Theme tab.
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
Start from one of the [predefined themes](https://surveyjs.io/form-library/documentation/manage-default-themes-and-styles#add-surveyjs-themes-to-your-application) and adjust colors, fonts and layout on the Theme tab with a live preview. The predefined themes prioritize accessibility: dark mode and a contrast theme are particularly suited for individuals with visual impairments.

Please also refer to the collection of demos specially designed for various real-life use cases: [View Featured Demos](https://surveyjs.io/form-library/examples/overview).

= E-signature capture field =
SurveyJS offers a simple way to add digital signatures to your e-forms with a build-it input field. This [signature field](https://surveyjs.io/form-library/examples/signature-pad-widget-javascript/) enables respondents to sign a form with their mouse pointer or even a finger by simply drawing a signature within the signature pad area.

== External services ==
This plugin can open a form in the SurveyJS UI Builder, a free online visual form editor provided by Devsoft Baltic OÜ at [surveyjs.io/wordpress-ui-builder](https://surveyjs.io/wordpress-ui-builder).

The plugin connects to this service only when you click **Open in free UI Builder** in the form editor. The UI Builder then opens in a new browser tab, and the plugin sends it the form ID, the form name, the form JSON, and the theme JSON currently open in the editor. When you click **Save and back to WordPress**, the UI Builder sends the edited form JSON and theme JSON back to the editor, where you can review them before saving. Form entries and other site data are never sent.

The plugin works without this service: you can create and edit forms in the built-in JSON editor.

This service is provided by Devsoft Baltic OÜ: [Terms of Use](https://surveyjs.io/terms-use), [Privacy Policy](https://surveyjs.io/privacy-policy).

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
