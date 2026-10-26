<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SurveyJS_Surveys_List_Table extends WP_List_Table {

    const PER_PAGE = 20;

    public function __construct() {
        parent::__construct( array(
            'singular' => 'survey',
            'plural'   => 'surveys',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'        => '<input type="checkbox" />',
            'name'      => __( 'Name', 'surveyjs' ),
            'results'   => __( 'Results', 'surveyjs' ),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'name'    => array( 'name', false ),
            'results' => array( 'results', true ),
        );
    }

    protected function get_bulk_actions() {
        return array( 'delete' => __( 'Delete', 'surveyjs' ) );
    }

    public function no_items() {
        esc_html_e( 'No surveys found.', 'surveyjs' );
    }

    public function prepare_items() {
        global $wpdb;

        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $orderby_columns = array( 'name' => 's.name', 'results' => 'results' );
        $orderby = $orderby_columns[ sanitize_key( $_REQUEST['orderby'] ?? '' ) ] ?? 's.id';
        $order = 'desc' === sanitize_key( $_REQUEST['order'] ?? '' ) ? 'DESC' : 'ASC';

        $where = '';
        $args = array();
        if ( '' !== $search ) {
            $where = 'WHERE s.name LIKE %s';
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}sjs_my_surveys s $where";
        $total = (int) $wpdb->get_var( $args ? $wpdb->prepare( $count_sql, $args ) : $count_sql );

        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.id, s.name, COUNT(r.id) AS results
            FROM {$wpdb->prefix}sjs_my_surveys s
            LEFT JOIN {$wpdb->prefix}sjs_results r ON r.surveyId = s.id
            $where
            GROUP BY s.id, s.name
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
            array_merge( $args, array( self::PER_PAGE, ( $this->get_pagenum() - 1 ) * self::PER_PAGE ) )
        ) );

        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'name' );
        $this->set_pagination_args( array(
            'total_items' => $total,
            'per_page'    => self::PER_PAGE,
        ) );
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="survey[]" value="%d" />', $item->id );
    }

    protected function column_name( $item ) {
        $edit_url = self::page_url( 'surveyjs_editor', array( 'id' => $item->id, 'name' => $item->name ) );
        $results_url = self::page_url( 'surveyjs_results', array( 'id' => $item->id, 'name' => $item->name ) );
        $clone_url = wp_nonce_url( self::page_url( 'surveyjs-main-menu', array( 'action' => 'clone', 'id' => $item->id ) ), 'surveyjs-clone-survey_' . $item->id );
        $delete_url = wp_nonce_url( self::page_url( 'surveyjs-main-menu', array( 'action' => 'delete', 'id' => $item->id ) ), 'surveyjs-delete-survey_' . $item->id );

        $actions = array(
            'edit'    => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'surveyjs' ) ),
            'results' => sprintf( '<a href="%s">%s</a>', esc_url( $results_url ), esc_html__( 'Results', 'surveyjs' ) ),
            'clone'   => sprintf( '<a href="%s">%s</a>', esc_url( $clone_url ), esc_html__( 'Clone', 'surveyjs' ) ),
            'delete'  => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url( $delete_url ),
                esc_js( __( 'This action CANNOT be undone! Are you ABSOLUTELY sure?', 'surveyjs' ) ),
                esc_html__( 'Delete', 'surveyjs' )
            ),
        );

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            esc_url( $edit_url ),
            esc_html( $item->name ),
            $this->row_actions( $actions )
        );
    }

    protected function column_results( $item ) {
        $results_url = self::page_url( 'surveyjs_results', array( 'id' => $item->id, 'name' => $item->name ) );
        return sprintf( '<a href="%s">%d</a>', esc_url( $results_url ), $item->results );
    }

    public static function page_url( $page, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
    }
}
