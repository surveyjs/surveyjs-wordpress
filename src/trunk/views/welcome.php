<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Welcome {

    const PAGE = 'surveyjs-getting-started';
    const REDIRECT_TRANSIENT = 'surveyjs_activation_redirect';
    const CHECKLIST_OPTION = 'surveyjs_checklist';
    const ENTRY_OPTION = 'surveyjs_received_entry';
    const DOCS_URL = 'https://surveyjs.io/form-library/documentation/overview';

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
        if ( ! current_user_can( 'manage_options' ) ) {
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

    public static function enqueue_assets() {
        if ( ! in_array( sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ), array( self::PAGE, 'surveyjs-main-menu' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        wp_add_inline_style( 'common', '.wp-sjs-checklist.card,.wp-sjs-welcome-import.card,.wp-sjs-pro-upsell.card{max-width:640px}.wp-sjs-checklist progress{width:100%}.wp-sjs-checklist__steps{margin:16px 0 0;list-style:none}.wp-sjs-checklist__steps li{display:flex;gap:8px;margin:0;padding:12px 0;border-top:1px solid #f0f0f1}.wp-sjs-checklist__steps p{margin:4px 0 8px}.wp-sjs-checklist .dashicons-yes-alt{color:#00a32a}.wp-sjs-checklist .dashicons-marker{color:#a7aaad}.wp-sjs-checklist[hidden],.wp-sjs-checklist-compact[hidden],.wp-sjs-checklist-hidden[hidden]{display:none}' );
        wp_add_inline_script( 'common', 'var surveyjsChecklist = ' . wp_json_encode( array( 'nonce' => wp_create_nonce( 'surveyjs-checklist' ) ) ) . ';' . <<<'JS'
document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-sjs-checklist]");
    if (!button) {
        return;
    }
    var action = button.getAttribute("data-sjs-checklist");
    document.querySelectorAll(".wp-sjs-checklist, .wp-sjs-checklist-compact").forEach(function (element) {
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
        $forms = SurveyJS_Forms::all();
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

        $entry = get_option( self::ENTRY_OPTION ) || SurveyJS_Entries::exists();
        $editor_url = $latest_id ? add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => $latest_id ), admin_url( 'admin.php' ) ) : self::add_form_url();
        $entries_url = $latest_id ? add_query_arg( array( 'page' => 'surveyjs_results', 'id' => $latest_id ), admin_url( 'admin.php' ) ) : self::add_form_url();
        $placement_url = $placement && is_post_type_viewable( get_post_type( $placement ) ) ? get_permalink( $placement ) : '';

        return array(
            array(
                'done'        => (bool) $form_ids,
                'title'       => __( 'Create a form', 'surveyjs' ),
                'description' => __( 'Start with a ready-made template or a blank form and edit the questions in the JSON editor or the visual UI Builder.', 'surveyjs' ),
                'url'         => self::add_form_url(),
                'action'      => __( 'Create a form', 'surveyjs' ),
            ),
            array(
                'done'        => (bool) $placement,
                'title'       => __( 'Add the form to a page', 'surveyjs' ),
                'description' => $latest_id
                    /* translators: %s: form shortcode */
                    ? sprintf( __( 'Insert the SurveyJS Forms block, or paste the shortcode %s, and publish the page.', 'surveyjs' ), '[surveyjs id="' . $latest_id . '"]' )
                    : __( 'Insert the SurveyJS Forms block or the form shortcode, and publish the page.', 'surveyjs' ),
                'url'         => admin_url( 'post-new.php?post_type=page' ),
                'action'      => __( 'Add a new page', 'surveyjs' ),
            ),
            array(
                'done'        => $notification,
                'title'       => __( 'Set up an email notification', 'surveyjs' ),
                'description' => __( 'Get an email every time someone submits the form.', 'surveyjs' ),
                'url'         => $latest_id ? add_query_arg( 'tab', 'email', $editor_url ) : $editor_url,
                'action'      => $latest_id ? __( 'Set up notification', 'surveyjs' ) : __( 'Create a form', 'surveyjs' ),
            ),
            array(
                'done'        => (bool) $entry,
                'title'       => __( 'Receive your first entry', 'surveyjs' ),
                'description' => __( 'Submit the form on your site to check that everything works. Entries appear on the form\'s Entries page.', 'surveyjs' ),
                'url'         => $placement_url ? $placement_url : $entries_url,
                'action'      => $placement_url ? __( 'Open the page', 'surveyjs' ) : ( $latest_id ? __( 'View entries', 'surveyjs' ) : __( 'Create a form', 'surveyjs' ) ),
            ),
        );
    }

    private static function find_placement( $form_ids ) {
        global $wpdb;
        if ( ! $form_ids ) {
            return 0;
        }
        $posts = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status = 'publish' AND ( post_content LIKE '%wp:sjs/gutenberg-block%' OR post_content LIKE '%[survey%' ) ORDER BY ID DESC LIMIT 50" );
        foreach ( $posts as $post ) {
            if ( array_intersect( array_map( array( 'SurveyJS_Forms', 'resolve_id' ), SurveyJS_SurveyJS::content_survey_ids( $post->post_content ) ), $form_ids ) ) {
                return (int) $post->ID;
            }
        }
        return 0;
    }

    private static function checklist() {
        $state = self::state();
        $steps = self::steps();
        $done = count( array_filter( wp_list_pluck( $steps, 'done' ) ) );
        if ( ( count( $steps ) === $done ) !== $state['completed'] ) {
            self::update_state( array( 'completed' => count( $steps ) === $done ) );
        }
        return array( 'steps' => $steps, 'done' => $done );
    }

    private static function progress_text( $done, $total ) {
        /* translators: 1: number of completed steps, 2: total number of steps */
        return sprintf( __( '%1$d of %2$d steps completed', 'surveyjs' ), $done, $total );
    }

    public static function render_compact() {
        $state = self::state();
        if ( $state['dismissed'] || $state['completed'] ) {
            return;
        }
        $checklist = self::checklist();
        if ( count( $checklist['steps'] ) === $checklist['done'] ) {
            return;
        }
        $next = current( array_filter( $checklist['steps'], function ( $step ) {
            return ! $step['done'];
        } ) );
        ?>
        <div class="notice notice-info wp-sjs-checklist-compact">
            <p>
                <strong><?php esc_html_e( 'Get started:', 'surveyjs' ); ?></strong>
                <?php echo esc_html( self::progress_text( $checklist['done'], count( $checklist['steps'] ) ) ); ?>.
                <?php esc_html_e( 'Next:', 'surveyjs' ); ?> <a href="<?php echo esc_url( $next['url'] ); ?>"><?php echo esc_html( $next['title'] ); ?></a>
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
        $total = count( $checklist['steps'] );
        ?>
        <div class="card wp-sjs-checklist"<?php echo $hidden ? ' hidden' : ''; ?>>
            <?php if ( $total === $checklist['done'] ) : ?>
                <h2><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'You\'re all set!', 'surveyjs' ); ?></h2>
                <p><?php esc_html_e( 'Your form is live, sends email notifications and has received its first entry.', 'surveyjs' ); ?></p>
                <p><a href="<?php echo esc_url( add_query_arg( 'page', 'surveyjs-main-menu', admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Go to all forms', 'surveyjs' ); ?></a></p>
            <?php else : ?>
                <h2><?php esc_html_e( 'Get started', 'surveyjs' ); ?></h2>
                <p><?php echo esc_html( self::progress_text( $checklist['done'], $total ) ); ?></p>
                <progress max="<?php echo esc_attr( $total ); ?>" value="<?php echo esc_attr( $checklist['done'] ); ?>"></progress>
                <ol class="wp-sjs-checklist__steps">
                    <?php foreach ( $checklist['steps'] as $step ) : ?>
                        <li>
                            <span class="dashicons <?php echo $step['done'] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" aria-hidden="true"></span>
                            <div>
                                <strong><?php echo esc_html( $step['title'] ); ?></strong>
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
