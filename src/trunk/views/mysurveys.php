<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_MySurveys {

    function __construct() {
    }

    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require_once __DIR__ . '/surveys_list_table.php';
        $list_url = SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu' );

        $action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );
        if ( '' === $action || '-1' === $action ) {
            $action = sanitize_key( wp_unslash( $_REQUEST['action2'] ?? '' ) );
        }

        if ( 'clone' === $action ) {
            $id = absint( $_GET['id'] ?? 0 );
            check_admin_referer( 'surveyjs-clone-survey_' . $id );
            $row = SurveyJS_Forms::find( $id );
            if ( $row ) {
                SurveyJS_Forms::create( array(
                    'name' => sanitize_text_field( 'Copy of ' . $row->name ),
                    'json' => $row->json,
                    'theme' => $row->theme,
                    'settings' => $row->settings,
                ) );
            }
            wp_safe_redirect( add_query_arg( 'message', 'cloned', $list_url ) );
            exit;
        }

        $in_trash = SurveyJS_Surveys_List_Table::is_trash();
        if ( $in_trash ) {
            $list_url = add_query_arg( 'post_status', 'trash', $list_url );
        }

        if ( isset( $_REQUEST['delete_all'] ) && $in_trash ) {
            check_admin_referer( 'bulk-surveys' );
            $deleted = count( array_filter( array_map( array( 'SurveyJS_Forms', 'delete' ), wp_list_pluck( SurveyJS_Forms::trashed(), 'id' ) ) ) );
            wp_safe_redirect( add_query_arg( array( 'message' => 'deleted', 'count' => $deleted ), $list_url ) );
            exit;
        }

        $handlers = array(
            'trash'   => array( 'SurveyJS_Forms', 'trash' ),
            'untrash' => array( 'SurveyJS_Forms', 'untrash' ),
            'delete'  => array( 'SurveyJS_Forms', 'delete' ),
        );
        if ( isset( $handlers[ $action ] ) ) {
            $ids = self::requested_ids( $action );
            if ( ! $ids ) {
                wp_safe_redirect( $list_url );
                exit;
            }
            $done = array_values( array_filter( $ids, $handlers[ $action ] ) );
            $messages = array( 'trash' => 'trashed', 'untrash' => 'untrashed', 'delete' => 'deleted' );
            $args = array( 'message' => $messages[ $action ], 'count' => count( $done ) );
            if ( 'trash' === $action && $done ) {
                $args['ids'] = implode( ',', $done );
            }
            wp_safe_redirect( add_query_arg( $args, $list_url ) );
            exit;
        }

        if ( ! empty( $_GET['_wp_http_referer'] ) ) {
            wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) );
            exit;
        }
    }

    public static function die_not_found( $id ) {
        if ( SurveyJS_Forms::is_trashed( $id ) ) {
            require_once __DIR__ . '/surveys_list_table.php';
            wp_die(
                esc_html__( 'This form is in the Trash. Restore it to edit it or to see its entries.', 'surveyjs' )
                . ' <a href="' . esc_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu', array( 'post_status' => 'trash' ) ) ) . '">' . esc_html__( 'Go to the Trash', 'surveyjs' ) . '</a>'
            );
        }
        wp_die( esc_html__( 'Form not found.', 'surveyjs' ) );
    }

    private static function requested_ids( $action ) {
        if ( isset( $_REQUEST['survey'] ) || isset( $_GET['ids'] ) ) {
            check_admin_referer( 'bulk-surveys' );
            $ids = isset( $_REQUEST['survey'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['survey'] ) ) : explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ) ) );
            return array_values( array_filter( array_map( 'absint', $ids ) ) );
        }
        if ( isset( $_GET['id'] ) ) {
            $id = absint( $_GET['id'] );
            check_admin_referer( 'surveyjs-' . $action . '-survey_' . $id );
            return $id ? array( $id ) : array();
        }
        return array();
    }

    public static function render() {
        require_once __DIR__ . '/surveys_list_table.php';
        $table = new SurveyJS_Surveys_List_Table();
        $table->prepare_items();

        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $message = sanitize_key( $_GET['message'] ?? '' );
        $count = absint( $_GET['count'] ?? 0 );
        $messages = array(
            /* translators: %s: number of forms */
            'trashed'   => sprintf( _n( '%s form moved to the Trash.', '%s forms moved to the Trash.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            /* translators: %s: number of forms */
            'untrashed' => sprintf( _n( '%s form restored from the Trash.', '%s forms restored from the Trash.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            /* translators: %s: number of forms */
            'deleted'   => sprintf( _n( '%s form permanently deleted.', '%s forms permanently deleted.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            'cloned'    => __( 'Form duplicated.', 'surveyjs' ),
        );
        $undo_ids = 'trashed' === $message ? implode( ',', array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ?? '' ) ) ) ) ) ) : '';
        $undo_url = '' !== $undo_ids ? wp_nonce_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu', array( 'action' => 'untrash', 'ids' => $undo_ids ) ), 'bulk-surveys' ) : '';
        ?>
        <div class="wrap">
            <?php // Notices from WordPress and other plugins go here, above the title, on every plugin page. ?>
            <hr class="wp-header-end">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Forms', 'surveyjs' ); ?></h1>
            <a href="<?php echo esc_url( SurveyJS_Templates::url() ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Form', 'surveyjs' ); ?></a>
            <a href="<?php echo esc_url( SurveyJS_Import::url( SurveyJS_Import::default_source() ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import', 'surveyjs' ); ?></a>
            <?php if ( '' !== $search ) : ?>
                <span class="subtitle">
                    <?php
                    /* translators: %s: search query */
                    printf( esc_html__( 'Search results for: %s', 'surveyjs' ), '<strong>' . esc_html( $search ) . '</strong>' );
                    ?>
                </span>
            <?php endif; ?>
            <div class="wp-sjs-title-end"></div>

            <?php SurveyJS_Welcome::render_compact(); ?>

            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible" data-sjs-snackbar>
                    <p>
                        <?php echo esc_html( $messages[ $message ] ); ?>
                        <?php if ( $undo_url ) : ?>
                            <a href="<?php echo esc_url( $undo_url ); ?>" data-sjs-snackbar-action><?php esc_html_e( 'Undo', 'surveyjs' ); ?></a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php $table->views(); ?>
            <?php if ( SurveyJS_Surveys_List_Table::is_trash() ) : ?>
                <p class="wp-sjs-trash-note">
                    <?php
                    /* translators: %s: number of days */
                    echo esc_html( sprintf( _n( 'Forms in the Trash are not shown on your site and do not accept entries. Their entries are kept until the form is deleted permanently, which happens automatically %s day after it was moved to the Trash.', 'Forms in the Trash are not shown on your site and do not accept entries. Their entries are kept until the form is deleted permanently, which happens automatically %s days after it was moved to the Trash.', EMPTY_TRASH_DAYS, 'surveyjs' ), number_format_i18n( EMPTY_TRASH_DAYS ) ) );
                    ?>
                </p>
            <?php endif; ?>
            <form method="get" id="wp-sjs-forms-list">
                <input type="hidden" name="page" value="surveyjs-main-menu" />
                <?php if ( SurveyJS_Surveys_List_Table::is_trash() ) : ?>
                    <input type="hidden" name="post_status" value="trash" />
                <?php endif; ?>
                <?php
                $table->display();
                ?>
            </form>
        </div>
        <script>
            document.getElementById("wp-sjs-forms-list").addEventListener("submit", function (event) {
                var button = event.submitter;
                if (button && button.name === "delete_all") {
                    if (!window.confirm(<?php echo wp_json_encode( __( 'Permanently delete all forms in the Trash? All their entries and uploaded files will be deleted too. This cannot be undone.', 'surveyjs' ), JSON_HEX_TAG ); ?>)) {
                        event.preventDefault();
                    }
                    return;
                }
                if (!button || !/^doaction2?$/.test(button.id)) {
                    return;
                }
                var select = document.getElementById(button.id === "doaction2" ? "bulk-action-selector-bottom" : "bulk-action-selector-top");
                if (select && select.value === "delete" && !window.confirm(<?php echo wp_json_encode( __( 'Delete the selected forms? All their entries and uploaded files will be deleted too. This cannot be undone.', 'surveyjs' ), JSON_HEX_TAG ); ?>)) {
                    event.preventDefault();
                }
            });
        </script>
        <?php
    }
}

?>
