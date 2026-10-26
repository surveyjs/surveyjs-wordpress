<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SurveyJS_Results_List_Table extends WP_List_Table {

    const PER_PAGE = 20;

    private $survey_id;
    private $questions;

    public function __construct( $survey_id, $questions ) {
        parent::__construct( array(
            'singular' => 'result',
            'plural'   => 'results',
            'ajax'     => false,
        ) );
        $this->survey_id = $survey_id;
        $this->questions = $questions;
    }

    public function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id' => __( 'ID', 'surveyjs' ),
        );
        foreach ( $this->questions as $index => $question ) {
            $columns[ 'question_' . $index ] = esc_html( self::question_title( $question ) );
        }
        return $columns;
    }

    protected function get_sortable_columns() {
        return array( 'id' => array( 'id', true ) );
    }

    protected function get_bulk_actions() {
        return array( 'delete' => __( 'Delete', 'surveyjs' ) );
    }

    public function no_items() {
        esc_html_e( 'No results found.', 'surveyjs' );
    }

    public function prepare_items() {
        global $wpdb;

        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $order = 'asc' === sanitize_key( $_REQUEST['order'] ?? '' ) ? 'ASC' : 'DESC';

        $where = 'WHERE surveyId = %d';
        $args = array( $this->survey_id );
        if ( '' !== $search ) {
            $where .= ' AND json LIKE %s';
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sjs_results $where", $args ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, json FROM {$wpdb->prefix}sjs_results $where ORDER BY id $order LIMIT %d OFFSET %d",
            array_merge( $args, array( self::PER_PAGE, ( $this->get_pagenum() - 1 ) * self::PER_PAGE ) )
        ) );

        $this->items = array_map( function ( $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            return (object) array(
                'id'   => (int) $row->id,
                'data' => is_array( $data ) ? $data : array(),
            );
        }, $rows );

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'id' );
        $this->set_pagination_args( array(
            'total_items' => $total,
            'per_page'    => self::PER_PAGE,
        ) );
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="result[]" value="%d" />', $item->id );
    }

    protected function column_id( $item ) {
        $delete_url = wp_nonce_url(
            SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array( 'id' => $this->survey_id, 'action' => 'delete', 'result_id' => $item->id ) ),
            'surveyjs-delete-result_' . $item->id
        );
        $actions = array(
            'view'   => sprintf( '<a href="#" class="wp-sjs-view-result" data-result-id="%d">%s</a>', $item->id, esc_html__( 'View', 'surveyjs' ) ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url( $delete_url ),
                esc_js( __( 'This action CANNOT be undone! Are you ABSOLUTELY sure?', 'surveyjs' ) ),
                esc_html__( 'Delete', 'surveyjs' )
            ),
        );
        return sprintf( '<strong>#%d</strong>%s', $item->id, $this->row_actions( $actions ) );
    }

    protected function column_default( $item, $column_name ) {
        $question = $this->questions[ (int) substr( $column_name, strlen( 'question_' ) ) ];
        return esc_html( self::display_value( $question, $item->data[ $question['name'] ] ?? null ) );
    }

    public function get_items_data() {
        $data = array();
        foreach ( $this->items as $item ) {
            $data[ $item->id ] = (object) $item->data;
        }
        return $data;
    }

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
