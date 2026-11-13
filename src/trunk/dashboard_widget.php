<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Dashboard_Widget {

    const ID = 'surveyjs_dashboard_widget';
    const CAPABILITY = 'manage_options';
    const PERIOD_META = 'surveyjs_dashboard_widget_period';
    const PERIOD_ACTION = 'surveyjs_dashboard_widget_period';
    const DEFAULT_PERIOD = '7d';
    const VISIBLE_FORMS = 10;
    const SUBMITTED = array( 'unread', 'read' );

    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'register' ) );
        add_action( 'admin_post_' . self::PERIOD_ACTION, array( __CLASS__, 'handle_period' ) );
        add_action( 'wp_ajax_' . self::PERIOD_ACTION, array( __CLASS__, 'handle_period' ) );
        add_action( 'save_post_' . SurveyJS_Forms::POST_TYPE, array( 'SurveyJS_Entries', 'flush_counts' ) );
    }

    public static function register() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return;
        }
        wp_add_dashboard_widget( self::ID, esc_html__( 'SurveyJS Forms', 'surveyjs' ), array( __CLASS__, 'render' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    public static function periods() {
        return array(
            '7d'  => array( 'label' => __( 'Last 7 days', 'surveyjs' ), 'days' => 7, 'empty' => __( 'No entries in the last 7 days.', 'surveyjs' ) ),
            '30d' => array( 'label' => __( 'Last 30 days', 'surveyjs' ), 'days' => 30, 'empty' => __( 'No entries in the last 30 days.', 'surveyjs' ) ),
            'all' => array( 'label' => __( 'All time', 'surveyjs' ), 'days' => 0, 'empty' => __( 'No entries yet.', 'surveyjs' ) ),
        );
    }

    public static function current_period() {
        $period = (string) get_user_meta( get_current_user_id(), self::PERIOD_META, true );
        return isset( self::periods()[ $period ] ) ? $period : self::DEFAULT_PERIOD;
    }

    public static function handle_period() {
        check_admin_referer( self::PERIOD_ACTION );
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'surveyjs' ), 403 );
        }
        $period = sanitize_key( wp_unslash( $_REQUEST['period'] ?? '' ) );
        if ( isset( self::periods()[ $period ] ) ) {
            update_user_meta( get_current_user_id(), self::PERIOD_META, $period );
        }
        if ( wp_doing_ajax() ) {
            ob_start();
            self::render();
            wp_send_json_success( array( 'html' => ob_get_clean() ) );
        }
        wp_safe_redirect( admin_url( 'index.php' ) );
        exit;
    }

    public static function enqueue_assets( $hook_suffix ) {
        if ( 'index.php' !== $hook_suffix ) {
            return;
        }
        wp_add_inline_style( 'dashboard', '#surveyjs_dashboard_widget .inside{margin:0;padding:0}'
            . '.wp-sjs-dw__periods{margin:0;padding:12px 12px 4px;color:#646970}'
            . '.wp-sjs-dw__periods .current{color:#1d2327;font-weight:600}'
            . '.wp-sjs-dw__list{margin:0;padding:0 12px}'
            . '.wp-sjs-dw__list li{display:flex;align-items:baseline;gap:8px;margin:0;padding:8px 0;border-bottom:1px solid #f0f0f1}'
            . '.wp-sjs-dw__name{flex:1;min-width:0;overflow-wrap:anywhere}'
            . '.wp-sjs-dw__unread{color:#646970;white-space:nowrap}'
            . '.wp-sjs-dw__count{white-space:nowrap;font-weight:600}'
            . '.wp-sjs-dw__more{padding:0}.wp-sjs-dw__more summary{padding:8px 12px;cursor:pointer;color:#2271b1}'
            . '.wp-sjs-dw__empty{margin:0;padding:12px}'
            . '.wp-sjs-dw__footer{margin:0;padding:12px;border-top:1px solid #f0f0f1;color:#dcdcde}' );
        wp_add_inline_script( 'dashboard', 'var surveyjsDashboardWidget = ' . wp_json_encode( array( 'action' => self::PERIOD_ACTION, 'nonce' => wp_create_nonce( self::PERIOD_ACTION ) ) ) . ';' . <<<'JS'
document.addEventListener("click", function (event) {
    var link = event.target.closest(".wp-sjs-dw__periods a[data-period]");
    if (!link) {
        return;
    }
    event.preventDefault();
    var widget = link.closest(".wp-sjs-dw");
    widget.setAttribute("aria-busy", "true");
    fetch(window.ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        body: new URLSearchParams({ action: surveyjsDashboardWidget.action, _wpnonce: surveyjsDashboardWidget.nonce, period: link.dataset.period })
    }).then(function (response) {
        return response.json();
    }).then(function (response) {
        if (!response.success) {
            throw new Error();
        }
        widget.outerHTML = response.data.html;
    }).catch(function () {
        window.location.href = link.href;
    });
});
JS
        );
    }

    private static function period_start( $days ) {
        if ( ! $days ) {
            return '';
        }
        $start = new DateTimeImmutable( 'today', wp_timezone() );
        return $start->modify( '-' . ( $days - 1 ) . ' days' )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
    }

    public static function form_counts( $period ) {
        global $wpdb;
        $start = self::period_start( self::periods()[ $period ]['days'] );
        $key = 'dashboard_widget:' . $period . ':' . $start . ':' . wp_cache_get_last_changed( SurveyJS_Entries::CACHE_GROUP );
        $rows = wp_cache_get( $key, SurveyJS_Entries::CACHE_GROUP );
        if ( is_array( $rows ) ) {
            return $rows;
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID AS id, p.post_title AS name, COUNT(*) AS total FROM %i r
            INNER JOIN {$wpdb->posts} p ON p.ID = r.form_id AND p.post_type = %s AND p.post_status = 'publish'
            WHERE r.status IN ( %s, %s ) AND ( %s = '' OR r.created_at >= NULLIF( %s, '' ) )
            GROUP BY p.ID, p.post_title
            ORDER BY total DESC, p.post_title, p.ID",
            SurveyJS_Entries::table(),
            SurveyJS_Forms::POST_TYPE,
            self::SUBMITTED[0],
            self::SUBMITTED[1],
            $start,
            $start
        ) );
        wp_cache_set( $key, $rows, SurveyJS_Entries::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $rows;
    }

    private static function admin_url( $page, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
    }

    private static function period_url( $period ) {
        return wp_nonce_url( add_query_arg( array( 'action' => self::PERIOD_ACTION, 'period' => $period ), admin_url( 'admin-post.php' ) ), self::PERIOD_ACTION );
    }

    public static function render() {
        $period = self::current_period();
        $rows = self::form_counts( $period );
        $unread = SurveyJS_Entries::unread_by_form();
        ?>
        <div class="wp-sjs-dw">
            <p class="wp-sjs-dw__periods">
                <?php
                $links = array();
                foreach ( self::periods() as $key => $config ) {
                    $links[] = $key === $period
                        ? '<span class="current" aria-current="true">' . esc_html( $config['label'] ) . '</span>'
                        : '<a href="' . esc_url( self::period_url( $key ) ) . '" data-period="' . esc_attr( $key ) . '">' . esc_html( $config['label'] ) . '</a>';
                }
                echo implode( ' | ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each link is escaped above.
                ?>
            </p>
            <?php if ( $rows ) : ?>
                <ul class="wp-sjs-dw__list">
                    <?php self::render_rows( array_slice( $rows, 0, self::VISIBLE_FORMS ), $unread ); ?>
                </ul>
                <?php if ( count( $rows ) > self::VISIBLE_FORMS ) : ?>
                    <details class="wp-sjs-dw__more">
                        <summary>
                            <?php
                            $more = count( $rows ) - self::VISIBLE_FORMS;
                            /* translators: %s: number of forms */
                            echo esc_html( sprintf( _n( 'Show %s more form', 'Show %s more forms', $more, 'surveyjs' ), number_format_i18n( $more ) ) );
                            ?>
                        </summary>
                        <ul class="wp-sjs-dw__list">
                            <?php self::render_rows( array_slice( $rows, self::VISIBLE_FORMS ), $unread ); ?>
                        </ul>
                    </details>
                <?php endif; ?>
            <?php elseif ( SurveyJS_Forms::exists() ) : ?>
                <p class="wp-sjs-dw__empty"><?php echo esc_html( self::periods()[ $period ]['empty'] ); ?></p>
            <?php else : ?>
                <p class="wp-sjs-dw__empty"><?php esc_html_e( 'You haven\'t created any forms yet.', 'surveyjs' ); ?></p>
            <?php endif; ?>
            <p class="wp-sjs-dw__footer">
                <a href="<?php echo esc_url( self::admin_url( 'surveyjs-main-menu' ) ); ?>"><?php esc_html_e( 'All Forms', 'surveyjs' ); ?></a>
                |
                <a href="<?php echo esc_url( self::admin_url( SurveyJS_Templates::PAGE ) ); ?>"><?php esc_html_e( 'Create a new form', 'surveyjs' ); ?></a>
                <?php SurveyJS_Upsell::render_dashboard_widget_link(); ?>
            </p>
        </div>
        <?php
    }

    private static function render_rows( $rows, $unread ) {
        foreach ( $rows as $row ) {
            $id = (int) $row->id;
            $total = (int) $row->total;
            $name = '' !== (string) $row->name ? (string) $row->name : __( '(no title)', 'surveyjs' );
            ?>
            <li>
                <a class="wp-sjs-dw__name" href="<?php echo esc_url( self::admin_url( 'surveyjs_results', array( 'id' => $id ) ) ); ?>"><?php echo esc_html( $name ); ?></a>
                <?php if ( ! empty( $unread[ $id ] ) ) : ?>
                    <a class="wp-sjs-dw__unread" href="<?php echo esc_url( self::admin_url( 'surveyjs_results', array( 'id' => $id, 'status' => 'unread' ) ) ); ?>">
                        <?php /* translators: %s: number of unread entries */ echo esc_html( sprintf( __( '%s unread', 'surveyjs' ), number_format_i18n( $unread[ $id ] ) ) ); ?>
                    </a>
                <?php endif; ?>
                <span class="wp-sjs-dw__count"><?php /* translators: %s: number of entries */ echo esc_html( sprintf( _n( '%s entry', '%s entries', $total, 'surveyjs' ), number_format_i18n( $total ) ) ); ?></span>
            </li>
            <?php
        }
    }
}
