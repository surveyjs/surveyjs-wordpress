<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SurveyJS_Results_List_Table extends WP_List_Table {

    const PER_PAGE = 20;

    private $survey_id;
    private $questions;
    private $counts;

    public function __construct( $survey_id, $questions ) {
        parent::__construct( array(
            'singular' => 'result',
            'plural'   => 'results',
            'ajax'     => false,
        ) );
        $this->survey_id = $survey_id;
        $this->questions = $questions;
    }

    public static function current_status() {
        $status = sanitize_key( $_REQUEST['status'] ?? '' );
        return in_array( $status, SurveyJS_Entries::STATUSES, true ) ? $status : '';
    }

    public function get_columns() {
        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'id'         => __( 'ID', 'surveyjs' ),
            'created_at' => __( 'Date', 'surveyjs' ),
            'status'     => __( 'Status', 'surveyjs' ),
            'submitter'  => __( 'Submitted by', 'surveyjs' ),
        );
        foreach ( $this->questions as $index => $question ) {
            $columns[ 'question_' . $index ] = esc_html( SurveyJS_Survey_Values::question_title( $question ) );
        }
        return $columns;
    }

    protected function get_sortable_columns() {
        return array(
            'id'         => array( 'id', true ),
            'created_at' => array( 'created_at', true ),
        );
    }

    protected function get_views() {
        $current = self::current_status();
        $counts = $this->counts;
        $views = array( '' => array( __( 'All', 'surveyjs' ), $counts['unread'] + $counts['read'] ) );
        foreach ( SurveyJS_Entries::statuses() as $status => $label ) {
            $views[ $status ] = array( $label, $counts[ $status ] );
        }
        $links = array();
        foreach ( $views as $status => $view ) {
            $url = SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array_filter( array( 'id' => $this->survey_id, 'status' => $status ) ) );
            $links[ '' === $status ? 'all' : $status ] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                esc_url( $url ),
                $current === $status ? ' class="current" aria-current="page"' : '',
                esc_html( $view[0] ),
                esc_html( number_format_i18n( $view[1] ) )
            );
        }
        return $links;
    }

    protected function get_bulk_actions() {
        $actions = 'spam' === self::current_status()
            ? array( 'unspam' => __( 'Not Spam', 'surveyjs' ) )
            : array(
                'read'   => __( 'Mark as Read', 'surveyjs' ),
                'unread' => __( 'Mark as Unread', 'surveyjs' ),
                'spam'   => __( 'Mark as Spam', 'surveyjs' ),
            );
        return $actions + array( 'delete' => __( 'Delete', 'surveyjs' ) );
    }

    public function no_items() {
        esc_html_e( 'No results found.', 'surveyjs' );
    }

    public function prepare_items() {
        global $wpdb;

        $this->counts = SurveyJS_Entries::counts( $this->survey_id );
        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $order = 'asc' === sanitize_key( $_REQUEST['order'] ?? '' ) ? 'ASC' : 'DESC';
        $orderby = 'id' === sanitize_key( $_REQUEST['orderby'] ?? '' ) ? "id $order" : "created_at $order, id $order";
        $status = self::current_status();

        $where = 'WHERE form_id = %d';
        $args = array( $this->survey_id );
        if ( '' !== $status ) {
            $where .= ' AND status = %s';
            $args[] = $status;
        } else {
            $where .= " AND status <> 'spam'";
        }
        if ( '' !== $search ) {
            $where .= ' AND json LIKE %s';
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $table = SurveyJS_Entries::table();
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table $where", $args ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, json, status, user_id, ip, created_at FROM $table $where ORDER BY $orderby LIMIT %d OFFSET %d",
            array_merge( $args, array( self::PER_PAGE, ( $this->get_pagenum() - 1 ) * self::PER_PAGE ) )
        ) );

        $this->items = array_map( function ( $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            return (object) array(
                'id'         => (int) $row->id,
                'status'     => $row->status,
                'user_id'    => (int) $row->user_id,
                'ip'         => (string) $row->ip,
                'created_at' => $row->created_at,
                'data'       => is_array( $data ) ? $data : array(),
            );
        }, $rows );

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'id' );
        $this->set_pagination_args( array(
            'total_items' => $total,
            'per_page'    => self::PER_PAGE,
        ) );
    }

    public function single_row( $item ) {
        echo '<tr' . ( 'unread' === $item->status ? ' class="wp-sjs-entry-unread"' : '' ) . '>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="result[]" value="%d" />', $item->id );
    }

    protected function column_id( $item ) {
        $delete_url = wp_nonce_url(
            $this->action_url( 'delete', $item->id ),
            'surveyjs-delete-result_' . $item->id
        );
        $actions = array(
            'view' => sprintf( '<a href="#" class="wp-sjs-view-result" data-result-id="%d">%s</a>', $item->id, esc_html__( 'View', 'surveyjs' ) ),
        );
        if ( 'spam' === $item->status ) {
            $status_actions = array( 'unspam' => __( 'Not Spam', 'surveyjs' ) );
        } else {
            $status_actions = 'unread' === $item->status
                ? array( 'read' => __( 'Mark as Read', 'surveyjs' ) )
                : array( 'unread' => __( 'Mark as Unread', 'surveyjs' ) );
            $status_actions['spam'] = __( 'Spam', 'surveyjs' );
        }
        foreach ( $status_actions as $action => $label ) {
            $url = wp_nonce_url( $this->action_url( $action, $item->id ), 'surveyjs-result-status_' . $item->id );
            $actions[ $action ] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
        }
        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $delete_url ),
            esc_js( __( 'This action CANNOT be undone! Are you ABSOLUTELY sure?', 'surveyjs' ) ),
            esc_html__( 'Delete', 'surveyjs' )
        );
        return sprintf( '<strong>#%d</strong>%s', $item->id, $this->row_actions( $actions ) );
    }

    protected function column_created_at( $item ) {
        $date = SurveyJS_Entries::format_date( $item->created_at );
        return '' !== $date ? esc_html( $date ) : '&mdash;';
    }

    protected function column_status( $item ) {
        return esc_html( SurveyJS_Entries::statuses()[ $item->status ] ?? $item->status );
    }

    protected function column_submitter( $item ) {
        $user = $item->user_id ? get_userdata( $item->user_id ) : false;
        $name = $user
            ? sprintf( '<a href="%s">%s</a>', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->display_name ) )
            : esc_html__( 'Guest', 'surveyjs' );
        return '' !== $item->ip ? $name . '<br><span class="description">' . esc_html( $item->ip ) . '</span>' : $name;
    }

    protected function column_default( $item, $column_name ) {
        $question = $this->questions[ (int) substr( $column_name, strlen( 'question_' ) ) ];
        $value = $item->data[ $question['name'] ] ?? null;
        $links = SurveyJS_Uploaded_Files::links( $value );
        if ( $links ) {
            return SurveyJS_Uploaded_Files::links_html( $links );
        }
        return esc_html( SurveyJS_Survey_Values::display_value( $question, $value ) );
    }

    private function action_url( $action, $result_id ) {
        return SurveyJS_Surveys_List_Table::page_url( 'surveyjs_results', array_filter( array(
            'id'        => $this->survey_id,
            'status'    => self::current_status(),
            'action'    => $action,
            'result_id' => $result_id,
        ) ) );
    }

    public function get_items_data() {
        $data = array();
        foreach ( $this->items as $item ) {
            $data[ $item->id ] = (object) SurveyJS_Uploaded_Files::with_admin_urls( $item->data );
        }
        return $data;
    }
}
