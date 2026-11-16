<?php
/**
 * Plugin Name: SurveyJS E2E stream uploads
 * Description: Copied to mu-plugins by file-storage.spec.js only while it runs. Moves uploads behind the sjstest:// stream wrapper,
 * which behaves like VIP Files (vip://): folders cannot be listed or removed, chmod() and touch() fail, realpath() returns false,
 * and the files are not reachable by their uploads URL. Calls the platform does not support are logged for the spec to check.
 */

class SurveyJS_E2E_Stream {

    const SCHEME = 'sjstest';

    public $context;
    private $handle;

    public static function root() {
        return WP_CONTENT_DIR . '/sjstest-uploads/';
    }

    public static function local( $url ) {
        return self::root() . substr( $url, strlen( self::SCHEME . '://' ) );
    }

    public static function log( $operation, $url ) {
        file_put_contents( WP_CONTENT_DIR . '/sjstest-unsupported.log', $operation . ' ' . $url . "\n", FILE_APPEND );
        return false;
    }

    public function stream_open( $path, $mode, $options, &$opened_path ) {
        $local = self::local( $path );
        if ( strpbrk( $mode, 'waxc' ) && ! is_dir( dirname( $local ) ) ) {
            mkdir( dirname( $local ), 0777, true );
        }
        $this->handle = fopen( $local, $mode );
        return false !== $this->handle;
    }

    public function stream_read( $count ) {
        return fread( $this->handle, $count );
    }

    public function stream_write( $data ) {
        return fwrite( $this->handle, $data );
    }

    public function stream_eof() {
        return feof( $this->handle );
    }

    public function stream_tell() {
        return ftell( $this->handle );
    }

    public function stream_seek( $offset, $whence ) {
        return 0 === fseek( $this->handle, $offset, $whence );
    }

    public function stream_flush() {
        return fflush( $this->handle );
    }

    public function stream_close() {
        fclose( $this->handle );
    }

    public function stream_stat() {
        return fstat( $this->handle );
    }

    public function stream_lock( $operation ) {
        return false;
    }

    public function stream_set_option( $option, $arg1, $arg2 ) {
        return false;
    }

    // Like VIP Files, a folder always exists.
    public function url_stat( $path, $flags ) {
        $local = self::local( $path );
        if ( ! file_exists( $local ) && ! str_contains( basename( $local ), '.' ) ) {
            mkdir( $local, 0777, true );
        }
        return file_exists( $local ) ? stat( $local ) : false;
    }

    public function unlink( $path ) {
        return unlink( self::local( $path ) );
    }

    public function mkdir( $path, $mode, $options ) {
        $local = self::local( $path );
        return is_dir( $local ) || mkdir( $local, 0777, true );
    }

    public function rename( $from, $to ) {
        return self::log( 'rename', $from );
    }

    public function rmdir( $path, $options ) {
        return self::log( 'rmdir', $path );
    }

    public function dir_opendir( $path, $options ) {
        return self::log( 'opendir', $path );
    }

    public function stream_metadata( $path, $option, $value ) {
        return self::log( 'metadata', $path );
    }
}

stream_wrapper_register( SurveyJS_E2E_Stream::SCHEME, 'SurveyJS_E2E_Stream' );

add_filter(
    'upload_dir',
    function ( $dirs ) {
        $local = WP_CONTENT_DIR . '/uploads';
        $stream = SurveyJS_E2E_Stream::SCHEME . '://uploads';
        foreach ( array( 'path', 'basedir' ) as $key ) {
            if ( str_starts_with( $dirs[ $key ], $local ) ) {
                $dirs[ $key ] = $stream . substr( $dirs[ $key ], strlen( $local ) );
            }
        }
        return $dirs;
    }
);
