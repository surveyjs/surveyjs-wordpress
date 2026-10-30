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
        add_shortcode('surveyjs', array($this, 'wps_process_shortcode'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));


        add_action('init', array($this, 'register_frontend_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        // Before _wp_admin_bar_init (priority 0) so the admin bar stays off in the preview.
        add_action('template_redirect', array($this, 'render_preview_page'), -1);

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
                self::asset_version('block/block.js'),
                true
            );

            wp_localize_script(
                $blockscriptname,
                'surveys',
                $surveys
            );
            wp_localize_script(
                $blockscriptname,
                'surveyjsBlock',
                array( 'editUrl' => add_query_arg( 'page', 'surveyjs_editor', admin_url( 'admin.php' ) ) )
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

    private static function asset_version( $file ) {
        return (string) filemtime( plugin_dir_path( __FILE__ ) . $file );
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
            wp_enqueue_script('wps-adm--editor.js', plugins_url('editor/editor.js', __FILE__), array('wps-adm--survey-js-ui.min.js', 'wps-adm--survey.linter.min.js'), self::asset_version('editor/editor.js'), true);
            wp_enqueue_style('wps-adm--editor.css', plugins_url('editor/editor.css', __FILE__), array('dashicons'), self::asset_version('editor/editor.css'));
        }
    }

    public function render_preview_page() {
        if ( ! isset( $_GET['surveyjs_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to preview forms.', 'surveyjs' ), 403 );
        }
        nocache_headers();
        show_admin_bar( false );
        add_filter( 'wp_robots', 'wp_robots_no_robots' );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
        include plugin_dir_path( __FILE__ ) . 'views/preview.php';
        exit;
    }

    public function enqueue_preview_assets() {
        wp_enqueue_style('wps-survey-override-css');
        wp_enqueue_style('wps-front--wordpress.css');
        wp_add_inline_style('wps-front--wordpress.css', '.wp-sjs-preview-page__main{padding-block:24px}.wp-sjs-preview__run-again{display:block;margin:16px auto}');
        wp_register_script('wps-front--survey.i18n.min.js', plugins_url('libs/library/survey.i18n.min.js', __FILE__), array('wps-front--survey.core.min.js'));
        wp_enqueue_script('wps-front--preview.js', plugins_url('editor/preview.js', __FILE__), array('wps-front--survey-js-ui.min.js', 'wps-front--survey.i18n.min.js', 'wps-front--wordpress.js'), self::asset_version('editor/preview.js'), true);
        wp_localize_script('wps-front--preview.js', 'surveyjsPreview', array(
            'editorUrl' => admin_url(),
            'runAgain'  => __( 'Run again', 'surveyjs' ),
        ));
    }

    public function register_frontend_assets() {
        wp_register_style('wps-survey-override-css', plugins_url('/survey.css', __FILE__), array('wps-front--survey-core.min.css'), self::asset_version('survey.css'));
        wp_register_style('wps-front--survey-core.min.css', plugins_url('libs/library/survey-core.min.css', __FILE__));
        wp_register_script('wps-front--survey.core.min.js', plugins_url('libs/library/survey.core.min.js', __FILE__), array());
        wp_register_script('wps-front--survey-js-ui.min.js', plugins_url('libs/library/survey-js-ui.min.js', __FILE__), array('wps-front--survey.core.min.js'));
        wp_register_script('wps-front--survey.js', plugins_url('/survey.js', __FILE__), array('wps-front--survey-js-ui.min.js'), self::asset_version('survey.js'));
        wp_register_style('wps-front--wordpress.css', plugins_url('themes/wordpress.css', __FILE__), array('wps-front--survey-core.min.css'), self::asset_version('themes/wordpress.css'));
        wp_register_script('wps-front--wordpress.js', plugins_url('themes/wordpress.js', __FILE__), array(), self::asset_version('themes/wordpress.js'));
    }

    public function enqueue_frontend_scripts() {
        $client = new SurveyJS_Client();
        foreach ( $this->get_current_post_survey_ids() as $id ) {
            $row = $client->getSurvey($id);
            if ( $row ) {
                $this->enqueue_frontend_assets($row);
            }
        }
    }

    private function get_current_post_survey_ids() {
        $post = is_singular() ? get_post() : null;
        if ( ! $post ) {
            return array();
        }
        $ids = array();
        if ( preg_match_all('/' . get_shortcode_regex(array('surveyjs', 'Survey')) . '/', $post->post_content, $matches) ) {
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

    private function enqueue_frontend_assets($row) {
        $json = SurveyJS_Client::decode_survey_json($row->json, false);
        wp_enqueue_style('wps-survey-override-css');
        wp_enqueue_style('wps-front--survey-core.min.css');
        $surveyScript = wp_scripts()->query('wps-front--survey.js');
        if ( 'adaptive' === SurveyJS_Themes::resolve($row)['mode'] ) {
            wp_enqueue_style('wps-front--wordpress.css');
            if ( $surveyScript && ! in_array('wps-front--wordpress.js', $surveyScript->deps, true) ) {
                $surveyScript->deps[] = 'wps-front--wordpress.js';
            }
        }
        $locales = array();
        if ( isset($json->locale) && is_string($json->locale) ) {
            $locales[] = strtolower($json->locale);
        }
        $this->collect_survey_locales($json, $locales);
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
        $hook = add_menu_page( 'Forms', 'SurveyJS Forms', 'manage_options', 'surveyjs-main-menu', array(
                        "SurveyJS_MySurveys", 'render'
                        ), plugins_url('images/logo_20х20.png', __FILE__));
        add_action( 'load-' . $hook, array( 'SurveyJS_MySurveys', 'handle_actions' ) );
        add_submenu_page( 'surveyjs-main-menu', esc_html__( 'Forms', 'surveyjs' ), esc_html__( 'Forms', 'surveyjs' ), 'manage_options', 'surveyjs-main-menu' );
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
        <a onclick="tb_click.call(this); return false;" href="<?php echo esc_url($url); ?>" class="button" title="<?php esc_attr_e('Insert Form', 'surveyjs'); ?>">
            <?php esc_html_e('Add Form', 'surveyjs'); ?>
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
        $style = SurveyJS_Themes::resolve($row);

        $this->enqueue_frontend_assets($row);
        $deleteFileUri = add_query_arg(array('action' => 'SurveyJS_DeleteFile'), admin_url('admin-ajax.php'));
        $config = array(
            'id' => $id,
            'json' => empty($json) ? new stdClass() : $json,
            'theme' => $style['theme'],
            'themeMode' => $style['mode'],
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
