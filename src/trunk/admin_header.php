<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// The plugin header: one bar with the plugin's sections at the top of every SurveyJS Forms page except the pages of
// a single form (the form editor and its entries). The sections are the plugin's submenu, so items that other code
// adds there (Pro's Dashboard, the Get Pro link) show up here too.
class SurveyJS_Admin_Header {

    const STYLE = 'wps-adm--header.css';

    // Pages of one form; they get their own navigation.
    const FORM_PAGES = array( 'surveyjs_editor' );

    // Hidden pages and the section they belong to.
    const PARENT_PAGES = array( 'surveyjs_import' => 'surveyjs-main-menu' );

    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_action( 'in_admin_header', array( __CLASS__, 'render' ) );
    }

    private static function current_page() {
        global $plugin_page;
        $page = is_string( $plugin_page ) ? $plugin_page : '';
        if ( '' === $page || in_array( $page, self::FORM_PAGES, true ) ) {
            return '';
        }
        if ( isset( self::PARENT_PAGES[ $page ] ) ) {
            return self::PARENT_PAGES[ $page ];
        }
        foreach ( self::submenu() as $item ) {
            if ( $item[2] === $page ) {
                return $page;
            }
        }
        return '';
    }

    private static function submenu() {
        global $submenu;
        return isset( $submenu[ SurveyJS_SurveyJS::MENU_SLUG ] ) ? (array) $submenu[ SurveyJS_SurveyJS::MENU_SLUG ] : array();
    }

    // Any page of the plugin, the form pages included.
    private static function on_plugin_page() {
        global $plugin_page;
        return is_string( $plugin_page ) && 0 === strpos( $plugin_page, 'surveyjs' );
    }

    public static function enqueue() {
        if ( self::on_plugin_page() ) {
            wp_enqueue_style( 'wps-adm--admin.css', plugins_url( 'admin.css', __FILE__ ), array( 'dashicons' ), (string) filemtime( __DIR__ . '/admin.css' ) );
            wp_enqueue_script( 'wps-adm--admin.js', plugins_url( 'admin.js', __FILE__ ), array(), (string) filemtime( __DIR__ . '/admin.js' ), true );
            // Core's own string, so it comes translated with WordPress.
            wp_add_inline_script( 'wps-adm--admin.js', 'window.surveyjsSnackbarL10n = ' . wp_json_encode( array( 'dismiss' => __( 'Dismiss this notice.' ) ) ) . ';', 'before' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
        }
        if ( '' === self::current_page() ) {
            return;
        }
        $file = 'admin_header.css';
        wp_enqueue_style( self::STYLE, plugins_url( $file, __FILE__ ), array(), (string) filemtime( __DIR__ . '/' . $file ) );
    }

    public static function list_url() {
        return admin_url( 'admin.php?page=' . SurveyJS_SurveyJS::MENU_SLUG );
    }

    public static function form_url( $form_id ) {
        return add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => (int) $form_id ), admin_url( 'admin.php' ) );
    }

    // Breadcrumbs above a page title. $items: array( label, url ) pairs from All Forms down; the last item is the
    // current page and has no url. Extra attributes for an item's label go in its third element.
    public static function breadcrumbs( $items ) {
        $last = count( $items ) - 1;
        ?>
        <nav class="wp-sjs-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'surveyjs' ); ?>">
            <ol>
                <?php foreach ( array_values( $items ) as $i => $item ) : ?>
                    <?php $attrs = isset( $item[2] ) ? ' ' . $item[2] : ''; ?>
                    <li>
                        <?php if ( $i < $last && ! empty( $item[1] ) ) : ?>
                            <a href="<?php echo esc_url( $item[1] ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attributes from the caller. ?>><?php echo esc_html( $item[0] ); ?></a>
                        <?php else : ?>
                            <span<?php echo $i === $last ? ' aria-current="page"' : ''; ?><?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attributes from the caller. ?>><?php echo esc_html( $item[0] ); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php
    }

    public static function render() {
        $current = self::current_page();
        if ( '' === $current ) {
            return;
        }
        $tabs = array();
        $add_new = null;
        $links = array();
        foreach ( self::submenu() as $item ) {
            if ( ! current_user_can( $item[1] ) ) {
                continue;
            }
            $slug = $item[2];
            $label = ! empty( $item[3] ) ? $item[3] : wp_strip_all_tags( $item[0] );
            if ( SurveyJS_Templates::PAGE === $slug ) {
                $add_new = array( 'url' => admin_url( 'admin.php?page=' . $slug ), 'label' => $label );
            } elseif ( false !== strpos( $slug, '://' ) ) {
                // A full URL (the Get Pro link) is an outside link, not a section.
                $links[] = array( 'url' => $slug, 'label' => wp_strip_all_tags( $item[0] ), 'class' => isset( $item[4] ) ? $item[4] : '' );
            } else {
                $tabs[] = array( 'slug' => $slug, 'url' => admin_url( 'admin.php?page=' . $slug ), 'label' => $label );
            }
        }
        $unread = array_sum( SurveyJS_Entries::unread_by_form() );
        ?>
        <div class="wp-sjs-header">
            <a class="wp-sjs-header__brand" href="<?php echo esc_url( admin_url( 'admin.php?page=' . SurveyJS_SurveyJS::MENU_SLUG ) ); ?>">
                <img src="<?php echo esc_url( plugins_url( 'images/logo.svg', __FILE__ ) ); ?>" width="24" height="24" alt="" />
                <span><?php esc_html_e( 'SurveyJS Forms', 'surveyjs' ); ?></span>
            </a>
            <nav class="wp-sjs-header__tabs" aria-label="<?php esc_attr_e( 'SurveyJS Forms sections', 'surveyjs' ); ?>">
                <?php foreach ( $tabs as $tab ) : ?>
                    <?php $active = $tab['slug'] === $current; ?>
                    <a class="wp-sjs-header__tab<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $tab['url'] ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                        <?php echo esc_html( $tab['label'] ); ?>
                        <?php if ( 'surveyjs_results' === $tab['slug'] && $unread ) : ?>
                            <span class="wp-sjs-count" title="<?php esc_attr_e( 'Unread entries', 'surveyjs' ); ?>"><?php echo esc_html( number_format_i18n( $unread ) ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="wp-sjs-header__actions">
                <?php foreach ( $links as $link ) : ?>
                    <?php
                    // Get Pro is a green button: Pro is free and has the full feature set, so it is the main thing to install.
                    $is_pro = false !== strpos( (string) $link['class'], 'wp-sjs-upgrade-pro' );
                    $class = $is_pro ? 'button wp-sjs-button-pro ' . $link['class'] : 'wp-sjs-header__link ' . $link['class'];
                    ?>
                    <a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['label'] ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></a>
                <?php endforeach; ?>
                <?php if ( $add_new ) : ?>
                    <a class="button button-primary wp-sjs-header__add" href="<?php echo esc_url( $add_new['url'] ); ?>"><?php echo esc_html( $add_new['label'] ); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
