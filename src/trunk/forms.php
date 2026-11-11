<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Forms {

    const POST_TYPE = 'sjs_form';
    const THEME_META = '_sjs_theme';
    const SETTINGS_META = '_sjs_settings';
    const LEGACY_ID_META = '_sjs_legacy_id';
    const LEGACY_MAP_OPTION = 'surveyjs_legacy_form_ids';

    private static $saving = false;

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ), 0 );
        add_filter( 'wp_insert_post_data', array( __CLASS__, 'keep_raw_fields' ), 99, 3 );
        add_action( 'before_delete_post', array( __CLASS__, 'before_delete' ), 10, 2 );
    }

    public static function register_post_type() {
        if ( post_type_exists( self::POST_TYPE ) ) {
            return;
        }
        $caps = array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'edit_private_posts', 'edit_published_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'create_posts' );
        register_post_type( self::POST_TYPE, array(
            'labels'       => array(
                'name'          => __( 'Forms', 'surveyjs' ),
                'singular_name' => __( 'Form', 'surveyjs' ),
            ),
            'public'       => false,
            'show_ui'      => false,
            'show_in_rest' => false,
            'rewrite'      => false,
            'query_var'    => false,
            'can_export'   => true,
            'supports'     => array( 'title' ),
            'map_meta_cap' => false,
            'capabilities' => array_fill_keys( $caps, 'manage_options' ),
        ) );
    }

    // wp_insert_post() runs post_content through kses and other content filters, which corrupts the form JSON;
    // only our own saves get the raw value back, so imports from other tools are still filtered.
    public static function keep_raw_fields( $data, $postarr, $unsanitized_postarr ) {
        if ( self::$saving && self::POST_TYPE === $data['post_type'] ) {
            $data['post_content'] = $unsanitized_postarr['post_content'] ?? $data['post_content'];
            $data['post_title'] = $unsanitized_postarr['post_title'] ?? $data['post_title'];
        }
        return $data;
    }

    public static function resolve_id( $id ) {
        $id = absint( $id );
        if ( ! $id ) {
            return 0;
        }
        if ( self::POST_TYPE === get_post_type( $id ) ) {
            return $id;
        }
        $new_id = absint( self::legacy_map()[ $id ] ?? 0 );
        return $new_id && self::POST_TYPE === get_post_type( $new_id ) ? $new_id : 0;
    }

    public static function legacy_map() {
        $map = get_option( self::LEGACY_MAP_OPTION, array() );
        return is_array( $map ) ? $map : array();
    }

    public static function get( $id ) {
        return self::find( self::resolve_id( $id ) );
    }

    public static function find( $post_id ) {
        $post = $post_id ? get_post( $post_id ) : null;
        return $post && self::POST_TYPE === $post->post_type && 'publish' === $post->post_status ? self::row( $post ) : null;
    }

    public static function all() {
        $posts = surveyjs_get_all_posts( array(
            'post_type'        => self::POST_TYPE,
            'post_status'      => 'publish',
            'orderby'          => 'ID',
            'order'            => 'DESC',
        ) );
        return array_map( array( __CLASS__, 'row' ), $posts );
    }

    public static function names() {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT ID AS id, post_title AS name FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY post_title, ID",
            self::POST_TYPE
        ) );
    }

    public static function exists() {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' LIMIT 1", self::POST_TYPE ) );
    }

    public static function create( $fields ) {
        $fields = array_merge( array( 'name' => '', 'json' => '', 'theme' => '', 'settings' => '', 'import_id' => 0 ), $fields );
        $postarr = array(
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => (string) $fields['name'],
            'post_content' => (string) $fields['json'],
        );
        if ( $fields['import_id'] ) {
            $postarr['import_id'] = (int) $fields['import_id'];
        }
        $id = self::save( $postarr );
        if ( $id ) {
            self::save_meta( $id, $fields );
        }
        return $id;
    }

    public static function update( $id, $fields ) {
        $postarr = array( 'ID' => (int) $id );
        if ( isset( $fields['name'] ) ) {
            $postarr['post_title'] = (string) $fields['name'];
        }
        if ( isset( $fields['json'] ) ) {
            $postarr['post_content'] = (string) $fields['json'];
        }
        if ( count( $postarr ) > 1 && ! self::save( $postarr ) ) {
            return false;
        }
        self::save_meta( $id, $fields );
        return true;
    }

    public static function delete( $id ) {
        return self::find( $id ) ? (bool) wp_delete_post( $id, true ) : false;
    }

    public static function before_delete( $post_id, $post ) {
        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return;
        }
        SurveyJS_Entries::delete_for_form( self::row( $post ) );
        SurveyJS_Importer::forget( array( $post_id ) );
    }

    private static function save( $postarr ) {
        self::$saving = true;
        $id = isset( $postarr['ID'] ) ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
        self::$saving = false;
        return is_wp_error( $id ) ? 0 : (int) $id;
    }

    private static function save_meta( $id, $fields ) {
        $meta = array( 'theme' => self::THEME_META, 'settings' => self::SETTINGS_META );
        foreach ( $meta as $field => $key ) {
            if ( isset( $fields[ $field ] ) ) {
                update_post_meta( $id, $key, wp_slash( (string) $fields[ $field ] ) );
            }
        }
    }

    private static function row( $post ) {
        return (object) array(
            'id'       => (int) $post->ID,
            'name'     => $post->post_title,
            'json'     => $post->post_content,
            'theme'    => (string) get_post_meta( $post->ID, self::THEME_META, true ),
            'settings' => (string) get_post_meta( $post->ID, self::SETTINGS_META, true ),
        );
    }
}
