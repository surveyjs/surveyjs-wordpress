<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Accounts connected on Settings > Integrations. Form settings refer to a connection by its id and never hold its
// credentials, so exporting, copying or showing form settings to the AI does not reveal them.
class SurveyJS_Connections {

    const OPTION = 'surveyjs_connections';
    const TAB = 'integrations';
    const NOTICE_TRANSIENT = 'surveyjs_connection_notice_';
    const CIPHER_PREFIX = 'sjs1:';
    const KEY_BYTES = 32;
    const NONCE_BYTES = 24;
    const MAX_CONNECTIONS = 50;

    public static function init() {
        add_action( 'admin_post_surveyjs_connection_save', array( __CLASS__, 'handle_save' ) );
        add_action( 'admin_post_surveyjs_connection_delete', array( __CLASS__, 'handle_delete' ) );
        add_action( 'admin_post_surveyjs_connection_test', array( __CLASS__, 'handle_test' ) );
        add_action( 'admin_notices', array( __CLASS__, 'attention_notice' ) );
    }

    public static function all() {
        $connections = get_option( self::OPTION, array() );
        return is_array( $connections ) ? array_filter( $connections, 'is_array' ) : array();
    }

    public static function find( $id ) {
        $connections = self::all();
        return is_string( $id ) && isset( $connections[ $id ] ) ? $connections[ $id ] : null;
    }

    public static function for_service( $service_id ) {
        return array_values( array_filter( self::all(), function ( $connection ) use ( $service_id ) {
            return ( $connection['service'] ?? '' ) === $service_id;
        } ) );
    }

    public static function credentials( $connection ) {
        $plain = is_array( $connection ) ? self::decrypt( (string) ( $connection['credentials'] ?? '' ) ) : false;
        $credentials = false !== $plain ? json_decode( $plain, true ) : null;
        return is_array( $credentials ) ? $credentials : null;
    }

    // What the form editor and the AI may see: no credentials.
    public static function public_list() {
        return array_values( array_map( function ( $connection ) {
            return array(
                'id'      => $connection['id'],
                'service' => $connection['service'],
                'label'   => $connection['label'],
                'account' => $connection['account'],
                'status'  => $connection['status'],
            );
        }, self::all() ) );
    }

    public static function mark( $id, $status, $error = '' ) {
        $connections = self::all();
        if ( ! isset( $connections[ $id ] ) || ( $connections[ $id ]['status'] === $status && $connections[ $id ]['error'] === $error ) ) {
            return;
        }
        $connections[ $id ]['status'] = $status;
        $connections[ $id ]['error'] = mb_substr( (string) $error, 0, 300 );
        update_option( self::OPTION, $connections, false );
    }

    public static function secret_values() {
        $values = array();
        foreach ( self::all() as $connection ) {
            $service = SurveyJS_Integrations::service( $connection['service'] ?? '' );
            $credentials = self::credentials( $connection );
            if ( $service && $credentials ) {
                $values = array_merge( $values, $service->secret_values( $credentials ) );
            }
        }
        return $values;
    }

    public static function usage( $id ) {
        $forms = array();
        foreach ( SurveyJS_Forms::all() as $form ) {
            foreach ( (array) SurveyJS_Form_Settings::get( $form )['integrations'] as $feed ) {
                if ( ( $feed['connection'] ?? '' ) === $id ) {
                    $forms[ (int) $form->id ] = $form->name;
                }
            }
        }
        return $forms;
    }

    private static function clean_credential( $value ) {
        return substr( (string) preg_replace( '/[\x00-\x20\x7F]/', '', (string) $value ), 0, 2000 );
    }

    private static function new_id( $taken ) {
        do {
            $id = 'c' . strtolower( wp_generate_password( 8, false ) );
        } while ( in_array( $id, $taken, true ) );
        return $id;
    }

    // The key comes from SURVEYJS_ENCRYPTION_KEY or the site's salts: a database dump or backup alone does not
    // reveal the credentials. New salts make them unreadable, and the connection asks for the key again.
    private static function key() {
        $secret = defined( 'SURVEYJS_ENCRYPTION_KEY' ) && '' !== (string) SURVEYJS_ENCRYPTION_KEY ? (string) SURVEYJS_ENCRYPTION_KEY : wp_salt( 'logged_in' );
        return hash_hkdf( 'sha256', $secret, self::KEY_BYTES, 'surveyjs-connections' );
    }

    public static function can_encrypt() {
        return function_exists( 'sodium_crypto_secretbox' ) && function_exists( 'random_bytes' );
    }

    public static function encrypt( $plain ) {
        $nonce = random_bytes( self::NONCE_BYTES );
        return self::CIPHER_PREFIX . bin2hex( $nonce . sodium_crypto_secretbox( (string) $plain, $nonce, self::key() ) );
    }

    public static function decrypt( $cipher ) {
        if ( ! self::can_encrypt() || 0 !== strpos( $cipher, self::CIPHER_PREFIX ) ) {
            return false;
        }
        $raw = hex2bin( substr( $cipher, strlen( self::CIPHER_PREFIX ) ) );
        if ( false === $raw || strlen( $raw ) <= self::NONCE_BYTES ) {
            return false;
        }
        try {
            return sodium_crypto_secretbox_open( substr( $raw, self::NONCE_BYTES ), substr( $raw, 0, self::NONCE_BYTES ), self::key() );
        } catch ( SodiumException $exception ) {
            return false;
        }
    }

    private static function settings_url( $args = array() ) {
        return add_query_arg( array_merge( array( 'page' => 'surveyjs-settings', 'tab' => self::TAB ), $args ), admin_url( 'admin.php' ) );
    }

    private static function finish( $type, $message, $anchor = '' ) {
        set_transient( self::NOTICE_TRANSIENT . get_current_user_id(), array( 'type' => $type, 'message' => $message ), MINUTE_IN_SECONDS );
        wp_safe_redirect( self::settings_url() . ( '' !== $anchor ? '#' . $anchor : '' ) );
        exit;
    }

    private static function require_capability() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not allowed.', 'surveyjs' ), 403 );
        }
    }

    public static function handle_save() {
        self::require_capability();
        check_admin_referer( 'surveyjs_connection_save' );
        $service = SurveyJS_Integrations::service( sanitize_key( wp_unslash( $_POST['service'] ?? '' ) ) );
        if ( ! $service ) {
            self::finish( 'error', __( 'Unknown service.', 'surveyjs' ) );
        }
        if ( ! self::can_encrypt() ) {
            self::finish( 'error', __( 'This server cannot encrypt the key (the PHP sodium extension is missing), so it was not saved.', 'surveyjs' ) );
        }
        $connections = self::all();
        $id = sanitize_key( wp_unslash( $_POST['connection'] ?? '' ) );
        $existing = '' !== $id ? ( $connections[ $id ] ?? null ) : null;
        if ( '' !== $id && ( ! $existing || $existing['service'] !== $service->id() ) ) {
            self::finish( 'error', __( 'The connection was not found.', 'surveyjs' ) );
        }
        if ( ! $existing && count( $connections ) >= self::MAX_CONNECTIONS ) {
            self::finish( 'error', __( 'Too many connections. Delete one you no longer use first.', 'surveyjs' ) );
        }
        $input = array();
        foreach ( array_keys( $service->credential_fields() ) as $field ) {
            $value = isset( $_POST['credentials'][ $field ] ) && is_string( $_POST['credentials'][ $field ] ) ? wp_unslash( $_POST['credentials'][ $field ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cleaned by clean_credential(); sanitize_text_field() would strip percent-encoded parts of webhook URLs.
            $input[ $field ] = self::clean_credential( $value );
        }
        $connected = $service->connect( $input );
        if ( is_wp_error( $connected ) ) {
            /* translators: %s: error message from the service */
            self::finish( 'error', sprintf( __( 'Not connected: %s', 'surveyjs' ), $connected->get_error_message() ), 'sjs-service-' . $service->id() );
        }
        $label = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
        $connection = array_merge( $existing ? $existing : array(
            'id'         => self::new_id( array_keys( $connections ) ),
            'service'    => $service->id(),
            'created_at' => time(),
            'created_by' => get_current_user_id(),
        ), array(
            'label'       => '' !== $label ? $label : ( $existing ? $existing['label'] : $connected['account'] ),
            'account'     => (string) $connected['account'],
            'status'      => 'ok',
            'error'       => '',
            'credentials' => self::encrypt( wp_json_encode( $connected['credentials'] ) ),
        ) );
        $connections[ $connection['id'] ] = $connection;
        update_option( self::OPTION, $connections, false );
        SurveyJS_Integrations::forget_targets( $connection['id'] );
        if ( $existing ) {
            /* translators: %s: connection name */
            $message = sprintf( __( '%s: the new key is saved.', 'surveyjs' ), $connection['label'] );
        } else {
            /* translators: %s: connection name */
            $message = sprintf( __( '%s is connected. Add it to a form on the Integrations tab of the form editor.', 'surveyjs' ), $connection['label'] );
        }
        self::finish( 'success', $message, 'sjs-service-' . $service->id() );
    }

    public static function handle_delete() {
        self::require_capability();
        check_admin_referer( 'surveyjs_connection_delete' );
        $connections = self::all();
        $id = sanitize_key( wp_unslash( $_POST['connection'] ?? '' ) );
        if ( ! isset( $connections[ $id ] ) ) {
            self::finish( 'error', __( 'The connection was not found.', 'surveyjs' ) );
        }
        $label = $connections[ $id ]['label'];
        unset( $connections[ $id ] );
        update_option( self::OPTION, $connections, false );
        SurveyJS_Integrations::forget_targets( $id );
        /* translators: %s: connection name */
        self::finish( 'success', sprintf( __( '%s was disconnected. Forms that used it no longer send entries there.', 'surveyjs' ), $label ) );
    }

    public static function handle_test() {
        self::require_capability();
        check_admin_referer( 'surveyjs_connection_test' );
        $connection = self::find( sanitize_key( wp_unslash( $_POST['connection'] ?? '' ) ) );
        $service = $connection ? SurveyJS_Integrations::service( $connection['service'] ) : null;
        if ( ! $service ) {
            self::finish( 'error', __( 'The connection was not found.', 'surveyjs' ) );
        }
        $credentials = self::credentials( $connection );
        $account = $credentials ? $service->check( $credentials ) : new WP_Error( 'surveyjs_unreadable', __( 'The saved key cannot be read, for example because the site\'s security keys changed. Enter the key again.', 'surveyjs' ) );
        if ( is_wp_error( $account ) ) {
            self::mark( $connection['id'], 'error', $account->get_error_message() );
            /* translators: 1: connection name, 2: error message */
            self::finish( 'error', sprintf( __( '%1$s: %2$s', 'surveyjs' ), $connection['label'], $account->get_error_message() ), 'sjs-service-' . $service->id() );
        }
        self::mark( $connection['id'], 'ok' );
        /* translators: 1: connection name, 2: account, for example an email address */
        self::finish( 'success', sprintf( __( '%1$s works: %2$s.', 'surveyjs' ), $connection['label'], $account ), 'sjs-service-' . $service->id() );
    }

    public static function attention_notice() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || false === strpos( (string) $screen->id, 'surveyjs' ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $broken = array_filter( self::all(), function ( $connection ) {
            return 'error' === $connection['status'];
        } );
        if ( ! $broken ) {
            return;
        }
        $names = implode( ', ', array_map( function ( $connection ) {
            return $connection['label'];
        }, $broken ) );
        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
            /* translators: %s: comma-separated connection names */
            esc_html( sprintf( __( 'SurveyJS integrations need your attention: %s could not send entries. They are retried for a few hours.', 'surveyjs' ), $names ) ),
            esc_url( self::settings_url() ),
            esc_html__( 'Check the connections', 'surveyjs' )
        );
    }

    public static function render_tab() {
        $notice = get_transient( self::NOTICE_TRANSIENT . get_current_user_id() );
        if ( is_array( $notice ) ) {
            delete_transient( self::NOTICE_TRANSIENT . get_current_user_id() );
            printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( 'error' === $notice['type'] ? 'error' : 'success' ), esc_html( $notice['message'] ) );
        }
        ?>
        <p><?php esc_html_e( 'Connect your accounts here once, then choose what each form sends on the Integrations tab of the form editor. Keys are stored encrypted and are never shown again, not even to AI assistants.', 'surveyjs' ); ?></p>
        <div class="sjs-integrations">
            <?php
            foreach ( SurveyJS_Integrations::services() as $service ) {
                self::render_service( $service );
            }
            ?>
        </div>
        <?php
    }

    private static function render_service( $service ) {
        $connections = self::for_service( $service->id() );
        $id = 'sjs-service-' . $service->id();
        ?>
        <div class="card sjs-integrations__service" id="<?php echo esc_attr( $id ); ?>">
            <h2 class="title"><?php echo esc_html( $service->label() ); ?></h2>
            <p><?php echo esc_html( $service->description() ); ?></p>
            <?php if ( $connections ) : ?>
                <table class="widefat striped sjs-integrations__connections">
                    <thead><tr>
                        <th scope="col"><?php esc_html_e( 'Connection', 'surveyjs' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Status', 'surveyjs' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Used in', 'surveyjs' ); ?></th>
                        <th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'surveyjs' ); ?></span></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $connections as $connection ) : ?>
                        <?php self::render_connection_row( $service, $connection ); ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <details class="sjs-integrations__add"<?php echo $connections ? '' : ' open'; ?>>
                <summary><?php echo esc_html( $connections ? __( 'Add another connection', 'surveyjs' ) : __( 'Add connection', 'surveyjs' ) ); ?></summary>
                <?php self::render_connection_form( $service ); ?>
            </details>
            <?php
            $help = $service->help_html();
            if ( '' !== $help ) {
                echo '<div class="sjs-integrations__help">' . wp_kses_post( $help ) . '</div>';
            }
            ?>
        </div>
        <?php
    }

    private static function render_connection_row( $service, $connection ) {
        $usage = self::usage( $connection['id'] );
        ?>
        <tr>
            <td>
                <strong><?php echo esc_html( $connection['label'] ); ?></strong><br />
                <span class="description"><?php echo esc_html( $connection['account'] ); ?></span>
            </td>
            <td>
                <?php if ( 'error' === $connection['status'] ) : ?>
                    <span class="sjs-integrations__status is-error"><?php esc_html_e( 'Needs attention', 'surveyjs' ); ?></span>
                    <br /><span class="description"><?php echo esc_html( $connection['error'] ); ?></span>
                <?php else : ?>
                    <span class="sjs-integrations__status is-ok"><?php esc_html_e( 'Connected', 'surveyjs' ); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php
                if ( ! $usage ) {
                    esc_html_e( 'No forms yet', 'surveyjs' );
                }
                $links = array();
                foreach ( $usage as $form_id => $name ) {
                    $links[] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'surveyjs_editor', 'id' => $form_id, 'tab' => SurveyJS_Integrations::TAB ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $name ) . '</a>';
                }
                echo wp_kses( implode( ', ', $links ), array( 'a' => array( 'href' => array() ) ) );
                ?>
            </td>
            <td class="sjs-integrations__actions">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="surveyjs_connection_test" />
                    <input type="hidden" name="connection" value="<?php echo esc_attr( $connection['id'] ); ?>" />
                    <?php wp_nonce_field( 'surveyjs_connection_test' ); ?>
                    <button type="submit" class="button"><?php esc_html_e( 'Test', 'surveyjs' ); ?></button>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-confirm="<?php echo esc_attr( $usage ? __( 'Disconnect? The forms that use it stop sending entries there.', 'surveyjs' ) : __( 'Disconnect?', 'surveyjs' ) ); ?>">
                    <input type="hidden" name="action" value="surveyjs_connection_delete" />
                    <input type="hidden" name="connection" value="<?php echo esc_attr( $connection['id'] ); ?>" />
                    <?php wp_nonce_field( 'surveyjs_connection_delete' ); ?>
                    <button type="submit" class="button-link button-link-delete"><?php esc_html_e( 'Disconnect', 'surveyjs' ); ?></button>
                </form>
                <details>
                    <summary><?php esc_html_e( 'Replace key', 'surveyjs' ); ?></summary>
                    <?php self::render_connection_form( $service, $connection ); ?>
                </details>
            </td>
        </tr>
        <?php
    }

    private static function render_connection_form( $service, $connection = null ) {
        $prefix = 'sjs-' . $service->id() . '-' . ( $connection ? $connection['id'] : 'new' );
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sjs-integrations__form">
            <input type="hidden" name="action" value="surveyjs_connection_save" />
            <input type="hidden" name="service" value="<?php echo esc_attr( $service->id() ); ?>" />
            <?php if ( $connection ) : ?>
                <input type="hidden" name="connection" value="<?php echo esc_attr( $connection['id'] ); ?>" />
            <?php endif; ?>
            <?php wp_nonce_field( 'surveyjs_connection_save' ); ?>
            <p>
                <label for="<?php echo esc_attr( $prefix . '-label' ); ?>"><?php esc_html_e( 'Name', 'surveyjs' ); ?></label><br />
                <input type="text" class="regular-text" name="label" id="<?php echo esc_attr( $prefix . '-label' ); ?>" value="<?php echo esc_attr( $connection ? $connection['label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'For example: Marketing team', 'surveyjs' ); ?>" />
            </p>
            <?php foreach ( $service->credential_fields() as $field => $args ) : ?>
                <p>
                    <label for="<?php echo esc_attr( $prefix . '-' . $field ); ?>"><?php echo esc_html( $args['label'] ); ?></label><br />
                    <input type="password" class="large-text code" name="credentials[<?php echo esc_attr( $field ); ?>]" id="<?php echo esc_attr( $prefix . '-' . $field ); ?>" autocomplete="new-password" required placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>" />
                    <?php if ( ! empty( $args['description'] ) ) : ?>
                        <span class="description"><?php echo esc_html( $args['description'] ); ?></span>
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
            <p><button type="submit" class="button button-primary"><?php echo esc_html( $connection ? __( 'Save new key', 'surveyjs' ) : __( 'Connect', 'surveyjs' ) ); ?></button></p>
        </form>
        <?php
    }
}
