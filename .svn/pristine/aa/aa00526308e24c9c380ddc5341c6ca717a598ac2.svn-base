<?php

include( "views/settings.php" );

if ( is_admin() ) {
    include( "views/mysurveys.php" );
    include( "views/editor.php" );
    include( "views/results.php" );
}

class SurveyJS_SurveyJS {
    public static $prefix = "sjs";
    public $plugin_version = "0.1.0";

    function __construct() {
        add_action('admin_menu', array( $this, 'wps_add_menu'));
        add_filter('media_buttons', array($this, 'wps_media_button'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));


        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        add_action('init', array($this, 'register_sjs_gutenberg_block'));
    }

    public function register_sjs_gutenberg_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $blockname = 'sjs/gutenberg-block';
        $blockscriptname = 'sjs-block-js';

        $client = new SurveyJS_Client();
        $surveys = $client->getSurveys();

        if ( is_admin() ) {
            wp_enqueue_script(
                $blockscriptname,
                plugins_url( 'block/block.js', __FILE__),
                array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
                true // Enqueue the script in the footer.
            );

            wp_localize_script(
                $blockscriptname,
                'surveys',
                $surveys
            );
        }

        register_block_type(
            $blockname,
            array(
                'attributes'      => array(
                    'shortcode'          => array(
                        'type' => 'string',
                        'default' => '',
                    ),
                    'surveyid'   => array(
                        'type' => 'string',
                        'default' => 'none',
                    ),
                ),
                'editor_script' => $blockscriptname,
                'render_callback' => array( self::class, 'render_callback_shortcode' ),
            )
        );
    }

    public static function render_callback_shortcode( $attributes ) {
		return do_shortcode( sanitize_text_field( $attributes['shortcode'] ) );
	}

    public function enqueue_admin_scripts() {
        if ( isset( $_GET["page"] ) &&
            ($_GET["page"] == "sjs-settings" ||
            $_GET['page'] == "sjs-main-menu" ||
            $_GET['page'] == "surveyjs_editor" ||
            $_GET['page'] == "surveyjs_results")
        ) {
            wp_enqueue_style('wps-adm-index-css', plugins_url('/index.css', __FILE__) );

            wp_enqueue_script('wps-adm-knockout-js', plugins_url('libs/knockout.min.js', __FILE__));
            wp_enqueue_style('wps-adm-bootstrap-css', plugins_url('libs/bootstrap.min.css', __FILE__) );

            wp_enqueue_style('wps-adm-survey-css', plugins_url('libs/modern.css', __FILE__) );
            wp_enqueue_script('wps-adm-survey-ko-js', plugins_url('libs/survey.ko.min.js', __FILE__), array('wps-adm-knockout-js'));
            wp_enqueue_style('wps-adm-surveyjseditor-css', plugins_url('libs/survey-creator.css', __FILE__) );
            wp_enqueue_script('wps-adm-surveyjseditor-js', plugins_url('libs/survey-creator.min.js', __FILE__), array('wps-adm-survey-ko-js'));

            wp_enqueue_style('wps-adm-datatables-css', plugins_url('libs/datatables/dataTables.min.css', __FILE__) );
            wp_enqueue_script('wps-adm-sdatatables-js', plugins_url('libs/datatables/dataTables.min.js', __FILE__) );
            wp_enqueue_script('wps-adm-datatables-js-1', plugins_url('libs/datatables/dataTables.buttons.min.js', __FILE__) );
            wp_enqueue_style('wps-adm-datatables-css-1', plugins_url('libs/datatables/buttons.dataTables.min.css', __FILE__) );
            wp_enqueue_script('wps-adm-sdatatables-js-2', plugins_url('libs/datatables/buttons.flash.min.js', __FILE__) );
            wp_enqueue_script('wps-adm-sdatatables-js-3', plugins_url('libs/datatables/jszip.min.js', __FILE__) );
            // wp_enqueue_script('wps-adm-sdatatables-js-4', plugins_url('libs/datatables/pdfmake.min.js', __FILE__)  );
            wp_enqueue_script('wps-adm-sdatatables-js-5', plugins_url('libs/datatables/vfs_fonts.js', __FILE__)   );
            wp_enqueue_script('wps-adm-sdatatables-js-6', plugins_url('libs/datatables/buttons.html5.min.js', __FILE__)   );
            wp_enqueue_script('wps-adm-sdatatables-js-7', plugins_url('libs/datatables/buttons.print.min.js', __FILE__)   );

            wp_enqueue_style('thickbox');
            wp_enqueue_script('thickbox');
        }
    }

    public function enqueue_frontend_scripts() {       
        wp_enqueue_style('wps-survey-css', plugins_url('libs/survey.css', __FILE__) );
        wp_enqueue_style('wps-survey-override-css', plugins_url('/survey.css', __FILE__) );
        wp_enqueue_style('wps-survey-modern-css', plugins_url('libs/modern.css', __FILE__) );
        wp_enqueue_style('wps-survey-modern-override-css', plugins_url('libs/modern.css', __FILE__) );
        wp_enqueue_script('wps-survey-jquery-js', plugins_url('libs/survey.jquery.min.js', __FILE__), array('jquery'));
    }
  
    function wps_add_menu() {
        add_menu_page( 'My surveys', 'SurveyJS', 'manage_options', 'sjs-main-menu', array(
                        "SurveyJS_MySurveys", 'render'
                        ), plugins_url('images/logo_20х20.png', __FILE__));
        // add_submenu_page( 'sjs-main-menu', __( 'My Surveys', 'sjs-main-menu' ), __( 'My Surveys', 'sjs-main-menu' ), 'manage_options', 'sjs-my-surveys', array(
        //                 __CLASS__, 'wps_mysurveys_page'
        //                 ));
        add_submenu_page( 'sjs-main-menu', __( 'Settings', 'sjs-main-menu' ), __( 'Settings', 'sjs-main-menu' ), 'manage_options', 'sjs-settings', array( 'SurveyJS_SettingsPage', 'sjs_render_settings' ) );
        add_submenu_page('', '', '', 'manage_options', 'surveyjs_editor', array('SurveyJS_Editor', 'render'));
        add_submenu_page('', '', '', 'manage_options', 'surveyjs_results', array('SurveyJS_Results', 'render'));
    }
  
    // function wps_mysurveys_page() {
    // }

    function wps_media_button() {
        $url = add_query_arg(array('action' => 'SurveyJS_InsertSurvey'), admin_url('admin-ajax.php'));
        ?>
        <a onclick="tb_click.call(this); return false;" href="<?php echo esc_url($url); ?>" class="button" title="<?php _e('Insert Survey', SurveyJS_SurveyJS::$prefix); ?>">
            <?php _e('Add Survey', SurveyJS_SurveyJS::$prefix); ?>
        </a>
        <?php
    }

    function wps_process_shortcode($attrs) {
        ob_start();
        
        $id = sanitize_text_field($attrs["id"]);
        $getSurveyJsonUri = add_query_arg(array('action' => 'SurveyJS_GetSurveyJson'), admin_url('admin-ajax.php'));
        $saveResultUri = add_query_arg(array('action' => 'SurveyJS_SaveResult'), admin_url('admin-ajax.php'));
        ?>
        <div class="wp-sjs-plugin" id="surveyContainer-<?php echo $id ?>">
            <div id="surveyElement-<?php echo $id ?>">Survey is loading...</div>
            <div id="surveyResult-<?php echo $id ?>"></div>
        </div>
        <script>
            jQuery.ajax({
                url:  "<?php echo esc_url($getSurveyJsonUri)  ?>",
                type: "POST",
                data: { Id: <?php echo $id ?> },
                success: function (data) {
                    var json = {}
                    if (data.json) {
                        json = JSON.parse(data.json.replace(/\\\"/g, "\"").replace(/\\\\/g, "\\").replace(/\\'/g, "'"));
                    }
                    initSurvey<?php echo $id ?>(json);
                }
            });

            function initSurvey<?php echo $id ?>(json) {
                Survey.StylesManager.applyTheme('<?php echo sanitize_text_field(SurveyJS_SettingsPage::get_theme()) ?>');

                var customCss = {
                    <?php 
                        if (SurveyJS_SettingsPage::get_allow_paddings() == 0) {
                            echo '"root": "sv_main sv_default_css"';
                        }
                    ?>
                }

                window.survey<?php echo $id ?> = new Survey.Model(json);

                survey<?php echo $id ?>
                    .onComplete
                    .add(function (result) {
                        jQuery.ajax({
                            url:  "<?php echo esc_url($saveResultUri) ?>",
                            type: "POST",
                            data: { SurveyId: '<?php echo $id ?>', Json : JSON.stringify(result.data) },
                            success: function (data) {}
                        });
                        //document
                        //    .querySelector("#surveyResult-<?php echo $id ?>")
                        //    .innerHTML = "result: " + JSON.stringify(result.data);
                    });

                jQuery("#surveyElement-<?php echo $id ?>").Survey({model: survey<?php echo $id ?>, css: customCss});
            }
        </script>        
        <?php
                        
        return ob_get_clean();
    }
  
  }
  
?>
