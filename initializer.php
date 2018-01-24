<?php

class WP_SurveyJS {
    public $prefix;

    function __construct() {
        $this->prefix = "sjs";
        add_action( 'admin_menu', array( $this, 'wps_add_menu' ) );
        add_filter( 'media_buttons', array($this, 'wps_media_button'));
        register_activation_hook( __FILE__, array( $this, 'wps_install' ) );
        register_deactivation_hook( __FILE__, array( $this, 'wps_uninstall' ) );
    }
  
    function wps_add_menu() {
        add_menu_page( 'SurveyJS', 'SurveyJS', 'manage_options', 'sjs-main-menu', array(
                        __CLASS__,
                        'wps_mysurveys_page'
                        ), plugins_url('images/logo_20х20.png', __FILE__));
        add_submenu_page( 'sjs-main-menu', 'SurveyJS' . ' My Surveys', ' My Surveys', 'manage_options', 'sjs-my-surveys', array(
                        __CLASS__,
                        'wps_mysurveys_page'
                        ));
    }
  
    function wps_mysurveys_page() {
    }

    function wps_media_button() {
        $url = add_query_arg(array('action' => 'WP_SJS_InsertSurvey', 'task' => 'form', 'TB_iframe' => '1'), admin_url('admin-ajax.php'));
        ?>
        <a onclick="tb_click.call(this); return false;" href="<?php echo $url; ?>" class="button" title="<?php _e('Insert Survey', $this->prefix); ?>">
            <?php _e('Add Survey', $this->prefix); ?>
        </a>
        <?php
    }

    function wps_install() {
    }

    function wps_uninstall() {

    }
  
  }
  
?>