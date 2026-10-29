<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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


        add_action('init', array($this, 'register_frontend_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        add_action('init', array($this, 'register_surveyjs_gutenberg_block'));
    }

    public function register_surveyjs_gutenberg_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $blockname = 'sjs/gutenberg-block';
        $blockscriptname = 'surveyjs-block-js';

        $client = new SurveyJS_Client();
        $surveys = $client->getSurveys();

        if ( is_admin() ) {
            wp_enqueue_script(
                $blockscriptname,
                plugins_url( 'block/block.js', __FILE__),
                array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor' ),
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
        $page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
        if ( 'surveyjs_editor' !== $page && 'surveyjs_results' !== $page ) {
            return;
        }

        wp_enqueue_script('wps-adm--survey.core.min.js', plugins_url('libs/library/survey.core.min.js', __FILE__));
        wp_enqueue_script('wps-adm--survey.i18n.min.js', plugins_url('libs/library/survey.i18n.min.js', __FILE__), array('wps-adm--survey.core.min.js'));
        wp_enqueue_script('wps-adm--survey-js-ui.min.js', plugins_url('libs/library/survey-js-ui.min.js', __FILE__), array('wps-adm--survey.core.min.js'));
        wp_enqueue_style('wps-adm--survey-core.min.css', plugins_url('libs/library/survey-core.min.css', __FILE__));

        if ( 'surveyjs_editor' === $page ) {
            wp_enqueue_script('wps-adm--survey.linter.min.js', plugins_url('libs/library/survey.linter.min.js', __FILE__), array('wps-adm--survey.core.min.js'));
            wp_enqueue_script('wps-adm--editor.js', plugins_url('editor/editor.js', __FILE__), array('wps-adm--survey-js-ui.min.js', 'wps-adm--survey.linter.min.js'), false, true);
            wp_enqueue_style('wps-adm--editor.css', plugins_url('editor/editor.css', __FILE__), array('dashicons'));
        }
    }

    public function register_frontend_assets() {
        wp_register_style('wps-survey-override-css', plugins_url('/survey.css', __FILE__), array('wps-front--survey-core.min.css'));
        wp_register_style('wps-front--survey-core.min.css', plugins_url('libs/library/survey-core.min.css', __FILE__));
        wp_register_script('wps-front--survey.core.min.js', plugins_url('libs/library/survey.core.min.js', __FILE__), array());
        wp_register_script('wps-front--survey-js-ui.min.js', plugins_url('libs/library/survey-js-ui.min.js', __FILE__), array('wps-front--survey.core.min.js'));
        wp_register_script('wps-front--survey.js', plugins_url('/survey.js', __FILE__), array('wps-front--survey-js-ui.min.js'));
    }

    public function enqueue_frontend_scripts() {
        $client = new SurveyJS_Client();
        foreach ( $this->get_current_post_survey_ids() as $id ) {
            $row = $client->getSurvey($id);
            if ( $row ) {
                $this->enqueue_frontend_assets(SurveyJS_Client::decode_survey_json($row->json, false));
            }
        }
    }

    private function get_current_post_survey_ids() {
        $post = is_singular() ? get_post() : null;
        if ( ! $post ) {
            return array();
        }
        $ids = array();
        if ( preg_match_all('/' . get_shortcode_regex(array('Survey')) . '/', $post->post_content, $matches) ) {
            foreach ( $matches[3] as $shortcodeAttrs ) {
                $attrs = shortcode_parse_atts($shortcodeAttrs);
                $ids[] = absint($attrs['id'] ?? 0);
            }
        }
        $this->collect_block_survey_ids(parse_blocks($post->post_content), $ids);
        return array_unique(array_filter($ids));
    }

    private function collect_block_survey_ids($blocks, &$ids) {
        foreach ( $blocks as $block ) {
            if ( 'sjs/gutenberg-block' === $block['blockName'] ) {
                $ids[] = absint($block['attrs']['surveyid'] ?? 0);
            }
            $this->collect_block_survey_ids($block['innerBlocks'], $ids);
        }
    }

    private function enqueue_frontend_assets($json) {
        wp_enqueue_style('wps-survey-override-css');
        wp_enqueue_style('wps-front--survey-core.min.css');
        $locales = array();
        if ( isset($json->locale) && is_string($json->locale) ) {
            $locales[] = strtolower($json->locale);
        }
        $this->collect_survey_locales($json, $locales);
        $surveyScript = wp_scripts()->query('wps-front--survey.js');
        foreach ( array_unique($locales) as $locale ) {
            $file = 'libs/library/i18n/' . $locale . '.min.js';
            if ( ! preg_match('/^[a-z-]+$/', $locale) || ! file_exists(plugin_dir_path(__FILE__) . $file) ) {
                continue;
            }
            $handle = 'wps-front--survey-i18n-' . $locale;
            wp_register_script($handle, plugins_url($file, __FILE__), array('wps-front--survey.core.min.js'));
            if ( $surveyScript && ! in_array($handle, $surveyScript->deps, true) ) {
                $surveyScript->deps[] = $handle;
            }
        }
        wp_enqueue_script('wps-front--survey.js');
    }

    private function collect_survey_locales($node, &$locales) {
        if ( is_object($node) ) {
            $node = get_object_vars($node);
            if ( isset($node['default']) && is_string($node['default']) ) {
                foreach ( $node as $key => $value ) {
                    if ( 'default' !== $key && is_string($value) ) {
                        $locales[] = strtolower($key);
                    }
                }
                return;
            }
        }
        if ( is_array($node) ) {
            foreach ( $node as $child ) {
                $this->collect_survey_locales($child, $locales);
            }
        }
    }
  
    function wps_add_menu() {
        $hook = add_menu_page( 'My surveys', 'SurveyJS', 'manage_options', 'surveyjs-main-menu', array(
                        "SurveyJS_MySurveys", 'render'
                        ), plugins_url('images/logo_20х20.png', __FILE__));
        add_action( 'load-' . $hook, array( 'SurveyJS_MySurveys', 'handle_actions' ) );
        // add_submenu_page( 'surveyjs-main-menu', __( 'My Surveys', 'surveyjs-main-menu' ), __( 'My Surveys', 'surveyjs-main-menu' ), 'manage_options', 'sjs-my-surveys', array(
        //                 __CLASS__, 'wps_mysurveys_page'
        //                 ));
        add_submenu_page( 'surveyjs-main-menu', esc_html__( 'Settings', 'surveyjs' ), esc_html__( 'Settings', 'surveyjs' ), 'manage_options', 'surveyjs-settings', array( 'SurveyJS_SettingsPage', 'surveyjs_render_settings' ) );
        add_submenu_page('', '', '', 'manage_options', 'surveyjs_editor', array('SurveyJS_Editor', 'render'));
        $results_hook = add_submenu_page('', '', '', 'manage_options', 'surveyjs_results', array('SurveyJS_Results', 'render'));
        add_action( 'load-' . $results_hook, array( 'SurveyJS_Results', 'handle_actions' ) );
    }
  
    // function wps_mysurveys_page() {
    // }

    function wps_media_button() {
        $url = add_query_arg(array('action' => 'SurveyJS_InsertSurvey'), admin_url('admin-ajax.php'));
        $url = wp_nonce_url($url, 'surveyjs-insert-survey', '_wpnonce');
        ?>
        <a onclick="tb_click.call(this); return false;" href="<?php echo esc_url($url); ?>" class="button" title="<?php esc_attr_e('Insert Survey', 'surveyjs'); ?>">
            <?php esc_html_e('Add Survey', 'surveyjs'); ?>
        </a>
        <?php
    }

    function wps_process_shortcode($attrs) {
        $id = absint($attrs["id"] ?? 0);
        $client = new SurveyJS_Client();
        $row = $client->getSurvey($id);
        if ( ! $row ) {
            return '';
        }

        $json = SurveyJS_Client::decode_survey_json($row->json, false);
        $theme = SurveyJS_Client::decode_survey_json($row->theme, false);

        $this->enqueue_frontend_assets($json);
        $deleteFileUri = add_query_arg(array('action' => 'SurveyJS_DeleteFile'), admin_url('admin-ajax.php'));
        $config = array(
            'id' => $id,
            'json' => empty($json) ? new stdClass() : $json,
            'theme' => empty($theme) ? null : $theme,
            'saveResultUrl' => add_query_arg(array('action' => 'SurveyJS_SaveResult'), admin_url('admin-ajax.php')),
            'saveResultNonce' => wp_create_nonce('surveyjs-save-result'),
            'uploadFilesUrl' => add_query_arg(array('action' => 'SurveyJS_UploadFiles'), admin_url('admin-ajax.php')),
            'uploadFilesNonce' => wp_create_nonce('surveyjs-upload-files'),
            'deleteFileUrl' => add_query_arg('_wpnonce', wp_create_nonce('surveyjs-delete-file'), $deleteFileUri),
        );

        ob_start();
        ?>
        <div class="wp-sjs-plugin" id="surveyContainer-<?php echo esc_attr($id) ?>">
            <div class="wp-sjs-survey" id="surveyElement-<?php echo esc_attr($id) ?>"></div>
            <div class="wp-sjs-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>
            <script type="application/json" class="wp-sjs-survey-config"><?php echo wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
            <script>window.wpSurveyJSInit && window.wpSurveyJSInit(document.currentScript.previousElementSibling);</script>
        </div>
        <?php
        return ob_get_clean();
    }
  
  }
  
?>
