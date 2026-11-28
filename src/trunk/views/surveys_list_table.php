<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SurveyJS_Surveys_List_Table extends WP_List_Table {

    const PER_PAGE = 20;

    private $trash_count = 0;

    public function __construct() {
        parent::__construct( array(
            'singular' => 'survey',
            'plural'   => 'surveys',
            'ajax'     => false,
        ) );
    }

    // The search field sits in the top bar, on the bulk actions row, instead of in a row of its own above it.
    protected function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            $this->search_box( __( 'Search Forms', 'surveyjs' ), 'surveyjs-survey' );
        }
        if ( self::is_trash() && $this->has_items() ) {
            echo '<div class="alignleft actions">';
            submit_button( __( 'Empty Trash', 'surveyjs' ), 'apply', 'delete_all', false );
            echo '</div>';
        }
    }

    public static function is_trash() {
        return SurveyJS_Forms::trash_enabled() && 'trash' === sanitize_key( $_REQUEST['post_status'] ?? '' );
    }

    protected function get_views() {
        if ( ! $this->trash_count && ! self::is_trash() ) {
            return array();
        }
        global $wpdb;
        $all = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'", SurveyJS_Forms::POST_TYPE ) );
        $views = array(
            'all'   => array( __( 'All', 'surveyjs' ), $all, array(), ! self::is_trash() ),
            'trash' => array( __( 'Trash', 'surveyjs' ), $this->trash_count, array( 'post_status' => 'trash' ), self::is_trash() ),
        );
        $links = array();
        foreach ( $views as $key => $view ) {
            $links[ $key ] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
                esc_url( self::page_url( 'surveyjs-main-menu', $view[2] ) ),
                $view[3] ? ' class="current" aria-current="page"' : '',
                esc_html( $view[0] ),
                esc_html( number_format_i18n( $view[1] ) )
            );
        }
        return $links;
    }

    public function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'name'      => __( 'Name', 'surveyjs' ),
            'results'   => __( 'Entries', 'surveyjs' ),
        );
        if ( SurveyJS_Form_Stats::enabled() ) {
            $columns['views'] = __( 'Views', 'surveyjs' );
            $columns['conversion'] = __( 'Conversion', 'surveyjs' );
        }
        $columns['modified'] = __( 'Last modified', 'surveyjs' );
        return $columns;
    }

    protected function get_sortable_columns() {
        return array(
            'name'       => array( 'name', false ),
            'results'    => array( 'results', true ),
            'views'      => array( 'views', true ),
            'conversion' => array( 'conversion', true ),
            'modified'   => array( 'modified', true, '', '', 'desc' ),
        );
    }

    protected function get_bulk_actions() {
        if ( self::is_trash() ) {
            return array(
                'untrash' => __( 'Restore', 'surveyjs' ),
                'delete'  => __( 'Delete Permanently', 'surveyjs' ),
            );
        }
        if ( ! SurveyJS_Forms::trash_enabled() ) {
            return array( 'delete' => __( 'Delete Permanently', 'surveyjs' ) );
        }
        return array( 'trash' => __( 'Move to Trash', 'surveyjs' ) );
    }

    public function no_items() {
        if ( self::is_trash() ) {
            esc_html_e( 'No forms found in Trash.', 'surveyjs' );
        } else {
            esc_html_e( 'No forms found.', 'surveyjs' );
        }
    }

    public function prepare_items() {
        global $wpdb;

        $status = self::is_trash() ? 'trash' : 'publish';
        $this->trash_count = SurveyJS_Forms::trash_enabled() ? count( SurveyJS_Forms::trashed() ) : 0;

        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $orderby_columns = array( 'name' => 'name', 'results' => 'results', 'modified' => 'modified_gmt' );
        if ( SurveyJS_Form_Stats::enabled() ) {
            $orderby_columns += array( 'views' => 'views', 'conversion' => 'conversion_rate' );
        }
        $requested = sanitize_key( $_REQUEST['orderby'] ?? '' );
        if ( isset( $orderby_columns[ $requested ] ) ) {
            $orderby = $orderby_columns[ $requested ];
            $order = 'desc' === sanitize_key( $_REQUEST['order'] ?? '' ) ? 'DESC' : 'ASC';
        } else {
            $orderby = 'modified_gmt';
            $order = 'DESC';
        }

        $like = '%' . $wpdb->esc_like( $search ) . '%';

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            WHERE p.post_type = %s AND p.post_status = %s AND ( %s = '' OR p.post_title LIKE %s )",
            SurveyJS_Forms::POST_TYPE,
            $status,
            $search,
            $like
        ) );

        $entries = SurveyJS_Entries::table();
        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID AS id, p.post_title AS name, p.post_modified_gmt AS modified_gmt, COUNT(r.id) AS entries, SUM(CASE WHEN r.status NOT IN ( 'spam', 'partial' ) THEN 1 ELSE 0 END) AS results,
                COALESCE( MAX(s.views), 0 ) AS views, COALESCE( MAX(s.submissions), 0 ) AS submissions, MAX(s.submissions) / NULLIF( MAX(s.views), 0 ) AS conversion_rate
            FROM {$wpdb->posts} p
            LEFT JOIN %i r ON r.form_id = p.ID
            LEFT JOIN ( SELECT form_id, SUM(views) AS views, SUM(submissions) AS submissions FROM %i GROUP BY form_id ) s ON s.form_id = p.ID
            WHERE p.post_type = %s AND p.post_status = %s AND ( %s = '' OR p.post_title LIKE %s )
            GROUP BY p.ID, p.post_title, p.post_modified_gmt
            ORDER BY " . sanitize_sql_orderby( $orderby . ' ' . $order . ', id ' . $order ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- validated by sanitize_sql_orderby(), which WPCS does not list as SQL escaping; placeholders cannot hold a sort clause.
            ' LIMIT %d OFFSET %d',
            $entries,
            SurveyJS_Form_Stats::table(),
            SurveyJS_Forms::POST_TYPE,
            $status,
            $search,
            $like,
            self::PER_PAGE,
            ( $this->get_pagenum() - 1 ) * self::PER_PAGE
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
        if ( self::is_trash() ) {
            return $this->column_name_in_trash( $item );
        }
        $edit_url = self::page_url( 'surveyjs_editor', array( 'id' => $item->id, 'name' => $item->name ) );
        $preview_url = SurveyJS_SurveyJS::form_preview_url( $item->id );
        $results_url = self::page_url( 'surveyjs_results', array( 'id' => $item->id, 'name' => $item->name ) );
        $clone_url = wp_nonce_url( self::page_url( 'surveyjs-main-menu', array( 'action' => 'clone', 'id' => $item->id ) ), 'surveyjs-clone-survey_' . $item->id );
        $trash_url = wp_nonce_url( self::page_url( 'surveyjs-main-menu', array( 'action' => 'trash', 'id' => $item->id ) ), 'surveyjs-trash-survey_' . $item->id );

        $actions = array(
            'edit'    => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'surveyjs' ) ),
            'full_preview' => sprintf( '<a href="%s">%s</a>', esc_url( $preview_url ), esc_html__( 'Preview', 'surveyjs' ) ),
            'results' => sprintf( '<a href="%s">%s</a>', esc_url( $results_url ), esc_html__( 'Entries', 'surveyjs' ) ),
            'embed'   => sprintf( '<a href="%s">%s</a>', esc_url( SurveyJS_Embed::create_page_url( $item->id ) ), esc_html__( 'Create a page', 'surveyjs' ) ),
            'clone'   => sprintf( '<a href="%s">%s</a>', esc_url( $clone_url ), esc_html__( 'Duplicate', 'surveyjs' ) ),
            'template' => sprintf( '<a href="%s">%s</a>', esc_url( SurveyJS_Templates::save_url( $item->id ) ), esc_html__( 'Save as Template', 'surveyjs' ) ),
            'trash'   => sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $trash_url ), esc_html__( 'Trash', 'surveyjs' ) ),
        );
        if ( ! SurveyJS_Forms::trash_enabled() ) {
            unset( $actions['trash'] );
            $actions['delete'] = self::delete_link( $item );
        }
        if ( ! $preview_url ) {
            unset( $actions['full_preview'] );
        }
        $actions = apply_filters( 'surveyjs_form_row_actions', $actions, $item );

        $ai_draft = SurveyJS_AI_Drafts::get( $item->id );
        $unsaved_new = $ai_draft && SurveyJS_AI_Drafts::is_unsaved_new_form( SurveyJS_Forms::find( $item->id ) );
        $state = $ai_draft ? sprintf(
            ' — <span class="post-state" title="%s">%s</span>',
            $unsaved_new ? esc_attr__( 'An AI app created this form. It is not shown on the site and can\'t be added to pages until you open it, review it and click Save.', 'surveyjs' ) : esc_attr__( 'Open the form to review the AI changes and click Save to publish them.', 'surveyjs' ),
            $unsaved_new ? esc_html__( 'AI draft — not saved yet', 'surveyjs' ) : esc_html__( 'AI changes to review', 'surveyjs' )
        ) : '';

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a>%s</strong>%s',
            esc_url( $edit_url ),
            esc_html( $item->name ),
            $state,
            $this->row_actions( $actions )
        );
    }

    private function column_name_in_trash( $item ) {
        $untrash_url = wp_nonce_url( self::page_url( 'surveyjs-main-menu', array( 'action' => 'untrash', 'id' => $item->id, 'post_status' => 'trash' ) ), 'surveyjs-untrash-survey_' . $item->id );
        $actions = array(
            'untrash' => sprintf( '<a href="%s">%s</a>', esc_url( $untrash_url ), esc_html__( 'Restore', 'surveyjs' ) ),
            'delete'  => self::delete_link( $item, array( 'post_status' => 'trash' ) ),
        );
        return sprintf( '<strong>%s</strong>%s', esc_html( $item->name ), $this->row_actions( $actions ) );
    }

    private static function delete_link( $item, $args = array() ) {
        $delete_url = wp_nonce_url( self::page_url( 'surveyjs-main-menu', array( 'action' => 'delete', 'id' => $item->id ) + $args ), 'surveyjs-delete-survey_' . $item->id );
        return sprintf(
            '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $delete_url ),
            esc_js( self::delete_confirmation( (int) $item->entries ) ),
            esc_html__( 'Delete Permanently', 'surveyjs' )
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
        if ( self::is_trash() ) {
            return esc_html( number_format_i18n( (int) $item->results ) );
        }
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

    protected function column_views( $item ) {
        return esc_html( number_format_i18n( (int) $item->views ) );
    }

    protected function column_conversion( $item ) {
        $title = sprintf(
            /* translators: 1: number of submissions, 2: number of views */
            _n( '%1$s submission from %2$s views', '%1$s submissions from %2$s views', (int) $item->submissions, 'surveyjs' ),
            number_format_i18n( (int) $item->submissions ),
            number_format_i18n( (int) $item->views )
        );
        return sprintf( '<span title="%s">%s</span>', esc_attr( $title ), esc_html( SurveyJS_Form_Stats::format_conversion( (int) $item->views, (int) $item->submissions ) ) );
    }

    protected function column_modified( $item ) {
        if ( empty( $item->modified_gmt ) || '0000-00-00 00:00:00' === $item->modified_gmt ) {
            return '&mdash;';
        }
        $timestamp = strtotime( $item->modified_gmt . ' UTC' );
        return sprintf(
            '<time datetime="%s">%s</time>',
            esc_attr( gmdate( 'c', $timestamp ) ),
            esc_html( sprintf(
                /* translators: 1: date, 2: time */
                __( '%1$s at %2$s', 'surveyjs' ),
                wp_date( get_option( 'date_format' ), $timestamp ),
                wp_date( get_option( 'time_format' ), $timestamp )
            ) )
        );
    }

    public static function page_url( $page, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
    }
}
