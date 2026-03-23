<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once("ajax_handler.php");
include( __DIR__ . "/../service_client.php");

class SurveyJS_InsertSurveyHandler extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_InsertSurvey", false);
    }
        
    function callback() {
        if ( 'GET' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
            wp_die( esc_html__( 'Invalid request method.', 'surveyjs' ), '', array( 'response' => 405 ) );
        }
        if ( ! check_ajax_referer( 'surveyjs-insert-survey', '_wpnonce', false ) ) {
            wp_die( esc_html__( 'Security check failed.', 'surveyjs' ), '', array( 'response' => 403 ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'surveyjs' ), '', array( 'response' => 403 ) );
        }
        //wp_send_json_success(array('test'=>'Works!'));
        $client = new SurveyJS_Client();
        ?>
            <script>
                function insert_survey_shortcode() {
                    var select = document.querySelector("select[name='survey-name']");
                    var text = '[Survey id="' + select.value + '" name="' + select.options[select.selectedIndex].text + '"]';
                    window.parent.send_to_editor(text);
                    window.parent.tb_remove();
                }
            </script>
            <select name="survey-name">
                <option>- Choose survey to insert -</option>
                <?php
                foreach ($client->getSurveys() as $surveyDefinition) {
                    ?>
                    <option value="<?php echo esc_attr($surveyDefinition->id) ?>"><?php echo esc_html($surveyDefinition->name) ?></option>
                    <?php
                }
                ?>
            </select>
            <button onclick="insert_survey_shortcode()">Insert</button>
        <?php
    }
}

?>