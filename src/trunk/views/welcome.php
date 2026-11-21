<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Welcome {

    const PAGE = 'surveyjs-getting-started';
    const REDIRECT_TRANSIENT = 'surveyjs_activation_redirect';
    const CHECKLIST_OPTION = 'surveyjs_checklist';
    const ENTRY_FORMS_OPTION = 'surveyjs_entry_forms';
    const DOCS_URL = 'https://surveyjs.io/form-library/documentation/overview';
    const CAPABILITY = 'manage_options';
    const REVIEW_URL = 'https://wordpress.org/support/plugin/surveyjs/reviews/#new-post';
    const PROGRESS_TTL = DAY_IN_SECONDS;
    const OPTIONAL_STEPS = array( 'notification' );
    const PROGRESS_CSS = '.wp-sjs-progress{-webkit-appearance:none;appearance:none;display:block;width:100%;height:4px;border:0;border-radius:2px;background:rgba(140,143,148,.35);overflow:hidden}.wp-sjs-progress::-webkit-progress-bar{background:transparent}.wp-sjs-progress::-webkit-progress-value{background:#00a32a;border-radius:2px}.wp-sjs-progress::-moz-progress-bar{background:#00a32a;border-radius:2px}.wp-sjs-checklist-progress[hidden]{display:none!important}';

    public static function init() {
        add_action( 'save_post', array( __CLASS__, 'forget_progress_for_post' ), 10, 2 );
        add_action( 'deleted_post', array( __CLASS__, 'forget_progress_for_post' ), 10, 2 );
        add_action( 'added_post_meta', array( __CLASS__, 'forget_progress_for_meta' ), 10, 3 );
        add_action( 'updated_post_meta', array( __CLASS__, 'forget_progress_for_meta' ), 10, 3 );
        add_action( 'wp_surveyjs_save_result', array( __CLASS__, 'record_entry' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_menu_style' ) );
    }

    public static function url() {
        return add_query_arg( 'page', self::PAGE, admin_url( 'admin.php' ) );
    }

    public static function add_form_url() {
        return SurveyJS_Templates::url();
    }

    public static function maybe_redirect() {
        $user_id = (int) get_transient( self::REDIRECT_TRANSIENT );
        if ( ! $user_id || get_current_user_id() !== $user_id || wp_doing_ajax() || ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }
        delete_transient( self::REDIRECT_TRANSIENT );
        if ( isset( $_GET['activate-multi'] ) || is_network_admin() || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        wp_safe_redirect( add_query_arg( 'welcome', 1, self::url() ) );
        exit;
    }

    public static function handle_checklist_action() {
        check_ajax_referer( 'surveyjs-checklist' );
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_send_json_error( null, 403 );
        }
        self::update_state( array( 'dismissed' => 'dismiss' === sanitize_key( wp_unslash( $_POST['checklist'] ?? '' ) ) ) );
        wp_send_json_success();
    }

    private static function state() {
        return array_merge( array( 'dismissed' => false, 'completed' => false ), (array) get_option( self::CHECKLIST_OPTION, array() ) );
    }

    private static function update_state( $changes ) {
        $state = array_merge( self::state(), $changes );
        update_option( self::CHECKLIST_OPTION, $state );
        return $state;
    }

    public static function forget_progress() {
        $state = self::state();
        if ( isset( $state['progress'] ) ) {
            unset( $state['progress'] );
            update_option( self::CHECKLIST_OPTION, $state );
        }
    }

    public static function forget_progress_for_post( $post_id, $post = null ) {
        $post = $post instanceof WP_Post ? $post : get_post( $post_id );
        if ( $post && ( SurveyJS_Forms::POST_TYPE === $post->post_type || false !== stripos( $post->post_content, 'wp:sjs/gutenberg-block' ) || false !== stripos( $post->post_content, '[survey' ) ) ) {
            self::forget_progress();
        }
    }

    // Fires only for a submission of a published form through SurveyJS_SaveResult, so editor and template previews,
    // which never send one, don't count. Kept per form so that deleting the form also drops the step.
    public static function record_entry( $form_id ) {
        $form_ids = array_map( 'intval', (array) get_option( self::ENTRY_FORMS_OPTION, array() ) );
        if ( ! in_array( (int) $form_id, $form_ids, true ) ) {
            $form_ids[] = (int) $form_id;
            update_option( self::ENTRY_FORMS_OPTION, $form_ids, false );
            self::forget_progress();
        }
    }

    public static function received_entry( $form_ids ) {
        global $wpdb;
        $form_ids = array_map( 'intval', $form_ids );
        if ( ! $form_ids ) {
            return false;
        }
        if ( array_intersect( $form_ids, array_map( 'intval', (array) get_option( self::ENTRY_FORMS_OPTION, array() ) ) ) ) {
            return true;
        }
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM %i WHERE status IN ( 'unread', 'read' ) AND form_id IN ( " . implode( ',', array_fill( 0, count( $form_ids ), '%d' ) ) . ' ) LIMIT 1',
            array_merge( array( SurveyJS_Entries::table() ), $form_ids )
        ) );
    }

    public static function forget_progress_for_meta( $meta_id, $object_id, $meta_key ) {
        if ( SurveyJS_Forms::SETTINGS_META === $meta_key ) {
            self::forget_progress();
        }
    }

    // The menu and the admin bar show this on every page, so the steps, which scan the posts for the form, are
    // checked only on admin pages and then cached until a change that can affect them, or for PROGRESS_TTL at most.
    public static function progress() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return null;
        }
        $state = self::state();
        if ( $state['dismissed'] || $state['completed'] ) {
            return null;
        }
        $progress = $state['progress'] ?? null;
        $titles = self::titles();
        // A cache written before the email step became optional counts it in the total.
        $outdated = is_array( $progress ) && count( $titles ) - count( self::OPTIONAL_STEPS ) !== (int) ( $progress['total'] ?? 0 );
        if ( is_admin() && ( ! is_array( $progress ) || $outdated || (int) ( $progress['time'] ?? 0 ) < time() - self::PROGRESS_TTL ) ) {
            $progress = self::checklist()['progress'];
            $outdated = false;
        }
        if ( $outdated || ! is_array( $progress ) || ! isset( $titles[ $progress['next'] ?? '' ] ) ) {
            return null;
        }
        $progress['title'] = $titles[ $progress['next'] ];
        return $progress;
    }

    public static function enqueue_assets() {
        if ( ! in_array( sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ), array( self::PAGE, 'surveyjs-main-menu' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        wp_add_inline_style( 'common', '.wp-sjs-checklist.card,.wp-sjs-welcome-import.card,.wp-sjs-pro-upsell.card{max-width:640px}.wp-sjs-checklist progress{width:100%}.wp-sjs-checklist__steps{margin:16px 0 0;list-style:none}.wp-sjs-checklist__steps li{display:flex;gap:8px;margin:0;padding:12px 0;border-top:1px solid #f0f0f1}.wp-sjs-checklist__steps p{margin:4px 0 8px}.wp-sjs-checklist .dashicons-yes-alt{color:#00a32a}.wp-sjs-checklist .dashicons-marker{color:#a7aaad}.wp-sjs-checklist[hidden],.wp-sjs-checklist-compact[hidden],.wp-sjs-checklist-hidden[hidden]{display:none}.wp-sjs-checklist .wp-sjs-progress{height:8px;border-radius:4px}.wp-sjs-checklist__optional{display:inline-block;margin-inline-start:6px;padding:0 6px;border-radius:2px;background:#f0f0f1;color:#50575e;font-size:12px;line-height:18px;vertical-align:1px}.wp-sjs-checklist__suggestion .wp-sjs-checklist__optional{margin-inline:0 6px}' . self::PROGRESS_CSS );
        wp_add_inline_script( 'common', 'var surveyjsChecklist = ' . wp_json_encode( array( 'nonce' => wp_create_nonce( 'surveyjs-checklist' ) ) ) . ';' . <<<'JS'
document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-sjs-checklist]");
    if (!button) {
        return;
    }
    var action = button.getAttribute("data-sjs-checklist");
    document.querySelectorAll(".wp-sjs-checklist, .wp-sjs-checklist-compact, .wp-sjs-checklist-progress").forEach(function (element) {
        element.hidden = action === "dismiss";
    });
    document.querySelectorAll(".wp-sjs-checklist-hidden").forEach(function (element) {
        element.hidden = action !== "dismiss";
    });
    fetch(window.ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        body: new URLSearchParams({ action: "surveyjs_checklist", checklist: action, _ajax_nonce: surveyjsChecklist.nonce })
    });
});
JS
        );
    }

    public static function steps() {
        $forms = SurveyJS_AI_Drafts::without_unsaved_new_forms( SurveyJS_Forms::all() );
        $form_ids = array_map( 'intval', wp_list_pluck( $forms, 'id' ) );
        $latest_id = $form_ids ? $form_ids[0] : 0;
        $placement = self::find_placement( $form_ids );

        $notification = false;
        foreach ( $forms as $form ) {
            if ( SurveyJS_Form_Settings::get( $form )['notificationEnabled'] ) {
                $notification = true;
                break;
            }
        }

        $entry = self::received_entry( $form_ids );
        $editor_url = $latest_id ? add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => $latest_id ), admin_url( 'admin.php' ) ) : self::add_form_url();
        $entries_url = $latest_id ? add_query_arg( array( 'page' => 'surveyjs_results', 'id' => $latest_id ), admin_url( 'admin.php' ) ) : self::add_form_url();
        $placement_url = $placement && is_post_type_viewable( get_post_type( $placement ) ) ? get_permalink( $placement ) : '';
        $titles = self::titles();

        return array(
            'form'         => array(
                'done'        => (bool) $form_ids,
                'title'       => $titles['form'],
                'description' => self::visual_editor()
                    ? __( 'Start with a ready-made template or a blank form and edit the questions visually in the Config tab.', 'surveyjs' )
                    : __( 'Start with a ready-made template or a blank form and edit the questions in the JSON editor or the visual UI Builder.', 'surveyjs' ),
                'url'         => self::add_form_url(),
                'action'      => __( 'Create a form', 'surveyjs' ),
            ),
            'placement'    => array(
                'done'        => (bool) $placement,
                'title'       => $titles['placement'],
                'description' => $latest_id
                    /* translators: %s: form shortcode */
                    ? sprintf( __( 'Insert the SurveyJS Forms block, or paste the shortcode %s, and publish the page.', 'surveyjs' ), '[surveyjs id="' . $latest_id . '"]' )
                    : __( 'Insert the SurveyJS Forms block or the form shortcode, and publish the page.', 'surveyjs' ),
                'url'         => $latest_id ? SurveyJS_Embed::create_page_url( $latest_id ) : admin_url( 'post-new.php?post_type=page' ),
                'next_url'    => $latest_id ? add_query_arg( 'embed', 1, $editor_url ) : admin_url( 'post-new.php?post_type=page' ),
                'action'      => $latest_id ? __( 'Create a page with this form', 'surveyjs' ) : __( 'Add a new page', 'surveyjs' ),
            ),
            'notification' => array(
                'done'        => $notification,
                'title'       => $titles['notification'],
                'description' => __( 'Turn on the email notification to get an email every time someone submits the form. Send a test email to check that emails reach your inbox; if they don\'t, set up an SMTP plugin.', 'surveyjs' ),
                'url'         => $latest_id ? add_query_arg( array( 'tab' => 'email', 'focus' => 'notification' ), $editor_url ) : $editor_url,
                'action'      => $latest_id ? __( 'Set up notification', 'surveyjs' ) : __( 'Create a form', 'surveyjs' ),
            ),
            'entry'        => array(
                'done'        => (bool) $entry,
                'title'       => $titles['entry'],
                'description' => __( 'Submit the form on your site to check that everything works. Entries appear on the form\'s Entries page.', 'surveyjs' ),
                'url'         => $placement_url ? $placement_url : $entries_url,
                'action'      => $placement_url ? __( 'Open the page', 'surveyjs' ) : ( $latest_id ? __( 'View entries', 'surveyjs' ) : __( 'Create a form', 'surveyjs' ) ),
            ),
        );
    }

    public static function visual_editor() {
        return (bool) apply_filters( 'surveyjs_visual_editor_enabled', false );
    }

    public static function editor_next_steps() {
        $progress = self::progress();
        $labels = array(
            'placement'    => __( 'Embed the form', 'surveyjs' ),
            'notification' => __( 'Turn on email notifications', 'surveyjs' ),
        );
        $steps = array();
        foreach ( $progress ? (array) ( $progress['left'] ?? array() ) : array() as $key ) {
            if ( isset( $labels[ $key ] ) ) {
                $steps[] = array( 'key' => $key, 'label' => $labels[ $key ] );
            }
        }
        return $steps;
    }

    private static function titles() {
        return array(
            'form'         => __( 'Create a form', 'surveyjs' ),
            'placement'    => __( 'Add the form to a page', 'surveyjs' ),
            'notification' => __( 'Set up an email notification', 'surveyjs' ),
            'entry'        => __( 'Receive your first entry', 'surveyjs' ),
        );
    }

    private static function find_placement( $form_ids ) {
        global $wpdb;
        if ( ! $form_ids ) {
            return 0;
        }
        $posts = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND ( post_content LIKE '%wp:sjs/gutenberg-block%' OR post_content LIKE '%[survey%' ) ORDER BY ID DESC LIMIT 50" );
        foreach ( $posts as $post ) {
            if ( array_intersect( SurveyJS_SurveyJS::content_survey_ids( $post->post_content ), $form_ids ) ) {
                return (int) $post->ID;
            }
        }
        return 0;
    }

    private static function checklist() {
        $steps = self::steps();
        $left = array_filter( $steps, function ( $step ) {
            return ! $step['done'];
        } );
        $required = array_diff_key( $steps, array_flip( self::OPTIONAL_STEPS ) );
        $required_left = array_intersect_key( $left, $required );
        $next = key( $required_left );
        $progress = array(
            'done'  => count( $required ) - count( $required_left ),
            'total' => count( $required ),
            'next'  => $next,
            'url'   => $next ? ( $steps[ $next ]['next_url'] ?? $steps[ $next ]['url'] ) : '',
            'left'  => array_keys( $left ),
            'time'  => time(),
        );
        self::update_state( array( 'completed' => ! $required_left, 'progress' => $progress ) );
        return array(
            'steps'    => $steps,
            'done'     => $progress['done'],
            'total'    => $progress['total'],
            'progress' => $required_left ? $progress : null,
        );
    }

    private static function progress_text( $done, $total ) {
        /* translators: 1: number of completed steps, 2: total number of steps */
        return sprintf( __( '%1$d of %2$d steps completed', 'surveyjs' ), $done, $total );
    }

    public static function menu_progress() {
        $progress = self::progress();
        return $progress ? self::progress_markup( $progress ) : '';
    }

    public static function progress_markup( $progress ) {
        /* translators: 1: number of completed steps, 2: total number of steps */
        $short = sprintf( __( '%1$s of %2$s', 'surveyjs' ), number_format_i18n( $progress['done'] ), number_format_i18n( $progress['total'] ) );
        return '<span class="wp-sjs-checklist-progress wp-sjs-menu-progress">'
            . '<span class="wp-sjs-menu-progress__text" aria-hidden="true">' . esc_html( $short ) . '</span>'
            . '<span class="screen-reader-text">' . esc_html( self::progress_text( $progress['done'], $progress['total'] ) ) . '</span>'
            . '<progress class="wp-sjs-progress" max="' . esc_attr( $progress['total'] ) . '" value="' . esc_attr( $progress['done'] ) . '" aria-hidden="true"></progress>'
            . '</span>';
    }

    public static function enqueue_menu_style() {
        if ( self::progress() ) {
            wp_add_inline_style( 'admin-menu', self::PROGRESS_CSS . '#adminmenu .wp-sjs-menu-progress{display:flex;align-items:center;gap:8px;margin-top:6px;font-size:12px;line-height:1.4}#adminmenu .wp-sjs-menu-progress__text{flex:none;opacity:.85}#adminmenu .wp-sjs-menu-progress .wp-sjs-progress{flex:1;min-width:24px}' );
        }
    }

    // The admin bar resets every property of its descendants with #wpadminbar *, so the bar and the label need ID-level selectors.
    public static function admin_bar_css() {
        return self::PROGRESS_CSS
            . '#wpadminbar #wp-admin-bar-surveyjs-getting-started>.ab-item{height:auto;padding-bottom:8px}'
            . '#wpadminbar .wp-sjs-menu-progress{display:flex;align-items:center;gap:8px;min-width:180px}'
            . '#wpadminbar .wp-sjs-menu-progress__text{flex:none;font-size:12px;line-height:16px;opacity:.85}'
            . '#wpadminbar .wp-sjs-menu-progress .wp-sjs-progress{display:block;flex:1;width:auto;min-width:60px;height:4px;border-radius:2px;background:rgba(140,143,148,.35)}';
    }

    public static function render_dashboard_progress() {
        $progress = self::progress();
        if ( ! $progress ) {
            return;
        }
        ?>
        <div class="wp-sjs-dw__progress">
            <p>
                <a href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Getting Started', 'surveyjs' ); ?></a>:
                <?php echo esc_html( self::progress_text( $progress['done'], $progress['total'] ) ); ?>.
                <?php esc_html_e( 'Next:', 'surveyjs' ); ?> <a href="<?php echo esc_url( $progress['url'] ); ?>"><?php echo esc_html( $progress['title'] ); ?></a>
            </p>
            <progress class="wp-sjs-progress" max="<?php echo esc_attr( $progress['total'] ); ?>" value="<?php echo esc_attr( $progress['done'] ); ?>" aria-hidden="true"></progress>
        </div>
        <?php
    }

    public static function render_compact() {
        $progress = self::progress();
        if ( ! $progress ) {
            return;
        }
        ?>
        <div class="notice notice-info wp-sjs-checklist-compact">
            <p>
                <strong><?php esc_html_e( 'Get started:', 'surveyjs' ); ?></strong>
                <?php echo esc_html( self::progress_text( $progress['done'], $progress['total'] ) ); ?>.
                <?php esc_html_e( 'Next:', 'surveyjs' ); ?> <a href="<?php echo esc_url( $progress['url'] ); ?>"><?php echo esc_html( $progress['title'] ); ?></a>
                &nbsp;|&nbsp; <a href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'View checklist', 'surveyjs' ); ?></a>
                &nbsp;|&nbsp; <button type="button" class="button-link" data-sjs-checklist="dismiss"><?php esc_html_e( 'Dismiss', 'surveyjs' ); ?></button>
            </p>
        </div>
        <?php
    }

    public static function render() {
        $has_forms = SurveyJS_Forms::exists();
        $checklist = self::checklist();
        $dismissed = self::state()['dismissed'];
        $import_sources = array_filter( array_map( function ( $info ) {
            return $info['importer']::forms();
        }, SurveyJS_Import::sources() ) );
        ?>
        <div class="wrap">
            <h1><?php empty( $_GET['welcome'] ) ? esc_html_e( 'Getting Started', 'surveyjs' ) : esc_html_e( 'Welcome to SurveyJS Forms', 'surveyjs' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></h1>
            <p class="about-description"><?php esc_html_e( 'Build contact forms, surveys and quizzes, add them to any page with a block or a shortcode, get an email on every submission and keep entries right in WordPress.', 'surveyjs' ); ?></p>
            <p>
                <a href="<?php echo esc_url( self::add_form_url() ); ?>" class="button button-primary button-hero"><?php $has_forms ? esc_html_e( 'Create a new form', 'surveyjs' ) : esc_html_e( 'Create your first form', 'surveyjs' ); ?></a>
            </p>
            <p>
                <a href="<?php echo esc_url( self::DOCS_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'surveyjs' ); ?></a>
                &nbsp;|&nbsp; <a href="<?php echo esc_url( SurveyJS_Editor::ui_builder_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'UI Builder', 'surveyjs' ); ?></a>
            </p>

            <?php SurveyJS_Upgrade_Notice::render_card(); ?>

            <?php foreach ( $import_sources as $source => $source_forms ) : ?>
                <div class="card wp-sjs-welcome-import">
                    <h2><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( 'Import from %s', 'surveyjs' ), SurveyJS_Import::sources()[ $source ]['title'] ) ); ?></h2>
                    <?php SurveyJS_Import::render_form( $source, $source_forms ); ?>
                </div>
            <?php endforeach; ?>

            <p class="wp-sjs-checklist-hidden"<?php echo $dismissed ? '' : ' hidden'; ?>><?php esc_html_e( 'The getting-started checklist is hidden.', 'surveyjs' ); ?> <button type="button" class="button-link" data-sjs-checklist="show"><?php esc_html_e( 'Show checklist', 'surveyjs' ); ?></button></p>
            <?php self::render_checklist( $checklist, $dismissed ); ?>
            <?php SurveyJS_Upsell::render_getting_started(); ?>
        </div>
        <?php
    }

    private static function render_checklist( $checklist, $hidden ) {
        $total = $checklist['total'];
        $notification = $checklist['steps']['notification'];
        ?>
        <div class="card wp-sjs-checklist"<?php echo $hidden ? ' hidden' : ''; ?>>
            <?php if ( $total === $checklist['done'] ) : ?>
                <h2><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'You\'re all set!', 'surveyjs' ); ?></h2>
                <p><?php esc_html_e( 'Your form is live and has received its first entry.', 'surveyjs' ); ?></p>
                <?php if ( ! $notification['done'] ) : ?>
                    <p class="wp-sjs-checklist__suggestion">
                        <span class="wp-sjs-checklist__optional"><?php esc_html_e( 'Optional', 'surveyjs' ); ?></span>
                        <?php esc_html_e( 'Want an email every time someone submits the form?', 'surveyjs' ); ?>
                        <a href="<?php echo esc_url( $notification['url'] ); ?>"><?php echo esc_html( $notification['title'] ); ?></a>
                    </p>
                <?php endif; ?>
                <p>
                    <a href="<?php echo esc_url( add_query_arg( 'page', 'surveyjs-main-menu', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Go to all forms', 'surveyjs' ); ?></a>
                    &nbsp;|&nbsp; <a href="<?php echo esc_url( self::REVIEW_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Rate SurveyJS Forms on WordPress.org', 'surveyjs' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></a>
                </p>
            <?php else : ?>
                <h2><?php esc_html_e( 'Get started', 'surveyjs' ); ?></h2>
                <p><?php echo esc_html( self::progress_text( $checklist['done'], $total ) ); ?></p>
                <progress class="wp-sjs-progress" max="<?php echo esc_attr( $total ); ?>" value="<?php echo esc_attr( $checklist['done'] ); ?>"></progress>
                <ol class="wp-sjs-checklist__steps">
                    <?php foreach ( $checklist['steps'] as $key => $step ) : ?>
                        <li>
                            <span class="dashicons <?php echo $step['done'] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" aria-hidden="true"></span>
                            <div>
                                <strong><?php echo esc_html( $step['title'] ); ?></strong>
                                <?php if ( in_array( $key, self::OPTIONAL_STEPS, true ) ) : ?>
                                    <span class="wp-sjs-checklist__optional"><?php esc_html_e( 'Optional', 'surveyjs' ); ?></span>
                                <?php endif; ?>
                                <span class="screen-reader-text"><?php $step['done'] ? esc_html_e( '(completed)', 'surveyjs' ) : esc_html_e( '(not completed)', 'surveyjs' ); ?></span>
                                <?php if ( ! $step['done'] ) : ?>
                                    <p><?php echo esc_html( $step['description'] ); ?></p>
                                    <a href="<?php echo esc_url( $step['url'] ); ?>" class="button"><?php echo esc_html( $step['action'] ); ?></a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <p><button type="button" class="button-link" data-sjs-checklist="dismiss"><?php esc_html_e( 'Hide checklist', 'surveyjs' ); ?></button></p>
        </div>
        <?php
    }
}
