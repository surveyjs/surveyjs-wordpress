<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Import {

    const RESULTS_TRANSIENT = 'surveyjs_cf7_import_';

    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }
        check_admin_referer( 'surveyjs-import-cf7' );
        $results = array();
        foreach ( array_map( 'absint', (array) ( $_POST['forms'] ?? array() ) ) as $cf7_id ) {
            $result = SurveyJS_CF7_Importer::import( $cf7_id );
            $results[] = is_wp_error( $result )
                ? array( 'name' => get_the_title( $cf7_id ), 'error' => $result->get_error_message() )
                : $result;
        }
        set_transient( self::RESULTS_TRANSIENT . get_current_user_id(), $results, HOUR_IN_SECONDS );
        wp_safe_redirect( self::url( array( 'imported' => 1 ) ) );
        exit;
    }

    public static function url( $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => 'surveyjs_import' ), $args ), admin_url( 'admin.php' ) );
    }

    public static function render() {
        $list_url = add_query_arg( 'page', 'surveyjs-main-menu', admin_url( 'admin.php' ) );
        $results = null;
        if ( ! empty( $_GET['imported'] ) ) {
            $results = get_transient( self::RESULTS_TRANSIENT . get_current_user_id() );
            delete_transient( self::RESULTS_TRANSIENT . get_current_user_id() );
        }
        $forms = SurveyJS_CF7_Importer::forms();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Import from Contact Form 7', 'surveyjs' ); ?></h1>

            <?php if ( is_array( $results ) ) : ?>
                <?php self::render_results( $results ); ?>
                <p><a href="<?php echo esc_url( $list_url ); ?>" class="button button-primary"><?php esc_html_e( 'Go to all forms', 'surveyjs' ); ?></a></p>
            <?php elseif ( ! SurveyJS_CF7_Importer::is_available() ) : ?>
                <p><?php esc_html_e( 'Activate Contact Form 7 to import its forms.', 'surveyjs' ); ?></p>
            <?php elseif ( empty( $forms ) ) : ?>
                <p><?php esc_html_e( 'Contact Form 7 has no forms to import.', 'surveyjs' ); ?></p>
            <?php else : ?>
                <?php self::render_form( $forms ); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_form( $forms ) {
        ?>
        <p><?php esc_html_e( 'Select the forms to import. Fields, the email notification and the auto-reply are copied to new forms; the Contact Form 7 forms stay unchanged.', 'surveyjs' ); ?></p>
        <form method="post" action="<?php echo esc_url( self::url() ); ?>" onsubmit="this.querySelector('.spinner').classList.add('is-active'); this.querySelector('[type=submit]').disabled = true;">
            <?php wp_nonce_field( 'surveyjs-import-cf7' ); ?>
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e( 'Contact Form 7 forms', 'surveyjs' ); ?></legend>
                <?php foreach ( $forms as $cf7_id => $title ) : ?>
                    <p><label><input type="checkbox" name="forms[]" value="<?php echo esc_attr( $cf7_id ); ?>" checked /> <?php echo esc_html( $title ); ?></label></p>
                <?php endforeach; ?>
            </fieldset>
            <p class="submit">
                <?php submit_button( __( 'Import', 'surveyjs' ), 'primary', 'submit', false ); ?>
                <span class="spinner" style="float:none;margin-top:0"></span>
            </p>
        </form>
        <?php
    }

    private static function render_results( $results ) {
        if ( empty( $results ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'No forms were selected.', 'surveyjs' ) . '</p></div>';
            return;
        }
        echo '<ul class="ul-disc">';
        foreach ( $results as $result ) {
            echo '<li><strong>' . esc_html( $result['name'] ) . '</strong>';
            if ( isset( $result['error'] ) ) {
                echo ' — ' . esc_html( $result['error'] );
            } else {
                $edit_url = add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => $result['id'] ), admin_url( 'admin.php' ) );
                echo ' — ' . esc_html__( 'imported', 'surveyjs' ) . ' <a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'surveyjs' ) . '</a>';
                if ( $result['skipped'] ) {
                    /* translators: %s: list of Contact Form 7 fields */
                    echo '<br>' . esc_html( sprintf( __( 'Not imported (no equivalent field): %s', 'surveyjs' ), implode( ', ', $result['skipped'] ) ) );
                }
                foreach ( $result['notes'] as $note ) {
                    echo '<br>' . esc_html( $note );
                }
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}
