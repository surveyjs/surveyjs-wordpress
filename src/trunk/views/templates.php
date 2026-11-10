<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Templates {

    const PAGE = 'surveyjs-templates';
    const BLANK = 'blank';
    const CONTACT = 'contact';
    const MINE = 'my-templates';
    const FEATURED = array( 'contact-form' );
    const POST_TYPE = 'sjs_template';
    const USER_PREFIX = 'user-';
    const FORM_META = '_sjs_form';
    const THEME_META = '_sjs_theme';
    const THEME_MODE_META = '_sjs_theme_mode';

    private static $templates = null;

    public static function url( $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
    }

    public static function save_url( $form_id ) {
        return self::url( array( 'form' => absint( $form_id ) ) );
    }

    private static function delete_url( $template ) {
        return wp_nonce_url( self::url( array( 'action' => 'delete-template', 'template' => $template['post_id'] ) ), 'surveyjs-delete-template_' . $template['post_id'] );
    }

    public static function register_post_type() {
        if ( post_type_exists( self::POST_TYPE ) ) {
            return;
        }
        $caps = array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'edit_private_posts', 'edit_published_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'create_posts' );
        register_post_type( self::POST_TYPE, array(
            'labels'       => array(
                'name'          => __( 'Form Templates', 'surveyjs' ),
                'singular_name' => __( 'Form Template', 'surveyjs' ),
            ),
            'public'       => false,
            'show_ui'      => false,
            'show_in_rest' => false,
            'rewrite'      => false,
            'query_var'    => false,
            'can_export'   => true,
            'supports'     => array( 'title', 'excerpt' ),
            'map_meta_cap' => false,
            'capabilities' => array_fill_keys( $caps, SurveyJS_SurveyJS::CAPABILITY ),
        ) );
    }

    // The only place that knows the layout of the templates repository, which scripts/update-templates.js copies as is.
    private static function load() {
        $templates = array();
        foreach ( (array) glob( dirname( __DIR__ ) . '/templates/*/*/form.json' ) as $file ) {
            $dir = dirname( $file );
            $id = sanitize_key( basename( $dir ) );
            $category = sanitize_key( basename( dirname( $dir ) ) );
            if ( isset( $templates[ $id ] ) ) {
                $templates[ $id ]['categories'][] = $category;
                continue;
            }
            $description = self::read_json( $dir . '/description.json' );
            $templates[ $id ] = array(
                'id'          => $id,
                'title'       => (string) ( $description['title'] ?? $id ),
                'description' => (string) ( $description['description'] ?? '' ),
                'tags'        => array_map( 'strval', (array) ( $description['tags'] ?? array() ) ),
                'categories'  => array( $category ),
                'files'       => array( 'form' => $file, 'theme' => $dir . '/theme.json' ),
            );
        }
        return $templates;
    }

    private static function user_templates() {
        $templates = array();
        $posts = get_posts( array(
            'post_type'        => self::POST_TYPE,
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'suppress_filters' => true,
        ) );
        foreach ( $posts as $post ) {
            $id = self::USER_PREFIX . $post->ID;
            $templates[ $id ] = array(
                'id'          => $id,
                'title'       => $post->post_title,
                'description' => $post->post_excerpt,
                'tags'        => array(),
                'categories'  => array( self::MINE ),
                'post_id'     => (int) $post->ID,
            );
        }
        return $templates;
    }

    public static function is_user( $template ) {
        return isset( $template['post_id'] );
    }

    private static function read_json( $file ) {
        $data = file_exists( $file ) ? wp_json_file_decode( $file, array( 'associative' => true ) ) : null;
        return is_array( $data ) ? $data : array();
    }

    private static function raw( $template, $part ) {
        if ( self::is_user( $template ) ) {
            return (string) get_post_meta( $template['post_id'], 'form' === $part ? self::FORM_META : self::THEME_META, true );
        }
        $file = $template['files'][ $part ];
        $data = file_exists( $file ) ? wp_json_file_decode( $file ) : null;
        return is_object( $data ) && array() !== get_object_vars( $data ) ? (string) wp_json_encode( $data ) : '';
    }

    private static function theme_mode( $template, $theme ) {
        if ( self::is_user( $template ) ) {
            $mode = get_post_meta( $template['post_id'], self::THEME_MODE_META, true );
            return in_array( $mode, SurveyJS_Form_Settings::theme_modes(), true ) ? $mode : 'adaptive';
        }
        return '' !== $theme ? 'surveyjs' : 'adaptive';
    }

    public static function all() {
        if ( null !== self::$templates ) {
            return self::$templates;
        }
        $templates = self::load() + self::user_templates();
        $order = array_flip( array_keys( self::categories( $templates ) ) );
        foreach ( $templates as &$template ) {
            usort( $template['categories'], function ( $a, $b ) use ( $order ) {
                return $order[ $a ] <=> $order[ $b ];
            } );
        }
        unset( $template );
        uasort( $templates, function ( $a, $b ) use ( $order ) {
            return array( $order[ $a['categories'][0] ], ! in_array( $a['id'], self::FEATURED, true ), strtolower( $a['title'] ) )
                <=> array( $order[ $b['categories'][0] ], ! in_array( $b['id'], self::FEATURED, true ), strtolower( $b['title'] ) );
        } );
        self::$templates = $templates;
        return $templates;
    }

    public static function find( $id ) {
        return self::all()[ $id ] ?? null;
    }

    private static function category_labels() {
        return array(
            'contact'               => __( 'Contact Forms', 'surveyjs' ),
            self::MINE              => __( 'My Templates', 'surveyjs' ),
            'booking'               => __( 'Booking', 'surveyjs' ),
            'calculators'           => __( 'Calculators', 'surveyjs' ),
            'consent-and-legal'     => __( 'Consent & Legal', 'surveyjs' ),
            'customer-feedback'     => __( 'Customer Feedback', 'surveyjs' ),
            'education'             => __( 'Education', 'surveyjs' ),
            'events'                => __( 'Events', 'surveyjs' ),
            'finance-and-insurance' => __( 'Finance & Insurance', 'surveyjs' ),
            'healthcare'            => __( 'Healthcare', 'surveyjs' ),
            'human-resources'       => __( 'Human Resources', 'surveyjs' ),
            'inspections'           => __( 'Inspections', 'surveyjs' ),
            'lead-generation'       => __( 'Lead Generation', 'surveyjs' ),
            'market-research'       => __( 'Market Research', 'surveyjs' ),
            'nonprofit'             => __( 'Nonprofit', 'surveyjs' ),
            'operations'            => __( 'Operations', 'surveyjs' ),
            'orders'                => __( 'Orders', 'surveyjs' ),
            'product-research'      => __( 'Product Research', 'surveyjs' ),
            'quizzes'               => __( 'Quizzes', 'surveyjs' ),
            'real-estate'           => __( 'Real Estate', 'surveyjs' ),
            'recruitment'           => __( 'Recruitment', 'surveyjs' ),
            'registration'          => __( 'Registration', 'surveyjs' ),
            'support'               => __( 'Support', 'surveyjs' ),
        );
    }

    public static function categories( $templates = null ) {
        $known = self::category_labels();
        $categories = array( self::MINE => $known[ self::MINE ] );
        foreach ( $templates ?? self::all() as $template ) {
            foreach ( $template['categories'] as $slug ) {
                $categories[ $slug ] = $known[ $slug ] ?? ucwords( str_replace( '-', ' ', $slug ) );
            }
        }
        $pinned = array_flip( array( self::MINE, self::CONTACT ) );
        uksort( $categories, function ( $a, $b ) use ( $categories, $pinned ) {
            return array( $pinned[ $a ] ?? 2, strtolower( $categories[ $a ] ) ) <=> array( $pinned[ $b ] ?? 2, strtolower( $categories[ $b ] ) );
        } );
        return $categories;
    }

    public static function settings( $form = array(), $theme_mode = 'adaptive' ) {
        $settings = array(
            'notificationEnabled' => true,
            'themeMode'           => $theme_mode,
        );
        foreach ( SurveyJS_Survey_Values::survey_questions( $form ) as $question ) {
            $type = $question['type'] ?? '';
            if ( empty( $settings['notificationReplyTo'] ) && 'text' === $type && 'email' === ( $question['inputType'] ?? '' ) ) {
                $settings['notificationReplyTo'] = $question['name'];
            }
            if ( empty( $settings['notificationAttachments'] ) && 'file' === $type ) {
                $settings['notificationAttachments'] = $question['name'];
            }
        }
        return SurveyJS_Form_Settings::sanitize( $settings );
    }

    public static function create( $id, $name = '' ) {
        if ( self::BLANK === $id ) {
            return SurveyJS_Forms::create( array(
                'name'     => '' !== $name ? $name : __( 'New Form', 'surveyjs' ),
                'settings' => wp_json_encode( self::settings() ),
            ) );
        }
        $template = self::find( $id );
        $json = $template ? self::raw( $template, 'form' ) : '';
        if ( ! $template || ( '' === $json && ! self::is_user( $template ) ) ) {
            return 0;
        }
        $theme = self::raw( $template, 'theme' );
        $form = SurveyJS_Client::decode_survey_json( $json );
        return SurveyJS_Forms::create( array(
            'name'     => '' !== $name ? $name : $template['title'],
            'json'     => $json,
            'theme'    => $theme,
            'settings' => wp_json_encode( self::settings( is_array( $form ) ? $form : array(), self::theme_mode( $template, $theme ) ) ),
        ) );
    }

    public static function save( $survey, $name, $description = '' ) {
        if ( '' === $name ) {
            $name = '' !== $survey->name ? $survey->name : __( 'Untitled template', 'surveyjs' );
        }
        $id = wp_insert_post( wp_slash( array(
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => $name,
            'post_excerpt' => $description,
        ) ), true );
        if ( is_wp_error( $id ) || ! $id ) {
            return 0;
        }
        update_post_meta( $id, self::FORM_META, wp_slash( (string) $survey->json ) );
        update_post_meta( $id, self::THEME_META, wp_slash( (string) $survey->theme ) );
        update_post_meta( $id, self::THEME_MODE_META, SurveyJS_Form_Settings::get( $survey )['themeMode'] );
        return (int) $id;
    }

    public static function delete( $post_id ) {
        $post = get_post( $post_id );
        return $post && self::POST_TYPE === $post->post_type && wp_delete_post( $post_id, true );
    }

    public static function handle_actions() {
        if ( ! current_user_can( SurveyJS_SurveyJS::CAPABILITY ) ) {
            return;
        }
        if ( isset( $_POST['save_template'] ) ) {
            self::handle_save();
        }
        if ( 'delete-template' === sanitize_key( wp_unslash( $_GET['action'] ?? '' ) ) ) {
            self::handle_delete();
        }
        if ( ! isset( $_POST['template'] ) ) {
            return;
        }
        check_admin_referer( 'surveyjs-use-template' );
        $id = self::create( sanitize_key( wp_unslash( $_POST['template'] ) ), sanitize_text_field( wp_unslash( $_POST['form_name'] ?? '' ) ) );
        if ( ! $id ) {
            wp_die( esc_html__( 'The form could not be created from this template.', 'surveyjs' ) );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => $id ), admin_url( 'admin.php' ) ) );
        exit;
    }

    private static function handle_save() {
        $form_id = absint( wp_unslash( $_POST['save_template'] ) );
        check_admin_referer( 'surveyjs-save-template_' . $form_id );
        $survey = SurveyJS_Forms::get( $form_id );
        $id = $survey ? self::save(
            $survey,
            sanitize_text_field( wp_unslash( $_POST['template_name'] ?? '' ) ),
            sanitize_textarea_field( wp_unslash( $_POST['template_description'] ?? '' ) )
        ) : 0;
        if ( ! $id ) {
            wp_die( esc_html__( 'The template could not be saved.', 'surveyjs' ) );
        }
        wp_safe_redirect( self::url( array( 'category' => self::MINE, 'message' => 'saved' ) ) );
        exit;
    }

    private static function handle_delete() {
        $post_id = absint( $_GET['template'] ?? 0 );
        check_admin_referer( 'surveyjs-delete-template_' . $post_id );
        if ( ! self::delete( $post_id ) ) {
            wp_die( esc_html__( 'The template could not be deleted.', 'surveyjs' ) );
        }
        wp_safe_redirect( self::url( array( 'category' => self::MINE, 'message' => 'deleted' ) ) );
        exit;
    }

    public static function handle_ajax() {
        check_ajax_referer( 'surveyjs-template' );
        if ( ! current_user_can( SurveyJS_SurveyJS::CAPABILITY ) ) {
            wp_send_json_error( null, 403 );
        }
        $template = self::find( sanitize_key( wp_unslash( $_GET['template'] ?? '' ) ) );
        $json = $template ? self::raw( $template, 'form' ) : '';
        if ( ! $template || ( '' === $json && ! self::is_user( $template ) ) ) {
            wp_send_json_error( null, 404 );
        }
        $theme = self::raw( $template, 'theme' );
        wp_send_json_success( array(
            'json'      => (object) SurveyJS_Client::decode_survey_json( $json, false ),
            'theme'     => (object) SurveyJS_Client::decode_survey_json( $theme, false ),
            'themeMode' => self::theme_mode( $template, $theme ),
        ) );
    }

    public static function enqueue_assets() {
        if ( self::PAGE !== sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        $dir = plugin_dir_path( __FILE__ );
        wp_enqueue_style( 'wps-adm--templates.css', plugins_url( 'templates.css', __FILE__ ), array( 'dashicons' ), (string) filemtime( $dir . 'templates.css' ) );
        if ( isset( $_GET['form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        wp_enqueue_script( 'wps-adm--templates.js', plugins_url( 'templates.js', __FILE__ ), array(), (string) filemtime( $dir . 'templates.js' ), true );
        wp_localize_script( 'wps-adm--templates.js', 'surveyjsTemplates', array(
            'previewUrl'   => add_query_arg( 'surveyjs_preview', '1', SurveyJS_SurveyJS::full_preview_url() ?: home_url( '/' ) ),
            'jsonUrl'      => wp_nonce_url( add_query_arg( 'action', 'surveyjs_template', admin_url( 'admin-ajax.php' ) ), 'surveyjs-template' ),
            'loadError'    => __( 'The template could not be loaded.', 'surveyjs' ),
            'mineCategory' => self::MINE,
        ) );
    }

    public static function render() {
        $form_id = absint( $_GET['form'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $form_id ) {
            self::render_save( $form_id );
            return;
        }
        $templates = self::all();
        $categories = self::categories();
        $counts = array_count_values( array_merge( array(), ...array_values( wp_list_pluck( $templates, 'categories' ) ) ) );
        $category = sanitize_key( wp_unslash( $_GET['category'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $message = sanitize_key( wp_unslash( $_GET['message'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $messages = array(
            'saved'   => __( 'Template saved. You can find it under My Templates.', 'surveyjs' ),
            'deleted' => __( 'Template deleted.', 'surveyjs' ),
        );
        $list_url = add_query_arg( 'page', SurveyJS_SurveyJS::MENU_SLUG, admin_url( 'admin.php' ) );
        ?>
        <div class="wrap wp-sjs-templates">
            <h1><?php esc_html_e( 'Add New Form', 'surveyjs' ); ?></h1>
            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $messages[ $message ] ); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url( self::url() ); ?>" id="wp-sjs-templates-form" data-category="<?php echo esc_attr( $category ); ?>">
                <?php wp_nonce_field( 'surveyjs-use-template' ); ?>
                <p class="wp-sjs-templates__name">
                    <label for="wp-sjs-templates-name"><?php esc_html_e( 'Form name', 'surveyjs' ); ?></label>
                    <input type="text" id="wp-sjs-templates-name" name="form_name" class="regular-text" placeholder="<?php esc_attr_e( 'The template name is used if you leave this empty', 'surveyjs' ); ?>" />
                </p>
                <p><?php esc_html_e( 'Start with a ready-made template, one of your own templates or a blank form. Every new form emails its entries to the site admin. You can change the questions, the theme and the email notification later in the editor.', 'surveyjs' ); ?></p>

                <div class="wp-sjs-templates__layout">
                    <div class="wp-sjs-templates__sidebar">
                        <label class="screen-reader-text" for="wp-sjs-templates-search"><?php esc_html_e( 'Search templates', 'surveyjs' ); ?></label>
                        <input type="search" id="wp-sjs-templates-search" class="wp-sjs-templates__search" placeholder="<?php esc_attr_e( 'Search templates', 'surveyjs' ); ?>" hidden />
                        <ul class="wp-sjs-templates__categories" hidden>
                            <li><button type="button" class="wp-sjs-templates__category is-active" data-category="" aria-pressed="true"><?php esc_html_e( 'All Templates', 'surveyjs' ); ?> <span class="count"><?php echo esc_html( number_format_i18n( count( $templates ) ) ); ?></span></button></li>
                            <?php foreach ( $categories as $slug => $label ) : ?>
                                <li><button type="button" class="wp-sjs-templates__category" data-category="<?php echo esc_attr( $slug ); ?>" aria-pressed="false"><?php echo esc_html( $label ); ?> <span class="count"><?php echo esc_html( number_format_i18n( $counts[ $slug ] ?? 0 ) ); ?></span></button></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="wp-sjs-templates__main">
                        <ul class="wp-sjs-templates__grid">
                            <li class="wp-sjs-template wp-sjs-template--blank">
                                <div class="wp-sjs-template__body">
                                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                                    <h2 class="wp-sjs-template__name"><?php esc_html_e( 'Blank Form', 'surveyjs' ); ?></h2>
                                    <p class="wp-sjs-template__description"><?php esc_html_e( 'Start from scratch and add questions in the JSON editor or the visual UI Builder.', 'surveyjs' ); ?></p>
                                </div>
                                <div class="wp-sjs-template__actions">
                                    <button type="submit" name="template" value="<?php echo esc_attr( self::BLANK ); ?>" class="button button-primary"><?php esc_html_e( 'Create blank form', 'surveyjs' ); ?></button>
                                </div>
                            </li>
                            <?php foreach ( $templates as $id => $template ) : ?>
                                <?php
                                $labels = array_map( function ( $slug ) use ( $categories ) {
                                    return $categories[ $slug ];
                                }, $template['categories'] );
                                $search = implode( ' ', array_merge( array( $template['title'], $template['description'] ), $template['tags'], $labels ) );
                                ?>
                                <li class="wp-sjs-template<?php echo self::is_user( $template ) ? ' wp-sjs-template--mine' : ''; ?>" data-template="<?php echo esc_attr( $id ); ?>" data-categories="<?php echo esc_attr( implode( ' ', $template['categories'] ) ); ?>" data-search="<?php echo esc_attr( strtolower( $search ) ); ?>">
                                    <div class="wp-sjs-template__body">
                                        <span class="wp-sjs-template__category"><?php echo esc_html( implode( ', ', $labels ) ); ?></span>
                                        <h2 class="wp-sjs-template__name"><?php echo esc_html( $template['title'] ); ?></h2>
                                        <?php if ( '' !== $template['description'] ) : ?>
                                            <p class="wp-sjs-template__description"><?php echo esc_html( $template['description'] ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wp-sjs-template__actions">
                                        <button type="submit" name="template" value="<?php echo esc_attr( $id ); ?>" class="button button-primary wp-sjs-template__use"><?php esc_html_e( 'Use template', 'surveyjs' ); ?></button>
                                        <button type="button" class="button wp-sjs-template__preview" hidden><?php esc_html_e( 'Preview', 'surveyjs' ); ?></button>
                                        <?php if ( self::is_user( $template ) ) : ?>
                                            <a href="<?php echo esc_url( self::delete_url( $template ) ); ?>" class="button-link button-link-delete wp-sjs-template__delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this template? Forms created from it are not changed.', 'surveyjs' ) ); ?>');"><?php esc_html_e( 'Delete', 'surveyjs' ); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="wp-sjs-templates__empty" hidden><?php esc_html_e( 'No templates match your search. Try other words, or start with a blank form.', 'surveyjs' ); ?></p>
                        <p class="wp-sjs-templates__empty-mine" hidden>
                            <?php esc_html_e( 'You have no templates yet. To make one, choose Save as Template under a form in the forms list or in the form editor.', 'surveyjs' ); ?>
                            <a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'Go to forms', 'surveyjs' ); ?></a>
                        </p>
                    </div>
                </div>

                <dialog class="wp-sjs-templates__dialog" id="wp-sjs-template-preview" aria-labelledby="wp-sjs-template-preview-title">
                    <div class="wp-sjs-templates__dialog-header">
                        <h2 id="wp-sjs-template-preview-title"></h2>
                        <button type="button" class="button-link wp-sjs-templates__dialog-close" data-close aria-label="<?php esc_attr_e( 'Close preview', 'surveyjs' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
                    </div>
                    <iframe class="wp-sjs-templates__dialog-frame" title="<?php esc_attr_e( 'Template preview', 'surveyjs' ); ?>"></iframe>
                    <div class="wp-sjs-templates__dialog-footer">
                        <span class="description" id="wp-sjs-template-preview-status"><?php esc_html_e( 'This is a preview: answers are not saved and no emails are sent.', 'surveyjs' ); ?></span>
                        <button type="button" class="button" data-close><?php esc_html_e( 'Close', 'surveyjs' ); ?></button>
                        <button type="submit" name="template" value="" class="button button-primary" id="wp-sjs-template-preview-use"><?php esc_html_e( 'Use template', 'surveyjs' ); ?></button>
                    </div>
                </dialog>
            </form>
        </div>
        <?php
    }

    private static function render_save( $form_id ) {
        $survey = SurveyJS_Forms::get( $form_id );
        if ( ! $survey ) {
            wp_die( esc_html__( 'Form not found.', 'surveyjs' ) );
        }
        $list_url = add_query_arg( 'page', SurveyJS_SurveyJS::MENU_SLUG, admin_url( 'admin.php' ) );
        ?>
        <div class="wrap wp-sjs-templates">
            <h1><?php esc_html_e( 'Save as Template', 'surveyjs' ); ?></h1>
            <p>
                <?php
                /* translators: %s: form name */
                echo esc_html( sprintf( __( 'Save the questions and the theme of "%s" as a template. It is listed under My Templates when you add a new form. The form itself does not change.', 'surveyjs' ), $survey->name ) );
                ?>
            </p>
            <form method="post" action="<?php echo esc_url( self::url() ); ?>">
                <?php wp_nonce_field( 'surveyjs-save-template_' . $survey->id ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wp-sjs-template-name"><?php esc_html_e( 'Template name', 'surveyjs' ); ?></label></th>
                        <td><input type="text" id="wp-sjs-template-name" name="template_name" class="regular-text" value="<?php echo esc_attr( $survey->name ); ?>" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wp-sjs-template-description"><?php esc_html_e( 'Description', 'surveyjs' ); ?></label></th>
                        <td><textarea id="wp-sjs-template-description" name="template_description" class="large-text" rows="3"></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="save_template" value="<?php echo esc_attr( $survey->id ); ?>" class="button button-primary"><?php esc_html_e( 'Save template', 'surveyjs' ); ?></button>
                    <a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'surveyjs' ); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
}
