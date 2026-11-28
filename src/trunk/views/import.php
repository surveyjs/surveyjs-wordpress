<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Import {

    const RESULTS_TRANSIENT = 'surveyjs_import_';

    const PART_TRANSIENT = 'surveyjs_import_part_';

    const ENTRIES_PER_REQUEST = 200;

    public static function sources() {
        return array(
            'surveyjs'    => array(
                'importer' => 'SurveyJS_JSON_Importer',
                'title'    => __( 'SurveyJS JSON', 'surveyjs' ),
                'json'     => true,
            ),
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
            'gravityforms' => array(
                'importer'      => 'SurveyJS_Gravity_Forms_Importer',
                'file_importer' => 'SurveyJS_Gravity_Forms_File_Importer',
                'title'         => __( 'Gravity Forms', 'surveyjs' ),
                'intro'         => __( 'Select the forms to import. Fields, sections, pages, conditional logic, the email notifications and the confirmations are copied to SurveyJS forms; the Gravity Forms forms stay unchanged.', 'surveyjs' ),
                'no_entries'    => __( 'The Gravity Forms entries tables were not found, so only the forms are imported.', 'surveyjs' ),
            ),
        );
    }

    public static function enqueue_style() {
        wp_enqueue_style( 'wps-adm--import.css', plugins_url( 'import.css', __FILE__ ), array(), (string) filemtime( __DIR__ . '/import.css' ) );
    }

    public static function handle_actions() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }
        $source = self::source();
        if ( ! empty( self::sources()[ $source ]['json'] ) ) {
            return;
        }
        if ( isset( $_POST['import_file'] ) && isset( self::sources()[ $source ]['file_importer'] ) ) {
            check_admin_referer( 'surveyjs-import-file-' . $source );
            set_transient( self::RESULTS_TRANSIENT . get_current_user_id(), self::import_file( self::sources()[ $source ]['file_importer'] ), HOUR_IN_SECONDS );
            wp_safe_redirect( self::url( $source, array( 'imported' => 1 ) ) );
            exit;
        }
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

    private static function import_file( $importer ) {
        $file = isset( $_FILES['file'] ) && is_array( $_FILES['file'] ) ? $_FILES['file'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the caller checks the nonce; the upload is validated below and only parsed as JSON.
        $name = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
        $tmp_name = (string) ( $file['tmp_name'] ?? '' );
        $error = function ( $message ) use ( $name ) {
            return array( array( 'name' => '' !== $name ? $name : __( 'Export file', 'surveyjs' ), 'error' => $message ) );
        };
        if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
            return $error( __( 'The file was not uploaded. Choose the .json file exported from Gravity Forms and try again.', 'surveyjs' ) );
        }
        if ( 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
            return $error( __( 'The file is not a .json file. Gravity Forms exports forms as a .json file.', 'surveyjs' ) );
        }
        $contents = file_get_contents( $tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- the uploaded temporary file checked by is_uploaded_file(), never a URL.
        $form_ids = $importer::load( false === $contents ? '' : $contents );
        if ( is_wp_error( $form_ids ) ) {
            return $error( $form_ids->get_error_message() );
        }
        $results = array();
        foreach ( $form_ids as $form_id ) {
            $result = $importer::import( $form_id );
            $results[] = is_wp_error( $result ) ? self::error_result( $importer, $form_id, $result ) : $result;
        }
        return $results;
    }

    public static function handle_ajax() {
        $source = sanitize_key( wp_unslash( $_POST['source'] ?? '' ) );
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( 'manage_options' ) || ! isset( self::sources()[ $source ] ) || ! empty( self::sources()[ $source ]['json'] ) ) {
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

    public static function handle_json_ajax() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Not allowed.', 'surveyjs' ) ), 403 );
        }
        check_ajax_referer( 'surveyjs-import-json' );
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON texts: SurveyJS_JSON_Importer::import() decodes and validates them and keeps them raw only for users with unfiltered_html, as the form editor does.
        $result = SurveyJS_JSON_Importer::import(
            $name,
            wp_unslash( $_POST['json'] ?? '' ),
            wp_unslash( $_POST['theme'] ?? '' ),
            wp_unslash( $_POST['settings'] ?? '' )
        );
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( is_wp_error( $result ) ) {
            wp_send_json_success( array(
                'error' => true,
                'html'  => self::result_html( array( 'name' => '' !== $name ? $name : __( 'Form', 'surveyjs' ), 'error' => $result->get_error_message() ) ),
            ) );
        }
        wp_send_json_success( array( 'html' => self::result_html( $result ) ) );
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
        return isset( self::sources()[ $source ] ) ? $source : self::default_source();
    }

    // The first plugin that has forms to import; SurveyJS JSON files otherwise, as they need no other plugin.
    public static function default_source() {
        foreach ( self::sources() as $source => $info ) {
            if ( $info['importer']::forms() ) {
                return $source;
            }
        }
        return 'surveyjs';
    }

    private static function render_sources( $current ) {
        ?>
        <nav class="nav-tab-wrapper wp-sjs-import__sources" aria-label="<?php esc_attr_e( 'Import from', 'surveyjs' ); ?>">
            <?php foreach ( self::sources() as $source => $info ) : ?>
                <?php
                $importer = $info['importer'];
                $count = $importer::is_available() ? count( (array) $importer::forms() ) : 0;
                $active = $source === $current;
                ?>
                <a href="<?php echo esc_url( self::url( $source ) ); ?>" class="nav-tab<?php echo $active ? ' nav-tab-active' : ''; ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                    <?php echo esc_html( $info['title'] ); ?>
                    <?php if ( $count ) : ?>
                        <span class="wp-sjs-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
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
        <div class="wrap wp-sjs-import">
            <?php // Notices from WordPress and other plugins go here, above the title, on every plugin page. ?>
            <hr class="wp-header-end">
            <?php SurveyJS_Admin_Header::breadcrumbs( array( array( __( 'All Forms', 'surveyjs' ), self::list_url() ), array( __( 'Import forms', 'surveyjs' ) ) ) ); ?>
            <h1><?php esc_html_e( 'Import forms', 'surveyjs' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Copy forms from SurveyJS JSON files or from another form plugin. The original forms stay unchanged.', 'surveyjs' ); ?></p>
            <?php self::render_sources( $source ); ?>

            <?php if ( is_array( $results ) ) : ?>
                <?php self::render_results( $results ); ?>
                <p><a href="<?php echo esc_url( self::list_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Go to all forms', 'surveyjs' ); ?></a></p>
            <?php elseif ( ! empty( $info['json'] ) ) : ?>
                <?php self::render_json_form(); ?>
            <?php elseif ( isset( $info['file_importer'] ) ) : ?>
                <?php if ( $forms ) : ?>
                    <?php self::render_form( $source, $forms ); ?>
                <?php endif; ?>
                <?php self::render_file_form( $source, (bool) $forms ); ?>
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
            <table class="wp-list-table widefat fixed striped wp-sjs-import__forms">
                <caption class="screen-reader-text"><?php /* translators: %s: form plugin name */ echo esc_html( sprintf( __( '%s forms', 'surveyjs' ), $info['title'] ) ); ?></caption>
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox" checked />
                            <label for="cb-select-all-1"><span class="screen-reader-text"><?php esc_html_e( 'Select All', 'surveyjs' ); ?></span></label>
                        </td>
                        <th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Form', 'surveyjs' ); ?></th>
                        <th scope="col" class="manage-column wp-sjs-import__status"><?php esc_html_e( 'Status', 'surveyjs' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $forms as $form_id => $title ) : ?>
                        <?php
                        $imported_id = $importer::imported_id( $form_id );
                        $checkbox_id = 'wp-sjs-import-form-' . sanitize_html_class( (string) $form_id );
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" id="<?php echo esc_attr( $checkbox_id ); ?>" name="forms[]" value="<?php echo esc_attr( $form_id ); ?>" data-name="<?php echo esc_attr( $title ); ?>" checked />
                            </th>
                            <td class="column-primary"><label for="<?php echo esc_attr( $checkbox_id ); ?>"><?php echo esc_html( $title ); ?></label></td>
                            <td class="wp-sjs-import__status">
                                <?php if ( $imported_id ) : ?>
                                    <?php esc_html_e( 'Imported before, will be updated', 'surveyjs' ); ?>
                                    · <a href="<?php echo esc_url( self::edit_url( $imported_id ) ); ?>"><?php esc_html_e( 'Open', 'surveyjs' ); ?></a>
                                <?php else : ?>
                                    <span class="wp-sjs-import__new"><?php esc_html_e( 'New form', 'surveyjs' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ( $importer::entries_available() ) : ?>
                <p>
                    <label><input type="checkbox" name="entries" value="1" /> <?php esc_html_e( 'Import entries', 'surveyjs' ); ?></label><br>
                    <span class="description">
                        <?php esc_html_e( 'Importing again updates the entries imported before and adds new ones, without duplicates. Spam and trashed entries are skipped.', 'surveyjs' ); ?>
                        <?php if ( 'wpforms' === $source ) : ?>
                            <?php esc_html_e( 'Partial and abandoned entries are imported as Partial, except those last saved more than 30 days ago.', 'surveyjs' ); ?>
                        <?php endif; ?>
                    </span>
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

    private static function render_file_form( $source, $below_list ) {
        $info = self::sources()[ $source ];
        ?>
        <?php if ( $below_list ) : ?>
            <h2><?php esc_html_e( 'Import from an export file', 'surveyjs' ); ?></h2>
        <?php endif; ?>
        <p><?php /* translators: %1$s: form plugin name */ echo esc_html( sprintf( __( 'In %1$s, go to Forms → Import/Export → Export Forms, select the forms and download the .json file. Upload it here to import every form in it. %1$s does not need to be installed on this site.', 'surveyjs' ), $info['title'] ) ); ?></p>
        <p><?php esc_html_e( 'Uploading the same forms again updates the SurveyJS forms created from them. The export file has no entries, so only the forms are imported.', 'surveyjs' ); ?></p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( self::url( $source ) ); ?>" onsubmit="this.querySelector('.spinner').classList.add('is-active'); this.querySelector('[type=submit]').disabled = true;">
            <?php wp_nonce_field( 'surveyjs-import-file-' . $source ); ?>
            <input type="hidden" name="import_file" value="1" />
            <p>
                <label for="wp-sjs-import-file"><?php esc_html_e( 'Export file (.json)', 'surveyjs' ); ?></label><br>
                <input type="file" id="wp-sjs-import-file" name="file" accept=".json,application/json" required />
            </p>
            <p class="submit">
                <?php submit_button( __( 'Upload and import', 'surveyjs' ), $below_list ? 'secondary' : 'primary', 'submit', false ); ?>
                <span class="spinner" style="float:none;margin-top:0"></span>
            </p>
        </form>
        <?php
    }

    private static function render_json_form() {
        if ( ! wp_script_is( 'surveyjs-import-json' ) ) {
            self::enqueue_script();
            wp_enqueue_script( 'surveyjs-import-json', plugins_url( 'import-json.js', __FILE__ ), array( 'surveyjs-import' ), (string) filemtime( __DIR__ . '/import-json.js' ), true );
            wp_localize_script( 'surveyjs-import-json', 'surveyjsImportJson', array(
                'nonce'    => wp_create_nonce( 'surveyjs-import-json' ),
                'maxBytes' => SurveyJS_JSON_Importer::max_bytes(),
                'names'    => array_values( array_unique( array_map( 'strval', wp_list_pluck( SurveyJS_Forms::names(), 'name' ) ) ) ),
                'i18n'     => array(
                    'newForm'      => __( 'New form', 'surveyjs' ),
                    'duplicate'    => __( 'A form with this name exists, a copy will be added', 'surveyjs' ),
                    'withTheme'    => __( 'with its theme', 'surveyjs' ),
                    'invalidJson'  => __( 'Not valid JSON', 'surveyjs' ),
                    'notAForm'     => __( 'Not a SurveyJS form JSON: no pages or questions', 'surveyjs' ),
                    /* translators: %s: maximum size, e.g. 5 MB */
                    'tooLarge'     => __( 'Larger than %s', 'surveyjs' ),
                    'notJsonFile'  => __( 'Not a .json or .zip file', 'surveyjs' ),
                    'badZip'       => __( 'The .zip file could not be read', 'surveyjs' ),
                    'noZipSupport' => __( 'This browser cannot open .zip files. Unzip the file and select the .json files.', 'surveyjs' ),
                    'emptyZip'     => __( 'No .json files in the .zip file', 'surveyjs' ),
                    'pasted'       => __( 'Pasted JSON', 'surveyjs' ),
                    'untitled'     => __( 'Imported form', 'surveyjs' ),
                    /* translators: 1: number of forms ready to import, 2: number of skipped items */
                    'summary'      => __( 'Forms ready to import: %1$s. Skipped: %2$s.', 'surveyjs' ),
                ),
            ) );
        }
        ?>
        <p><?php esc_html_e( 'Select SurveyJS form JSON files, or a .zip file with them. Each form is added as a new SurveyJS form; existing forms are not changed.', 'surveyjs' ); ?></p>
        <p class="description"><?php esc_html_e( 'Accepted: form JSON from Survey Creator, surveys from SurveyJS My Surveys with their themes, and export files of SurveyJS Forms from another site (All Forms → Bulk actions → Export).', 'surveyjs' ); ?></p>
        <form class="wp-sjs-import-json" data-sjs-import-json novalidate>
            <p>
                <label for="wp-sjs-import-json-files"><?php esc_html_e( 'Files (.json or .zip)', 'surveyjs' ); ?></label><br>
                <input type="file" id="wp-sjs-import-json-files" accept=".json,.zip,application/json,application/zip" multiple />
            </p>
            <details class="wp-sjs-import-json__paste">
                <summary><?php esc_html_e( 'Paste JSON', 'surveyjs' ); ?></summary>
                <p>
                    <label for="wp-sjs-import-json-text" class="screen-reader-text"><?php esc_html_e( 'Form JSON', 'surveyjs' ); ?></label>
                    <textarea id="wp-sjs-import-json-text" class="large-text code" rows="8" spellcheck="false"></textarea>
                </p>
                <p><button type="button" class="button" data-sjs-import-json-add><?php esc_html_e( 'Add to the list', 'surveyjs' ); ?></button></p>
            </details>
            <div data-sjs-import-json-list hidden>
                <p data-sjs-import-json-summary role="status"></p>
                <table class="wp-list-table widefat fixed striped wp-sjs-import__forms">
                    <caption class="screen-reader-text"><?php esc_html_e( 'Forms to import', 'surveyjs' ); ?></caption>
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-json" type="checkbox" checked />
                                <label for="cb-select-all-json"><span class="screen-reader-text"><?php esc_html_e( 'Select All', 'surveyjs' ); ?></span></label>
                            </td>
                            <th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Form', 'surveyjs' ); ?></th>
                            <th scope="col" class="manage-column wp-sjs-import__file"><?php esc_html_e( 'File', 'surveyjs' ); ?></th>
                            <th scope="col" class="manage-column wp-sjs-import__status"><?php esc_html_e( 'Status', 'surveyjs' ); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <p class="submit">
                    <?php submit_button( __( 'Import', 'surveyjs' ), 'primary', 'submit', false ); ?>
                    <button type="button" class="button" data-sjs-import-json-clear><?php esc_html_e( 'Clear the list', 'surveyjs' ); ?></button>
                </p>
            </div>
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
            echo '<div class="notice notice-warning" data-sjs-snackbar><p>' . esc_html__( 'No forms were selected.', 'surveyjs' ) . '</p></div>';
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
