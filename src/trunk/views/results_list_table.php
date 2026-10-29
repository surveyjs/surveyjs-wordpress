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
            $columns[ 'question_' . $index ] = esc_html( SurveyJS_Survey_Values::question_title( $question ) );
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
        return esc_html( SurveyJS_Survey_Values::display_value( $question, $item->data[ $question['name'] ] ?? null ) );
    }

    public function get_items_data() {
        $data = array();
        foreach ( $this->items as $item ) {
            $data[ $item->id ] = (object) $item->data;
        }
        return $data;
    }
}
