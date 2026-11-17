<?php
/**
 * Plugin Name: SurveyJS E2E upload prefilter
 * Description: Copied to mu-plugins by file-storage.spec.js only while it runs. Acts like a virus scanner (rejects files named
 * "infected…") and like a plugin that narrows the allowed types through wp_handle_upload_overrides (no CSV).
 */

add_filter(
    'wp_handle_upload_prefilter',
    function ( $file ) {
        if ( str_starts_with( $file['name'], 'infected' ) ) {
            $file['error'] = 'E2E scanner: this file is infected.';
        }
        return $file;
    }
);

add_filter(
    'wp_handle_upload_overrides',
    function ( $overrides ) {
        $mimes = get_allowed_mime_types();
        unset( $mimes['csv'] );
        $overrides['mimes'] = $mimes;
        return $overrides;
    }
);
