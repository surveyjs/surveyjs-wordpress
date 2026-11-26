<?php
// Runs inside the WordPress container (php reads it from stdin).
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';
$e2e_connections = function () {
    return array_filter( SurveyJS_Connections::all(), function ( $connection ) {
        return 0 === strpos( $connection['label'], 'E2E' );
    } );
};

if ( 'clear' === $command ) {
    update_option( SurveyJS_Connections::OPTION, array_diff_key( SurveyJS_Connections::all(), $e2e_connections() ), false );
    delete_option( 'surveyjs_e2e_integrations_mock' );
    echo '{}';
} elseif ( 'set-key' === $command ) {
    // A key revoked in Mailchimp after it was connected: only the stored credentials change.
    $connections = SurveyJS_Connections::all();
    foreach ( $e2e_connections() as $id => $connection ) {
        if ( 'mailchimp' === $connection['service'] ) {
            $connections[ $id ]['credentials'] = SurveyJS_Connections::encrypt( wp_json_encode( array( 'api_key' => $argv[2] ) ) );
        }
    }
    update_option( SurveyJS_Connections::OPTION, $connections, false );
    echo '{}';
} elseif ( 'state' === $command ) {
    $connections = array_values( array_map( function ( $connection ) {
        return array_diff_key( $connection, array( 'credentials' => true ) ) + array( 'encrypted' => 0 === strpos( $connection['credentials'], SurveyJS_Connections::CIPHER_PREFIX ) );
    }, $e2e_connections() ) );
    echo wp_json_encode( array(
        'mock'        => get_option( 'surveyjs_e2e_integrations_mock', array() ),
        'connections' => $connections,
        'log'         => SurveyJS_Delivery_Queue::log_entries( (int) ( $argv[2] ?? 0 ) ),
        'stored'      => (string) wp_json_encode( get_option( SurveyJS_Connections::OPTION ) ),
    ) );
} elseif ( 'ai' === $command ) {
    $form = SurveyJS_Forms::find( (int) $argv[2] );
    echo wp_json_encode( array(
        'redacted' => SurveyJS_AI_Abilities::redact( (string) ( $argv[3] ?? '' ) ),
        'site'     => SurveyJS_AI_Abilities::get_site_settings()['integrations'],
        'schema'   => SurveyJS_AI_Abilities::settings_schema()['integrations']['description'] ?? '',
        'settings' => $form ? SurveyJS_Form_Settings::get( $form )['integrations'] : null,
    ) );
} elseif ( 'keeps-unknown' === $command ) {
    // An integration of a service whose add-on is inactive: saving the form from the editor must keep it as stored.
    $form = SurveyJS_Forms::find( (int) $argv[2] );
    $settings = SurveyJS_Form_Settings::get( $form );
    $stored = array( 'id' => 'iaddon', 'name' => 'Add-on CRM', 'enabled' => true, 'service' => 'e2e_addon', 'connection' => 'cx', 'template' => "Line 1\nLine 2 %20 <b>" );
    $settings['integrations'][] = $stored;
    SurveyJS_Forms::update( $form->id, array( 'settings' => wp_json_encode( $settings ) ) );
    $form = SurveyJS_Forms::find( $form->id );
    $from_editor = SurveyJS_Form_Settings::get( $form );
    $from_editor['integrations'][ count( $from_editor['integrations'] ) - 1 ]['template'] = 'changed by the editor';
    $saved = SurveyJS_Form_Settings::sanitize( $from_editor, $form )['integrations'];
    echo wp_json_encode( array(
        'kept'    => end( $saved ) === $stored,
        'dropped' => ! array_filter( SurveyJS_Form_Settings::sanitize( $from_editor )['integrations'], function ( $item ) {
            return 'iaddon' === $item['id'];
        } ),
    ) );
}
