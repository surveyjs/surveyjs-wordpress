<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Upsell {

    const URL = 'https://surveyjs.io/wordpress-pro';

    public static function init() {
        add_action( 'surveyjs_admin_menu', array( __CLASS__, 'add_menu_item' ), 100, 2 );
    }

    public static function enabled() {
        return (bool) apply_filters( 'surveyjs_show_pro_upsell', ! defined( 'SURVEYJS_PRO_VERSION' ) );
    }

    public static function url( $placement ) {
        return add_query_arg(
            array(
                'utm_source' => 'surveyjs-plugin',
                'utm_medium' => $placement,
            ),
            apply_filters( 'surveyjs_pro_url', self::URL )
        );
    }

    public static function add_menu_item( $parent_slug, $capability ) {
        if ( ! self::enabled() ) {
            return;
        }
        // A full URL as the slug makes WordPress print it as the menu link href instead of an admin.php?page= link.
        add_submenu_page( $parent_slug, esc_html__( 'Upgrade to Pro', 'surveyjs' ), esc_html__( 'Upgrade to Pro', 'surveyjs' ), $capability, esc_url( self::url( 'admin-menu' ) ) );
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
}
