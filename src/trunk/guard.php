<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// A Throwable in a callback of SurveyJS Forms or its add-on on these hooks would break every admin page
// (or the whole site, or plugin updates), so it is logged and the callback is skipped instead.
// Data filters (form settings, sanitizing, secrets, entry handling) are not listed: there an error must stay visible.
class SurveyJS_Guard {

    const HOOKS = array(
        'plugins_loaded', 'after_setup_theme', 'init', 'wp_loaded', 'widgets_init', 'cron_schedules', 'shutdown',
        'admin_init', 'admin_menu', 'network_admin_menu', 'user_admin_menu', 'admin_bar_menu', 'wp_before_admin_bar_render',
        'admin_notices', 'network_admin_notices', 'user_admin_notices', 'all_admin_notices', 'current_screen', 'in_admin_header',
        'in_admin_footer', 'admin_head', 'admin_footer', 'admin_body_class', 'admin_enqueue_scripts', 'admin_print_scripts',
        'admin_print_styles', 'admin_print_footer_scripts', 'wp_dashboard_setup', 'wp_network_dashboard_setup',
        'plugin_row_meta', 'plugin_action_links', 'network_admin_plugin_action_links', 'plugin_action_links_surveyjs/surveyjs.php',
        'plugin_action_links_surveyjs-pro/surveyjs-pro.php', 'in_plugin_update_message-surveyjs/surveyjs.php',
        'in_plugin_update_message-surveyjs-pro/surveyjs-pro.php', 'update_plugins_surveyjs.io', 'plugins_api',
        'upgrader_pre_download', 'upgrader_install_package_result', 'upgrader_process_complete', 'site_status_tests', 'debug_information',
        'rest_api_init', 'wp_abilities_api_categories_init', 'wp_abilities_api_init', 'mcp_adapter_init',
        'enqueue_block_editor_assets', 'enqueue_block_assets', 'wp_enqueue_scripts', 'wp_head', 'wp_footer', 'template_redirect',
        'surveyjs_pro_loaded', 'surveyjs_admin_menu', 'surveyjs_admin_enqueue_scripts', 'surveyjs_register_settings',
        'surveyjs_editor_tabs', 'surveyjs_render_editor', 'surveyjs_editor_email_rows', 'surveyjs_results_views',
        'surveyjs_render_results_view', 'surveyjs_results_title_actions', 'surveyjs_entries_bulk_actions', 'surveyjs_entry_row_actions',
        'surveyjs_form_row_actions', 'surveyjs_ai_register_abilities', 'surveyjs_ai_plugin_guide', 'surveyjs_ai_admin_urls',
        'surveyjs_ai_form_settings_schema',
    );

    private static $failures = array();

    public static function init() {
        foreach ( self::HOOKS as $hook ) {
            add_filter( $hook, array( __CLASS__, 'protect' ), PHP_INT_MIN, 1 );
        }
        add_action( 'admin_notices', array( __CLASS__, 'notice' ), PHP_INT_MAX );
        add_action( 'network_admin_notices', array( __CLASS__, 'notice' ), PHP_INT_MAX );
    }

    public static function protect( $value = null ) {
        global $wp_filter;
        $hook = current_filter();
        if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook || ! is_array( $wp_filter[ $hook ]->callbacks ) ) {
            return $value;
        }
        // Wrapping in place keeps each callback's array key, so remove_action() with the original callback still works.
        foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
            foreach ( (array) $callbacks as $id => $callback ) {
                $function = $callback['function'] ?? null;
                if ( self::owned( $function ) ) {
                    $wp_filter[ $hook ]->callbacks[ $priority ][ $id ]['function'] = new SurveyJS_Guarded_Callback( $function, $hook );
                }
            }
        }
        return $value;
    }

    public static function owned( $callback ) {
        if ( $callback instanceof SurveyJS_Guarded_Callback || array( __CLASS__, 'protect' ) === $callback ) {
            return false;
        }
        if ( $callback instanceof Closure ) {
            return self::own_file( ( new ReflectionFunction( $callback ) )->getFileName() );
        }
        if ( is_object( $callback ) ) {
            return self::own_class( get_class( $callback ) );
        }
        if ( is_array( $callback ) && 2 === count( $callback ) && isset( $callback[0] ) ) {
            return self::own_class( is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0] );
        }
        if ( is_string( $callback ) ) {
            return str_contains( $callback, '::' ) ? self::own_class( strtok( $callback, ':' ) ) : 1 === preg_match( '/^(surveyjs_|wps_)/i', $callback );
        }
        return false;
    }

    private static function own_class( $class ) {
        return 0 === stripos( ltrim( $class, '\\' ), 'SurveyJS_' );
    }

    private static function own_file( $file ) {
        if ( ! is_string( $file ) ) {
            return false;
        }
        $file = wp_normalize_path( $file );
        $dirs = array( dirname( __FILE__ ) );
        if ( defined( 'SURVEYJS_PRO_FILE' ) ) {
            $dirs[] = dirname( (string) SURVEYJS_PRO_FILE );
        }
        foreach ( $dirs as $dir ) {
            if ( 0 === strpos( $file, trailingslashit( wp_normalize_path( $dir ) ) ) ) {
                return true;
            }
        }
        return false;
    }

    public static function call( $callback, $hook, $args ) {
        try {
            return call_user_func_array( $callback, $args );
        } catch ( Throwable $error ) {
            self::report( $hook, $callback, $error );
            return $args[0] ?? null;
        }
    }

    public static function report( $hook, $callback, Throwable $error ) {
        $name = self::callback_name( $callback );
        $key = $hook . '|' . $name;
        if ( isset( self::$failures[ $key ] ) ) {
            return;
        }
        self::$failures[ $key ] = array(
            'hook'     => $hook,
            'callback' => $name,
            'add_on'   => self::from_add_on( $callback, $error ),
        );
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- the only trace of an error that was caught so the page keeps working.
        error_log( sprintf( 'SurveyJS Forms: %s on the "%s" hook was skipped: %s: %s in %s:%d', $name, $hook, get_class( $error ), $error->getMessage(), $error->getFile(), $error->getLine() ) );
    }

    public static function failures() {
        return array_values( self::$failures );
    }

    private static function from_add_on( $callback, Throwable $error ) {
        if ( defined( 'SURVEYJS_PRO_FILE' ) && 0 === strpos( wp_normalize_path( $error->getFile() ), trailingslashit( wp_normalize_path( dirname( (string) SURVEYJS_PRO_FILE ) ) ) ) ) {
            return true;
        }
        $class = is_array( $callback ) && isset( $callback[0] ) ? ( is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0] ) : '';
        return 0 === stripos( $class, 'SurveyJS_Pro' );
    }

    private static function callback_name( $callback ) {
        if ( is_array( $callback ) && isset( $callback[0], $callback[1] ) ) {
            return ( is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0] ) . '::' . (string) $callback[1];
        }
        if ( $callback instanceof Closure ) {
            $reflection = new ReflectionFunction( $callback );
            return 'closure in ' . wp_basename( (string) $reflection->getFileName() ) . ':' . $reflection->getStartLine();
        }
        return is_string( $callback ) ? $callback : ( is_object( $callback ) ? get_class( $callback ) : 'callback' );
    }

    public static function notice() {
        if ( ! self::$failures || ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        $add_on = (bool) array_filter( array_column( self::$failures, 'add_on' ) );
        $message = $add_on
            ? __( 'SurveyJS Forms skipped part of SurveyJS Forms Add-on because of an error, so this page may miss some of its features. Update SurveyJS Forms and SurveyJS Forms Add-on to their latest versions.', 'surveyjs' )
            : __( 'SurveyJS Forms skipped one of its features because of an error, so this page may miss it. Update SurveyJS Forms to its latest version.', 'surveyjs' );
        ?>
        <div class="notice notice-warning surveyjs-guard-notice">
            <p><?php echo esc_html( $message ); ?> <a href="<?php echo esc_url( self_admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Go to Plugins', 'surveyjs' ); ?></a></p>
            <p class="description"><?php echo esc_html( implode( ', ', array_unique( array_column( self::$failures, 'callback' ) ) ) ); ?></p>
        </div>
        <?php
    }
}

final class SurveyJS_Guarded_Callback {

    private $callback;
    private $hook;

    public function __construct( $callback, $hook ) {
        $this->callback = $callback;
        $this->hook = $hook;
    }

    public function __invoke( ...$args ) {
        return SurveyJS_Guard::call( $this->callback, $this->hook, $args );
    }
}
