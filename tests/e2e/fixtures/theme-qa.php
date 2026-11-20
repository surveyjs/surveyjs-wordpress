<?php
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';

if ( 'themes' === $command ) {
    echo wp_json_encode(
        array(
            'active'    => get_stylesheet(),
            'installed' => array_keys( wp_get_themes() ),
        )
    );
} elseif ( 'activate' === $command ) {
    switch_theme( (string) $argv[2] );
    echo wp_json_encode( array( 'active' => get_stylesheet() ) );
} elseif ( 'setup' === $command ) {
    $form_id = SurveyJS_Forms::create(
        array(
            'name'     => (string) $argv[2],
            'json'     => (string) file_get_contents( (string) $argv[3] ), // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- a local file the spec copied into the container.
            'settings' => wp_json_encode( array( 'themeMode' => 'adaptive' ) ),
        )
    );
    $page_id = wp_insert_post(
        array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => (string) $argv[2],
            'post_content' => '<!-- wp:shortcode -->[surveyjs id="' . $form_id . '"]<!-- /wp:shortcode -->',
        )
    );
    echo wp_json_encode(
        array(
            'formId' => $form_id,
            'pageId' => $page_id,
            'url'    => get_permalink( $page_id ),
        )
    );
} elseif ( 'mode' === $command ) {
    SurveyJS_Forms::update( (int) $argv[2], array( 'settings' => wp_json_encode( array( 'themeMode' => (string) $argv[3] ) ) ) );
    echo '{}';
} elseif ( 'cleanup' === $command ) {
    SurveyJS_Forms::delete( (int) $argv[2] );
    wp_delete_post( (int) $argv[3], true );
    echo '{}';
}
