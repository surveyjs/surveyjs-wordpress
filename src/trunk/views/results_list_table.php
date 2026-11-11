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
            if ( SurveyJS_Entries::PARTIAL !== $status || $counts[ $status ] || $current === $status ) {
                $views[ $status ] = array( $label, $counts[ $status ] );
            }
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
        $status = self::current_status();
        if ( 'spam' === $status ) {
            $actions = array( 'unspam' => __( 'Not Spam', 'surveyjs' ) );
        } elseif ( SurveyJS_Entries::PARTIAL === $status ) {
            $actions = array(
                'keep'   => __( 'Keep', 'surveyjs' ),
                'unkeep' => __( 'Stop Keeping', 'surveyjs' ),
                'spam'   => __( 'Mark as Spam', 'surveyjs' ),
            );
        } else {
            $actions = array(
                'read'   => __( 'Mark as Read', 'surveyjs' ),
                'unread' => __( 'Mark as Unread', 'surveyjs' ),
                'spam'   => __( 'Mark as Spam', 'surveyjs' ),
            );
        }
        return apply_filters( 'surveyjs_entries_bulk_actions', $actions + array( 'delete' => __( 'Delete', 'surveyjs' ) ), $status, $this->survey_id );
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
        $entry = absint( $_REQUEST['entry'] ?? 0 );

        $table = SurveyJS_Entries::table();
        $filter_by_status = ! $entry && '' !== $status ? 1 : 0;
        $hide_spam_and_partial = ! $entry && '' === $status ? 1 : 0;
        $like = '%' . $wpdb->esc_like( $search ) . '%';

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM %i
            WHERE form_id = %d AND ( %d = 0 OR id = %d ) AND ( %d = 0 OR status = %s ) AND ( %d = 0 OR status NOT IN ( 'spam', 'partial' ) ) AND ( %s = '' OR json LIKE %s )",
            $table,
            $this->survey_id,
            $entry,
            $entry,
            $filter_by_status,
            $status,
            $hide_spam_and_partial,
            $search,
            $like
        ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, json, status, user_id, ip, created_at, last_page, kept, token_hash IS NOT NULL AS unfinished FROM %i
            WHERE form_id = %d AND ( %d = 0 OR id = %d ) AND ( %d = 0 OR status = %s ) AND ( %d = 0 OR status NOT IN ( 'spam', 'partial' ) ) AND ( %s = '' OR json LIKE %s )
            ORDER BY " . sanitize_sql_orderby( $orderby ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- validated by sanitize_sql_orderby(), which WPCS does not list as SQL escaping; placeholders cannot hold a sort clause.
            ' LIMIT %d OFFSET %d',
            $table,
            $this->survey_id,
            $entry,
            $entry,
            $filter_by_status,
            $status,
            $hide_spam_and_partial,
            $search,
            $like,
            self::PER_PAGE,
            ( $this->get_pagenum() - 1 ) * self::PER_PAGE
        ) );

        $this->items = array_map( function ( $row ) {
            $data = SurveyJS_Client::decode_survey_json( $row->json );
            return (object) array(
                'id'         => (int) $row->id,
                'status'     => $row->status,
                'user_id'    => (int) $row->user_id,
                'ip'         => (string) $row->ip,
                'created_at' => $row->created_at,
                'last_page'  => (int) $row->last_page,
                'kept'       => (bool) $row->kept,
                'unfinished' => (bool) $row->unfinished,
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
        } elseif ( SurveyJS_Entries::PARTIAL === $item->status ) {
            $status_actions = $item->kept
                ? array( 'unkeep' => __( 'Stop Keeping', 'surveyjs' ) )
                : array( 'keep' => __( 'Keep', 'surveyjs' ) );
            $status_actions['spam'] = __( 'Spam', 'surveyjs' );
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
        $actions = apply_filters( 'surveyjs_entry_row_actions', $actions, $item, $this->survey_id );
        return sprintf( '<strong>#%d</strong>%s', $item->id, $this->row_actions( $actions ) );
    }

    protected function column_created_at( $item ) {
        $date = SurveyJS_Entries::format_date( $item->created_at );
        return '' !== $date ? esc_html( $date ) : '&mdash;';
    }

    protected function column_status( $item ) {
        $label = esc_html( SurveyJS_Entries::status_label( $item->status, $item->last_page ) );
        if ( SurveyJS_Entries::PARTIAL !== $item->status || ! $item->unfinished ) {
            return $label;
        }
        if ( $item->kept ) {
            return $label . '<br><span class="description">' . esc_html__( 'Kept', 'surveyjs' ) . '</span>';
        }
        $deletion_time = SurveyJS_Entries::partial_deletion_time( $item->created_at, $item->kept );
        if ( ! SurveyJS_Entries::partial_deletion_soon( $deletion_time ) ) {
            return $label;
        }
        /* translators: %s: date when the partial entry will be deleted */
        $text = sprintf( __( 'Will be deleted on %s', 'surveyjs' ), wp_date( get_option( 'date_format' ), $deletion_time ) );
        return $label . '<br><span class="wp-sjs-entry-expiring">' . esc_html( $text ) . '</span>';
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
