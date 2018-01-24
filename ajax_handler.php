<?php

abstract class AJAX_Handler {

    function __construct($action_name) {
        $this->init_hooks($action_name);
    }

    public function init_hooks($action_name) {
        add_action('wp_ajax_'.$action_name       , array($this,'callback'));
        add_action('wp_ajax_nopriv_'.$action_name, array($this,'callback_nopriv'));
    }

    public function callback_nopriv() {
        $this->callback();
    }

    abstract public function callback();

}

?>