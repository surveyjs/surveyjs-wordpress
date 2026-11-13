<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Upsell {

    const PATH = '/wordpress-pro';

    public static function init() {
        add_action( 'surveyjs_admin_menu', array( __CLASS__, 'add_menu_item' ), 100, 2 );
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_item' ), 81 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_menu_style' ) );
    }

    public static function add_menu_style() {
        if ( ! self::enabled() ) {
            return;
        }
        wp_add_inline_style( 'admin-menu', '#adminmenu .wp-submenu li.wp-sjs-upgrade-pro a.wp-sjs-upgrade-pro,#adminmenu .wp-submenu li.wp-sjs-upgrade-pro a.wp-sjs-upgrade-pro:hover,#adminmenu .wp-submenu li.wp-sjs-upgrade-pro a.wp-sjs-upgrade-pro:focus{background:#00a32a;color:#fff}' );
    }

    public static function enabled() {
        return (bool) apply_filters( 'surveyjs_show_pro_upsell', ! defined( 'SURVEYJS_PRO_VERSION' ) );
    }

    public static function url( $placement ) {
        return add_query_arg(
            array(
                'utm_source'   => 'surveyjs-plugin',
                'utm_medium'   => $placement,
                'wp_admin_url' => rawurlencode( admin_url() ),
            ),
            surveyjs_site_url( self::PATH )
        );
    }

    public static function add_menu_item( $parent_slug, $capability ) {
        if ( ! self::enabled() ) {
            return;
        }
        // A full URL as the slug makes WordPress print it as the menu link href instead of an admin.php?page= link.
        add_submenu_page( $parent_slug, esc_html__( 'Get Pro – Free', 'surveyjs' ), esc_html__( 'Get Pro – Free', 'surveyjs' ), $capability, esc_url( self::url( 'admin-menu' ) ) );
        global $submenu;
        if ( ! empty( $submenu[ $parent_slug ] ) ) {
            end( $submenu[ $parent_slug ] );
            $submenu[ $parent_slug ][ key( $submenu[ $parent_slug ] ) ][4] = 'wp-sjs-upgrade-pro'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WordPress has no API to add a class to a submenu item.
        }
    }

    public static function add_admin_bar_item( $admin_bar ) {
        if ( ! self::enabled() || ! current_user_can( SurveyJS_Admin_Bar::CAPABILITY ) ) {
            return;
        }
        $admin_bar->add_node( array(
            'parent' => SurveyJS_Admin_Bar::NODE,
            'id'     => SurveyJS_Admin_Bar::NODE . '-upgrade',
            'title'  => esc_html__( 'Get Pro – Free', 'surveyjs' ),
            'href'   => self::url( 'admin-bar' ),
            'meta'   => array( 'target' => '_blank', 'rel' => 'noopener' ),
        ) );
    }

    public static function render_getting_started() {
        if ( ! self::enabled() ) {
            return;
        }
        ?>
        <div class="card wp-sjs-pro-upsell">
            <h2><?php esc_html_e( 'SurveyJS Forms Pro', 'surveyjs' ); ?></h2>
            <p><?php esc_html_e( 'A free add-on for SurveyJS Forms: design forms visually with Survey Creator right in the form editor, export entries to PDF and see answers as charts.', 'surveyjs' ); ?></p>
            <p>
                <a href="<?php echo esc_url( self::url( 'getting-started' ) ); ?>" class="button" target="_blank" rel="noopener">
                    <?php esc_html_e( 'Get SurveyJS Forms Pro for free', 'surveyjs' ); ?>
                    <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span>
                </a>
            </p>
        </div>
        <?php
    }

    public static function render_editor_link() {
        if ( ! self::enabled() ) {
            return;
        }
        ?>
        <a href="<?php echo esc_url( self::url( 'editor' ) ); ?>" class="wp-sjs-editor__pro-link" target="_blank" rel="noopener"><?php esc_html_e( 'Or edit visually right here with the free Pro add-on', 'surveyjs' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></a>
        <?php
    }

    public static function render_dashboard_widget_link() {
        if ( ! self::enabled() ) {
            return;
        }
        ?>
        | <a href="<?php echo esc_url( self::url( 'dashboard-widget' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See answers as charts with the free Pro add-on', 'surveyjs' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></a>
        <?php
    }
}
