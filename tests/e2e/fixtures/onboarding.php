<?php
// Runs inside the WordPress container: php reads it from stdin.
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';

if ( 'get' === $command ) {
    echo wp_json_encode( array( 'state' => get_option( SurveyJS_Welcome::CHECKLIST_OPTION, null ) ) );
} elseif ( 'set' === $command ) {
    $state = json_decode( (string) ( $argv[2] ?? 'null' ), true );
    if ( null === $state ) {
        delete_option( SurveyJS_Welcome::CHECKLIST_OPTION );
    } else {
        update_option( SurveyJS_Welcome::CHECKLIST_OPTION, $state );
    }
    echo '{}';
} elseif ( 'entry' === $command ) {
    echo wp_json_encode( array( 'received' => SurveyJS_Welcome::received_entry( array( (int) $argv[2] ) ) ) );
} elseif ( 'progress' === $command ) {
    $state = array(
        'dismissed' => false,
        'completed' => false,
        'progress'  => array(
            'done'  => (int) $argv[2],
            'total' => 4,
            'next'  => (string) $argv[3],
            'url'   => admin_url( 'post-new.php?post_type=page' ),
            'time'  => time(),
        ),
    );
    update_option( SurveyJS_Welcome::CHECKLIST_OPTION, $state );
    echo '{}';
}
