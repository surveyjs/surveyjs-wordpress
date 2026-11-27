<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// An email marketing service connected with an API key (Mailchimp, Brevo, ActiveCampaign, MailerLite). A form
// integration adds or updates a contact in a list with mapped fields and tags. A subclass checks the key and
// talks to the API: account(), lists(), fields() and subscribe(); the form editor's "contacts" script does the rest.
abstract class SurveyJS_Integration_Contacts_Service extends SurveyJS_Integration_Service {

    const MAX_TAGS = 20;
    const MAX_FIELDS = 50;

    // Checks the format of what was entered; returns the credentials to store or WP_Error.
    abstract protected function credentials_from( $input );

    // The account the credentials belong to, as text (name, email), or WP_Error.
    abstract protected function account( $credentials );

    // array( array( 'value' => id, 'text' => label, 'name' => name ) ) or WP_Error.
    abstract public function lists( $credentials );

    // Contact fields of a list other than the email:
    // array( array( 'tag', 'name', 'type', 'required', 'supported' ) ) or WP_Error.
    abstract public function fields( $credentials, $list );

    // $contact: array( 'email' => string, 'fields' => array( tag => value ), 'tags' => string[] ). Returns result().
    abstract protected function subscribe( $credentials, $feed, $contact );

    public function editor_kind() {
        return 'contacts';
    }

    public function list_label() {
        return __( 'List', 'surveyjs' );
    }

    public function fields_label() {
        return __( 'Contact fields', 'surveyjs' );
    }

    protected function no_list_error() {
        return __( 'Choose a list in the integration settings.', 'surveyjs' );
    }

    protected function options_help() {
        return '';
    }

    public function consent_question_title() {
        return __( 'Subscribe me to the newsletter', 'surveyjs' );
    }

    public function feed_error( $feed ) {
        if ( ! empty( $feed['signupForm'] ) || ! empty( $feed['conditions']['rules'] ) ) {
            return '';
        }
        return sprintf(
            /* translators: 1: integration name, 2: service name, for example Mailchimp, 3: label of the checkbox */
            __( 'The integration "%1$s" would add every respondent to %2$s without their consent. Add a consent question and a condition on it (the button under Conditions does both), or check "%3$s".', 'surveyjs' ),
            (string) ( $feed['name'] ?? '' ),
            $this->label(),
            $this->signup_form_label()
        );
    }

    protected function signup_form_label() {
        return __( 'This form is a newsletter signup (submitting it means consent)', 'surveyjs' );
    }

    // "tags", "doubleOptIn" and "updateExisting": the options the integration card shows.
    protected function supports( $feature ) {
        return in_array( $feature, array( 'tags', 'doubleOptIn', 'updateExisting' ), true );
    }

    public function credential_fields() {
        return array(
            'api_key' => array(
                'label'       => __( 'API key', 'surveyjs' ),
                'placeholder' => '',
                'description' => __( 'Stored encrypted and never shown again.', 'surveyjs' ),
            ),
        );
    }

    public function connect( $input ) {
        $credentials = $this->credentials_from( $input );
        if ( is_wp_error( $credentials ) ) {
            return $credentials;
        }
        $account = $this->account( $credentials );
        return is_wp_error( $account ) ? $account : array( 'credentials' => $credentials, 'account' => $account );
    }

    public function feed_defaults() {
        return array(
            'list'           => '',
            'emailQuestion'  => '',
            'fields'         => array(),
            'tags'           => '',
            'doubleOptIn'    => true,
            'updateExisting' => true,
            'signupForm'     => false,
        );
    }

    // Mapped fields are a list of pairs, not an object keyed by field: numeric field ids would turn an object into a
    // JSON array, and an empty object would read back as an empty array.
    public function sanitize_feed( $feed ) {
        $fields = array();
        foreach ( is_array( $feed['fields'] ) ? $feed['fields'] : array() as $pair ) {
            $field = is_array( $pair ) && is_scalar( $pair['field'] ?? null ) ? substr( (string) preg_replace( '/[^A-Za-z0-9_.-]/', '', (string) $pair['field'] ), 0, 100 ) : '';
            $value = is_array( $pair ) ? self::text( $pair['value'] ?? '' ) : '';
            if ( '' !== $field && '' !== $value && ! in_array( $field, array_column( $fields, 'field' ), true ) && count( $fields ) < self::MAX_FIELDS ) {
                $fields[] = array( 'field' => $field, 'value' => $value );
            }
        }
        return array(
            'list'           => substr( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', is_scalar( $feed['list'] ) ? (string) $feed['list'] : '' ), 0, 64 ),
            'emailQuestion'  => self::text( $feed['emailQuestion'] ),
            'fields'         => $fields,
            'tags'           => $this->supports( 'tags' ) ? self::text( $feed['tags'] ) : '',
            'doubleOptIn'    => $this->supports( 'doubleOptIn' ) && ! empty( $feed['doubleOptIn'] ),
            'updateExisting' => ! $this->supports( 'updateExisting' ) || ! empty( $feed['updateExisting'] ),
            'signupForm'     => ! empty( $feed['signupForm'] ),
        );
    }

    public function feed_schema() {
        $fields = array(
            'list (id of the ' . strtolower( $this->list_label() ) . '; the person picks it in the form editor)',
            'emailQuestion (name of the question with the email address; required)',
            'fields (list of {"field": field id from the ' . strtolower( $this->fields_label() ) . ' of the list, "value": text with tags, usually {field:<question name>}})',
        );
        if ( $this->supports( 'tags' ) ) {
            $fields[] = 'tags (comma-separated tags, tags allowed)';
        }
        if ( $this->supports( 'doubleOptIn' ) ) {
            $fields[] = 'doubleOptIn (true, the default: new contacts get a confirmation email first)';
        }
        if ( $this->supports( 'updateExisting' ) ) {
            $fields[] = 'updateExisting (true, the default: update contacts already in the list)';
        }
        $fields[] = 'signupForm (true only when the person says the whole form is a signup form, so submitting it means consent)';
        return $this->label() . ' fields: ' . implode( ', ', $fields ) . '. Consent is required: the integration is not saved without a condition on a consent question (for example a boolean question "' . $this->consent_question_title() . '" and the rule equal "true") unless signupForm is true.';
    }

    public function render_feed_rows() {
        ?>
        <tr>
            <th scope="row"><label for="wp-sjs-item-__KEY__-list"><?php echo esc_html( $this->list_label() ); ?></label></th>
            <td>
                <select id="wp-sjs-item-__KEY__-list" data-field="list" data-target-list></select>
                <span class="wp-sjs-single-choice" data-single-choice hidden></span>
                <button type="button" class="button" data-action="refresh-targets"><?php esc_html_e( 'Refresh', 'surveyjs' ); ?></button>
                <div data-targets-status aria-live="polite"></div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="wp-sjs-item-__KEY__-email-question"><?php esc_html_e( 'Email question', 'surveyjs' ); ?></label></th>
            <td>
                <select id="wp-sjs-item-__KEY__-email-question" data-field="emailQuestion" data-questions="email"></select>
                <p class="description"><?php esc_html_e( 'The question of this form whose answer is the contact\'s email. Entries without a valid email are skipped.', 'surveyjs' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html( $this->fields_label() ); ?></th>
            <td>
                <table class="widefat striped wp-sjs-field-map" data-field-map hidden>
                    <thead><tr><th scope="col"><?php echo esc_html( $this->label() ); ?></th><th scope="col"><?php esc_html_e( 'Value', 'surveyjs' ); ?></th></tr></thead>
                    <tbody></tbody>
                </table>
                <p class="description" data-field-map-empty></p>
                <p class="description">
                    <?php
                    /* translators: %s: service name, for example Mailchimp */
                    echo esc_html( sprintf( __( 'Which form question fills each field of the contact in %s.', 'surveyjs' ), $this->label() ) );
                    ?>
                </p>
            </td>
        </tr>
        <?php if ( $this->supports( 'tags' ) ) : ?>
            <tr>
                <th scope="row"><label for="wp-sjs-item-__KEY__-tags"><?php esc_html_e( 'Tags', 'surveyjs' ); ?></label></th>
                <td>
                    <input type="text" id="wp-sjs-item-__KEY__-tags" class="regular-text" data-field="tags" placeholder="<?php esc_attr_e( 'website, {field:interest}', 'surveyjs' ); ?>" />
                    <p class="description"><?php esc_html_e( 'Comma-separated. Tags are added to the contact and never removed.', 'surveyjs' ); ?></p>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <th scope="row"><?php esc_html_e( 'Options', 'surveyjs' ); ?></th>
            <td>
                <?php if ( $this->supports( 'doubleOptIn' ) ) : ?>
                    <p>
                        <label><input type="checkbox" data-field="doubleOptIn" /> <?php esc_html_e( 'Double opt-in', 'surveyjs' ); ?></label>
                        <br /><span class="description">
                            <?php
                            /* translators: %s: service name, for example Mailchimp */
                            echo esc_html( sprintf( __( '%s first emails a "Confirm your subscription" link; the contact is subscribed after clicking it.', 'surveyjs' ), $this->label() ) );
                            ?>
                        </span>
                    </p>
                <?php endif; ?>
                <?php if ( $this->supports( 'updateExisting' ) ) : ?>
                    <p>
                        <label><input type="checkbox" data-field="updateExisting" /> <?php esc_html_e( 'Update existing contacts', 'surveyjs' ); ?></label>
                        <br /><span class="description"><?php esc_html_e( 'If the email is already in the list, its fields are updated.', 'surveyjs' ); ?></span>
                    </p>
                <?php endif; ?>
                <?php if ( '' !== $this->options_help() ) : ?>
                    <p class="description"><?php echo esc_html( $this->options_help() ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    public function render_rows_after_conditions() {
        ?>
        <tr>
            <th scope="row"><?php esc_html_e( 'Consent', 'surveyjs' ); ?></th>
            <td>
                <p>
                    <button type="button" class="button" data-action="add-consent-question" data-title="<?php echo esc_attr( $this->consent_question_title() ); ?>">
                        <?php
                        /* translators: %s: title of the question that is added to the form */
                        echo esc_html( sprintf( __( 'Add "%s" question', 'surveyjs' ), $this->consent_question_title() ) );
                        ?>
                    </button>
                </p>
                <p class="description"><?php esc_html_e( 'Adds an optional checkbox at the end of the form and sends only the entries where it is checked.', 'surveyjs' ); ?></p>
                <p><label><input type="checkbox" data-field="signupForm" /> <?php echo esc_html( $this->signup_form_label() ); ?></label></p>
                <div data-consent-status aria-live="polite"></div>
            </td>
        </tr>
        <?php
    }

    public function targets( $credentials, $target, $args ) {
        if ( 'lists' === $target ) {
            return $this->lists( $credentials );
        }
        if ( 'fields' === $target ) {
            $list = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $args['list'] ?? '' ) );
            return '' === $list ? array() : $this->fields( $credentials, $list );
        }
        return parent::targets( $credentials, $target, $args );
    }

    public function deliver( $feed, $credentials, $payload ) {
        $feed = array_merge( $this->feed_defaults(), $feed );
        if ( '' === $feed['list'] ) {
            return self::result( false, 0, $this->no_list_error(), 'permanent' );
        }
        $email = sanitize_email( SurveyJS_Integrations::plain( ( is_scalar( $feed['emailQuestion'] ?? null ) ? $payload['answers'][ $feed['emailQuestion'] ] ?? '' : '' ) ) );
        if ( ! is_email( $email ) ) {
            return self::result( true, 0, '', 'ok', array( 'note' => __( 'Skipped: the entry has no valid email address.', 'surveyjs' ) ) );
        }
        return $this->subscribe( $credentials, $feed, array(
            'email'  => $email,
            'fields' => $this->field_values( $feed, $payload ),
            'tags'   => $this->supports( 'tags' ) ? $this->tag_values( $feed, $payload ) : array(),
        ) );
    }

    // Sample answers have no real email, so without entries the test only checks that the list can be reached.
    public function test( $feed, $credentials, $payload, $is_sample ) {
        if ( ! $is_sample ) {
            return $this->deliver( $feed, $credentials, $payload );
        }
        $feed = array_merge( $this->feed_defaults(), $feed );
        if ( '' === $feed['list'] ) {
            return self::result( false, 0, $this->no_list_error(), 'permanent' );
        }
        $lists = $this->lists( $credentials );
        if ( is_wp_error( $lists ) ) {
            return $this->failure( $lists );
        }
        foreach ( $lists as $list ) {
            if ( (string) $list['value'] === $feed['list'] ) {
                /* translators: %s: name of the list or audience */
                return self::result( true, 200, '', 'ok', array( 'note' => sprintf( __( '"%s" is reachable. No contact was added: submit the form once to send a real entry.', 'surveyjs' ), $list['name'] ?? $list['text'] ) ) );
            }
        }
        /* translators: %s: service name, for example Mailchimp */
        return self::result( false, 404, sprintf( __( 'The list was not found in %s. Choose it again.', 'surveyjs' ), $this->label() ), 'permanent' );
    }

    private function field_values( $feed, $payload ) {
        $values = array();
        foreach ( (array) $feed['fields'] as $pair ) {
            $value = trim( SurveyJS_Integrations::render_tags( (string) ( $pair['value'] ?? '' ), $payload ) );
            if ( '' !== $value ) {
                $values[ (string) $pair['field'] ] = $value;
            }
        }
        return $values;
    }

    private function tag_values( $feed, $payload ) {
        $tags = array_map( 'trim', explode( ',', SurveyJS_Integrations::render_tags( (string) $feed['tags'], $payload ) ) );
        return array_map( function ( $tag ) {
            return mb_substr( $tag, 0, 100 );
        }, array_slice( array_values( array_unique( array_filter( $tags, 'strlen' ) ) ), 0, self::MAX_TAGS ) );
    }

    // Any answer but 2xx comes back with a WP_Error whose data holds the HTTP status and Retry-After.
    protected function json_request( $method, $url, $headers, $body = null ) {
        $args = array( 'headers' => array_merge( array( 'Accept' => 'application/json' ), $headers ) );
        if ( null !== $body ) {
            $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
            $args['body'] = wp_json_encode( $body );
            $args['data_format'] = 'body';
        }
        $response = $this->request( $method, $url, $args );
        if ( is_wp_error( $response ) ) {
            return array( 'code' => 0, 'body' => array(), 'error' => new WP_Error( 'surveyjs_request', $response->get_error_message(), array( 'status' => 0 ) ) );
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        $decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        $decoded = is_array( $decoded ) ? $decoded : array();
        $error = null;
        if ( $code < 200 || $code >= 300 ) {
            $message = $this->error_message( $decoded );
            $message = '' !== $message ? $message : (string) wp_remote_retrieve_response_message( $response );
            /* translators: 1: service name, 2: HTTP status code, 3: error from the service */
            $error = new WP_Error( 'surveyjs_api', sprintf( __( '%1$s answered %2$d %3$s.', 'surveyjs' ), $this->label(), $code, rtrim( mb_substr( $message, 0, 250 ), '.' ) ), array(
                'status'      => $code,
                'retry_after' => self::retry_after( $response ),
            ) );
        }
        return array( 'code' => $code, 'body' => $decoded, 'error' => $error );
    }

    protected function error_message( $body ) {
        $parts = array();
        foreach ( array( 'title', 'detail', 'message', 'error' ) as $key ) {
            if ( is_scalar( $body[ $key ] ?? null ) && '' !== trim( (string) $body[ $key ] ) ) {
                $parts[] = trim( (string) $body[ $key ] );
            }
        }
        if ( ! $parts && is_array( $body['errors'][0] ?? null ) ) {
            $parts[] = (string) ( $body['errors'][0]['title'] ?? $body['errors'][0]['message'] ?? '' );
        }
        return implode( ': ', array_unique( array_filter( $parts, 'strlen' ) ) );
    }

    protected function failure( $error ) {
        $data = (array) $error->get_error_data();
        $code = (int) ( $data['status'] ?? 0 );
        $outcome = self::outcome_for( $code );
        $message = $error->get_error_message();
        if ( 'reauth' === $outcome ) {
            /* translators: %s: error from the service */
            $message = sprintf( __( '%s Replace the API key on Settings > Integrations.', 'surveyjs' ), $message );
        }
        return self::result( false, $code, $message, $outcome, array( 'retry_after' => (int) ( $data['retry_after'] ?? 0 ) ) );
    }
}
