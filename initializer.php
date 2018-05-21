<?php

include( "views/settings.php" );
if ( is_admin() ) {
    include( "views/mysurveys.php" );
    include( "views/editor.php" );
    include( "views/results.php" );
}

class WP_SurveyJS {
    public static $prefix = "sjs";
    public $plugin_version = "0.1.0";

    function __construct() {
        add_action('admin_menu', array( $this, 'wps_add_menu'));
        add_filter('media_buttons', array($this, 'wps_media_button'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));


        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('wps-adm-index-css', plugins_url('/index.css', __FILE__) );

        wp_enqueue_script('wps-adm-knockout-js', 'https://unpkg.com/knockout@3.4.2');
        wp_enqueue_style('wps-adm-bootstrap-css', 'https://unpkg.com/bootstrap@3.3.7/dist/css/bootstrap.css' );
        wp_enqueue_style('wps-adm-survey-css', 'https://unpkg.com/survey-knockout/survey.css' );
        wp_enqueue_script('wps-adm-survey-ko-js', 'https://unpkg.com/survey-knockout/survey.ko.js', array('wps-adm-knockout-js'));
        wp_enqueue_style('wps-adm-surveyjseditor-css', 'https://unpkg.com/surveyjs-editor/surveyeditor.css' );
        wp_enqueue_script('wps-adm-surveyjseditor-js', 'https://unpkg.com/surveyjs-editor/surveyeditor.js', array('wps-adm-survey-ko-js'));
        wp_enqueue_style('wps-adm-datatables-css', 'https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css' );
        wp_enqueue_script('wps-adm-sdatatables-js', 'https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js' );

        wp_enqueue_script('wps-adm-sdatatables-js-1', 'https://cdn.datatables.net/buttons/1.5.1/js/dataTables.buttons.min.js' );
        wp_enqueue_style('wps-adm-datatables-css-1', 'https://cdn.datatables.net/buttons/1.5.1/css/buttons.dataTables.min.css' );
        wp_enqueue_script('wps-adm-sdatatables-js-2', 'https://cdn.datatables.net/buttons/1.5.1/js/buttons.flash.min.js' );
        wp_enqueue_script('wps-adm-sdatatables-js-3', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js' );
        wp_enqueue_script('wps-adm-sdatatables-js-4', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js' );
        wp_enqueue_script('wps-adm-sdatatables-js-5', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js' );
        wp_enqueue_script('wps-adm-sdatatables-js-6', 'https://cdn.datatables.net/buttons/1.5.1/js/buttons.html5.min.js' );
        wp_enqueue_script('wps-adm-sdatatables-js-7', 'https://cdn.datatables.net/buttons/1.5.1/js/buttons.print.min.js' );
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');
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
        add_submenu_page('', '', '', 'manage_options', 'wp_surveyjs_results', array('WP_SJS_Results', 'render'));
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
        $getSurveyJsonUri = add_query_arg(array('action' => 'WP_SJS_GetSurveyJson'), admin_url('admin-ajax.php'));
        $saveResultUri = add_query_arg(array('action' => 'WP_SJS_SaveResult'), admin_url('admin-ajax.php'));
        ?>
        <div id="surveyContainer-<?php echo $id ?>" style="margin: 10px;">
            <div id="surveyElement-<?php echo $id ?>">Survey is loading...</div>
            <div id="surveyResult-<?php echo $id ?>"></div>
        </div>
        <script>
            jQuery.ajax({
                url:  "<?php echo $getSurveyJsonUri  ?>",
                type: "POST",
                data: { Id: <?php echo $id ?> },
                success: function (data) {
                    var json = JSON.parse(data.json.replace(/\\/g, ""));
                    initSurvey<?php echo $id ?>(json);
                }
            });

            function initSurvey<?php echo $id ?>(json) {
                Survey.StylesManager.applyTheme('<?php echo WP_SJS_SettingsPage::get_theme() ?>');

                var customCss = {
                    <?php 
                        if (WP_SJS_SettingsPage::get_allow_paddings() == 0) {
                            echo '"root": "sv_main sv_default_css"';
                        }
                    ?>
                }

                window.survey<?php echo $id ?> = new Survey.Model(json);

                survey<?php echo $id ?>
                    .onComplete
                    .add(function (result) {
                        jQuery.ajax({
                            url:  "<?php echo $saveResultUri ?>",
                            type: "POST",
                            data: { SurveyId: '<?php echo $id ?>', Json : JSON.stringify(result.data) },
                            success: function (data) {}
                        });
                        document
                            .querySelector("#surveyResult-<?php echo $id ?>")
                            .innerHTML = "result: " + JSON.stringify(result.data);
                    });

                jQuery("#surveyElement-<?php echo $id ?>").Survey({model: survey<?php echo $id ?>, css: customCss});
            }
        </script>        
        <?php
    }
  
  }
  
?>