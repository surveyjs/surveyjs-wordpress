<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include( __DIR__ . "/views/settings.php" );

if ( is_admin() ) {
    include( __DIR__ . "/views/mysurveys.php" );
    include( __DIR__ . "/views/editor.php" );
    include( __DIR__ . "/views/results.php" );
    include( __DIR__ . "/views/import.php" );
    include( __DIR__ . "/views/welcome.php" );
    include( __DIR__ . "/views/templates.php" );
    add_action( 'init', array( 'SurveyJS_Templates', 'register_post_type' ) );
}

class SurveyJS_SurveyJS {
    const BLOCK_SCRIPT = 'surveyjs-block-js';
    const MENU_SLUG = 'surveyjs-main-menu';
    const CAPABILITY = 'manage_options';
    const HIDDEN_PAGES = array( 'surveyjs_editor', 'surveyjs_results', 'surveyjs_import' );
    const FULL_PREVIEW_PAGE_OPTION = 'surveyjs_full_preview_page_id';
    const CORE_SCRIPT = 'wps-front--survey.core.min.js';
    const UI_SCRIPT = 'wps-front--survey-js-ui.min.js';
    const CORE_STYLE = 'wps-front--survey-core.min.css';
    const MARKDOWN_SCRIPT = 'wps-front--markdown-it.min.js';
    const MARKDOWN_HANDLER_SCRIPT = 'wps-front--markdown.js';
    const EDITOR_SCRIPT = 'wps-adm--editor.js';
    // File answers are uploaded and stored as links: base64 in entries bloats the database and breaks size limits.
    // The value in a form's JSON is ignored, not only defaulted, so an old "storeDataAsText": true cannot bring base64 back.
    const FILE_STORAGE_SCRIPT = <<<'JS'
(function () {
    var property = Survey.Serializer.findProperty("file", "storeDataAsText");
    if (!property) return;
    property.defaultValue = false;
    property.visible = false;
    property.onSetValue = function () {};
})();
JS;

    private static $library_version = null;

    public static $rendered_forms = array();

    public static $prefix = "sjs";
    public $plugin_version = "0.1.0";

    function __construct() {
        add_action('admin_menu', array( $this, 'wps_add_menu'));
        add_filter('parent_file', array( $this, 'highlight_menu'));
        add_filter('submenu_file', array( $this, 'highlight_submenu'));
        add_action('media_buttons', array($this, 'wps_media_button'));
        add_shortcode('surveyjs', array($this, 'wps_process_shortcode'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));


        add_action('init', array($this, 'register_frontend_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        // Before _wp_admin_bar_init (priority 0) so the admin bar stays off in the preview.
        add_action('template_redirect', array($this, 'render_preview_page'), -1);
        add_action('template_redirect', array($this, 'prepare_full_preview_page'), -1);
        add_action('pre_get_posts', array($this, 'hide_full_preview_page'));

        add_action('init', array($this, 'register_surveyjs_gutenberg_block'));
        add_action('enqueue_block_editor_assets', array($this, 'localize_block_editor'));

        add_action('admin_init', array('SurveyJS_Welcome', 'maybe_redirect'));
        add_action('wp_ajax_surveyjs_checklist', array('SurveyJS_Welcome', 'handle_checklist_action'));
        add_action('wp_ajax_surveyjs_import', array('SurveyJS_Import', 'handle_ajax'));
        add_action('wp_ajax_surveyjs_entry_read', array('SurveyJS_Results', 'handle_mark_read'));
        add_action('admin_enqueue_scripts', array('SurveyJS_Welcome', 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array('SurveyJS_Templates', 'enqueue_assets'));
        add_action('wp_ajax_surveyjs_template', array('SurveyJS_Templates', 'handle_ajax'));
    }

    public function register_surveyjs_gutenberg_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $blockname = 'sjs/gutenberg-block';
        $blockscriptname = self::BLOCK_SCRIPT;

        wp_register_script(
            $blockscriptname,
            plugins_url( 'block/block.js', __FILE__),
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor' ),
            self::asset_version('block/block.js'),
            true
        );
        wp_set_script_translations( $blockscriptname, 'surveyjs' );

        register_block_type(
            $blockname,
            array(
                'api_version'     => 3,
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

    public function localize_block_editor() {
        wp_localize_script( self::BLOCK_SCRIPT, 'surveys', SurveyJS_Forms::names() );
        wp_localize_script( self::BLOCK_SCRIPT, 'surveyjsBlock', array(
            'editUrl'   => add_query_arg( 'page', 'surveyjs_editor', admin_url( 'admin.php' ) ),
            'legacyIds' => (object) SurveyJS_Forms::legacy_map(),
        ) );
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

        wp_enqueue_script(self::CORE_SCRIPT);
        wp_enqueue_script('wps-adm--survey.i18n.min.js', plugins_url('libs/library/survey.i18n.min.js', __FILE__), array(self::CORE_SCRIPT));
        wp_enqueue_script(self::UI_SCRIPT);
        wp_enqueue_style(self::CORE_STYLE);

        if ( 'surveyjs_results' === $page ) {
            wp_enqueue_script(self::MARKDOWN_SCRIPT);
            wp_enqueue_script(self::MARKDOWN_HANDLER_SCRIPT);
        }

        if ( 'surveyjs_editor' === $page ) {
            wp_enqueue_media();
            wp_enqueue_script('wps-adm--survey.linter.min.js', plugins_url('libs/library/survey.linter.min.js', __FILE__), array(self::CORE_SCRIPT));
            wp_enqueue_script(self::EDITOR_SCRIPT, plugins_url('editor/editor.js', __FILE__), array(self::UI_SCRIPT, 'wps-adm--survey.linter.min.js', 'wp-i18n'), self::asset_version('editor/editor.js'), true);
            wp_set_script_translations(self::EDITOR_SCRIPT, 'surveyjs');
            wp_enqueue_style('wps-adm--editor.css', plugins_url('editor/editor.css', __FILE__), array('dashicons'), self::asset_version('editor/editor.css'));
        }

        do_action( 'surveyjs_admin_enqueue_scripts', $page );
    }

    public static function library_version() {
        if ( null === self::$library_version ) {
            $header = (string) file_get_contents( __DIR__ . '/libs/library/survey.core.min.js', false, null, 0, 200 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            self::$library_version = preg_match( '/ v(\d+\.\d+\.\d+\S*)/', $header, $match ) ? $match[1] : '';
        }
        return self::$library_version;
    }

    public static function survey_locale( $wp_locale = null ) {
        $wp_locale = null === $wp_locale ? determine_locale() : $wp_locale;
        $full = strtolower( str_replace( '_', '-', (string) $wp_locale ) );
        $aliases = array( 'nb' => 'no', 'nn' => 'no', 'te' => 'tel', 'my' => 'mm', 'tl' => 'fil', 'zh-hk' => 'zh-tw', 'zh-sg' => 'zh-cn' );
        $locale = '';
        foreach ( array( $full, strtok( $full, '-' ) ) as $code ) {
            $code = $aliases[ $code ] ?? $code;
            if ( preg_match( '/^[a-z-]+$/', $code ) && file_exists( __DIR__ . '/libs/library/i18n/' . $code . '.min.js' ) ) {
                $locale = $code;
                break;
            }
        }
        return (string) apply_filters( 'surveyjs_form_locale', $locale, $wp_locale );
    }

    public function render_preview_page() {
        if ( ! isset( $_GET['surveyjs_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to preview forms.', 'surveyjs' ), 403 );
        }
        nocache_headers();
        show_admin_bar( false ); // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected -- hidden only on the admin-only preview page shown in the editor iframe; not removed site-wide.
        add_filter( 'wp_robots', 'wp_robots_no_robots' );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
        include plugin_dir_path( __FILE__ ) . 'views/preview.php';
        exit;
    }

    // The full preview needs a real page so the theme renders it with its own page template, header and footer.
    // Forms are not posts, so a private page, seen only by admins, stands in for the page the form will be placed on.
    public static function full_preview_page_id() {
        $page_id = (int) get_option( self::FULL_PREVIEW_PAGE_OPTION );
        $page = $page_id ? get_post( $page_id ) : null;
        if ( $page && 'page' === $page->post_type && 'private' === $page->post_status ) {
            return $page_id;
        }
        $page_id = wp_insert_post( array(
            'post_type'      => 'page',
            'post_status'    => 'private',
            'post_title'     => __( 'Form Preview', 'surveyjs' ),
            'post_content'   => '',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ) );
        if ( ! $page_id || is_wp_error( $page_id ) ) {
            return 0;
        }
        update_option( self::FULL_PREVIEW_PAGE_OPTION, $page_id, false );
        return $page_id;
    }

    public static function full_preview_url() {
        $page_id = self::full_preview_page_id();
        return $page_id ? get_permalink( $page_id ) : '';
    }

    private static function is_full_preview_page() {
        $page_id = (int) get_option( self::FULL_PREVIEW_PAGE_OPTION );
        return $page_id && is_page( $page_id );
    }

    public function prepare_full_preview_page() {
        if ( ! self::is_full_preview_page() ) {
            return;
        }
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to preview forms.', 'surveyjs' ), 403 );
        }
        nocache_headers();
        show_admin_bar( false ); // phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected -- hidden only on the admin-only preview page shown in the editor iframe; not removed site-wide.
        add_filter( 'wp_robots', 'wp_robots_no_robots' );
        add_filter( 'private_title_format', array( $this, 'full_preview_title_format' ) );
        add_filter( 'the_content', array( $this, 'full_preview_content' ), PHP_INT_MAX );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
    }

    public function full_preview_title_format() {
        return '%s';
    }

    public function full_preview_content( $content ) {
        if ( ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        return '<div class="wp-sjs-plugin"><div class="wp-sjs-survey" id="wp-sjs-preview-survey"></div></div>';
    }

    public function hide_full_preview_page( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() || 'page' !== $query->get( 'post_type' ) ) {
            return;
        }
        $page_id = (int) get_option( self::FULL_PREVIEW_PAGE_OPTION );
        if ( $page_id ) {
            $query->set( 'post__not_in', array_merge( (array) $query->get( 'post__not_in' ), array( $page_id ) ) );
        }
    }

    public function enqueue_preview_assets() {
        wp_enqueue_style('wps-survey-override-css');
        wp_enqueue_style('wps-front--wordpress.css');
        wp_add_inline_style('wps-front--wordpress.css', '.wp-sjs-preview-page__main{padding-block:24px}.wp-sjs-preview__run-again{display:block;margin:16px auto}');
        wp_register_script('wps-front--survey.i18n.min.js', plugins_url('libs/library/survey.i18n.min.js', __FILE__), array('wps-front--survey.core.min.js'));
        wp_enqueue_script('wps-front--preview.js', plugins_url('editor/preview.js', __FILE__), array('wps-front--survey-js-ui.min.js', 'wps-front--survey.i18n.min.js', 'wps-front--wordpress.js', self::MARKDOWN_SCRIPT, self::MARKDOWN_HANDLER_SCRIPT), self::asset_version('editor/preview.js'), true);
        wp_localize_script('wps-front--preview.js', 'surveyjsPreview', array(
            'editorUrl' => admin_url(),
            'runAgain'  => __( 'Run again', 'surveyjs' ),
            'locale'    => self::survey_locale(),
        ));
    }

    public function register_frontend_assets() {
        wp_register_style('wps-survey-override-css', plugins_url('/survey.css', __FILE__), array(self::CORE_STYLE), self::asset_version('survey.css'));
        wp_register_style(self::CORE_STYLE, plugins_url('libs/library/survey-core.min.css', __FILE__));
        wp_register_script(self::CORE_SCRIPT, plugins_url('libs/library/survey.core.min.js', __FILE__), array());
        wp_add_inline_script(self::CORE_SCRIPT, self::FILE_STORAGE_SCRIPT);
        wp_register_script(self::UI_SCRIPT, plugins_url('libs/library/survey-js-ui.min.js', __FILE__), array(self::CORE_SCRIPT));
        wp_register_script(self::MARKDOWN_SCRIPT, plugins_url('libs/markdown-it/markdown-it.min.js', __FILE__), array());
        wp_register_script(self::MARKDOWN_HANDLER_SCRIPT, plugins_url('markdown.js', __FILE__), array(), self::asset_version('markdown.js'));
        wp_register_script('wps-front--survey.js', plugins_url('/survey.js', __FILE__), array(self::UI_SCRIPT, self::MARKDOWN_HANDLER_SCRIPT), self::asset_version('survey.js'));
        wp_register_style('wps-front--wordpress.css', plugins_url('themes/wordpress.css', __FILE__), array(self::CORE_STYLE), self::asset_version('themes/wordpress.css'));
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
        return self::content_survey_ids($post->post_content);
    }

    public static function content_survey_ids($content) {
        $ids = self::shortcode_survey_ids($content);
        self::collect_block_survey_ids(parse_blocks($content), $ids);
        return array_unique(array_filter($ids));
    }

    public static function shortcode_form_id($attrs, $tag) {
        $id = is_array($attrs) ? ($attrs['id'] ?? 0) : 0;
        return 'Survey' === $tag ? SurveyJS_Forms::resolve_legacy_id($id) : SurveyJS_Forms::resolve_id($id);
    }

    private static function shortcode_survey_ids($content) {
        $ids = array();
        if ( preg_match_all('/' . get_shortcode_regex(array('surveyjs', 'Survey')) . '/', $content, $matches, PREG_SET_ORDER) ) {
            foreach ( $matches as $match ) {
                $ids[] = self::shortcode_form_id(shortcode_parse_atts($match[3]), $match[2]);
            }
        }
        return $ids;
    }

    private static function collect_block_survey_ids($blocks, &$ids) {
        foreach ( $blocks as $block ) {
            if ( 'sjs/gutenberg-block' === $block['blockName'] ) {
                $block_ids = self::shortcode_survey_ids((string) ($block['attrs']['shortcode'] ?? ''));
                $ids = array_merge($ids, $block_ids ? $block_ids : array(SurveyJS_Forms::resolve_id($block['attrs']['surveyid'] ?? 0)));
            }
            self::collect_block_survey_ids($block['innerBlocks'], $ids);
        }
    }

    private function enqueue_frontend_assets($row) {
        $json = SurveyJS_Client::decode_survey_json($row->json, false);
        $settings = SurveyJS_Form_Settings::get( $row );
        wp_enqueue_style('wps-survey-override-css');
        wp_enqueue_style('wps-front--survey-core.min.css');
        $surveyScript = wp_scripts()->query('wps-front--survey.js');
        if ( 'adaptive' === SurveyJS_Themes::resolve($row)['mode'] ) {
            wp_enqueue_style('wps-front--wordpress.css');
            if ( $surveyScript && ! in_array('wps-front--wordpress.js', $surveyScript->deps, true) ) {
                $surveyScript->deps[] = 'wps-front--wordpress.js';
            }
        }
        $locales = array( self::survey_locale() );
        if ( isset($json->locale) && is_string($json->locale) ) {
            $locales[] = strtolower($json->locale);
        }
        if ( $settings['markdown'] && $surveyScript && ! in_array(self::MARKDOWN_SCRIPT, $surveyScript->deps, true) ) {
            $surveyScript->deps[] = self::MARKDOWN_SCRIPT;
        }
        if ( SurveyJS_Captcha::is_enabled( $settings ) ) {
            SurveyJS_Captcha::enqueue_script();
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

        do_action( 'surveyjs_enqueue_form_assets', $row, $settings );
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
        $hook = add_menu_page( 'Forms', 'SurveyJS Forms', self::CAPABILITY, self::MENU_SLUG, array(
                        "SurveyJS_MySurveys", 'render'
                        ), plugins_url('images/logo_20х20.png', __FILE__), '30.5' );
        add_action( 'load-' . $hook, array( 'SurveyJS_MySurveys', 'handle_actions' ) );
        // Registering the parent slug first stops WordPress from adding a duplicate "SurveyJS Forms" submenu item.
        add_submenu_page( 'surveyjs-main-menu', esc_html__( 'All Forms', 'surveyjs' ), esc_html__( 'All Forms', 'surveyjs' ), 'manage_options', 'surveyjs-main-menu' );
        $templates_hook = add_submenu_page( 'surveyjs-main-menu', esc_html__( 'Add New Form', 'surveyjs' ), esc_html__( 'Add New Form', 'surveyjs' ), 'manage_options', SurveyJS_Templates::PAGE, array( 'SurveyJS_Templates', 'render' ) );
        add_action( 'load-' . $templates_hook, array( 'SurveyJS_Templates', 'handle_actions' ) );
        add_submenu_page( 'surveyjs-main-menu', esc_html__( 'Getting Started', 'surveyjs' ), esc_html__( 'Getting Started', 'surveyjs' ), 'manage_options', SurveyJS_Welcome::PAGE, array( 'SurveyJS_Welcome', 'render' ) );
        add_submenu_page( 'surveyjs-main-menu', esc_html__( 'Settings', 'surveyjs' ), esc_html__( 'Settings', 'surveyjs' ), 'manage_options', 'surveyjs-settings', array( 'SurveyJS_SettingsPage', 'surveyjs_render_settings' ) );
        add_submenu_page('', '', '', 'manage_options', 'surveyjs_editor', array('SurveyJS_Editor', 'render'));
        $results_hook = add_submenu_page('', '', '', 'manage_options', 'surveyjs_results', array('SurveyJS_Results', 'render'));
        add_action( 'load-' . $results_hook, array( 'SurveyJS_Results', 'handle_actions' ) );
        $import_hook = add_submenu_page('', '', '', 'manage_options', 'surveyjs_import', array('SurveyJS_Import', 'render'));
        add_action( 'load-' . $import_hook, array( 'SurveyJS_Import', 'handle_actions' ) );
        // WordPress resolves the menu parent from these '' entries after the parent_file filter, which would undo highlight_menu().
        foreach ( self::HIDDEN_PAGES as $hidden_page ) {
            remove_submenu_page( '', $hidden_page );
        }

        do_action( 'surveyjs_admin_menu', self::MENU_SLUG, self::CAPABILITY );
    }

    private static function on_hidden_page() {
        global $plugin_page;
        return in_array( $plugin_page, self::HIDDEN_PAGES, true );
    }

    public function highlight_menu( $parent_file ) {
        return self::on_hidden_page() ? self::MENU_SLUG : $parent_file;
    }

    public function highlight_submenu( $submenu_file ) {
        return self::on_hidden_page() ? self::MENU_SLUG : $submenu_file;
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

    function wps_process_shortcode($attrs, $content = null, $tag = '') {
        $row = SurveyJS_Forms::find(self::shortcode_form_id($attrs, $tag));
        if ( ! $row ) {
            return '';
        }
        $id = $row->id;
        self::$rendered_forms[ $id ] = $row->name;

        $json = SurveyJS_Client::decode_survey_json($row->json, false);
        $style = SurveyJS_Themes::resolve($row);

        $this->enqueue_frontend_assets($row);
        $deleteFileUri = add_query_arg(array('action' => 'SurveyJS_DeleteFile'), admin_url('admin-ajax.php'));
        $config = array(
            'id' => $id,
            'json' => empty($json) ? new stdClass() : $json,
            'theme' => $style['theme'],
            'themeMode' => $style['mode'],
            'transparentBackground' => $style['transparentBackground'],
            'saveResultUrl' => add_query_arg(array('action' => 'SurveyJS_SaveResult'), admin_url('admin-ajax.php')),
            'saveResultNonce' => wp_create_nonce('surveyjs-save-result'),
            'uploadFilesUrl' => add_query_arg(array('action' => 'SurveyJS_UploadFiles'), admin_url('admin-ajax.php')),
            'uploadFilesNonce' => wp_create_nonce('surveyjs-upload-files'),
            'deleteFileUrl' => add_query_arg('_wpnonce', wp_create_nonce('surveyjs-delete-file'), $deleteFileUri),
            'draft' => SurveyJS_Drafts::client_config($row),
            'markdown' => SurveyJS_Form_Settings::get( $row )['markdown'],
            'locale' => self::survey_locale(),
        );
        if ( SurveyJS_Entries::partial_enabled( SurveyJS_Form_Settings::get( $row ) ) ) {
            $config['partial'] = array(
                'url'        => add_query_arg( array( 'action' => 'SurveyJS_SavePartialResult' ), admin_url( 'admin-ajax.php' ) ),
                'storageKey' => 'surveyjs-partial:' . home_url() . ':' . absint( $id ),
            );
        }
        if ( SurveyJS_Captcha::is_enabled( SurveyJS_Form_Settings::get( $row ) ) ) {
            $config['captcha'] = SurveyJS_Captcha::client_config();
        }
        $config = apply_filters( 'surveyjs_form_client_config', $config, $row, SurveyJS_Form_Settings::get( $row ) );

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
