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

    const MARKDOWN_LINK = '/\[([^\]]+)\]\((https?:\/\/[^\s()]+)\)/';

    public static function question_title( $question ) {
        $title = self::form_text( self::localized( $question['title'] ?? '' ), false );
        return '' === trim( $title ) ? $question['name'] : $title;
    }

    public static function display_value( $question, $value ) {
        return self::format_value( $question, $value, false );
    }

    public static function display_html( $question, $value ) {
        return self::format_value( $question, $value, true );
    }

    private static function format_value( $question, $value, $html ) {
        $plain = $html ? 'esc_html' : 'strval';
        if ( null === $value ) {
            return '';
        }
        if ( is_bool( $value ) ) {
            $label = $value ? ( $question['labelTrue'] ?? '' ) : ( $question['labelFalse'] ?? '' );
            $label = self::localized( $label );
            return '' !== $label ? self::form_text( $label, $html ) : $plain( $value ? __( 'Yes', 'surveyjs' ) : __( 'No', 'surveyjs' ) );
        }
        if ( is_scalar( $value ) ) {
            return self::choice_text( $question, $value, $html );
        }
        if ( array_is_list( $value ) ) {
            $parts = array();
            foreach ( $value as $part ) {
                if ( is_scalar( $part ) ) {
                    $parts[] = self::choice_text( $question, $part, $html );
                } elseif ( is_array( $part ) && isset( $part['name'] ) && is_string( $part['name'] ) ) {
                    $parts[] = $plain( $part['name'] );
                } else {
                    return $plain( wp_json_encode( $value ) );
                }
            }
            return implode( ', ', $parts );
        }
        return $plain( wp_json_encode( $value ) );
    }

    private static function choice_text( $question, $value, $html ) {
        foreach ( (array) ( $question['choices'] ?? array() ) as $choice ) {
            if ( is_array( $choice ) && isset( $choice['value'] ) && (string) $choice['value'] === (string) $value ) {
                $text = self::localized( $choice['text'] ?? '' );
                if ( '' !== $text ) {
                    return self::form_text( $text, $html );
                }
                break;
            }
        }
        return $html ? esc_html( (string) $value ) : (string) $value;
    }

    // Form texts render [text](https://…) as a link (survey.js); respondents' answers are never treated as markdown.
    private static function form_text( $text, $html ) {
        if ( ! $html ) {
            return preg_replace( self::MARKDOWN_LINK, '$1', $text );
        }
        $result = '';
        $offset = 0;
        preg_match_all( self::MARKDOWN_LINK, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
        foreach ( $matches as $match ) {
            $result .= esc_html( substr( $text, $offset, $match[0][1] - $offset ) );
            $result .= sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $match[2][0] ), esc_html( $match[1][0] ) );
            $offset = $match[0][1] + strlen( $match[0][0] );
        }
        return $result . esc_html( substr( $text, $offset ) );
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
