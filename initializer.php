<?php

class WP_SurveyJS {
    public $prefix;
    public $plugin_version = "0.1.0";

    function __construct() {
        $this->prefix = "sjs";
        add_action( 'admin_menu', array( $this, 'wps_add_menu' ) );
        add_filter( 'media_buttons', array($this, 'wps_media_button'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));
        register_activation_hook( __FILE__, array( $this, 'wps_install' ) );
        register_deactivation_hook( __FILE__, array( $this, 'wps_uninstall' ) );

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    public function enqueue_frontend_scripts() {       
        wp_enqueue_style('wps-survey-css', 'https://unpkg.com/survey-jquery/survey.css' );
        wp_enqueue_script('wps-survey-jquery-js', 'https://unpkg.com/survey-jquery/survey.jquery.js', array('jquery'));
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

    function wps_process_shortcode($attrs) {
        ?>
        <div id="surveyElement"></div>
        <div id="surveyResult"></div>
        <script>
            var json = {
                surveyId: '<?php echo $attrs["id"] ?>'
            };

            window.survey = new Survey.Model(json);

            survey
                .onComplete
                .add(function (result) {
                    document
                        .querySelector('#surveyResult')
                        .innerHTML = "result: " + JSON.stringify(result.data);
                });

            jQuery("#surveyElement").Survey({model: survey});
        </script>        
        <?php
    }

    function wps_install() {
    }

    function wps_uninstall() {

    }
  
  }
  
?>