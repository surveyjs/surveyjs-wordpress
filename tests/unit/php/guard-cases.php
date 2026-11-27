<?php
require __DIR__ . '/wp-stubs.php';
require __DIR__ . '/../../../src/trunk/guard.php';
require __DIR__ . '/fixtures/surveyjs-forms-addon/surveyjs-forms-addon.php';

class Test_Admin_Bar {
    public $nodes = array();

    public function add_node( $node ) {
        $this->nodes[] = $node['id'];
    }

    public function get_node( $id ) {
        return in_array( $id, $this->nodes, true ) ? (object) array( 'id' => $id ) : null;
    }
}

class SurveyJS_Test_Base {
    public static $calls = array();

    public static function broken_filter( $value ) {
        return $value . count( 'not an array' );
    }

    public static function append( $value ) {
        self::$calls[] = 'append';
        return $value . '+base';
    }

    public static function sanitize( $value ) {
        throw new TypeError( 'sanitize failed' );
    }
}

function wps_test_broken_function() {
    throw new Error( 'Class "SurveyJS_Removed" not found' );
}

function other_plugin_callback() {
    throw new RuntimeException( 'other plugin error' );
}

SurveyJS_Guard::init();

test_run( array(
    'old-addon-admin-bar' => function () {
        SurveyJS_Addon_Dashboard::init();
        add_action( 'admin_bar_menu', function ( $bar ) {
            $bar->add_node( array( 'id' => 'core-after-addon' ) );
        }, 200 );
        $bar = new Test_Admin_Bar();
        do_action( 'admin_bar_menu', $bar );
        do_action( 'admin_bar_menu', $bar );
        SurveyJS_Guard::notice();
        return array( 'nodes' => $bar->nodes, 'failures' => SurveyJS_Guard::failures() );
    },
    'filter-keeps-value' => function () {
        add_filter( 'plugin_row_meta', array( 'SurveyJS_Test_Base', 'broken_filter' ) );
        add_filter( 'plugin_row_meta', array( 'SurveyJS_Test_Base', 'append' ), 20 );
        return apply_filters( 'plugin_row_meta', 'links' );
    },
    'add-on-closure' => function () {
        add_filter( 'plugin_action_links', surveyjs_addon_fixture_closure() );
        return array( 'value' => apply_filters( 'plugin_action_links', array( 'deactivate' ) ), 'failures' => SurveyJS_Guard::failures() );
    },
    'base-function' => function () {
        add_action( 'admin_init', 'wps_test_broken_function' );
        do_action( 'admin_init' );
        return SurveyJS_Guard::failures();
    },
    'foreign-callback' => function () {
        add_action( 'admin_init', 'other_plugin_callback' );
        try {
            do_action( 'admin_init' );
        } catch ( RuntimeException $error ) {
            return $error->getMessage();
        }
        return 'not thrown';
    },
    'unlisted-hook' => function () {
        add_filter( 'surveyjs_form_settings_sanitize', array( 'SurveyJS_Test_Base', 'sanitize' ) );
        try {
            apply_filters( 'surveyjs_form_settings_sanitize', array() );
        } catch ( TypeError $error ) {
            return $error->getMessage();
        }
        return 'not thrown';
    },
    'remove-after-wrap' => function () {
        add_filter( 'admin_body_class', array( 'SurveyJS_Test_Base', 'append' ) );
        $first = apply_filters( 'admin_body_class', 'body' );
        $removed = remove_filter( 'admin_body_class', array( 'SurveyJS_Test_Base', 'append' ) );
        return array( $first, $removed, apply_filters( 'admin_body_class', 'body' ) );
    },
    'notice-hidden-without-capability' => function () {
        $GLOBALS['test_user_can'] = false;
        add_action( 'admin_init', 'wps_test_broken_function' );
        do_action( 'admin_init' );
        SurveyJS_Guard::notice();
        return count( SurveyJS_Guard::failures() );
    },
) );
