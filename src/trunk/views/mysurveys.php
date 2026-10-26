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
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $list_url = SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu' );

        $action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );
        if ( '' === $action || '-1' === $action ) {
            $action = sanitize_key( wp_unslash( $_REQUEST['action2'] ?? '' ) );
        }

        if ( 'add' === $action ) {
            check_admin_referer( 'surveyjs-add-survey' );
            $name = 'New Survey';
            $wpdb->insert( $table_name, array( 'name' => $name ) );
            wp_safe_redirect( SurveyJS_Surveys_List_Table::page_url( 'surveyjs_editor', array( 'id' => $wpdb->insert_id, 'name' => $name ) ) );
            exit;
        }

        if ( 'clone' === $action ) {
            $id = absint( $_GET['id'] ?? 0 );
            check_admin_referer( 'surveyjs-clone-survey_' . $id );
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sjs_my_surveys WHERE id = %d", $id ) );
            if ( $row ) {
                $wpdb->insert( $table_name, array(
                    'name' => sanitize_text_field( 'Copy of ' . $row->name ),
                    'json' => $row->json,
                ) );
            }
            wp_safe_redirect( add_query_arg( 'message', 'cloned', $list_url ) );
            exit;
        }

        if ( 'delete' === $action ) {
            if ( isset( $_REQUEST['survey'] ) ) {
                check_admin_referer( 'bulk-surveys' );
                $ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['survey'] ) );
            } else {
                $id = absint( $_GET['id'] ?? 0 );
                check_admin_referer( 'surveyjs-delete-survey_' . $id );
                $ids = array( $id );
            }
            foreach ( $ids as $id ) {
                $wpdb->delete( $table_name, array( 'id' => $id ) );
            }
            wp_safe_redirect( add_query_arg( array( 'message' => 'deleted', 'count' => count( $ids ) ), $list_url ) );
            exit;
        }

        if ( ! empty( $_GET['_wp_http_referer'] ) ) {
            wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
            exit;
        }
    }

    public static function render() {
        require_once __DIR__ . '/surveys_list_table.php';
        $table = new SurveyJS_Surveys_List_Table();
        $table->prepare_items();

        $search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
        $message = sanitize_key( $_GET['message'] ?? '' );
        $count = absint( $_GET['count'] ?? 0 );
        $messages = array(
            /* translators: %s: number of deleted surveys */
            'deleted' => sprintf( _n( '%s survey deleted.', '%s surveys deleted.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            'cloned'  => __( 'Survey cloned.', 'surveyjs' ),
        );
        $add_url = wp_nonce_url( SurveyJS_Surveys_List_Table::page_url( 'surveyjs-main-menu', array( 'action' => 'add' ) ), 'surveyjs-add-survey' );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Surveys', 'surveyjs' ); ?></h1>
            <a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Survey', 'surveyjs' ); ?></a>
            <?php if ( '' !== $search ) : ?>
                <span class="subtitle">
                    <?php
                    /* translators: %s: search query */
                    printf( esc_html__( 'Search results for: %s', 'surveyjs' ), '<strong>' . esc_html( $search ) . '</strong>' );
                    ?>
                </span>
            <?php endif; ?>
            <hr class="wp-header-end">

            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $messages[ $message ] ); ?></p></div>
            <?php endif; ?>

            <form method="get">
                <input type="hidden" name="page" value="surveyjs-main-menu" />
                <?php
                $table->search_box( __( 'Search Surveys', 'surveyjs' ), 'surveyjs-survey' );
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }
}

?>
