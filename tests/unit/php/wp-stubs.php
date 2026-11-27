<?php
// The smallest part of WordPress the guarded code needs. WP_Hook keeps the core behavior the guard relies on:
// callbacks sit in public $callbacks[ priority ][ id ], and each priority is read when the loop reaches it.

define( 'ABSPATH', __DIR__ . '/' );
define( 'MB_IN_BYTES', 1048576 );
define( 'DAY_IN_SECONDS', 86400 );

class WP_Hook {
    public $callbacks = array();

    public function add_filter( $callback, $priority, $accepted_args ) {
        $this->callbacks[ $priority ][ _wp_filter_build_unique_id( $callback ) ] = array( 'function' => $callback, 'accepted_args' => $accepted_args );
        ksort( $this->callbacks, SORT_NUMERIC );
    }

    public function remove_filter( $callback, $priority ) {
        $id = _wp_filter_build_unique_id( $callback );
        $found = isset( $this->callbacks[ $priority ][ $id ] );
        unset( $this->callbacks[ $priority ][ $id ] );
        return $found;
    }

    public function apply_filters( $value, $args ) {
        foreach ( array_keys( $this->callbacks ) as $priority ) {
            foreach ( $this->callbacks[ $priority ] ?? array() as $callback ) {
                $args[0] = $value;
                $value = call_user_func_array( $callback['function'], array_slice( $args, 0, (int) $callback['accepted_args'] ) );
            }
        }
        return $value;
    }
}

$GLOBALS['wp_filter'] = array();
$GLOBALS['wp_current_filter'] = array();
$GLOBALS['test_user_can'] = true;

function _wp_filter_build_unique_id( $callback ) {
    if ( is_string( $callback ) ) {
        return $callback;
    }
    if ( is_object( $callback ) ) {
        return spl_object_hash( $callback );
    }
    return ( is_object( $callback[0] ) ? spl_object_hash( $callback[0] ) : $callback[0] ) . '::' . $callback[1];
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    global $wp_filter;
    if ( ! isset( $wp_filter[ $hook ] ) ) {
        $wp_filter[ $hook ] = new WP_Hook();
    }
    $wp_filter[ $hook ]->add_filter( $callback, $priority, $accepted_args );
    return true;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    return add_filter( $hook, $callback, $priority, $accepted_args );
}

function remove_filter( $hook, $callback, $priority = 10 ) {
    global $wp_filter;
    return isset( $wp_filter[ $hook ] ) && $wp_filter[ $hook ]->remove_filter( $callback, $priority );
}

function remove_action( $hook, $callback, $priority = 10 ) {
    return remove_filter( $hook, $callback, $priority );
}

function apply_filters( $hook, $value, ...$args ) {
    global $wp_filter, $wp_current_filter;
    if ( ! isset( $wp_filter[ $hook ] ) ) {
        return $value;
    }
    $wp_current_filter[] = $hook;
    $value = $wp_filter[ $hook ]->apply_filters( $value, array_merge( array( $value ), $args ) );
    array_pop( $wp_current_filter );
    return $value;
}

function do_action( $hook, ...$args ) {
    apply_filters( $hook, $args[0] ?? '', ...array_slice( $args, 1 ) );
}

function current_filter() {
    return end( $GLOBALS['wp_current_filter'] );
}

function current_user_can() {
    return $GLOBALS['test_user_can'];
}

function wp_normalize_path( $path ) {
    return str_replace( '\\', '/', $path );
}

function trailingslashit( $value ) {
    return rtrim( $value, '/\\' ) . '/';
}

function wp_basename( $path ) {
    return basename( str_replace( '\\', '/', $path ) );
}

function __( $text ) {
    return $text;
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr( $text ) {
    return esc_html( $text );
}

function esc_url( $url ) {
    return esc_html( $url );
}

function esc_html_e( $text ) {
    echo esc_html( $text );
}

function esc_html__( $text ) {
    return esc_html( $text );
}

function self_admin_url( $path = '' ) {
    return 'http://example.test/wp-admin/' . $path;
}

function admin_url( $path = '' ) {
    return self_admin_url( $path );
}

function wp_specialchars_decode( $text, $quotes = ENT_NOQUOTES ) {
    return htmlspecialchars_decode( $text, $quotes );
}

function sanitize_text_field( $value ) {
    return is_array( $value ) || is_object( $value ) ? '' : trim( strip_tags( (string) $value ) );
}

function test_error_log() {
    $file = ini_get( 'error_log' );
    return is_string( $file ) && is_file( $file ) ? (string) file_get_contents( $file ) : '';
}

function test_run( $cases ) {
    $name = $GLOBALS['argv'][1] ?? '';
    if ( ! isset( $cases[ $name ] ) ) {
        fwrite( STDERR, 'Unknown case: ' . $name . "\n" );
        exit( 2 );
    }
    ini_set( 'error_log', tempnam( sys_get_temp_dir(), 'sjs' ) );
    ob_start();
    $result = $cases[ $name ]();
    $output = ob_get_clean();
    echo json_encode( array( 'result' => $result, 'output' => $output, 'log' => test_error_log() ) );
}
