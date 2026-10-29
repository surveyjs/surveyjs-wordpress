<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Uploaded_Files {

    public static function paths( $value ) {
        $dir = self::dir();
        if ( null === $dir || ! is_array( $value ) ) {
            return array();
        }

        $paths = array();
        foreach ( $value as $file ) {
            $url = is_array( $file ) ? ( $file['content'] ?? '' ) : '';
            if ( ! is_string( $url ) ) {
                continue;
            }
            $name = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
            if ( '' === $name || '.' === $name[0] || 'index.php' === $name ) {
                continue;
            }
            $path = $dir . '/' . $name;
            if ( is_file( $path ) ) {
                $original = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
                $paths[ '' !== $original ? $original : $name ] = $path;
            }
        }
        return $paths;
    }

    public static function entry_paths( $questions, $data ) {
        $paths = array();
        foreach ( $questions as $question ) {
            if ( 'file' === ( $question['type'] ?? '' ) ) {
                $paths = array_merge( $paths, array_values( self::paths( $data[ $question['name'] ] ?? null ) ) );
            }
        }
        return $paths;
    }

    private static function dir() {
        $upload_dir = wp_upload_dir( null, false );
        if ( ! empty( $upload_dir['error'] ) ) {
            return null;
        }
        $dir = realpath( $upload_dir['basedir'] . SurveyJS_UploadFiles::SUBDIR );
        return false === $dir ? null : $dir;
    }
}
