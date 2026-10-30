<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Themes {

    public static function url() {
        return plugins_url( 'themes/surveyjs-themes.json', __FILE__ );
    }

    public static function presets() {
        static $presets = null;
        if ( null === $presets ) {
            $presets = wp_json_file_decode( __DIR__ . '/themes/surveyjs-themes.json', array( 'associative' => true ) );
            $presets = is_array( $presets ) ? $presets : array();
        }
        return $presets;
    }

    public static function labels() {
        $labels = array();
        foreach ( array_keys( self::presets() ) as $slug ) {
            $panelless = str_ends_with( $slug, '-panelless' );
            $label = ucwords( str_replace( '-', ' ', preg_replace( '/-panelless$/', '', $slug ) ) );
            $labels[ $slug ] = $panelless ? sprintf( __( '%s (panelless)', 'surveyjs' ), $label ) : $label;
        }
        return $labels;
    }

    public static function resolve( $survey ) {
        $theme = SurveyJS_Client::decode_survey_json( $survey->theme, false );
        return array(
            'mode'  => SurveyJS_Form_Settings::get( $survey )['themeMode'],
            'theme' => empty( $theme ) ? null : $theme,
        );
    }
}
