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

    // The search field sits in the top bar, on the bulk actions row, instead of in a row of its own above it.
    protected function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            $this->search_box( __( 'Search Entries', 'surveyjs' ), 'surveyjs-result' );
        }
    }

    // The list is short: who and when, plus a one-line summary. All answers of the selected entry are in the
    // preview panel next to it.
    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'id'         => __( 'ID', 'surveyjs' ),
            'created_at' => __( 'Date', 'surveyjs' ),
            'status'     => __( 'Status', 'surveyjs' ),
            'summary'    => __( 'Summary', 'surveyjs' ),
        );
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
        esc_html_e( 'No entries found.', 'surveyjs' );
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

    // The list on the left, the preview of the selected entry on the right; the top and bottom bars (bulk
    // actions, search, pagination) stay full width above and below them.
    public function display() {
        ob_start();
        parent::display();
        $html = (string) ob_get_clean();
        ob_start();
        $this->render_panel();
        $panel = (string) ob_get_clean();
        $start = strpos( $html, '<table class="wp-list-table' );
        $end = strpos( $html, '</table>' );
        if ( false !== $start && false !== $end ) {
            $end += strlen( '</table>' );
            $html = substr( $html, 0, $start )
                . '<div class="wp-sjs-entries"><div class="wp-sjs-entries__list">' . substr( $html, $start, $end - $start ) . '</div>' . $panel . '</div>'
                . substr( $html, $end );
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP_List_Table output and the panel, escaped where they were built.
    }

    public function single_row( $item ) {
        $classes = array( 'wp-sjs-entries__row' );
        if ( 'unread' === $item->status ) {
            $classes[] = 'wp-sjs-entry-unread';
        }
        echo '<tr class="' . esc_attr( implode( ' ', $classes ) ) . '" data-entry-id="' . esc_attr( $item->id ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="result[]" value="%d" />', $item->id );
    }

    protected function column_id( $item ) {
        return sprintf( '<a href="#wp-sjs-entry-%1$d" class="wp-sjs-view-result" data-result-id="%1$d"><strong>#%1$d</strong></a>', $item->id );
    }

    // Status, delete and add-on actions for one entry, as links (the add-on adds its own through the filter).
    private function entry_actions( $item ) {
        $actions = array();
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
            esc_url( wp_nonce_url( $this->action_url( 'delete', $item->id ), 'surveyjs-delete-result_' . $item->id ) ),
            esc_js( __( 'This action CANNOT be undone! Are you ABSOLUTELY sure?', 'surveyjs' ) ),
            esc_html__( 'Delete', 'surveyjs' )
        );
        return apply_filters( 'surveyjs_entry_row_actions', $actions, $item, $this->survey_id );
    }

    // Date on the first line, time under it in secondary text, so the column stays narrow.
    protected function column_created_at( $item ) {
        $timestamp = strtotime( (string) $item->created_at . ' UTC' );
        if ( ! $timestamp || $timestamp <= 0 ) {
            return '&mdash;';
        }
        return sprintf(
            '<time datetime="%s">%s<br><span class="description">%s</span></time>',
            esc_attr( gmdate( 'c', $timestamp ) ),
            esc_html( wp_date( get_option( 'date_format' ), $timestamp ) ),
            esc_html( wp_date( get_option( 'time_format' ), $timestamp ) )
        );
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

    // The first two answers that read as short text (name, email, a choice), cut to two lines.
    protected function column_summary( $item ) {
        $parts = array();
        foreach ( $this->questions as $question ) {
            $value = $item->data[ $question['name'] ] ?? null;
            if ( ! is_scalar( $value ) || is_bool( $value ) || '' === trim( (string) $value ) || ( is_string( $value ) && 0 === strpos( $value, 'data:' ) ) ) {
                continue;
            }
            $parts[] = SurveyJS_Survey_Values::display_value( $question, $value );
            if ( count( $parts ) === 2 ) {
                break;
            }
        }
        $text = implode( ' · ', $parts );
        return '' === $text ? '<span class="description">&mdash;</span>' : sprintf( '<span class="wp-sjs-entries__clamp" title="%s">%s</span>', esc_attr( $text ), esc_html( $text ) );
    }

    protected function column_default( $item, $column_name ) {
        return '';
    }

    // The preview panel: one article per entry on this page; results.php shows the selected one.
    private function render_panel() {
        ?>
        <div class="wp-sjs-entries__panel" id="wp-sjs-entry-panel">
            <p class="wp-sjs-entries__empty description"><?php esc_html_e( 'Select an entry to see its answers.', 'surveyjs' ); ?></p>
            <?php foreach ( $this->items as $item ) : ?>
                <?php $this->render_entry( $item ); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_entry( $item ) {
        $timestamp = strtotime( (string) $item->created_at . ' UTC' );
        $actions = $this->entry_actions( $item );
        $answers = array();
        $unanswered = 0;
        foreach ( $this->questions as $question ) {
            $html = $this->answer_html( $question, $item->data[ $question['name'] ] ?? null );
            if ( '' === $html ) {
                $unanswered++;
                continue;
            }
            $answers[] = array( SurveyJS_Survey_Values::question_title( $question ), $html );
        }
        ?>
        <article class="wp-sjs-entry" id="wp-sjs-entry-<?php echo esc_attr( $item->id ); ?>" data-entry-id="<?php echo esc_attr( $item->id ); ?>" aria-labelledby="wp-sjs-entry-title-<?php echo esc_attr( $item->id ); ?>" hidden>
            <header class="wp-sjs-entry__header">
                <h2 class="wp-sjs-entry__title" id="wp-sjs-entry-title-<?php echo esc_attr( $item->id ); ?>"><?php /* translators: %d: entry ID */ echo esc_html( sprintf( __( 'Entry #%d', 'surveyjs' ), $item->id ) ); ?></h2>
                <span class="wp-sjs-entry__status" data-sjs-entry-status><?php echo esc_html( SurveyJS_Entries::status_label( $item->status, $item->last_page ) ); ?></span>
                <span class="wp-sjs-entry__nav">
                    <button type="button" class="button wp-sjs-button-icon" data-sjs-entry-nav="prev" aria-label="<?php esc_attr_e( 'Previous entry', 'surveyjs' ); ?>" title="<?php esc_attr_e( 'Previous entry', 'surveyjs' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
                    <button type="button" class="button wp-sjs-button-icon" data-sjs-entry-nav="next" aria-label="<?php esc_attr_e( 'Next entry', 'surveyjs' ); ?>" title="<?php esc_attr_e( 'Next entry', 'surveyjs' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
                </span>
            </header>
            <p class="wp-sjs-entry__meta">
                <?php if ( $timestamp && $timestamp > 0 ) : ?>
                    <time datetime="<?php echo esc_attr( gmdate( 'c', $timestamp ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ); ?></time>
                    &middot;
                <?php endif; ?>
                <?php echo str_replace( '<br>', ' &middot; ', $this->column_submitter( $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in column_submitter(). ?>
            </p>
            <p class="wp-sjs-entry__actions">
                <button type="button" class="button" data-sjs-entry-open="<?php echo esc_attr( $item->id ); ?>"><?php esc_html_e( 'View as form', 'surveyjs' ); ?></button>
                <span class="wp-sjs-entry__links"><?php echo implode( ' | ', $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- links built and escaped in entry_actions() and by add-ons. ?></span>
            </p>
            <?php if ( $answers ) : ?>
                <dl class="wp-sjs-entry__answers">
                    <?php foreach ( $answers as $answer ) : ?>
                        <dt><?php echo esc_html( $answer[0] ); ?></dt>
                        <dd><?php echo $answer[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built in answer_html(). ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php else : ?>
                <p class="description"><?php esc_html_e( 'This entry has no answers.', 'surveyjs' ); ?></p>
            <?php endif; ?>
            <?php if ( $unanswered && $answers ) : ?>
                <p class="description wp-sjs-entry__unanswered"><?php /* translators: %s: number of questions */ echo esc_html( sprintf( _n( '%s question not answered.', '%s questions not answered.', $unanswered, 'surveyjs' ), number_format_i18n( $unanswered ) ) ); ?></p>
            <?php endif; ?>
        </article>
        <?php
    }

    // One answer for the panel: file links, a drawn image, row-by-row lines for matrices, or the answer text.
    private function answer_html( $question, $value ) {
        if ( null === $value || '' === $value || array() === $value ) {
            return '';
        }
        $links = SurveyJS_Uploaded_Files::links( $value );
        if ( $links ) {
            return SurveyJS_Uploaded_Files::links_html( $links );
        }
        if ( is_string( $value ) && preg_match( '#^data:image/(png|jpe?g|gif|webp|svg\+xml)[;,]#i', $value ) ) {
            return sprintf( '<img class="wp-sjs-entries__image" src="%s" alt="" />', esc_attr( $value ) );
        }
        if ( is_array( $value ) && ! array_is_list( $value ) ) {
            $text = self::cell_text( $question, $value );
            if ( false === strpos( $text, '{' ) ) {
                return '<ul class="wp-sjs-entry__lines"><li>' . implode( '</li><li>', array_map( 'esc_html', explode( '; ', $text ) ) ) . '</li></ul>';
            }
        }
        // Dynamic panels: one line per panel, "Field: answer, Field: answer", with the titles of the panel's questions.
        if ( is_array( $value ) && array_is_list( $value ) && is_array( $value[0] ?? null ) && ! isset( $value[0]['name'] ) ) {
            $titles = self::texts( $question['templateElements'] ?? $question['columns'] ?? array() );
            $lines = array();
            foreach ( $value as $panel ) {
                $fields = array();
                foreach ( (array) $panel as $field => $answer ) {
                    if ( is_array( $answer ) ) {
                        $answer = array_is_list( $answer ) && ! array_filter( $answer, 'is_array' ) ? implode( ', ', array_map( 'strval', $answer ) ) : wp_json_encode( $answer );
                    } elseif ( is_bool( $answer ) ) {
                        $answer = $answer ? __( 'Yes', 'surveyjs' ) : __( 'No', 'surveyjs' );
                    }
                    $fields[] = ( $titles[ (string) $field ] ?? (string) $field ) . ': ' . $answer;
                }
                $lines[] = esc_html( implode( ', ', $fields ) );
            }
            return '<ul class="wp-sjs-entry__lines"><li>' . implode( '</li><li>', $lines ) . '</li></ul>';
        }
        $html = SurveyJS_Survey_Values::display_html( $question, $value );
        return '' === trim( wp_strip_all_tags( $html ) ) ? '' : nl2br( $html );
    }

    // Table-only text for an answer. Matrix-like answers (an object of row => value) read as "Row: Value; ...",
    // with row, column and choice texts from the question; lists of objects (dynamic panels and matrices) show
    // how many there are. Emails, CSV and webhooks keep SurveyJS_Survey_Values::display_value().
    private static function cell_text( $question, $value ) {
        if ( is_array( $value ) && $value && ! array_is_list( $value ) ) {
            $rows = self::texts( $question['rows'] ?? $question['items'] ?? array() );
            $columns = self::texts( $question['columns'] ?? $question['choices'] ?? array() );
            $parts = array();
            $word = function ( $one ) use ( $columns ) {
                return is_bool( $one ) ? ( $one ? __( 'Yes', 'surveyjs' ) : __( 'No', 'surveyjs' ) ) : ( $columns[ (string) $one ] ?? (string) $one );
            };
            foreach ( $value as $key => $answer ) {
                $row = $rows[ (string) $key ] ?? (string) $key;
                if ( is_scalar( $answer ) || ( is_array( $answer ) && array_is_list( $answer ) && ! array_filter( $answer, 'is_array' ) ) ) {
                    $parts[] = $row . ': ' . implode( ', ', array_map( $word, (array) $answer ) );
                    continue;
                }
                // Multi-select matrix: a row holds column => answer pairs, read as "Row: Column value, Column value".
                if ( is_array( $answer ) && ! array_is_list( $answer ) ) {
                    $cells = array();
                    foreach ( $answer as $column => $cell ) {
                        if ( ! is_scalar( $cell ) && ! ( is_array( $cell ) && array_is_list( $cell ) && ! array_filter( $cell, 'is_array' ) ) ) {
                            return SurveyJS_Survey_Values::display_value( $question, $value );
                        }
                        $cells[] = ( $columns[ (string) $column ] ?? (string) $column ) . ' ' . implode( ', ', array_map( function ( $one ) {
                            return is_bool( $one ) ? ( $one ? __( 'Yes', 'surveyjs' ) : __( 'No', 'surveyjs' ) ) : (string) $one;
                        }, (array) $cell ) );
                    }
                    $parts[] = $row . ': ' . implode( ', ', $cells );
                    continue;
                }
                return SurveyJS_Survey_Values::display_value( $question, $value );
            }
            return implode( '; ', $parts );
        }
        if ( is_array( $value ) && $value && array_is_list( $value ) && is_array( $value[0] ) && ! isset( $value[0]['name'] ) ) {
            /* translators: %s: number of items, e.g. rows of a dynamic matrix */
            return sprintf( _n( '%s item', '%s items', count( $value ), 'surveyjs' ), number_format_i18n( count( $value ) ) );
        }
        return SurveyJS_Survey_Values::display_value( $question, $value );
    }

    // value => text for rows, columns, items or choices, which may be plain strings or { value, text } objects.
    private static function texts( $list ) {
        $texts = array();
        foreach ( (array) $list as $entry ) {
            if ( is_array( $entry ) ) {
                $key = (string) ( $entry['value'] ?? $entry['name'] ?? '' );
                $text = $entry['text'] ?? $entry['title'] ?? $key;
                $texts[ $key ] = is_array( $text ) ? (string) ( $text['default'] ?? reset( $text ) ) : (string) $text;
            } elseif ( is_scalar( $entry ) ) {
                $texts[ (string) $entry ] = (string) $entry;
            }
        }
        return $texts;
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
