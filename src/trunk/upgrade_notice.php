<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Upgrade_Notice {

    const FLAG_OPTION = 'surveyjs_updated_from_legacy';
    const VERSION_OPTION = 'surveyjs_version';
    const DISMISSED_META = 'surveyjs_upgrade_notice_dismissed';
    const DISMISS_ACTION = 'surveyjs_dismiss_upgrade_notice';
    const CAPABILITY = 'manage_options';
    const WHAT_CHANGED_URL = 'https://wordpress.org/support/plugin/surveyjs/';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'store_version' ), 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_' . self::DISMISS_ACTION, array( __CLASS__, 'handle_dismiss' ) );
        add_action( 'admin_post_' . self::DISMISS_ACTION, array( __CLASS__, 'handle_dismiss' ) );
        add_action( 'in_plugin_update_message-' . plugin_basename( __DIR__ . '/surveyjs.php' ), array( __CLASS__, 'render_update_message' ), 10, 2 );
    }

    public static function flag_legacy_update() {
        update_option( self::FLAG_OPTION, 1 );
    }

    public static function store_version() {
        if ( get_option( self::VERSION_OPTION ) !== SURVEYJS_VERSION ) {
            update_option( self::VERSION_OPTION, SURVEYJS_VERSION );
        }
    }

    public static function is_active() {
        return get_option( self::FLAG_OPTION )
            && ! defined( 'SURVEYJS_PRO_VERSION' )
            && current_user_can( self::CAPABILITY )
            && ! get_user_meta( get_current_user_id(), self::DISMISSED_META, true );
    }

    private static function on_getting_started() {
        return SurveyJS_Welcome::PAGE === sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    private static function dismiss_url() {
        return wp_nonce_url( add_query_arg( 'action', self::DISMISS_ACTION, admin_url( 'admin-post.php' ) ), self::DISMISS_ACTION );
    }

    public static function handle_dismiss() {
        check_admin_referer( self::DISMISS_ACTION );
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'surveyjs' ), 403 );
        }
        update_user_meta( get_current_user_id(), self::DISMISSED_META, 1 );
        if ( wp_doing_ajax() ) {
            wp_send_json_success();
        }
        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
        exit;
    }

    public static function enqueue_assets() {
        if ( ! self::is_active() || ! self::on_getting_started() ) {
            return;
        }
        wp_add_inline_style( 'common', '.wp-sjs-upgrade-card.card{max-width:640px}.wp-sjs-upgrade-card .wp-sjs-upgrade__actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px}' );
        wp_add_inline_script( 'common', 'var surveyjsUpgradeNotice = ' . wp_json_encode( array( 'action' => self::DISMISS_ACTION, 'nonce' => wp_create_nonce( self::DISMISS_ACTION ) ) ) . ';' . <<<'JS'
document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-sjs-upgrade-dismiss]");
    if (!button) {
        return;
    }
    event.preventDefault();
    document.querySelectorAll(".wp-sjs-upgrade-card").forEach(function (element) {
        element.remove();
    });
    fetch(window.ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        body: new URLSearchParams({ action: surveyjsUpgradeNotice.action, _wpnonce: surveyjsUpgradeNotice.nonce })
    });
});
JS
        );
    }

    public static function render_card() {
        if ( ! self::is_active() ) {
            return;
        }
        ?>
        <div class="card wp-sjs-upgrade-card">
            <h2><?php /* translators: %s: plugin version */ echo esc_html( sprintf( __( 'What changed in %s', 'surveyjs' ), SURVEYJS_VERSION ) ); ?></h2>
        <p><?php esc_html_e( 'Your forms, entries and settings are unchanged and keep working on your site.', 'surveyjs' ); ?></p>
        <p><?php esc_html_e( 'To edit forms, SurveyJS Forms now has a built-in JSON editor and an "Open in free UI Builder" button that opens the visual editor in a new tab.', 'surveyjs' ); ?></p>
        <p><?php esc_html_e( 'To design forms visually right inside WordPress, as before, install the free SurveyJS Forms Pro add-on. It also adds charts and PDF export, and picks up your SurveyJS license key automatically.', 'surveyjs' ); ?></p>
        <p><?php esc_html_e( 'Why the change: WordPress.org hosts only GPL-licensed code, and Survey Creator is a commercial SurveyJS component, so it now ships in a separate add-on.', 'surveyjs' ); ?></p>
        <p class="wp-sjs-upgrade__actions">
            <a href="<?php echo esc_url( SurveyJS_Upsell::url( 'getting-started-upgrade' ) ); ?>" class="button button-primary" target="_blank" rel="noopener"><?php esc_html_e( 'Get SurveyJS Forms Pro', 'surveyjs' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></a>
            <a href="<?php echo esc_url( self::WHAT_CHANGED_URL ); ?>" class="button" target="_blank" rel="noopener"><?php esc_html_e( 'What changed', 'surveyjs' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></a>
            <a href="<?php echo esc_url( self::dismiss_url() ); ?>" class="button-link" data-sjs-upgrade-dismiss><?php esc_html_e( 'Dismiss', 'surveyjs' ); ?></a>
        </p>
        </div>
        <?php
    }

    public static function render_update_message( $plugin_data, $response ) {
        if ( ! is_object( $response ) || empty( $response->upgrade_notice ) ) {
            return;
        }
        echo '<br /><strong>' . esc_html( wp_strip_all_tags( (string) $response->upgrade_notice ) ) . '</strong>';
    }
}
