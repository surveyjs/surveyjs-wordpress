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
        esc_html_e( 'No forms found.', 'surveyjs' );
    }

    public function prepare_items() {
        global $wpdb;

        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $orderby_columns = array( 'name' => 'p.post_title', 'results' => 'results' );
        $orderby = $orderby_columns[ sanitize_key( $_REQUEST['orderby'] ?? '' ) ] ?? 'p.ID';
        $order = 'desc' === sanitize_key( $_REQUEST['order'] ?? '' ) ? 'DESC' : 'ASC';

        $where = "WHERE p.post_type = %s AND p.post_status = 'publish'";
        $args = array( SurveyJS_Forms::POST_TYPE );
        if ( '' !== $search ) {
            $where .= ' AND p.post_title LIKE %s';
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} p $where", $args ) );

        $entries = SurveyJS_Entries::table();
        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID AS id, p.post_title AS name, COUNT(r.id) AS entries, SUM(CASE WHEN r.status NOT IN ( 'spam', 'partial' ) THEN 1 ELSE 0 END) AS results
            FROM {$wpdb->posts} p
            LEFT JOIN $entries r ON r.form_id = p.ID
            $where
            GROUP BY p.ID, p.post_title
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
                esc_js( self::delete_confirmation( (int) $item->entries ) ),
                esc_html__( 'Delete', 'surveyjs' )
            ),
        );
        $actions = apply_filters( 'surveyjs_form_row_actions', $actions, $item );

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s',
            esc_url( $edit_url ),
            esc_html( $item->name ),
            $this->row_actions( $actions )
        );
    }

    private static function delete_confirmation( $entries ) {
        if ( ! $entries ) {
            return __( 'Delete this form? This cannot be undone.', 'surveyjs' );
        }
        return sprintf(
            /* translators: %s: number of entries */
            _n( 'Delete this form and its %s entry? Files uploaded with the entry will be deleted too. This cannot be undone.', 'Delete this form and its %s entries? Files uploaded with these entries will be deleted too. This cannot be undone.', $entries, 'surveyjs' ),
            number_format_i18n( $entries )
        );
    }

    protected function column_results( $item ) {
        $results_url = self::page_url( 'surveyjs_results', array( 'id' => $item->id, 'name' => $item->name ) );
        $html = sprintf( '<a href="%s">%d</a>', esc_url( $results_url ), $item->results );
        $expiring = SurveyJS_Entries::count_partial_deleted_soon( $item->id );
        if ( $expiring ) {
            /* translators: %s: number of partial entries */
            $text = sprintf( _n( '%s partial entry will be deleted soon', '%s partial entries will be deleted soon', $expiring, 'surveyjs' ), number_format_i18n( $expiring ) );
            $html .= sprintf( '<br><a href="%s" style="color:#b32d2e">%s</a>', esc_url( self::page_url( 'surveyjs_results', array( 'id' => $item->id, 'status' => SurveyJS_Entries::PARTIAL ) ) ), esc_html( $text ) );
        }
        return $html;
    }

    public static function page_url( $page, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
    }
}
