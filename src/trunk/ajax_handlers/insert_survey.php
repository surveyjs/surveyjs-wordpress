<?php

include_once("ajax_handler.php");
include( __DIR__ . "/../service_client.php");

class SurveyJS_InsertSurveyHandler extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_InsertSurvey", false);
    }
        
    function callback() {
        check_ajax_referer( 'surveyjs-insert-survey' );
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