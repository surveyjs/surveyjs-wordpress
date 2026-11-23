<?php
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- absolute path of WordPress in the sandbox container; the script is piped to php over stdin, so it has no __DIR__.
if ( ! defined( 'ABSPATH' ) ) exit;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

const SURVEYJS_E2E_AKISMET_PLUGIN = 'akismet/akismet.php';

$command = $argv[1] ?? '';
if ( 'activate' === $command ) {
    $was_active = is_plugin_active( SURVEYJS_E2E_AKISMET_PLUGIN );
    delete_option( 'surveyjs_e2e_akismet_request' );
    $activation = $was_active ? null : activate_plugin( SURVEYJS_E2E_AKISMET_PLUGIN );
    echo wp_json_encode( array( 'wasActive' => $was_active, 'error' => is_wp_error( $activation ) ? $activation->get_error_message() : null ) ), "\n";
} elseif ( 'restore' === $command ) {
    if ( 'false' === ( $argv[2] ?? '' ) ) {
        deactivate_plugins( SURVEYJS_E2E_AKISMET_PLUGIN );
    }
    delete_option( 'surveyjs_e2e_akismet_request' );
    echo "{}\n";
} elseif ( 'last-request' === $command ) {
    parse_str( (string) get_option( 'surveyjs_e2e_akismet_request', '' ), $request );
    echo wp_json_encode( $request ), "\n";
} elseif ( 'redact' === $command ) {
    echo wp_json_encode( array(
        'key'      => SurveyJS_Akismet::api_key(),
        'redacted' => SurveyJS_AI_Abilities::redact( 'The Akismet key is ' . SurveyJS_Akismet::api_key() ),
    ) ), "\n";
} elseif ( 'existing-form' === $command ) {
    $form_id = SurveyJS_Forms::create( array(
        'name'     => 'E2E existing form ' . microtime( true ),
        'json'     => wp_json_encode( array( 'elements' => array( array( 'type' => 'text', 'name' => 'q1' ) ) ) ),
        'settings' => wp_json_encode( array( 'storeEntries' => true ) ),
    ) );
    $enabled = SurveyJS_Submit_Time::is_enabled( SurveyJS_Form_Settings::get( SurveyJS_Forms::find( $form_id ) ) );
    SurveyJS_Forms::delete( $form_id );
    echo wp_json_encode( array( 'enabled' => $enabled, 'newForm' => SurveyJS_Form_Settings::for_new_form()['minSubmitTimeEnabled'] ) ), "\n";
}
