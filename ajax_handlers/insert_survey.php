<?php

include_once("ajax_handler.php");
include( __DIR__ . "/../service_client.php");

class WP_InsertSurveyHandler extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_InsertSurvey");
    }
        
    function callback() {
        //wp_send_json_success(array('test'=>'Works!'));
        $client = new WP_Service_Client();
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
                    <option value="<?php echo $surveyDefinition->id ?>"><?php echo $surveyDefinition->name ?></option>
                    <?php
                }
                ?>
            </select>
            <button onclick="insert_survey_shortcode()">Insert</button>
        <?php
    }
}

?>