<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Admin_Bar {

    const NODE = 'surveyjs';
    const CAPABILITY = 'manage_options';

    public static function init() {
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_menu' ), 80 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_style' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_style' ) );
    }

    public static function add_style() {
        if ( ! is_admin_bar_showing() || ! current_user_can( self::CAPABILITY ) ) {
            return;
        }
        wp_add_inline_style( 'admin-bar', '#wpadminbar #wp-admin-bar-surveyjs .ab-icon:before{content:"\f175";top:2px}'
            . '#wpadminbar #wp-admin-bar-surveyjs .wp-sjs-ab-count{display:inline-block;box-sizing:border-box;min-width:18px;height:18px;margin-left:6px;padding:0 5px;border-radius:9px;background:#d63638;color:#fff;font-size:11px;line-height:18px;text-align:center;vertical-align:middle}'
            . '#wpadminbar #wp-admin-bar-surveyjs .wp-sjs-ab-count--expiring{background:#dba617;color:#1d2327}' );
    }

    private static function admin_url( $page, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
    }

    public static function add_menu( $admin_bar ) {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return;
        }

        $forms_url = self::admin_url( 'surveyjs-main-menu' );
        $unread = SurveyJS_Entries::unread_by_form();
        $total = array_sum( $unread );
        $unread_url = 1 === count( $unread ) ? self::admin_url( 'surveyjs_results', array( 'id' => key( $unread ), 'status' => 'unread' ) ) : $forms_url;

        $title = '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'SurveyJS Forms', 'surveyjs' ) . '</span>';
        if ( $total ) {
            /* translators: %s: number of unread entries */
            $label = sprintf( _n( '%s unread entry', '%s unread entries', $total, 'surveyjs' ), number_format_i18n( $total ) );
            $title .= '<span class="wp-sjs-ab-count" aria-hidden="true">' . esc_html( number_format_i18n( $total ) ) . '</span><span class="screen-reader-text">' . esc_html( $label ) . '</span>';
        }
        $expiring = SurveyJS_Entries::partial_deleted_soon_by_form();
        $expiring_total = array_sum( $expiring );
        $expiring_url = 1 === count( $expiring ) ? self::admin_url( 'surveyjs_results', array( 'id' => key( $expiring ), 'status' => SurveyJS_Entries::PARTIAL ) ) : $forms_url;
        if ( $expiring_total ) {
            /* translators: %s: number of partial entries */
            $expiring_label = sprintf( _n( '%s partial entry will be deleted soon', '%s partial entries will be deleted soon', $expiring_total, 'surveyjs' ), number_format_i18n( $expiring_total ) );
            $title .= '<span class="wp-sjs-ab-count wp-sjs-ab-count--expiring" aria-hidden="true">' . esc_html( number_format_i18n( $expiring_total ) ) . '</span><span class="screen-reader-text">' . esc_html( $expiring_label ) . '</span>';
        }
        $admin_bar->add_node( array(
            'id'    => self::NODE,
            'title' => $title,
            'href'  => $total ? $unread_url : ( $expiring_total ? $expiring_url : $forms_url ),
        ) );

        if ( $total ) {
            $admin_bar->add_node( array(
                'parent' => self::NODE,
                'id'     => self::NODE . '-unread',
                'title'  => esc_html( $label ),
                'href'   => $unread_url,
            ) );
        }
        if ( $expiring_total ) {
            $admin_bar->add_node( array(
                'parent' => self::NODE,
                'id'     => self::NODE . '-expiring',
                'title'  => esc_html( $expiring_label ),
                'href'   => $expiring_url,
            ) );
        }
        $admin_bar->add_node( array(
            'parent' => self::NODE,
            'id'     => self::NODE . '-forms',
            'title'  => esc_html__( 'All Forms', 'surveyjs' ),
            'href'   => $forms_url,
        ) );
        $admin_bar->add_node( array(
            'parent' => self::NODE,
            'id'     => self::NODE . '-add',
            'title'  => esc_html__( 'Add New', 'surveyjs' ),
            'href'   => wp_nonce_url( self::admin_url( 'surveyjs-main-menu', array( 'action' => 'add' ) ), 'surveyjs-add-survey' ),
        ) );

        if ( is_admin() ) {
            return;
        }
        foreach ( self::page_forms() as $form ) {
            $admin_bar->add_node( array(
                'parent' => self::NODE,
                'id'     => self::NODE . '-edit-' . $form->id,
                /* translators: %s: form name */
                'title'  => esc_html( sprintf( __( 'Edit form: %s', 'surveyjs' ), $form->name ) ),
                'href'   => self::admin_url( 'surveyjs_editor', array( 'id' => $form->id ) ),
            ) );
        }
    }

    // A classic theme prints the toolbar at wp_body_open, before the content runs its shortcodes,
    // so the forms of the current post are also read from its content.
    private static function page_forms() {
        $forms = array();
        foreach ( SurveyJS_SurveyJS::$rendered_forms as $id => $name ) {
            $forms[ $id ] = (object) array( 'id' => $id, 'name' => $name );
        }
        $post = is_singular() ? get_post() : null;
        if ( $post ) {
            foreach ( SurveyJS_SurveyJS::content_survey_ids( $post->post_content ) as $id ) {
                $form = SurveyJS_Forms::get( $id );
                if ( $form && ! isset( $forms[ $form->id ] ) ) {
                    $forms[ $form->id ] = $form;
                }
            }
        }
        return $forms;
    }
}
