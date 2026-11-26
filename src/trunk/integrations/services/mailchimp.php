<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Mailchimp Marketing API 3.0 with an API key: https://mailchimp.com/developer/marketing/api/
class SurveyJS_Integration_Mailchimp extends SurveyJS_Integration_Contacts_Service {

    const KEY_PATTERN = '/^([0-9a-f]{32})-([a-z]{2,5}[0-9]{1,3})$/';
    // Address merge fields take an object, and birthday takes MM/DD while a date question answers YYYY-MM-DD.
    const UNSUPPORTED_TYPES = array( 'address', 'birthday' );

    public function id() {
        return 'mailchimp';
    }

    public function label() {
        return __( 'Mailchimp', 'surveyjs' );
    }

    public function description() {
        return __( 'Add or update a contact in a Mailchimp audience for each entry, with merge fields and tags.', 'surveyjs' );
    }

    public function help_html() {
        return '<p>' . wp_kses(
            sprintf(
                /* translators: %s: URL of the Mailchimp API keys page */
                __( 'How to get the API key: in Mailchimp open <a href="%s" target="_blank" rel="noopener noreferrer">Profile > Extras > API keys</a>, click "Create A Key" and copy it. The key ends with your data center, for example -us21.', 'surveyjs' ),
                'https://us1.admin.mailchimp.com/account/api/'
            ),
            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
        ) . '</p>';
    }

    public function list_label() {
        return __( 'Audience', 'surveyjs' );
    }

    protected function no_list_error() {
        return __( 'Choose a Mailchimp audience.', 'surveyjs' );
    }

    protected function options_help() {
        return __( 'Contacts who unsubscribed are never subscribed again.', 'surveyjs' );
    }

    public function credential_fields() {
        $fields = parent::credential_fields();
        $fields['api_key']['placeholder'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us21';
        return $fields;
    }

    private static function key_parts( $credentials ) {
        return preg_match( self::KEY_PATTERN, strtolower( trim( (string) ( $credentials['api_key'] ?? '' ) ) ), $matches ) ? $matches : null;
    }

    protected function credentials_from( $input ) {
        $parts = self::key_parts( $input );
        if ( ! $parts ) {
            return new WP_Error( 'surveyjs_mailchimp_key', __( 'This does not look like a Mailchimp API key: 32 letters and digits, a dash and the data center, for example -us21.', 'surveyjs' ) );
        }
        return array( 'api_key' => $parts[0] );
    }

    protected function account( $credentials ) {
        $response = $this->api( $credentials, 'GET', '/', array( 'fields' => 'account_name,email' ) );
        if ( $response['error'] ) {
            return $response['error'];
        }
        $name = trim( (string) ( $response['body']['account_name'] ?? '' ) );
        $email = (string) ( $response['body']['email'] ?? '' );
        return trim( $name . ( '' !== $email ? ' (' . $email . ')' : '' ) ) . ' · ' . self::key_parts( $credentials )[2];
    }

    public function secret_values( $credentials ) {
        $parts = self::key_parts( $credentials );
        return $parts ? array( $parts[0], $parts[1] ) : array();
    }

    public function lists( $credentials ) {
        $response = $this->api( $credentials, 'GET', '/lists', array(
            'fields' => 'lists.id,lists.name,lists.stats.member_count',
            'count'  => 1000,
        ) );
        if ( $response['error'] ) {
            return $response['error'];
        }
        return array_map( function ( $list ) {
            $count = (int) ( $list['stats']['member_count'] ?? 0 );
            return array(
                'value' => (string) $list['id'],
                /* translators: 1: audience name, 2: number of contacts */
                'text'  => sprintf( _n( '%1$s (%2$d contact)', '%1$s (%2$d contacts)', $count, 'surveyjs' ), $list['name'], $count ),
                'name'  => (string) $list['name'],
            );
        }, (array) ( $response['body']['lists'] ?? array() ) );
    }

    public function fields( $credentials, $list ) {
        $response = $this->api( $credentials, 'GET', '/lists/' . rawurlencode( $list ) . '/merge-fields', array(
            'fields' => 'merge_fields.tag,merge_fields.name,merge_fields.type,merge_fields.required',
            'count'  => 1000,
        ) );
        if ( $response['error'] ) {
            return $response['error'];
        }
        $fields = array_filter( (array) ( $response['body']['merge_fields'] ?? array() ), function ( $field ) {
            return 'EMAIL' !== ( $field['tag'] ?? '' );
        } );
        return array_values( array_map( function ( $field ) {
            return array(
                'tag'       => (string) $field['tag'],
                'name'      => (string) $field['name'],
                'type'      => (string) $field['type'],
                'required'  => ! empty( $field['required'] ),
                'supported' => ! in_array( $field['type'], self::UNSUPPORTED_TYPES, true ),
            );
        }, $fields ) );
    }

    // PUT with status_if_new adds a contact or updates one and never subscribes again someone who unsubscribed.
    protected function subscribe( $credentials, $feed, $contact ) {
        $member = '/lists/' . rawurlencode( $feed['list'] ) . '/members/' . md5( strtolower( $contact['email'] ) );
        if ( empty( $feed['updateExisting'] ) ) {
            $existing = $this->api( $credentials, 'GET', $member, array( 'fields' => 'status' ) );
            if ( 200 === $existing['code'] ) {
                return $this->add_tags( $credentials, $member, $contact, __( 'Already in the audience; not updated.', 'surveyjs' ) );
            }
            if ( 404 !== $existing['code'] ) {
                return $this->failure( $existing['error'] );
            }
        }
        $body = array(
            'email_address' => $contact['email'],
            'status_if_new' => empty( $feed['doubleOptIn'] ) ? 'subscribed' : 'pending',
        );
        if ( $contact['fields'] ) {
            $body['merge_fields'] = $contact['fields'];
        }
        $response = $this->api( $credentials, 'PUT', $member, array(), $body );
        if ( $response['error'] ) {
            return $this->failure( $response['error'] );
        }
        $status = (string) ( $response['body']['status'] ?? '' );
        $note = '';
        if ( 'pending' === $status ) {
            $note = __( 'Waiting for the contact to confirm the subscription (double opt-in).', 'surveyjs' );
        } elseif ( in_array( $status, array( 'unsubscribed', 'cleaned' ), true ) ) {
            /* translators: %s: Mailchimp status, for example "unsubscribed" */
            $note = sprintf( __( 'The contact is %s in Mailchimp, so it was updated but not subscribed again.', 'surveyjs' ), $status );
        }
        return $this->add_tags( $credentials, $member, $contact, $note );
    }

    private function add_tags( $credentials, $member, $contact, $note ) {
        if ( $contact['tags'] ) {
            $response = $this->api( $credentials, 'POST', $member . '/tags', array(), array(
                'tags' => array_map( function ( $tag ) {
                    return array( 'name' => $tag, 'status' => 'active' );
                }, $contact['tags'] ),
            ) );
            if ( $response['error'] ) {
                return $this->failure( $response['error'] );
            }
        }
        return self::result( true, 200, '', 'ok', array( 'note' => $note ) );
    }

    private function api( $credentials, $method, $path, $query = array(), $body = null ) {
        $parts = self::key_parts( $credentials );
        if ( ! $parts ) {
            return array( 'code' => 0, 'body' => array(), 'error' => new WP_Error( 'surveyjs_mailchimp_key', __( 'The saved Mailchimp API key is not valid.', 'surveyjs' ), array( 'status' => 401 ) ) );
        }
        $url = add_query_arg( $query, 'https://' . $parts[2] . '.api.mailchimp.com/3.0' . $path );
        return $this->json_request( $method, $url, array( 'Authorization' => 'Bearer ' . $parts[0] ), $body );
    }
}

SurveyJS_Integrations::register( 'SurveyJS_Integration_Mailchimp' );
