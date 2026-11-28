<?php
// Runs inside the WordPress container (php reads it from stdin).
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$found = array();
foreach ( SurveyJS_Forms::all() as $survey ) {
    if ( $survey->name === ( $argv[1] ?? '' ) ) {
        $found[] = array(
            'id'       => $survey->id,
            'json'     => json_decode( $survey->json ),
            'theme'    => json_decode( $survey->theme ),
            'settings' => json_decode( $survey->settings ),
        );
    }
}
echo wp_json_encode( $found );
