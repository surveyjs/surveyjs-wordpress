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
        return $client->getSurvey( absint( $_GET['id'] ?? 0 ) );
    }

    public static function handle_actions() {
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
            wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
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
        $table = SurveyJS_Entries::table();
        $wpdb->query( $wpdb->prepare( "UPDATE $table SET status = 'read' WHERE form_id = %d AND id = %d AND status = 'unread'", $survey->id, absint( $_POST['entry'] ?? 0 ) ) );
        SurveyJS_Entries::flush_counts();
        wp_send_json_success();
    }

    private static function export_csv( $survey ) {
        global $wpdb;
        $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $table = SurveyJS_Entries::table();
        $partial = SurveyJS_Entries::PARTIAL === SurveyJS_Results_List_Table::current_status();
        $where = $partial ? "status = 'partial'" : "status NOT IN ( 'spam', 'partial' )";
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, json, status, created_at, last_page FROM $table WHERE form_id = %d AND $where ORDER BY created_at, id", $survey->id ) );
        $file_name = $partial ? $survey->name . '-partial-results.csv' : $survey->name . '-results.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file_name ) . '"' );
        $output = fopen( 'php://output', 'w' );
        fwrite( $output, "\xEF\xBB\xBF" );
        fputcsv( $output, array_merge( array( 'ID', __( 'Date', 'surveyjs' ), __( 'Status', 'surveyjs' ) ), array_map( array( 'SurveyJS_Survey_Values', 'question_title' ), $questions ) ) );
        foreach ( $rows as $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            $line = array( $row->id, SurveyJS_Entries::format_date( $row->created_at ), SurveyJS_Entries::status_label( $row->status, (int) $row->last_page ) );
            foreach ( $questions as $question ) {
                $value = is_array( $data ) ? ( $data[ $question['name'] ] ?? null ) : null;
                $links = SurveyJS_Uploaded_Files::links( $value );
                $line[] = $links ? SurveyJS_Uploaded_Files::links_text( $links ) : SurveyJS_Survey_Values::display_value( $question, $value );
            }
            fputcsv( $output, $line );
        }
        fclose( $output );
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
        <h1 class="wp-heading-inline">
            <?php
            /* translators: %s: form name */
            printf( esc_html__( '%s: Results', 'surveyjs' ), esc_html( $survey->name ) );
            ?>
        </h1>
        <a href="<?php echo esc_url( self::export_url( $survey ) ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'surveyjs' ); ?></a>
        <?php do_action( 'surveyjs_results_title_actions', $survey, $current_view ); ?>
        <hr class="wp-header-end">
        <p><a href="<?php echo esc_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu' ) ); ?>"><?php echo is_rtl() ? '&rarr;' : '&larr;'; ?> <?php esc_html_e( 'Back to Forms', 'surveyjs' ); ?></a></p>
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
        if ( ! $survey ) {
            wp_die( esc_html__( 'Form not found.', 'surveyjs' ) );
        }

        $current_view = self::current_view( $survey );
        if ( 'entries' !== $current_view ) {
            ?>
            <div class="wrap">
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
            /* translators: %s: number of deleted results */
            'deleted' => sprintf( _n( '%s result deleted.', '%s results deleted.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            /* translators: %s: number of updated results */
            'updated' => sprintf( _n( '%s result updated.', '%s results updated.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
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
            <?php self::render_header( $survey, $current_view ); ?>

            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $messages[ $message ] ); ?></p></div>
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
                $table->search_box( __( 'Search Results', 'surveyjs' ), 'surveyjs-result' );
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
                var viewData = JSON.parse(document.getElementById("wp-sjs-results-data").textContent);
                function markRead(link) {
                    var row = link.closest("tr");
                    if (!row || !row.classList.contains("wp-sjs-entry-unread")) {
                        return;
                    }
                    row.classList.remove("wp-sjs-entry-unread");
                    var status = row.querySelector(".column-status");
                    if (status) {
                        status.textContent = viewData.readLabel;
                    }
                    fetch(window.ajaxurl, {
                        method: "POST",
                        credentials: "same-origin",
                        body: new URLSearchParams({ action: "surveyjs_entry_read", form: viewData.formId, entry: link.dataset.resultId, _ajax_nonce: viewData.readNonce })
                    });
                }
                document.addEventListener("click", function (event) {
                    var link = event.target.closest(".wp-sjs-view-result");
                    if (!link) {
                        return;
                    }
                    event.preventDefault();
                    markRead(link);
                    var survey = new Survey.Model(viewData.survey);
                    survey.onTextMarkdown.add(SurveyJSMarkdown.handler(viewData.markdown));
                    survey.data = viewData.results[link.dataset.resultId];
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
                });
                var entryId = parseInt(new URLSearchParams(window.location.search).get("entry"), 10);
                var entryLink = entryId ? document.querySelector(".wp-sjs-view-result[data-result-id=\"" + entryId + "\"]") : null;
                if (entryLink) {
                    entryLink.click();
                }
            })();
        </script>
        <?php
    }
}
