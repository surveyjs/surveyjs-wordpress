<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// A service that entries can be sent to. A service of the plugin is one file in integrations/services/ that defines
// its class and passes it to SurveyJS_Integrations::register(); add-ons return instances from the
// surveyjs_integration_services filter. Services that post to a webhook URL of a chat app extend
// SurveyJS_Integration_Chat_Service; email marketing services with an API key extend SurveyJS_Integration_Contacts_Service.
abstract class SurveyJS_Integration_Service {

    const TIMEOUT = 15;

    abstract public function id();

    abstract public function label();

    public function description() {
        return '';
    }

    public function help_html() {
        return '';
    }

    // Which shared script of the form editor fills the service's rows: "contacts" (lists and field mapping) or ''.
    public function editor_kind() {
        return '';
    }

    // Fields of the connection form on Settings > Integrations: key => array( label, placeholder, description ).
    public function credential_fields() {
        return array();
    }

    // Returns array( 'credentials' => array, 'account' => string ) or WP_Error.
    abstract public function connect( $input );

    public function check( $credentials ) {
        $connected = $this->connect( $credentials );
        return is_wp_error( $connected ) ? $connected : $connected['account'];
    }

    // Exact values that let someone act as this site in the service; the AI never sees them.
    public function secret_values( $credentials ) {
        return array_values( array_filter( array_map( 'strval', is_array( $credentials ) ? $credentials : array() ) ) );
    }

    public function feed_defaults() {
        return array();
    }

    public function sanitize_feed( $feed ) {
        return array_intersect_key( $feed, $this->feed_defaults() );
    }

    // What the AI is told about the service's own fields of a form integration.
    public function feed_schema() {
        return '';
    }

    public function feed_error( $feed ) {
        return '';
    }

    // $payload is the answers snapshot taken at submission (SurveyJS_Webhooks::snapshot()).
    // Returns array( ok, code, error, outcome: ok|retry|permanent|reauth, retry_after, note ).
    abstract public function deliver( $feed, $credentials, $payload );

    // $payload is the latest entry or sample answers; $is_sample tells which.
    public function test( $feed, $credentials, $payload, $is_sample ) {
        return $this->deliver( $feed, $credentials, $payload );
    }

    // Lists the form editor loads from the service, e.g. the audiences of Mailchimp.
    public function targets( $credentials, $target, $args ) {
        return new WP_Error( 'surveyjs_no_targets', __( 'Nothing to load for this service.', 'surveyjs' ) );
    }

    // Rows of the integration card in the form editor, inside its form table. Inputs bound to a feed key have
    // data-field, and "__KEY__" in id and for attributes is replaced with the card's key.
    public function render_feed_rows() {
    }

    public function render_rows_after_conditions() {
    }

    protected function request( $method, $url, $args = array() ) {
        return wp_safe_remote_request( $url, array_merge( array(
            'method'      => $method,
            'timeout'     => (int) apply_filters( 'surveyjs_integration_timeout', self::TIMEOUT, $this->id() ),
            'redirection' => 0,
        ), $args ) );
    }

    protected static function result( $ok, $code = 0, $error = '', $outcome = '', $extra = array() ) {
        return array_merge( array(
            'ok'      => (bool) $ok,
            'code'    => (int) $code,
            'error'   => (string) $error,
            'outcome' => '' !== $outcome ? $outcome : ( $ok ? 'ok' : 'retry' ),
            'note'    => '',
        ), $extra );
    }

    // 5xx, 429 and network errors are worth retrying; 401 and 403 mean the connection needs a new key; any other
    // 4xx fails the same way again.
    protected static function outcome_for( $code ) {
        if ( 429 === $code || $code >= 500 || 0 === $code ) {
            return 'retry';
        }
        return in_array( $code, array( 401, 403 ), true ) ? 'reauth' : 'permanent';
    }

    protected static function retry_after( $response ) {
        $header = wp_remote_retrieve_header( $response, 'retry-after' );
        return is_numeric( $header ) ? (int) $header : 0;
    }

    protected static function text( $value ) {
        return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
    }
}
