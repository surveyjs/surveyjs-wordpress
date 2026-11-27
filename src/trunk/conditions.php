<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Rules "question operator value" checked on the server against the submitted answers. Operator names and
// comparison follow SurveyJS expressions: strings are compared trimmed and case-insensitively, and "contains"
// on a multiple-choice answer checks the selected values.
class SurveyJS_Conditions {

    const OPERATORS = array( 'equal', 'notequal', 'contains', 'notcontains', 'greater', 'less', 'empty', 'notempty' );
    const MAX_RULES = 20;

    public static function sanitize( $conditions ) {
        $conditions = is_array( $conditions ) ? $conditions : array();
        $rules = array();
        foreach ( array_slice( is_array( $conditions['rules'] ?? null ) ? array_values( $conditions['rules'] ) : array(), 0, self::MAX_RULES ) as $rule ) {
            $question = is_array( $rule ) && is_scalar( $rule['question'] ?? null ) ? sanitize_text_field( (string) $rule['question'] ) : '';
            if ( '' === $question ) {
                continue;
            }
            $operator = is_scalar( $rule['operator'] ?? null ) ? (string) $rule['operator'] : '';
            $rules[] = array(
                'question' => $question,
                'operator' => in_array( $operator, self::OPERATORS, true ) ? $operator : 'equal',
                'value'    => sanitize_text_field( is_scalar( $rule['value'] ?? null ) ? (string) $rule['value'] : '' ),
            );
        }
        return array(
            'logic' => 'any' === ( $conditions['logic'] ?? '' ) ? 'any' : 'all',
            'rules' => $rules,
        );
    }

    public static function matches( $conditions, $data ) {
        $conditions = self::sanitize( $conditions );
        if ( ! $conditions['rules'] ) {
            return true;
        }
        $data = is_array( $data ) ? $data : array();
        $any = 'any' === $conditions['logic'];
        foreach ( $conditions['rules'] as $rule ) {
            if ( self::rule_matches( $rule, $data[ $rule['question'] ] ?? null ) === $any ) {
                return $any;
            }
        }
        return ! $any;
    }

    private static function rule_matches( $rule, $answer ) {
        $values = self::values( $answer );
        $expected = self::normalize( $rule['value'] );
        switch ( $rule['operator'] ) {
            case 'empty':
                return ! $values;
            case 'notempty':
                return (bool) $values;
            case 'notequal':
                return ! self::equals_any( $values, $expected );
            case 'contains':
                return self::contains( $values, $expected );
            case 'notcontains':
                return ! self::contains( $values, $expected );
            case 'greater':
                return 1 === count( $values ) && self::compare( $values[0], $expected ) > 0;
            case 'less':
                return 1 === count( $values ) && self::compare( $values[0], $expected ) < 0;
            default:
                return self::equals_any( $values, $expected );
        }
    }

    private static function values( $answer ) {
        if ( is_array( $answer ) ) {
            $values = array();
            array_walk_recursive( $answer, function ( $item ) use ( &$values ) {
                $values[] = $item;
            } );
            return array_values( array_filter( array_map( array( __CLASS__, 'normalize' ), $values ), 'strlen' ) );
        }
        $value = self::normalize( $answer );
        return '' === $value ? array() : array( $value );
    }

    // Saved answers pass through wp_kses(), which stores "R&D" as "R&amp;D", while a rule value keeps the raw
    // choice value from the form JSON (and sanitize_text_field() turns a lone "<" into "&lt;"), so both sides are decoded.
    private static function normalize( $value ) {
        if ( is_bool( $value ) ) {
            return $value ? 'true' : 'false';
        }
        return is_scalar( $value ) ? ( function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower' )( trim( wp_specialchars_decode( (string) $value, ENT_QUOTES ) ) ) : '';
    }

    private static function equals_any( $values, $expected ) {
        foreach ( $values as $value ) {
            if ( $value === $expected || ( is_numeric( $value ) && is_numeric( $expected ) && (float) $value === (float) $expected ) ) {
                return true;
            }
        }
        return false;
    }

    private static function contains( $values, $expected ) {
        foreach ( $values as $value ) {
            if ( str_contains( $value, $expected ) ) {
                return true;
            }
        }
        return false;
    }

    private static function compare( $value, $expected ) {
        if ( is_numeric( $value ) && is_numeric( $expected ) ) {
            return (float) $value <=> (float) $expected;
        }
        return strcmp( $value, $expected );
    }
}
