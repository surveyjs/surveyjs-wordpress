<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_Results {

    const STATUS_ACTIONS = array(
        'read'   => 'read',
        'unread' => 'unread',
        'spam'   => 'spam',
        'unspam' => 'read',
        'keep'   => 'partial',
        'unkeep' => 'partial',
    );

    function __construct() {
    }

    private static function load_survey() {
        require_once __DIR__ . '/surveys_list_table.php';
        require_once __DIR__ . '/results_list_table.php';
        $client = new SurveyJS_Client();
        $id = absint( $_GET['id'] ?? 0 );
        return $client->getSurvey( $id ? $id : self::default_form_id() );
    }

    // Entries opened from the menu without a form: the form with the latest entry, or the newest form.
    public static function default_form_id() {
        global $wpdb;
        $id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT r.form_id FROM %i r INNER JOIN {$wpdb->posts} p ON p.ID = r.form_id AND p.post_type = %s AND p.post_status = 'publish' ORDER BY r.created_at DESC, r.id DESC LIMIT 1",
            SurveyJS_Entries::table(),
            SurveyJS_Forms::POST_TYPE
        ) );
        if ( $id ) {
            return $id;
        }
        $forms = SurveyJS_Forms::names();
        if ( ! $forms ) {
            return 0;
        }
        return (int) max( wp_list_pluck( $forms, 'id' ) );
    }

    private static function render_no_forms() {
        ?>
        <div class="wrap">
            <?php // Notices from WordPress and other plugins go here, above the title, on every plugin page. ?>
            <hr class="wp-header-end">
            <h1><?php esc_html_e( 'Entries', 'surveyjs' ); ?></h1>
            <p><?php esc_html_e( 'Entries of your forms are listed here. You have no forms yet.', 'surveyjs' ); ?></p>
            <p><a class="button button-primary" href="<?php echo esc_url( SurveyJS_Templates::url() ); ?>"><?php esc_html_e( 'Add New Form', 'surveyjs' ); ?></a></p>
        </div>
        <?php
    }

    // A dropdown of forms with an unread badge on each; picking one keeps the current view (Entries or Charts).
    private static function render_form_switcher( $survey, $current_view ) {
        $unread = SurveyJS_Entries::unread_by_form();
        $forms = SurveyJS_Forms::names();
        $view = array_key_first( self::views( $survey ) ) === $current_view ? '' : $current_view;
        $count = $unread[ (int) $survey->id ] ?? 0;
        ?>
        <details class="wp-sjs-form-switcher">
            <summary class="wp-sjs-form-switcher__toggle">
                <span class="screen-reader-text"><?php esc_html_e( 'Form:', 'surveyjs' ); ?></span>
                <span class="wp-sjs-form-switcher__name"><?php echo esc_html( $survey->name ); ?></span>
                <?php self::render_unread_badge( $count ); ?>
                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </summary>
            <ul class="wp-sjs-form-switcher__menu">
                <?php foreach ( $forms as $form ) : ?>
                    <?php $is_current = (int) $form->id === (int) $survey->id; ?>
                    <li>
                        <a href="<?php echo esc_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array_filter( array( 'id' => $form->id, 'view' => $view ) ) ) ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
                            <span class="wp-sjs-form-switcher__name"><?php echo esc_html( $form->name ); ?></span>
                            <?php self::render_unread_badge( $unread[ (int) $form->id ] ?? 0 ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
        <a href="<?php echo esc_url( SurveyJS_Admin_Header::form_url( $survey->id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Form', 'surveyjs' ); ?></a>
        <?php
    }

    private static function render_unread_badge( $count ) {
        if ( ! $count ) {
            return;
        }
        /* translators: %s: number of unread entries */
        $label = sprintf( _n( '%s unread entry', '%s unread entries', $count, 'surveyjs' ), number_format_i18n( $count ) );
        ?>
        <span class="wp-sjs-count" title="<?php echo esc_attr( $label ); ?>"><span aria-hidden="true"><?php echo esc_html( number_format_i18n( $count ) ); ?></span><span class="screen-reader-text"><?php echo esc_html( $label ); ?></span></span>
        <?php
    }

    public static function enqueue_style() {
        wp_enqueue_style( 'wps-adm--entries-table.css', plugins_url( 'entries_table.css', __FILE__ ), array(), (string) filemtime( __DIR__ . '/entries_table.css' ) );
    }

    public static function handle_actions() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $survey = self::load_survey();
        if ( ! $survey ) {
            return;
        }
        $results_url = SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array_filter( array(
            'id'     => $survey->id,
            'status' => SurveyJS_Results_List_Table::current_status(),
        ) ) );

        $action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );
        if ( '' === $action || '-1' === $action ) {
            $action = sanitize_key( wp_unslash( $_REQUEST['action2'] ?? '' ) );
        }

        if ( ( 'delete' === $action || isset( self::STATUS_ACTIONS[ $action ] ) ) && ! isset( $_REQUEST['result'] ) && ! isset( $_GET['result_id'] ) ) {
            wp_safe_redirect( $results_url );
            exit;
        }

        if ( 'delete' === $action ) {
            if ( isset( $_REQUEST['result'] ) ) {
                check_admin_referer( 'bulk-results' );
                $ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['result'] ) );
            } else {
                $id = absint( $_GET['result_id'] ?? 0 );
                check_admin_referer( 'surveyjs-delete-result_' . $id );
                $ids = array( $id );
            }
            $count = SurveyJS_Entries::delete( $survey, $ids );
            wp_safe_redirect( add_query_arg( array( 'message' => 'deleted', 'count' => $count ), $results_url ) );
            exit;
        }

        if ( isset( self::STATUS_ACTIONS[ $action ] ) ) {
            if ( isset( $_REQUEST['result'] ) ) {
                check_admin_referer( 'bulk-results' );
                $ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['result'] ) );
            } else {
                $id = absint( $_GET['result_id'] ?? 0 );
                check_admin_referer( 'surveyjs-result-status_' . $id );
                $ids = array( $id );
            }
            if ( 'unspam' === $action ) {
                SurveyJS_Entries::unspam( $survey->id, $ids );
            } elseif ( 'keep' === $action || 'unkeep' === $action ) {
                SurveyJS_Entries::set_kept( $survey->id, $ids, 'keep' === $action );
            } else {
                SurveyJS_Entries::set_status( $survey->id, $ids, self::STATUS_ACTIONS[ $action ] );
            }
            wp_safe_redirect( add_query_arg( array( 'message' => 'updated', 'count' => count( $ids ) ), $results_url ) );
            exit;
        }

        if ( 'export' === $action ) {
            check_admin_referer( 'surveyjs-export-results_' . $survey->id );
            self::export_csv( $survey );
            exit;
        }

        if ( '' !== $action && isset( $_REQUEST['result'] ) && has_action( 'surveyjs_entries_bulk_action_' . $action ) ) {
            check_admin_referer( 'bulk-results' );
            $ids = array_filter( array_map( 'absint', (array) wp_unslash( $_REQUEST['result'] ) ) );
            do_action( 'surveyjs_entries_bulk_action_' . $action, $ids, $survey, $results_url );
            wp_safe_redirect( add_query_arg( array( 'message' => 'updated', 'count' => count( $ids ) ), $results_url ) );
            exit;
        }

        if ( ! empty( $_GET['_wp_http_referer'] ) ) {
            wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) );
            exit;
        }
    }

    public static function handle_mark_read() {
        check_ajax_referer( 'surveyjs-entry-read' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( null, 403 );
        }
        $survey = SurveyJS_Forms::find( absint( $_POST['form'] ?? 0 ) );
        if ( ! $survey ) {
            wp_send_json_error( null, 404 );
        }
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'read' WHERE form_id = %d AND id = %d AND status = 'unread'", SurveyJS_Entries::table(), $survey->id, absint( $_POST['entry'] ?? 0 ) ) );
        SurveyJS_Entries::flush_counts();
        wp_send_json_success();
    }

    private static function export_csv( $survey ) {
        global $wpdb;
        $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $table = SurveyJS_Entries::table();
        $partial = SurveyJS_Entries::PARTIAL === SurveyJS_Results_List_Table::current_status();
        $rows = $partial
            ? $wpdb->get_results( $wpdb->prepare( "SELECT id, json, status, created_at, last_page FROM %i WHERE form_id = %d AND status = 'partial' ORDER BY created_at, id", $table, $survey->id ) )
            : $wpdb->get_results( $wpdb->prepare( "SELECT id, json, status, created_at, last_page FROM %i WHERE form_id = %d AND status NOT IN ( 'spam', 'partial' ) ORDER BY created_at, id", $table, $survey->id ) );
        $file_name = $partial ? $survey->name . '-partial-results.csv' : $survey->name . '-results.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
        echo "\xEF\xBB\xBF";
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array_merge( array( 'ID', __( 'Date', 'surveyjs' ), __( 'Status', 'surveyjs' ) ), array_map( array( 'SurveyJS_Survey_Values', 'question_title' ), $questions ) ), ',', '"', '\\' ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- writes to php://output (the HTTP response), not the filesystem.
        foreach ( $rows as $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            $line = array( $row->id, SurveyJS_Entries::format_date( $row->created_at ), SurveyJS_Entries::status_label( $row->status, (int) $row->last_page ) );
            foreach ( $questions as $question ) {
                $value = is_array( $data ) ? ( $data[ $question['name'] ] ?? null ) : null;
                $links = SurveyJS_Uploaded_Files::links( $value );
                $line[] = $links ? SurveyJS_Uploaded_Files::links_text( $links ) : SurveyJS_Survey_Values::display_value( $question, $value );
            }
            fputcsv( $output, $line, ',', '"', '\\' ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- writes to php://output (the HTTP response), not the filesystem.
        }
    }

    public static function views( $survey ) {
        $views = apply_filters( 'surveyjs_results_views', array( 'entries' => __( 'Entries', 'surveyjs' ) ), $survey );
        return is_array( $views ) && $views ? $views : array( 'entries' => __( 'Entries', 'surveyjs' ) );
    }

    public static function current_view( $survey ) {
        $views = self::views( $survey );
        $view = sanitize_key( wp_unslash( $_GET['view'] ?? '' ) );
        return isset( $views[ $view ] ) ? $view : (string) array_key_first( $views );
    }

    public static function view_url( $survey, $view ) {
        return SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array_filter( array(
            'id'   => $survey->id,
            'view' => array_key_first( self::views( $survey ) ) === $view ? '' : $view,
        ) ) );
    }

    private static function export_url( $survey ) {
        return wp_nonce_url(
            SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array_filter( array(
                'id'     => $survey->id,
                'action' => 'export',
                'status' => SurveyJS_Entries::PARTIAL === SurveyJS_Results_List_Table::current_status() ? SurveyJS_Entries::PARTIAL : '',
            ) ) ),
            'surveyjs-export-results_' . $survey->id
        );
    }

    private static function render_header( $survey, $current_view ) {
        $views = self::views( $survey );
        ?>
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Entries', 'surveyjs' ); ?></h1>
        <?php self::render_form_switcher( $survey, $current_view ); ?>
        <a href="<?php echo esc_url( self::export_url( $survey ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'surveyjs' ); ?></a>
        <?php do_action( 'surveyjs_results_title_actions', $survey, $current_view ); ?>
        <?php // Ends the title row. Core does this with the hr.wp-header-end, which now sits above the title for notices. ?>
        <div class="wp-sjs-title-end"></div>
        <?php if ( count( $views ) > 1 ) : ?>
            <nav class="nav-tab-wrapper wp-sjs-results__views">
                <?php foreach ( $views as $view => $label ) : ?>
                    <a href="<?php echo esc_url( self::view_url( $survey, $view ) ); ?>" class="nav-tab<?php echo $view === $current_view ? ' nav-tab-active' : ''; ?>"<?php echo $view === $current_view ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <?php
    }

    public static function render() {
        $survey = self::load_survey();
        if ( ! $survey && ! absint( $_GET['id'] ?? 0 ) ) {
            self::render_no_forms();
            return;
        }
        if ( ! $survey ) {
            SurveyJS_MySurveys::die_not_found( absint( $_GET['id'] ?? 0 ) );
        }

        $current_view = self::current_view( $survey );
        if ( 'entries' !== $current_view ) {
            ?>
            <div class="wrap">
                <?php // Notices from WordPress and other plugins go here, above the title, on every plugin page. ?>
                <hr class="wp-header-end">
                <?php self::render_header( $survey, $current_view ); ?>
                <?php do_action( 'surveyjs_render_results_view', $current_view, $survey ); ?>
            </div>
            <?php
            return;
        }

        $json = SurveyJS_Client::decode_survey_json( $survey->json );
        $table = new SurveyJS_Results_List_Table( (int) $survey->id, SurveyJS_Survey_Values::survey_questions( $json ) );
        $table->prepare_items();

        $message = sanitize_key( $_GET['message'] ?? '' );
        $count = absint( $_GET['count'] ?? 0 );
        $messages = array(
            /* translators: %s: number of deleted entries */
            'deleted' => sprintf( _n( '%s entry deleted.', '%s entries deleted.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            /* translators: %s: number of updated entries */
            'updated' => sprintf( _n( '%s entry updated.', '%s entries updated.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
        );
        $view_data = array(
            'survey'    => empty( $json ) ? new stdClass() : SurveyJS_Client::decode_survey_json( $survey->json, false ),
            'results'   => $table->get_items_data(),
            'formId'    => (int) $survey->id,
            'readNonce' => wp_create_nonce( 'surveyjs-entry-read' ),
            'readLabel' => SurveyJS_Entries::statuses()['read'],
            'markdown'  => SurveyJS_Form_Settings::get( $survey )['markdown'],
        );
        ?>
        <div class="wrap">
            <?php // Notices from WordPress and other plugins go here, above the title, on every plugin page. ?>
            <hr class="wp-header-end">
            <?php self::render_header( $survey, $current_view ); ?>

            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible" data-sjs-snackbar><p><?php echo esc_html( $messages[ $message ] ); ?></p></div>
            <?php endif; ?>

            <?php $expiring = SurveyJS_Entries::count_partial_deleted_soon( $survey->id ); ?>
            <?php if ( $expiring ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <?php
                        /* translators: %s: number of partial entries */
                        echo esc_html( sprintf( _n( '%s partial entry will be deleted within 5 days.', '%s partial entries will be deleted within 5 days.', $expiring, 'surveyjs' ), number_format_i18n( $expiring ) ) );
                        ?>
                        <?php esc_html_e( 'Click Keep on the ones you need.', 'surveyjs' ); ?>
                        <?php if ( SurveyJS_Entries::PARTIAL !== SurveyJS_Results_List_Table::current_status() ) : ?>
                            <a href="<?php echo esc_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array( 'id' => $survey->id, 'status' => SurveyJS_Entries::PARTIAL ) ) ); ?>"><?php esc_html_e( 'View partial entries', 'surveyjs' ); ?></a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php $table->views(); ?>
            <form method="get" id="wp-sjs-results-list">
                <input type="hidden" name="page" value="surveyjs_results" />
                <input type="hidden" name="id" value="<?php echo esc_attr( $survey->id ); ?>" />
                <input type="hidden" name="status" value="<?php echo esc_attr( SurveyJS_Results_List_Table::current_status() ); ?>" />
                <?php
                $table->display();
                ?>
            </form>
            <div id="wp-sjs-result-popup"></div>
        </div>
        <style>.wp-sjs-entry-unread td, .wp-sjs-entry-unread th { font-weight: 600; } .wp-sjs-entry-expiring { color: #b32d2e; }</style>
        <script>
            document.getElementById("wp-sjs-results-list").addEventListener("submit", function (event) {
                var button = event.submitter;
                if (!button || !/^doaction2?$/.test(button.id)) {
                    return;
                }
                var select = document.getElementById(button.id === "doaction2" ? "bulk-action-selector-bottom" : "bulk-action-selector-top");
                if (select && select.value === "delete" && !window.confirm(<?php echo wp_json_encode( __( 'Delete the selected entries? Files uploaded with them will be deleted too. This cannot be undone.', 'surveyjs' ), JSON_HEX_TAG ); ?>)) {
                    event.preventDefault();
                }
            });
        </script>
        <script type="application/json" id="wp-sjs-results-data"><?php echo wp_json_encode( $view_data, JSON_HEX_TAG | JSON_HEX_AMP ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
        <script>
            (function () {
                // Read on use: the live search in admin.js swaps the rows, the panel and this data for the found entries.
                var viewData = JSON.parse(document.getElementById("wp-sjs-results-data").textContent);
                var selectedId = 0;

                function rows() {
                    return Array.prototype.slice.call(document.querySelectorAll("#the-list .wp-sjs-entries__row"));
                }
                function rowFor(id) {
                    return document.querySelector('#the-list .wp-sjs-entries__row[data-entry-id="' + id + '"]');
                }
                function markRead(id) {
                    var row = rowFor(id);
                    if (!row || !row.classList.contains("wp-sjs-entry-unread")) {
                        return;
                    }
                    row.classList.remove("wp-sjs-entry-unread");
                    var status = row.querySelector(".column-status");
                    if (status) {
                        status.textContent = viewData.readLabel;
                    }
                    var panelStatus = document.querySelector('#wp-sjs-entry-' + id + ' [data-sjs-entry-status]');
                    if (panelStatus) {
                        panelStatus.textContent = viewData.readLabel;
                    }
                    fetch(window.ajaxurl, {
                        method: "POST",
                        credentials: "same-origin",
                        body: new URLSearchParams({ action: "surveyjs_entry_read", form: viewData.formId, entry: id, _ajax_nonce: viewData.readNonce })
                    });
                }
                // Shows one entry in the panel and highlights its row. Only a choice of the user (click, arrows, a link
                // to the entry) marks it read; the entry shown first when the page opens stays as it was.
                function select(id, scroll, read) {
                    var row = rowFor(id);
                    var article = document.getElementById("wp-sjs-entry-" + id);
                    if (!row || !article) {
                        return;
                    }
                    selectedId = id;
                    rows().forEach(function (other) {
                        other.classList.toggle("is-selected", other === row);
                        other.setAttribute("aria-selected", other === row ? "true" : "false");
                    });
                    document.querySelectorAll("#wp-sjs-entry-panel .wp-sjs-entry").forEach(function (other) {
                        other.hidden = other !== article;
                    });
                    var panel = document.getElementById("wp-sjs-entry-panel");
                    panel.classList.add("has-selection");
                    if (scroll) {
                        row.scrollIntoView({ block: "nearest" });
                    }
                    // The panel scrolls with the page: a new entry starts at its top, so bring the top back into view
                    // when the page was scrolled past it (reading a long entry, or a row far down the list).
                    if (read && panel.getBoundingClientRect().top < 0) {
                        panel.scrollIntoView({ block: "start" });
                    }
                    if (read) {
                        markRead(id);
                    }
                }
                function step(delta) {
                    var list = rows();
                    var index = list.findIndex(function (row) { return Number(row.dataset.entryId) === selectedId; });
                    var next = list[index + delta];
                    if (next) {
                        select(Number(next.dataset.entryId), true, true);
                    }
                }
                function openAsForm(id) {
                    var survey = new Survey.Model(viewData.survey);
                    survey.onTextMarkdown.add(SurveyJSMarkdown.handler(viewData.markdown));
                    survey.data = viewData.results[id];
                    survey.readOnly = true;
                    var popupContainer = document.createElement("div");
                    document.getElementById("wp-sjs-result-popup").appendChild(popupContainer);
                    SurveyUI.renderPopupSurvey(survey, popupContainer, {
                        isExpanded: true,
                        allowClose: true,
                        onClose: function () {
                            popupContainer.remove();
                        }
                    });
                }

                document.addEventListener("click", function (event) {
                    var open = event.target.closest("[data-sjs-entry-open]");
                    if (open) {
                        openAsForm(open.getAttribute("data-sjs-entry-open"));
                        return;
                    }
                    var nav = event.target.closest("[data-sjs-entry-nav]");
                    if (nav) {
                        step(nav.getAttribute("data-sjs-entry-nav") === "next" ? 1 : -1);
                        return;
                    }
                    var row = event.target.closest("#the-list .wp-sjs-entries__row");
                    // The checkbox and its cell keep selecting rows for bulk actions.
                    if (!row || event.target.closest(".check-column, input, label")) {
                        return;
                    }
                    if (event.target.closest("a") && !event.target.closest(".wp-sjs-view-result")) {
                        return;
                    }
                    event.preventDefault();
                    select(Number(row.dataset.entryId), false, true);
                });
                // Up and down arrows (or K and J) move through the list while no field has focus.
                document.addEventListener("keydown", function (event) {
                    if (event.altKey || event.ctrlKey || event.metaKey || event.target.closest("input, select, textarea, [contenteditable]") || document.querySelector("#wp-sjs-result-popup > *")) {
                        return;
                    }
                    if (event.key === "ArrowDown" || event.key === "j") {
                        event.preventDefault();
                        step(1);
                    } else if (event.key === "ArrowUp" || event.key === "k") {
                        event.preventDefault();
                        step(-1);
                    }
                });
                document.addEventListener("surveyjs:list-updated", function () {
                    viewData = JSON.parse(document.getElementById("wp-sjs-results-data").textContent);
                    var first = rows()[0];
                    if (first) {
                        select(Number(first.dataset.entryId), false, false);
                    }
                });

                // Opens the entry from the URL (links from the dashboard), otherwise the first one on the page.
                var entryId = parseInt(new URLSearchParams(window.location.search).get("entry"), 10);
                var first = rows()[0];
                if (entryId && rowFor(entryId)) {
                    select(entryId, true, true);
                } else if (first) {
                    select(Number(first.dataset.entryId), false, false);
                }
            })();
        </script>
        <?php
    }
}
