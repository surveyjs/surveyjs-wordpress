<?php
// SurveyJS Forms Add-on at commit 709f874 (surveyjs-wordpress-addon): its admin bar item used SurveyJS_Admin_Bar,
// which SurveyJS Forms later removed, and every admin page failed with "Class SurveyJS_Admin_Bar not found".
define( 'SURVEYJS_ADDON_FILE', __FILE__ );

class SurveyJS_Addon_Dashboard {

    public static function init() {
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_item' ), 81 );
    }

    public static function add_admin_bar_item( $admin_bar ) {
        if ( ! current_user_can( SurveyJS_Admin_Bar::CAPABILITY ) || ! $admin_bar->get_node( SurveyJS_Admin_Bar::NODE ) ) {
            return;
        }
        $admin_bar->add_node( array(
            'parent' => SurveyJS_Admin_Bar::NODE,
            'id'     => SurveyJS_Admin_Bar::NODE . '-addon-dashboard',
            'title'  => esc_html__( 'Dashboard', 'surveyjs-forms-addon' ),
            'href'   => '',
        ) );
    }
}

function surveyjs_addon_fixture_closure() {
    return function ( $links ) {
        throw new Error( 'Call to undefined function surveyjs_removed_function()' );
    };
}
