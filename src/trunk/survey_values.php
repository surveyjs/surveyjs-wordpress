<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Survey_Values {

    public static function collect_questions( $elements, &$questions ) {
        foreach ( (array) $elements as $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }
            $type = $element['type'] ?? '';
            if ( 'panel' === $type || ! isset( $element['type'] ) ) {
                self::collect_questions( $element['elements'] ?? array(), $questions );
                continue;
            }
            if ( in_array( $type, array( 'html', 'image' ), true ) || empty( $element['name'] ) ) {
                continue;
            }
            $questions[] = $element;
        }
    }

    public static function survey_questions( $json ) {
        $questions = array();
        if ( is_array( $json ) ) {
            self::collect_questions( $json['pages'] ?? array(), $questions );
            self::collect_questions( $json['elements'] ?? array(), $questions );
        }
        return $questions;
    }

    public static function question_title( $question ) {
        $title = self::localized( $question['title'] ?? '' );
        return '' === trim( $title ) ? $question['name'] : $title;
    }

    public static function display_value( $question, $value ) {
        if ( null === $value ) {
            return '';
        }
        if ( is_bool( $value ) ) {
            $label = $value ? ( $question['labelTrue'] ?? '' ) : ( $question['labelFalse'] ?? '' );
            $label = self::localized( $label );
            return '' !== $label ? $label : ( $value ? __( 'Yes', 'surveyjs' ) : __( 'No', 'surveyjs' ) );
        }
        if ( is_scalar( $value ) ) {
            return self::choice_text( $question, $value );
        }
        if ( array_is_list( $value ) ) {
            $parts = array();
            foreach ( $value as $part ) {
                if ( is_scalar( $part ) ) {
                    $parts[] = self::choice_text( $question, $part );
                } elseif ( is_array( $part ) && isset( $part['name'] ) && is_string( $part['name'] ) ) {
                    $parts[] = $part['name'];
                } else {
                    return wp_json_encode( $value );
                }
            }
            return implode( ', ', $parts );
        }
        return wp_json_encode( $value );
    }

    private static function choice_text( $question, $value ) {
        foreach ( (array) ( $question['choices'] ?? array() ) as $choice ) {
            if ( is_array( $choice ) && isset( $choice['value'] ) && (string) $choice['value'] === (string) $value ) {
                $text = self::localized( $choice['text'] ?? '' );
                return '' !== $text ? $text : (string) $value;
            }
        }
        return (string) $value;
    }

    private static function localized( $text ) {
        if ( is_string( $text ) ) {
            return $text;
        }
        if ( is_array( $text ) ) {
            if ( isset( $text['default'] ) && is_string( $text['default'] ) ) {
                return $text['default'];
            }
            foreach ( $text as $value ) {
                if ( is_string( $value ) ) {
                    return $value;
                }
            }
        }
        return '';
    }
}
