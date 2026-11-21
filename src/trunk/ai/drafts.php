<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// AI assistants never change a live form: they write a draft that the form editor loads,
// and the form changes only when a person clicks Save there.
class SurveyJS_AI_Drafts {

    const META = '_sjs_ai_draft';
    const DISCARD_ACTION = 'surveyjs_ai_discard_draft';
    const HEARTBEAT_KEY = 'surveyjs_ai_draft';

    // Who writes drafts in this request: "chat" while the editor's AI chat runs, "mcp" otherwise. The editor
    // already shows the chat's changes itself, so only drafts from MCP apps get the "AI app changed this form" notice.
    public static $source = 'mcp';
    const EMAIL_KEYS = array( 'notificationEnabled', 'notificationTo', 'notificationReplyTo', 'notificationAttachments', 'autoReplyEnabled', 'autoReplyAttachments' );

    public static function init() {
        add_filter( 'heartbeat_received', array( __CLASS__, 'heartbeat' ), 10, 2 );
        add_action( 'wp_ajax_' . self::DISCARD_ACTION, array( __CLASS__, 'ajax_discard' ) );
    }

    public static function get( $form_id ) {
        $raw = get_post_meta( (int) $form_id, self::META, true );
        $draft = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;
        if ( ! is_array( $draft ) ) {
            return null;
        }
        foreach ( array( 'name', 'json', 'theme', 'settings', 'revision' ) as $key ) {
            if ( ! isset( $draft[ $key ] ) || ! is_string( $draft[ $key ] ) ) {
                return null;
            }
        }
        return $draft;
    }

    // A form an AI app created stays empty until a person saves it once, so it is not offered for display on the site.
    public static function is_unsaved_new_form( $form ) {
        if ( ! $form || '' !== trim( (string) $form->json ) ) {
            return false;
        }
        $draft = self::get( $form->id );
        return $draft && ! empty( $draft['created'] );
    }

    public static function unsaved_new_form_ids() {
        $ids = get_posts( array(
            'post_type'        => SurveyJS_Forms::POST_TYPE,
            'post_status'      => 'publish',
            'fields'           => 'ids',
            'posts_per_page'   => 500,
            'no_found_rows'    => true,
            'suppress_filters' => false,
            'meta_key'         => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- only forms with an AI draft, usually none or a few.
        ) );
        return array_values( array_filter( array_map( 'intval', $ids ), function ( $id ) {
            return self::is_unsaved_new_form( SurveyJS_Forms::find( $id ) );
        } ) );
    }

    public static function without_unsaved_new_forms( $forms ) {
        $hidden = self::unsaved_new_form_ids();
        return array_values( array_filter( (array) $forms, function ( $form ) use ( $hidden ) {
            return ! in_array( (int) $form->id, $hidden, true );
        } ) );
    }

    public static function working_copy( $form ) {
        $draft = self::get( $form->id );
        if ( ! $draft ) {
            return $form;
        }
        return (object) array(
            'id'       => (int) $form->id,
            'name'     => $draft['name'],
            'json'     => $draft['json'],
            'theme'    => $draft['theme'],
            'settings' => $draft['settings'],
        );
    }

    public static function revision( $copy ) {
        return substr( md5( (string) wp_json_encode( array( (string) $copy->name, (string) $copy->json, (string) $copy->theme, (string) $copy->settings ) ) ), 0, 12 );
    }

    public static function save( $form, $changes, $created = false ) {
        $current = self::get( $form->id );
        $draft = $current ? $current : array(
            'name'     => (string) $form->name,
            'json'     => (string) $form->json,
            'theme'    => (string) $form->theme,
            'settings' => (string) $form->settings,
            'base'     => self::revision( $form ),
            'created'  => (bool) $created,
        );
        $draft = array_merge( $draft, array_map( 'strval', array_intersect_key( $changes, array_flip( array( 'name', 'json', 'theme', 'settings' ) ) ) ) );
        $draft['time'] = time();
        $draft['user'] = get_current_user_id();
        $draft['source'] = self::$source;
        $draft['revision'] = self::revision( (object) $draft );
        update_post_meta( (int) $form->id, self::META, wp_slash( (string) wp_json_encode( $draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
        return $draft;
    }

    public static function discard( $form_id, $revision ) {
        $draft = self::get( $form_id );
        if ( ! $draft || ! hash_equals( $draft['revision'], (string) $revision ) ) {
            return false;
        }
        return delete_post_meta( (int) $form_id, self::META );
    }

    public static function is_outdated( $form, $draft ) {
        return isset( $draft['base'] ) && self::revision( $form ) !== $draft['base'];
    }

    public static function author_name( $draft ) {
        $user = get_userdata( (int) ( $draft['user'] ?? 0 ) );
        return $user ? $user->display_name : '';
    }

    public static function editor_config( $form ) {
        $config = array(
            'discardUrl'    => add_query_arg( array( 'action' => self::DISCARD_ACTION ), admin_url( 'admin-ajax.php' ) ),
            'nonce'         => wp_create_nonce( self::DISCARD_ACTION . $form->id ),
            'heartbeatKey'  => self::HEARTBEAT_KEY,
            'draft'         => null,
        );
        $draft = self::get( $form->id );
        if ( ! $draft ) {
            return $config;
        }
        $copy = self::working_copy( $form );
        $draft_settings = self::shown_settings( SurveyJS_Form_Settings::get( $copy ) );
        // A new form is compared with what a new form gets anyway, so settings derived from its questions don't count as AI changes.
        $live_settings = self::shown_settings( empty( $draft['created'] ) ? SurveyJS_Form_Settings::get( $form ) : array_merge(
            SurveyJS_Form_Settings::defaults(),
            SurveyJS_Form_Settings::for_new_form( SurveyJS_Client::decode_survey_json( $copy->json ), $draft_settings['themeMode'] )
        ) );
        $changed_settings = array_keys( array_filter( $draft_settings, function ( $value, $key ) use ( $live_settings ) {
            return ( $live_settings[ $key ] ?? null ) !== $value;
        }, ARRAY_FILTER_USE_BOTH ) );
        $config['draft'] = array_merge( self::editor_state( $copy ), array(
            'revision'        => $draft['revision'],
            'created'         => ! empty( $draft['created'] ),
            'outdated'        => ! empty( $draft['created'] ) ? false : self::is_outdated( $form, $draft ),
            'time'            => self::time_text( $draft ),
            'author'          => self::author_name( $draft ),
            'changedName'     => $copy->name !== $form->name,
            'changedJson'     => SurveyJS_Client::decode_survey_json( $copy->json ) != SurveyJS_Client::decode_survey_json( $form->json ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- compares decoded JSON structures, key order and number types may differ.
            'changedTheme'    => SurveyJS_Client::decode_survey_json( $copy->theme ) != SurveyJS_Client::decode_survey_json( $form->theme ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- see above.
            'changedSettings' => $changed_settings,
            'emailChanged'    => (bool) array_intersect( $changed_settings, (array) apply_filters( 'surveyjs_ai_sensitive_form_settings', self::EMAIL_KEYS ) ),
        ) );
        return $config;
    }

    // What the form editor shows for a form copy: decoded JSON, and settings with the defaults of empty email fields.
    public static function editor_state( $copy ) {
        $json = SurveyJS_Client::decode_survey_json( $copy->json, false );
        return array(
            'name'     => (string) $copy->name,
            'json'     => empty( (array) $json ) ? new stdClass() : $json,
            'theme'    => SurveyJS_Themes::decode( $copy->theme ),
            'settings' => self::shown_settings( SurveyJS_Form_Settings::get( $copy ) ),
        );
    }

    // The form editor fills empty email and message fields with their defaults, and Save stores them, so an empty
    // field and its default are the same setting.
    public static function shown_settings( $settings ) {
        foreach ( SurveyJS_Form_Settings::fallbacks() as $key => $fallback ) {
            if ( '' === trim( (string) ( $settings[ $key ] ?? '' ) ) ) {
                $settings[ $key ] = $fallback;
            }
        }
        return $settings;
    }

    private static function time_text( $draft ) {
        $time = (int) ( $draft['time'] ?? 0 );
        return $time ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) : '';
    }

    public static function heartbeat( $response, $data ) {
        if ( empty( $data[ self::HEARTBEAT_KEY ]['id'] ) || ! current_user_can( SurveyJS_SurveyJS::CAPABILITY ) ) {
            return $response;
        }
        $form = SurveyJS_Forms::find( absint( $data[ self::HEARTBEAT_KEY ]['id'] ) );
        if ( ! $form ) {
            return $response;
        }
        $draft = self::get( $form->id );
        $response[ self::HEARTBEAT_KEY ] = array(
            'revision' => $draft ? $draft['revision'] : '',
            'time'     => $draft ? self::time_text( $draft ) : '',
            'author'   => $draft ? self::author_name( $draft ) : '',
            'source'   => $draft ? (string) ( $draft['source'] ?? 'mcp' ) : '',
        );
        return $response;
    }

    public static function ajax_discard() {
        $id = absint( wp_unslash( $_POST['id'] ?? 0 ) );
        check_ajax_referer( self::DISCARD_ACTION . $id );
        if ( ! current_user_can( SurveyJS_SurveyJS::CAPABILITY ) || ! SurveyJS_Forms::find( $id ) ) {
            wp_send_json_error( array( 'message' => __( 'You are not allowed to change this form.', 'surveyjs' ) ), 403 );
        }
        if ( ! self::discard( $id, sanitize_key( wp_unslash( $_POST['revision'] ?? '' ) ) ) ) {
            wp_send_json_error( array( 'message' => __( 'The AI changes were not discarded: the AI changed the form again. Reload the page to see the latest changes.', 'surveyjs' ) ), 409 );
        }
        wp_send_json_success();
    }
}
