<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Privacy {

    const PER_PAGE = 50;
    const GROUP = 'surveyjs-entries';

    public static function init() {
        add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
    }

    public static function register_exporter( $exporters ) {
        $exporters[ self::GROUP ] = array(
            'exporter_friendly_name' => __( 'SurveyJS Forms entries', 'surveyjs' ),
            'callback'               => array( __CLASS__, 'export' ),
        );
        return $exporters;
    }

    public static function register_eraser( $erasers ) {
        $erasers[ self::GROUP ] = array(
            'eraser_friendly_name' => __( 'SurveyJS Forms entries', 'surveyjs' ),
            'callback'             => array( __CLASS__, 'erase' ),
        );
        return $erasers;
    }

    public static function export( $email, $page = 1 ) {
        $candidates = self::candidates( $email, self::PER_PAGE, ( max( 1, (int) $page ) - 1 ) * self::PER_PAGE );
        $items = array();
        $forms = array();
        foreach ( self::matching( $candidates, $email ) as $row ) {
            $form_id = (int) $row->form_id;
            if ( ! array_key_exists( $form_id, $forms ) ) {
                $forms[ $form_id ] = SurveyJS_Forms::find( $form_id );
            }
            $items[] = array(
                'group_id'    => self::GROUP,
                'group_label' => __( 'Form entries', 'surveyjs' ),
                'item_id'     => 'surveyjs-entry-' . $row->id,
                'data'        => self::export_data( $row, $forms[ $form_id ] ),
            );
        }
        return array(
            'data' => $items,
            'done' => count( $candidates ) < self::PER_PAGE,
        );
    }

    public static function erase( $email, $page = 1 ) {
        $matching = self::matching( self::candidates( $email ), $email );
        $removed = SurveyJS_Entries::delete_rows( array_slice( $matching, 0, self::PER_PAGE ) );
        return array(
            'items_removed'  => $removed > 0,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => count( $matching ) <= self::PER_PAGE,
        );
    }

    private static function candidates( $email, $limit = 0, $offset = 0 ) {
        global $wpdb;
        $email = trim( (string) $email );
        if ( ! is_email( $email ) ) {
            return array();
        }
        $table = SurveyJS_Entries::table();
        $user = get_user_by( 'email', $email );
        $where = $wpdb->prepare( 'json LIKE %s', '%' . $wpdb->esc_like( trim( wp_json_encode( $email ), '"' ) ) . '%' );
        if ( $user ) {
            $where .= $wpdb->prepare( ' OR user_id = %d', $user->ID );
        }
        $paging = $limit ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset ) : '';
        return $wpdb->get_results( "SELECT id, form_id, json, status, user_id, ip, created_at FROM $table WHERE $where ORDER BY id$paging" );
    }

    private static function matching( $rows, $email ) {
        $user = get_user_by( 'email', trim( (string) $email ) );
        return array_values( array_filter( $rows, function ( $row ) use ( $user, $email ) {
            return ( $user && (int) $row->user_id === $user->ID ) || self::contains_email( SurveyJS_Client::decode_survey_json( $row->json ), trim( $email ) );
        } ) );
    }

    private static function contains_email( $value, $email ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                if ( self::contains_email( $item, $email ) ) {
                    return true;
                }
            }
            return false;
        }
        return is_string( $value ) && 0 === strcasecmp( trim( $value ), $email );
    }

    private static function export_data( $row, $form ) {
        $data = array(
            array( 'name' => __( 'Form', 'surveyjs' ), 'value' => $form ? $form->name : __( '(deleted form)', 'surveyjs' ) ),
            array( 'name' => __( 'Date', 'surveyjs' ), 'value' => SurveyJS_Entries::format_date( $row->created_at ) ),
        );
        if ( '' !== (string) $row->ip ) {
            $data[] = array( 'name' => __( 'IP address', 'surveyjs' ), 'value' => $row->ip );
        }
        $values = SurveyJS_Client::decode_survey_json( $row->json );
        $values = is_array( $values ) ? $values : array();
        $questions = $form ? SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $form->json ) ) : array();
        foreach ( $questions as $question ) {
            if ( array_key_exists( $question['name'], $values ) ) {
                $data[] = array(
                    'name'  => SurveyJS_Survey_Values::question_title( $question ),
                    'value' => SurveyJS_Survey_Values::display_value( $question, $values[ $question['name'] ] ),
                );
                unset( $values[ $question['name'] ] );
            }
        }
        foreach ( $values as $name => $value ) {
            $data[] = array( 'name' => (string) $name, 'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
        }
        return $data;
    }
}
