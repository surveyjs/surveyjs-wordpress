<?php
/*
Plugin Name: SurveyJS Forms
Plugin URI: https://wordpress.org/plugins/surveyjs
Description: Contact forms, surveys and quizzes powered by SurveyJS. Email notifications, auto-replies, file uploads and entries stored in WordPress — free.
Version: 2.5.6
Requires at least: 6.4
Requires PHP: 8.2
Author: Devsoft Baltic OÜ
Author URI: http://devsoftbaltic.com/
License: "SEE LICENSE IN LICENSE"
License URI: "https://github.com/surveyjs/surveyjs-wordpress/tree/master?tab=License-1-ov-file"
*/
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<?php
    define( 'SURVEYJS_VERSION', '2.5.6' );
    define( 'SURVEYJS_DB_VERSION', '4' );

    function surveyjs_site_url( $path = '' ) {
        $site = apply_filters( 'surveyjs_site_url', defined( 'SURVEYJS_SITE_URL' ) ? SURVEYJS_SITE_URL : 'https://surveyjs.io' );
        return untrailingslashit( $site ) . $path;
    }

    function wps_install() {
        SurveyJS_Install::install();
    }

    function wps_maybe_upgrade() {
        SurveyJS_Install::maybe_upgrade();
    }
    add_action( 'init', 'wps_maybe_upgrade', 1 );

    function wps_uninstall() {
        SurveyJS_Uploaded_Files::unschedule_cleanup();
        delete_option( 'surveyjs_upload_guard_version' );
        SurveyJS_Drafts::unschedule_cleanup();
        SurveyJS_Entries::unschedule_partial_cleanup();
    }
    register_deactivation_hook( __FILE__, 'wps_uninstall');

    function wps_activate( $network_wide = false ) {
        wps_install();
        $bulk = isset( $_POST['checked'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $cli = defined( 'WP_CLI' ) && WP_CLI;
        $rest = defined( 'REST_REQUEST' ) && REST_REQUEST;
        if ( ! $network_wide && ! $bulk && ! $cli && ! $rest && ! wp_doing_ajax() && current_user_can( 'manage_options' ) ) {
            set_transient( 'surveyjs_activation_redirect', get_current_user_id(), 30 );
        }
    }
    register_activation_hook( __FILE__, 'wps_activate');

    include_once("service_client.php");
    include_once( __DIR__ . "/forms.php" );
    include_once( __DIR__ . "/entries.php" );
    include_once( __DIR__ . "/db_install.php" );
    include_once( __DIR__ . "/privacy.php" );
    include_once( __DIR__ . "/admin_bar.php" );
    include_once( __DIR__ . "/upsell.php" );
    include("survey_values.php");
    include("form_settings.php");
    include("surveyjs_themes.php");
    include("uploaded_files.php");
    include("notification.php");
    include("captcha.php");
    include("importer.php");
    include("cf7_importer.php");
    include("wpforms_importer.php");
    include("ninja_forms_importer.php");
    include("fluent_forms_importer.php");

    include("drafts.php");
    new SurveyJS_Drafts();

    include("ajax_handlers/insert_survey.php");
    new SurveyJS_InsertSurveyHandler();

    include("ajax_handlers/save_survey.php");
    new SurveyJS_SaveSurvey();

    include("ajax_handlers/save_result.php");
    new SurveyJS_SaveResult();

    include("ajax_handlers/save_partial_result.php");
    new SurveyJS_SavePartialResult();

    include("ajax_handlers/send_test_email.php");
    new SurveyJS_SendTestEmail();

    include("ajax_handlers/upload_files.php");
    new SurveyJS_UploadFiles();

    include("ajax_handlers/delete_file.php");
    new SurveyJS_DeleteFile();

    include("ajax_handlers/download_file.php");
    new SurveyJS_DownloadFile();

    add_action( SurveyJS_Uploaded_Files::CLEANUP_HOOK, array( 'SurveyJS_Uploaded_Files', 'cleanup' ) );
    add_action( SurveyJS_Uploaded_Files::DELETE_HOOK, array( 'SurveyJS_Uploaded_Files', 'delete_unreferenced' ) );
    add_action( 'admin_init', array( 'SurveyJS_UploadFiles', 'maybe_update_upload_dir_guards' ) );
    SurveyJS_Forms::init();
    SurveyJS_Entries::init();
    SurveyJS_Privacy::init();
    SurveyJS_Admin_Bar::init();
    SurveyJS_Upsell::init();

    include("initializer.php");
    new SurveyJS_SurveyJS();
?>
