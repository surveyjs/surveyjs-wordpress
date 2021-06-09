=== SurveyJS ===
Contributors: devsoftbaltic
Tags: survey, quiz, form builder, survey creator, survey maker, quiz creator
Requires at least: 4.6
Tested up to: 5.3.2
Stable tag: trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SurveyJS for WordPress - Survey, Quiz, Survey Creator and Form Builder plug-in.

== Description ==

Easy to use, drag & drop Survey Creator with myriad options. It is based on popular in JavaScript developer community [SurveyJS Library](https://surveyjs.io/Overview/Library/) and [Survey Creator](https://surveyjs.io/Survey/Survey-Creator/).

*Main features:*
* It has 15 elements (questions and panels) from simple inputs like text and dropdown to Panel containers and dynamic matrix (table), that allows to build you complex forms.
* Multi pages support
* It is localized on many languages and supporting multi language surveys/forms (one survey for several languages).
* Control survey flow by setting visibility expression for pages, panels, questions and even individual items in checkboxes, radio groups and dropdowns.
* Fill data for checkboxes, radio groups and dropdowns from web services.
* Validate user inputs with several built-in validators.
* Show results in a read only survey.
* Several good-looking themes and much more

The plugin supports the Gutenberg editor and old the editor as well.

To find out more and review SurveyJS Examples please go to [https://surveyjs.io/Examples/Library/](https://surveyjs.io/Examples/Library/).

== Installation and usage ==

1. Upload SurveyJS plugin files to the `/wp-content/plugins/surveyjs` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the SurveyJS page of wordpress admin menu and click the “Add Survey”.
4. Use the SurveyJS->Setting page to choose a theme.
5. Go to the Posts page and click the “Add Survey” button. Then choose a survey and click the “Insert” button to add a SurveyJS shortcut.
6. For the Gutenberg Editor, please add "SurveyJS" block in the "Common Blocks" section. Then choose a survey in the block's settings to add a SurveyJS shortcut.
7. Go to the preview and check your survey.
8. Check your wordpress database for *wp_sjs_my_surveys* and *wp_sjs_results* tables.

== Hooks ==
1. surveyjs_save_result :
    add_action( 'surveyjs_save_result', function ( ) {
        // do something with $_POST
    } );

== Screenshots ==

1. My surveys page
2. Survey Creator
3. Results page
4. Settings
5. Choose Gutenberg block
6. Choose the survey
7. Gutenberg block with the shortcode

== Changelog ==

= 0.5 =
* first public release
= 1.0.26 =
* synchronized version with SurveyJS project
* fixed the bug https://surveyjs.answerdesk.io/internal/ticket/details/T332

== Support ==
* [https://wordpress.org/support/plugin/surveyjs](https://wordpress.org/support/plugin/surveyjs)
* [https://github.com/surveyjs/surveyjs/issues](https://github.com/surveyjs/surveyjs/issues) or [https://github.com/surveyjs/editor/issues](https://github.com/surveyjs/editor/issues)
* [https://surveyjs.answerdesk.io](https://surveyjs.answerdesk.io)

== Our web sites ==
* [https://surveyjs.io/](https://surveyjs.io/)
* [http://devsoftbaltic.com/](http://devsoftbaltic.com/)

== Contact us ==
info@devsoftbaltic.com