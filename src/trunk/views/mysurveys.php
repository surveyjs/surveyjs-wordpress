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

        if ( 'delete' === $action ) {
            if ( ! isset( $_REQUEST['survey'] ) && ! isset( $_GET['id'] ) ) {
                wp_safe_redirect( $list_url );
                exit;
            }
            if ( isset( $_REQUEST['survey'] ) ) {
                check_admin_referer( 'bulk-surveys' );
                $ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['survey'] ) );
            } else {
                $id = absint( $_GET['id'] ?? 0 );
                check_admin_referer( 'surveyjs-delete-survey_' . $id );
                $ids = array( $id );
            }
            $deleted = count( array_filter( array_map( array( 'SurveyJS_Forms', 'delete' ), $ids ) ) );
            wp_safe_redirect( add_query_arg( array( 'message' => 'deleted', 'count' => $deleted ), $list_url ) );
            exit;
        }

        if ( ! empty( $_GET['_wp_http_referer'] ) ) {
            wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) );
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
            'deleted' => sprintf( _n( '%s form deleted.', '%s forms deleted.', $count, 'surveyjs' ), number_format_i18n( $count ) ),
            'cloned'  => __( 'Form cloned.', 'surveyjs' ),
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Forms', 'surveyjs' ); ?></h1>
            <a href="<?php echo esc_url( SurveyJS_Templates::url() ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Form', 'surveyjs' ); ?></a>
            <?php foreach ( SurveyJS_Import::sources() as $source => $info ) : ?>
                <?php if ( $info['importer']::forms() ) : ?>
                    <a href="<?php echo esc_url( SurveyJS_Import::url( $source ) ); ?>" class="page-title-action"><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( 'Import from %s', 'surveyjs' ), $info['title'] ) ); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ( '' !== $search ) : ?>
                <span class="subtitle">
                    <?php
                    /* translators: %s: search query */
                    printf( esc_html__( 'Search results for: %s', 'surveyjs' ), '<strong>' . esc_html( $search ) . '</strong>' );
                    ?>
                </span>
            <?php endif; ?>
            <hr class="wp-header-end">

            <?php SurveyJS_Welcome::render_compact(); ?>

            <?php if ( isset( $messages[ $message ] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $messages[ $message ] ); ?></p></div>
            <?php endif; ?>

            <form method="get" id="wp-sjs-forms-list">
                <input type="hidden" name="page" value="surveyjs-main-menu" />
                <?php
                $table->search_box( __( 'Search Forms', 'surveyjs' ), 'surveyjs-survey' );
                $table->display();
                ?>
            </form>
        </div>
        <script>
            document.getElementById("wp-sjs-forms-list").addEventListener("submit", function (event) {
                var button = event.submitter;
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
