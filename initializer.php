<?php

include( "views/settings.php" );
if ( is_admin() ) {
    include( "views/mysurveys.php" );
    include( "views/editor.php" );
}

class WP_SurveyJS {
    public $prefix;
    public $plugin_version = "0.1.0";

    function __construct() {
        $this->prefix = "sjs";
        add_action('admin_menu', array( $this, 'wps_add_menu'));
        add_filter('media_buttons', array($this, 'wps_media_button'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));
        register_activation_hook( __FILE__, array( $this, 'wps_install'));
        register_deactivation_hook( __FILE__, array( $this, 'wps_uninstall'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('wps-adm-knockout-js', 'https://unpkg.com/knockout@3.4.2');
        wp_enqueue_style('wps-adm-bootstrap-css', 'https://unpkg.com/bootstrap@3.3.7/dist/css/bootstrap.css' );
        wp_enqueue_style('wps-adm-survey-css', 'https://unpkg.com/survey-knockout/survey.css' );
        wp_enqueue_script('wps-adm-survey-ko-js', 'https://unpkg.com/survey-knockout/survey.ko.js', array('wps-adm-knockout-js'));
        wp_enqueue_style('wps-adm-surveyjseditor-css', 'https://unpkg.com/surveyjs-editor/surveyeditor.css' );
        wp_enqueue_script('wps-adm-surveyjseditor-js', 'https://unpkg.com/surveyjs-editor/surveyeditor.js', array('wps-adm-survey-ko-js'));
    }

    public function enqueue_frontend_scripts() {       
        wp_enqueue_style('wps-survey-css', 'https://unpkg.com/survey-jquery/survey.css' );
        wp_enqueue_script('wps-survey-jquery-js', 'https://unpkg.com/survey-jquery/survey.jquery.js', array('jquery'));
    }
  
    function wps_add_menu() {
        add_menu_page( 'My surveys', 'SurveyJS', 'manage_options', 'sjs-main-menu', array(
                        "WP_SJS_MySurveys", 'render'
                        ), plugins_url('images/logo_20х20.png', __FILE__));
        // add_submenu_page( 'sjs-main-menu', __( 'My Surveys', 'sjs-main-menu' ), __( 'My Surveys', 'sjs-main-menu' ), 'manage_options', 'sjs-my-surveys', array(
        //                 __CLASS__, 'wps_mysurveys_page'
        //                 ));
        add_submenu_page( 'sjs-main-menu', __( 'Settings', 'sjs-main-menu' ), __( 'Settings', 'sjs-main-menu' ), 'manage_options', 'sjs-settings', array( 'WP_SJS_SettingsPage', 'sjs_render_settings' ) );
        add_submenu_page('', '', '', 'manage_options', 'wp_surveyjs_editor', array('WP_SJS_Editor', 'render'));
    }
  
    // function wps_mysurveys_page() {
    // }

    function wps_media_button() {
        $url = add_query_arg(array('action' => 'WP_SJS_InsertSurvey'), admin_url('admin-ajax.php'));
        ?>
        <a onclick="tb_click.call(this); return false;" href="<?php echo $url; ?>" class="button" title="<?php _e('Insert Survey', $this->prefix); ?>">
            <?php _e('Add Survey', $this->prefix); ?>
        </a>
        <?php
    }

    function wps_process_shortcode($attrs) {
        $id = $attrs["id"];
        ?>
        <div id="surveyElement-<?php echo $id ?>"></div>
        <div id="surveyResult-<?php echo $id ?>"></div>
        <script>
            Survey.StylesManager.applyTheme('<?php echo WP_SJS_SettingsPage::get_theme() ?>');

            var json = {
                surveyId: '<?php echo $id ?>'
            };

            window.survey = new Survey.Model(json);

            survey
                .onComplete
                .add(function (result) {
                    document
                        .querySelector("#surveyResult-<?php echo $id ?>")
                        .innerHTML = "result: " + JSON.stringify(result.data);
                });

            jQuery("#surveyElement-<?php echo $id ?>").Survey({model: survey});
        </script>        
        <?php
    }

    function wps_install() {
    }

    function wps_uninstall() {

    }
  
  }
  
?>