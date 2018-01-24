<?php

include("ajax_handler.php");
include("service_client.php");

class WP_InsertSurveyHandler extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_InsertSurvey");
        
    }
        
    function callback() {
        //wp_send_json_success(array('test'=>'Works!'));
        $client = new WP_Service_Client("dc736a6f384d48f9b71a1dd94d9d5c24");
        ?>
            <script>
                function insert_survey_shorttext() {
                    var select = document.querySelector("select[name='survey-name']");
                    var text = '[Survey id="' + select.value + '"]';
                    window.parent.send_to_editor(text);
                    window.parent.tb_remove();
                }
            </script>
            <select name="survey-name">
                <option>- Choose survey to insert -</option>
                <?php
                foreach ($client->getSurveys() as $surveyDefinition) {
                    ?>
                    <option value="<?php echo $surveyDefinition->Id ?>"><?php echo $surveyDefinition->Name ?></option>
                    <?php
                }
                ?>
            </select>
            <button onclick="insert_survey_shorttext()">Insert</button>
        <?php
    }
}

?>