<?php

include("ajax_handler.php");

class WP_InsertSurveyHandler extends AJAX_Handler {
    
    function __construct() {
        parent::__construct("WP_SJS_InsertSurvey");
    }
        
    function callback() {
        //wp_send_json_success(array('test'=>'Works!'));
        ?>
        <select>
            <option>- Choose survey to insert -</option>
        </select>
        <?php
    }
}

?>