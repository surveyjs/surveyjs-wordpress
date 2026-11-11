<?php
/*
Plugin Name: SurveyJS Forms
Plugin URI: https://wordpress.org/plugins/surveyjs
Description: Contact forms, surveys and quizzes powered by SurveyJS. Email notifications, auto-replies, file uploads and entries stored in WordPress — free.
Version: 2.5.6
Requires at least: 6.5
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

    function surveyjs_get_all_posts( $args ) {
        $page_size = 100;
        $items = array();
        do {
            $query = new WP_Query( array_merge( $args, array(
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
                'posts_per_page'      => $page_size,
                'offset'              => count( $items ),
            ) ) );
            $items = array_merge( $items, $query->posts );
        } while ( count( $query->posts ) === $page_size );
        return $items;
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

    include_once( __DIR__ . "/service_client.php" );
    include_once( __DIR__ . "/forms.php" );
    include_once( __DIR__ . "/entries.php" );
    include_once( __DIR__ . "/db_install.php" );
    include_once( __DIR__ . "/privacy.php" );
    include_once( __DIR__ . "/admin_bar.php" );
    include_once( __DIR__ . "/upsell.php" );
    include_once( __DIR__ . "/upgrade_notice.php" );
    include( __DIR__ . "/survey_values.php" );
    include( __DIR__ . "/form_settings.php" );
    include( __DIR__ . "/surveyjs_themes.php" );
    include( __DIR__ . "/uploaded_files.php" );
    include( __DIR__ . "/notification.php" );
    include( __DIR__ . "/captcha.php" );
    include( __DIR__ . "/importer.php" );
    include( __DIR__ . "/cf7_importer.php" );
    include( __DIR__ . "/wpforms_importer.php" );
    include( __DIR__ . "/ninja_forms_importer.php" );
    include( __DIR__ . "/fluent_forms_importer.php" );

    include( __DIR__ . "/drafts.php" );
    new SurveyJS_Drafts();

    include( __DIR__ . "/ajax_handlers/insert_survey.php" );
    new SurveyJS_InsertSurveyHandler();

    include( __DIR__ . "/ajax_handlers/save_survey.php" );
    new SurveyJS_SaveSurvey();

    include( __DIR__ . "/ajax_handlers/save_result.php" );
    new SurveyJS_SaveResult();

    include( __DIR__ . "/ajax_handlers/save_partial_result.php" );
    new SurveyJS_SavePartialResult();

    include( __DIR__ . "/ajax_handlers/send_test_email.php" );
    new SurveyJS_SendTestEmail();

    include( __DIR__ . "/ajax_handlers/upload_files.php" );
    new SurveyJS_UploadFiles();

    include( __DIR__ . "/ajax_handlers/delete_file.php" );
    new SurveyJS_DeleteFile();

    include( __DIR__ . "/ajax_handlers/download_file.php" );
    new SurveyJS_DownloadFile();

    add_action( SurveyJS_Uploaded_Files::CLEANUP_HOOK, array( 'SurveyJS_Uploaded_Files', 'cleanup' ) );
    add_action( SurveyJS_Uploaded_Files::DELETE_HOOK, array( 'SurveyJS_Uploaded_Files', 'delete_unreferenced' ) );
    add_action( 'admin_init', array( 'SurveyJS_UploadFiles', 'maybe_update_upload_dir_guards' ) );
    SurveyJS_Forms::init();
    SurveyJS_Entries::init();
    SurveyJS_Privacy::init();
    SurveyJS_Admin_Bar::init();
    SurveyJS_Upsell::init();
    SurveyJS_Upgrade_Notice::init();

    include( __DIR__ . "/initializer.php" );
    new SurveyJS_SurveyJS();
