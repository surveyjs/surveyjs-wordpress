<?php

abstract class SurveyJS_AJAX_Handler {

    function __construct($action_name, $need_for_all_users) {
        $this->init_hooks($action_name, $need_for_all_users);
    }

    public function init_hooks($action_name, $need_for_all_users) {
        add_action('wp_ajax_'.$action_name       , array($this,'callback'));
        if($need_for_all_users) {
            add_action('wp_ajax_nopriv_'.$action_name, array($this,'callback_nopriv'));
        }
    }

    public function callback_nopriv() {
        $this->callback();
    }

    abstract public function callback();

}

?>