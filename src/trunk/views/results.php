<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_Results {

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
        global $wpdb;
        $results_url = SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array( 'id' => $survey->id ) );

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
            foreach ( $ids as $id ) {
                $wpdb->delete( $wpdb->prefix . 'sjs_results', array( 'id' => $id, 'surveyId' => $survey->id ) );
            }
            wp_safe_redirect( add_query_arg( array( 'message' => 'deleted', 'count' => count( $ids ) ), $results_url ) );
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

    private static function export_csv( $survey ) {
        global $wpdb;
        $questions = SurveyJS_Results_List_Table::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, json FROM {$wpdb->prefix}sjs_results WHERE surveyId = %d ORDER BY id", $survey->id ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $survey->name . '-results.csv' ) . '"' );
        $output = fopen( 'php://output', 'w' );
        fwrite( $output, "\xEF\xBB\xBF" );
        fputcsv( $output, array_merge( array( 'ID' ), array_map( array( 'SurveyJS_Results_List_Table', 'question_title' ), $questions ) ) );
        foreach ( $rows as $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            $line = array( $row->id );
            foreach ( $questions as $question ) {
                $line[] = SurveyJS_Results_List_Table::display_value( $question, is_array( $data ) ? ( $data[ $question['name'] ] ?? null ) : null );
            }
            fputcsv( $output, $line );
        }
        fclose( $output );
    }

    public static function render() {
        $survey = self::load_survey();
        if ( ! $survey ) {
            wp_die( esc_html__( 'Survey not found.', 'surveyjs' ) );
        }

        $json = SurveyJS_Client::decode_survey_json( $survey->json );
        $table = new SurveyJS_Results_List_Table( (int) $survey->id, SurveyJS_Results_List_Table::survey_questions( $json ) );
        $table->prepare_items();

        $message = sanitize_key( $_GET['message'] ?? '' );
        $count = absint( $_GET['count'] ?? 0 );
        $export_url = wp_nonce_url(
            SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array( 'id' => $survey->id, 'action' => 'export' ) ),
            'surveyjs-export-results_' . $survey->id
        );
        $view_data = array(
            'survey'  => empty( $json ) ? new stdClass() : SurveyJS_Client::decode_survey_json( $survey->json, false ),
            'results' => $table->get_items_data(),
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php
                /* translators: %s: survey name */
                printf( esc_html__( '%s: Results', 'surveyjs' ), esc_html( $survey->name ) );
                ?>
            </h1>
            <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'surveyjs' ); ?></a>
            <hr class="wp-header-end">
            <p><a href="<?php echo esc_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu' ) ); ?>">&larr; <?php esc_html_e( 'Back to Surveys', 'surveyjs' ); ?></a></p>

            <?php if ( 'deleted' === $message ) : ?>
                <div class="notice notice-success is-dismissible"><p>
                    <?php
                    /* translators: %s: number of deleted results */
                    echo esc_html( sprintf( _n( '%s result deleted.', '%s results deleted.', $count, 'surveyjs' ), number_format_i18n( $count ) ) );
                    ?>
                </p></div>
            <?php endif; ?>

            <form method="get">
                <input type="hidden" name="page" value="surveyjs_results" />
                <input type="hidden" name="id" value="<?php echo esc_attr( $survey->id ); ?>" />
                <?php
                $table->search_box( __( 'Search Results', 'surveyjs' ), 'surveyjs-result' );
                $table->display();
                ?>
            </form>
            <div id="wp-sjs-result-popup"></div>
        </div>
        <script type="application/json" id="wp-sjs-results-data"><?php echo wp_json_encode( $view_data, JSON_HEX_TAG | JSON_HEX_AMP ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
        <script>
            (function () {
                var viewData = JSON.parse(document.getElementById("wp-sjs-results-data").textContent);
                document.addEventListener("click", function (event) {
                    var link = event.target.closest(".wp-sjs-view-result");
                    if (!link) {
                        return;
                    }
                    event.preventDefault();
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
