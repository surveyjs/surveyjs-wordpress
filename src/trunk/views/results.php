<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_Results {

    const STATUS_ACTIONS = array(
        'read'   => 'read',
        'unread' => 'unread',
        'spam'   => 'spam',
        'unspam' => 'read',
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
            SurveyJS_Entries::set_status( $survey->id, $ids, self::STATUS_ACTIONS[ $action ] );
            wp_safe_redirect( add_query_arg( array( 'message' => 'updated', 'count' => count( $ids ) ), $results_url ) );
            exit;
        }

        if ( 'export' === $action ) {
            check_admin_referer( 'surveyjs-export-results_' . $survey->id );
            self::export_csv( $survey );
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
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, json, status, created_at FROM $table WHERE form_id = %d AND status <> 'spam' ORDER BY created_at, id", $survey->id ) );
        $statuses = SurveyJS_Entries::statuses();

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $survey->name . '-results.csv' ) . '"' );
        $output = fopen( 'php://output', 'w' );
        fwrite( $output, "\xEF\xBB\xBF" );
        fputcsv( $output, array_merge( array( 'ID', __( 'Date', 'surveyjs' ), __( 'Status', 'surveyjs' ) ), array_map( array( 'SurveyJS_Survey_Values', 'question_title' ), $questions ) ) );
        foreach ( $rows as $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            $line = array( $row->id, SurveyJS_Entries::format_date( $row->created_at ), $statuses[ $row->status ] ?? $row->status );
            foreach ( $questions as $question ) {
                $value = is_array( $data ) ? ( $data[ $question['name'] ] ?? null ) : null;
                $links = SurveyJS_Uploaded_Files::links( $value );
                $line[] = $links ? SurveyJS_Uploaded_Files::links_text( $links ) : SurveyJS_Survey_Values::display_value( $question, $value );
            }
            fputcsv( $output, $line );
        }
        fclose( $output );
    }

    public static function render() {
        $survey = self::load_survey();
        if ( ! $survey ) {
            wp_die( esc_html__( 'Form not found.', 'surveyjs' ) );
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
        $export_url = wp_nonce_url(
            SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array( 'id' => $survey->id, 'action' => 'export' ) ),
            'surveyjs-export-results_' . $survey->id
        );
        $view_data = array(
            'survey'    => empty( $json ) ? new stdClass() : SurveyJS_Client::decode_survey_json( $survey->json, false ),
            'results'   => $table->get_items_data(),
            'formId'    => (int) $survey->id,
            'readNonce' => wp_create_nonce( 'surveyjs-entry-read' ),
            'readLabel' => SurveyJS_Entries::statuses()['read'],
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php
                /* translators: %s: form name */
                printf( esc_html__( '%s: Results', 'surveyjs' ), esc_html( $survey->name ) );
                ?>
            </h1>
            <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'surveyjs' ); ?></a>
            <hr class="wp-header-end">
            <p><a href="<?php echo esc_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu' ) ); ?>">&larr; <?php esc_html_e( 'Back to Forms', 'surveyjs' ); ?></a></p>

            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $messages[ $message ] ); ?></p></div>
            <?php endif; ?>

            <?php $table->views(); ?>
            <form method="get">
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
        <style>.wp-sjs-entry-unread td, .wp-sjs-entry-unread th { font-weight: 600; }</style>
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
            })();
        </script>
        <?php
    }
}
