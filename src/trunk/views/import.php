<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Import {

    const RESULTS_TRANSIENT = 'surveyjs_import_';

    const PART_TRANSIENT = 'surveyjs_import_part_';

    const ENTRIES_PER_REQUEST = 200;

    public static function sources() {
        return array(
            'cf7'         => array(
                'importer'   => 'SurveyJS_CF7_Importer',
                'title'      => __( 'Contact Form 7', 'surveyjs' ),
                'intro'      => __( 'Select the forms to import. Fields, the text between them, the email notification and the auto-reply are copied to SurveyJS forms; the Contact Form 7 forms stay unchanged.', 'surveyjs' ),
                'no_entries' => __( 'Contact Form 7 does not keep entries (it only sends emails), so only the forms are imported.', 'surveyjs' ),
            ),
            'wpforms'     => array(
                'importer'   => 'SurveyJS_WPForms_Importer',
                'title'      => __( 'WPForms', 'surveyjs' ),
                'intro'      => __( 'Select the forms to import. Fields, pages, conditional logic, the email notification and the confirmations are copied to SurveyJS forms; the WPForms forms stay unchanged.', 'surveyjs' ),
                'no_entries' => __( 'WPForms Lite does not keep entries on your site, so only the forms are imported.', 'surveyjs' ),
            ),
            'ninja-forms' => array(
                'importer'   => 'SurveyJS_Ninja_Forms_Importer',
                'title'      => __( 'Ninja Forms', 'surveyjs' ),
                'intro'      => __( 'Select the forms to import. Fields, multi-part pages, conditional logic, the email actions and the success message are copied to SurveyJS forms; the Ninja Forms forms stay unchanged.', 'surveyjs' ),
                'no_entries' => __( 'Ninja Forms submissions cannot be read, so only the forms are imported.', 'surveyjs' ),
            ),
            'fluentform'  => array(
                'importer'   => 'SurveyJS_Fluent_Forms_Importer',
                'title'      => __( 'Fluent Forms', 'surveyjs' ),
                'intro'      => __( 'Select the forms to import. Fields, columns, steps, conditional logic, the email notifications and the confirmations are copied to SurveyJS forms; the Fluent Forms forms stay unchanged.', 'surveyjs' ),
                'no_entries' => __( 'The Fluent Forms entries table was not found, so only the forms are imported.', 'surveyjs' ),
            ),
        );
    }

    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }
        $source = self::source();
        check_admin_referer( 'surveyjs-import-' . $source );
        $importer = self::sources()[ $source ]['importer'];
        $with_entries = ! empty( $_POST['entries'] );
        $results = array();
        foreach ( array_map( 'absint', (array) ( $_POST['forms'] ?? array() ) ) as $form_id ) {
            $result = $importer::import( $form_id, $with_entries );
            $results[] = is_wp_error( $result ) ? self::error_result( $importer, $form_id, $result ) : $result;
        }
        set_transient( self::RESULTS_TRANSIENT . get_current_user_id(), $results, HOUR_IN_SECONDS );
        wp_safe_redirect( self::url( $source, array( 'imported' => 1 ) ) );
        exit;
    }

    public static function handle_ajax() {
        $source = sanitize_key( wp_unslash( $_POST['source'] ?? '' ) );
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) || ! isset( self::sources()[ $source ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Not allowed.', 'surveyjs' ) ), 403 );
        }
        check_ajax_referer( 'surveyjs-import-' . $source );
        $importer = self::sources()[ $source ]['importer'];
        $form_id = absint( $_POST['form'] ?? 0 );
        $offset = absint( $_POST['offset'] ?? 0 );
        $limit = min( absint( $_POST['limit'] ?? 0 ), self::entries_per_request() );
        $limit = $limit ? $limit : self::entries_per_request();
        $key = self::PART_TRANSIENT . get_current_user_id() . '_' . $source . '_' . $form_id;
        $previous = $offset ? get_transient( $key ) : array();
        $result = is_array( $previous )
            ? $importer::import_part( $form_id, ! empty( $_POST['entries'] ), $offset, $limit )
            : new WP_Error( 'interrupted', __( 'The import of this form was interrupted. Import it again to continue.', 'surveyjs' ) );
        if ( is_wp_error( $result ) ) {
            delete_transient( $key );
            wp_send_json_success( array(
                'done'  => true,
                'error' => true,
                'html'  => self::result_html( self::error_result( $importer, $form_id, $result ) ),
            ) );
        }
        $result = self::merge_result( $previous, $result );
        $next = $result['entries']['next'] ?? null;
        if ( null === $next ) {
            delete_transient( $key );
            wp_send_json_success( array( 'done' => true, 'html' => self::result_html( $result ) ) );
        }
        set_transient( $key, $result, HOUR_IN_SECONDS );
        wp_send_json_success( array(
            'done'  => false,
            'next'  => $next,
            'total' => $result['entries']['total'],
        ) );
    }

    private static function entries_per_request() {
        return max( 1, (int) apply_filters( 'surveyjs_import_entries_per_request', self::ENTRIES_PER_REQUEST ) );
    }

    private static function merge_result( $previous, $result ) {
        if ( ! $previous ) {
            return $result;
        }
        foreach ( array( 'added', 'updated', 'files_copied', 'files_not_copied' ) as $count ) {
            $previous['entries'][ $count ] += $result['entries'][ $count ];
        }
        $previous['entries']['total'] = $result['entries']['total'];
        $previous['entries']['next'] = $result['entries']['next'];
        $previous['notes'] = array_values( array_unique( array_merge( $previous['notes'], $result['notes'] ) ) );
        return $previous;
    }

    private static function error_result( $importer, $form_id, $error ) {
        return array(
            'name'  => (string) ( $importer::forms()[ $form_id ] ?? $form_id ),
            'error' => $error->get_error_message(),
        );
    }

    public static function url( $source, $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => 'surveyjs_import', 'source' => $source ), $args ), admin_url( 'admin.php' ) );
    }

    private static function source() {
        $source = sanitize_key( $_GET['source'] ?? '' );
        return isset( self::sources()[ $source ] ) ? $source : 'cf7';
    }

    private static function edit_url( $id ) {
        return add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => $id ), admin_url( 'admin.php' ) );
    }

    private static function list_url() {
        return add_query_arg( 'page', 'surveyjs-main-menu', admin_url( 'admin.php' ) );
    }

    public static function render() {
        $source = self::source();
        $info = self::sources()[ $source ];
        $importer = $info['importer'];
        $results = null;
        if ( ! empty( $_GET['imported'] ) ) {
            $results = get_transient( self::RESULTS_TRANSIENT . get_current_user_id() );
            delete_transient( self::RESULTS_TRANSIENT . get_current_user_id() );
        }
        $forms = $importer::forms();
        ?>
        <div class="wrap">
            <h1><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( 'Import from %s', 'surveyjs' ), $info['title'] ) ); ?></h1>

            <?php if ( is_array( $results ) ) : ?>
                <?php self::render_results( $results ); ?>
                <p><a href="<?php echo esc_url( self::list_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Go to all forms', 'surveyjs' ); ?></a></p>
            <?php elseif ( ! $importer::is_available() ) : ?>
                <p><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( 'Activate %s to import its forms.', 'surveyjs' ), $info['title'] ) ); ?></p>
            <?php elseif ( empty( $forms ) ) : ?>
                <p><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( '%s has no forms to import.', 'surveyjs' ), $info['title'] ) ); ?></p>
            <?php else : ?>
                <?php self::render_form( $source, $forms ); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_form( $source, $forms ) {
        $info = self::sources()[ $source ];
        $importer = $info['importer'];
        self::enqueue_script();
        ?>
        <p><?php echo esc_html( $info['intro'] ); ?></p>
        <p><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( 'Importing a form again updates the SurveyJS form created from it instead of adding a copy: its fields and email settings are replaced with the ones from %s, and changes you made to them in SurveyJS are lost. The form name, theme and entries are kept.', 'surveyjs' ), $info['title'] ) ); ?></p>
        <form method="post" action="<?php echo esc_url( self::url( $source ) ); ?>" data-sjs-import="<?php echo esc_attr( $source ); ?>" data-entries-per-request="<?php echo esc_attr( self::entries_per_request() ); ?>" onsubmit="this.querySelector('.spinner').classList.add('is-active'); this.querySelector('[type=submit]').disabled = true;">
            <?php wp_nonce_field( 'surveyjs-import-' . $source ); ?>
            <fieldset>
                <legend class="screen-reader-text"><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( '%s forms', 'surveyjs' ), $info['title'] ) ); ?></legend>
                <?php foreach ( $forms as $form_id => $title ) : ?>
                    <?php $imported_id = $importer::imported_id( $form_id ); ?>
                    <p>
                        <label><input type="checkbox" name="forms[]" value="<?php echo esc_attr( $form_id ); ?>" checked /> <?php echo esc_html( $title ); ?></label>
                        <?php if ( $imported_id ) : ?>
                            <span class="description">— <?php esc_html_e( 'imported before, will be updated', 'surveyjs' ); ?> (<a href="<?php echo esc_url( self::edit_url( $imported_id ) ); ?>"><?php esc_html_e( 'open', 'surveyjs' ); ?></a>)</span>
                        <?php endif; ?>
                    </p>
                <?php endforeach; ?>
            </fieldset>
            <?php if ( $importer::entries_available() ) : ?>
                <p>
                    <label><input type="checkbox" name="entries" value="1" /> <?php esc_html_e( 'Import entries', 'surveyjs' ); ?></label><br>
                    <span class="description"><?php esc_html_e( 'Importing again updates the entries imported before and adds new ones, without duplicates. Spam, trashed and partial entries are skipped.', 'surveyjs' ); ?></span>
                </p>
            <?php else : ?>
                <p class="description"><?php echo esc_html( $info['no_entries'] ); ?></p>
            <?php endif; ?>
            <p class="submit">
                <?php submit_button( __( 'Import', 'surveyjs' ), 'primary', 'submit', false ); ?>
                <span class="spinner" style="float:none;margin-top:0"></span>
            </p>
        </form>
        <?php
    }

    private static function enqueue_script() {
        if ( wp_script_is( 'surveyjs-import' ) ) {
            return;
        }
        wp_enqueue_script( 'surveyjs-import', plugins_url( 'import.js', __FILE__ ), array(), (string) filemtime( __DIR__ . '/import.js' ), true );
        wp_localize_script( 'surveyjs-import', 'surveyjsImport', array(
            'listUrl' => self::list_url(),
            'i18n'    => array(
                /* translators: 1: number of the form being imported, 2: number of selected forms, 3: form name */
                'progress'        => __( 'Importing %1$s of %2$s: %3$s…', 'surveyjs' ),
                /* translators: 1: number of the form being imported, 2: number of selected forms, 3: form name, 4: number of entries imported so far, 5: number of entries */
                'entriesProgress' => __( 'Importing %1$s of %2$s: %3$s — entries %4$s of %5$s…', 'surveyjs' ),
                /* translators: %s: number of forms */
                'done'            => __( 'Import finished. Forms processed: %s.', 'surveyjs' ),
                /* translators: 1: number of forms, 2: number of forms with errors */
                'doneWithErrors'  => __( 'Import finished. Forms processed: %1$s, with errors: %2$s.', 'surveyjs' ),
                /* translators: %s: error details, e.g. an HTTP status */
                'failed'          => __( 'The import failed: %s', 'surveyjs' ),
                'unknownError'    => __( 'unexpected server response', 'surveyjs' ),
                'allForms'        => __( 'Go to all forms', 'surveyjs' ),
            ),
        ) );
    }

    private static function render_results( $results ) {
        if ( empty( $results ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'No forms were selected.', 'surveyjs' ) . '</p></div>';
            return;
        }
        echo '<ul class="ul-disc">';
        foreach ( $results as $result ) {
            echo self::result_html( $result ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</ul>';
    }

    private static function result_html( $result ) {
        $html = '<li><strong>' . esc_html( $result['name'] ) . '</strong>';
        if ( isset( $result['error'] ) ) {
            return $html . ' — ' . esc_html( $result['error'] ) . '</li>';
        }
        $html .= ' — ' . ( empty( $result['updated'] ) ? esc_html__( 'imported', 'surveyjs' ) : esc_html__( 'updated', 'surveyjs' ) ) . ' <a href="' . esc_url( self::edit_url( $result['id'] ) ) . '">' . esc_html__( 'Edit', 'surveyjs' ) . '</a>';
        if ( isset( $result['entries'] ) ) {
            $entries = $result['entries'];
            $entries_url = add_query_arg( array( 'page' => 'surveyjs_results', 'id' => $result['id'] ), admin_url( 'admin.php' ) );
            /* translators: 1: number of added entries, 2: number of updated entries */
            $html .= '<br>' . esc_html( sprintf( __( 'Entries: %1$d added, %2$d updated.', 'surveyjs' ), $entries['added'], $entries['updated'] ) ) . ' <a href="' . esc_url( $entries_url ) . '">' . esc_html__( 'View entries', 'surveyjs' ) . '</a>';
            if ( ! empty( $entries['files_copied'] ) ) {
                /* translators: %d: number of files */
                $html .= '<br>' . esc_html( sprintf( _n( '%d entry file copied to the SurveyJS uploads folder.', '%d entry files copied to the SurveyJS uploads folder.', $entries['files_copied'], 'surveyjs' ), $entries['files_copied'] ) );
            }
            if ( ! empty( $entries['files_not_copied'] ) ) {
                /* translators: %d: number of files */
                $html .= '<br>' . esc_html( sprintf( _n( '%d entry file was not found or has a file type that is not allowed, so its entry keeps the link to the original file.', '%d entry files were not found or have a file type that is not allowed, so their entries keep the links to the original files.', $entries['files_not_copied'], 'surveyjs' ), $entries['files_not_copied'] ) );
            }
        }
        if ( $result['skipped'] ) {
            /* translators: %s: list of fields */
            $html .= '<br>' . esc_html( sprintf( __( 'Not imported (no equivalent field): %s', 'surveyjs' ), implode( ', ', $result['skipped'] ) ) );
        }
        foreach ( $result['notes'] as $note ) {
            $html .= '<br>' . esc_html( $note );
        }
        return $html . '</li>';
    }
}
