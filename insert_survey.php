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
        <select>
            <option>- Choose survey to insert -</option>
            <?php
            foreach ($client->getSurveys() as $surveyDefinition) {
                ?>
                <option value="<?php echo $surveyDefinition->Id ?>"><?php echo $surveyDefinition->Name ?></option>
                <?php
            }
            ?>
            </select>
        <?php
    }
}

?>