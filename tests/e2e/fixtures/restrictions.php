<?php
// Runs inside the WordPress container (php reads it from stdin): sets up entries and settings the specs cannot create through the UI quickly.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
global $wpdb;

$command = $argv[1] ?? '';
$table = SurveyJS_Entries::table();

if ( 'settings' === $command ) {
    $form = SurveyJS_Forms::find( (int) $argv[2] );
    $settings = array_merge( SurveyJS_Form_Settings::get( $form ), json_decode( $argv[3], true ) );
    SurveyJS_Forms::update( $form->id, array( 'settings' => wp_json_encode( SurveyJS_Form_Settings::sanitize( $settings, $form ) ) ) );
    echo wp_json_encode( SurveyJS_Form_Settings::get( SurveyJS_Forms::find( $form->id ) ) );
} elseif ( 'insert' === $command ) {
    $ids = array();
    for ( $i = 0; $i < (int) $argv[3]; $i++ ) {
        $ids[] = SurveyJS_Entries::insert( (int) $argv[2], wp_json_encode( array( 'e2e' => 'inserted' ) ), array(
            'status'     => $argv[5] ?? 'unread',
            'created_at' => gmdate( 'Y-m-d H:i:s', time() - (int) $argv[4] * DAY_IN_SECONDS ),
        ) );
    }
    echo wp_json_encode( array( 'ids' => $ids ) );
} elseif ( 'entries' === $command ) {
    echo wp_json_encode( $wpdb->get_results( $wpdb->prepare( 'SELECT id, user_id, ip, ip_hash, status FROM %i WHERE form_id = %d ORDER BY id', $table, (int) $argv[2] ) ) );
} elseif ( 'site-retention' === $command ) {
    $settings = (array) get_option( SurveyJS_SettingsPage::OPTION );
    $settings['entry_retention_days'] = (int) $argv[2];
    update_option( SurveyJS_SettingsPage::OPTION, $settings );
    echo wp_json_encode( array( 'days' => SurveyJS_SettingsPage::retention_days() ) );
} elseif ( 'run-retention' === $command ) {
    do_action( SurveyJS_Entry_Retention::HOOK );
    echo '{}';
} elseif ( 'scheduled' === $command ) {
    echo wp_json_encode( array( 'next' => wp_next_scheduled( SurveyJS_Entry_Retention::HOOK ) ) );
} elseif ( 'site-time' === $command ) {
    $time = time() + (int) $argv[2] * MINUTE_IN_SECONDS;
    echo wp_json_encode( array(
        'value'     => wp_date( 'Y-m-d\TH:i', $time ),
        'formatted' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time - $time % MINUTE_IN_SECONDS ),
    ) );
} elseif ( 'unschedule' === $command ) {
    SurveyJS_Entry_Retention::unschedule();
    echo '{}';
}
