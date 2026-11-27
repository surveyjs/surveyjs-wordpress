<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// A chat app that takes messages on an incoming webhook URL (Slack, Discord, Microsoft Teams, Google Chat). The
// connection is the URL; a form integration has a heading, a message with tags and an optional "View entry" link.
// A subclass gives the URL format and turns a heading and a text into the app's JSON body.
abstract class SurveyJS_Integration_Chat_Service extends SurveyJS_Integration_Service {

    // Regular expression the whole webhook URL must match.
    abstract protected function url_pattern();

    // The start of every webhook URL of the app, shown in hints and errors.
    abstract protected function url_prefix();

    // $title is plain text, $text is in the app's formatting with the answers already escaped, $entry_url is ''
    // when there is no link to show, and $context is a note for test messages or ''.
    abstract protected function message( $title, $text, $entry_url, $context );

    // A short plain text message, posted when the URL is connected or tested.
    abstract protected function text_message( $text );

    public function escape( $text ) {
        return (string) $text;
    }

    // One answer of {all_fields}; both arguments are escaped already.
    protected function field_line( $title, $answer ) {
        return $title . ":\n" . $answer;
    }

    protected function format_help() {
        return '';
    }

    public function credential_fields() {
        return array(
            'url' => array(
                'label'       => __( 'Webhook URL', 'surveyjs' ),
                'placeholder' => $this->url_prefix() . '…',
                'description' => __( 'Anyone who knows this URL can post to the channel, so it is stored encrypted and never shown again.', 'surveyjs' ),
            ),
        );
    }

    public function connect( $input ) {
        $url = trim( (string) ( $input['url'] ?? '' ) );
        if ( ! preg_match( $this->url_pattern(), $url ) ) {
            /* translators: 1: service name, for example Slack, 2: start of the URL, for example https://hooks.slack.com/services/ */
            return new WP_Error( 'surveyjs_webhook_url', sprintf( __( 'Paste the webhook URL from %1$s: it starts with %2$s.', 'surveyjs' ), $this->label(), $this->url_prefix() ) );
        }
        // A webhook URL cannot be checked without posting to it.
        $result = $this->post( $url, $this->text_message( __( 'SurveyJS Forms is connected to this channel.', 'surveyjs' ) ) );
        if ( ! $result['ok'] ) {
            return new WP_Error( 'surveyjs_webhook_url', $result['error'] );
        }
        return array(
            'credentials' => array( 'url' => $url ),
            /* translators: %s: last characters of the webhook URL */
            'account'     => sprintf( __( 'Webhook URL …%s', 'surveyjs' ), substr( rtrim( $url, '/' ), -4 ) ),
        );
    }

    // The token of a webhook URL is a long path segment or query value; the AI must not see it on its own either.
    public function secret_values( $credentials ) {
        $url = (string) ( $credentials['url'] ?? '' );
        if ( '' === $url ) {
            return array();
        }
        $parts = wp_parse_url( $url );
        wp_parse_str( (string) ( $parts['query'] ?? '' ), $query );
        $values = array( $url, (string) preg_replace( '#^https?://#', '', $url ) );
        foreach ( array_merge( explode( '/', (string) ( $parts['path'] ?? '' ) ), array_values( $query ) ) as $piece ) {
            if ( is_string( $piece ) && strlen( $piece ) >= 16 ) {
                $values[] = $piece;
            }
        }
        return $values;
    }

    public function feed_defaults() {
        return array(
            /* translators: Default heading of a chat message. Keep {form_name} as is. */
            'title'      => __( 'New entry: {form_name}', 'surveyjs' ),
            'message'    => '{all_fields}',
            'viewButton' => true,
        );
    }

    public function sanitize_feed( $feed ) {
        return array(
            'title'      => self::text( $feed['title'] ),
            'message'    => sanitize_textarea_field( is_scalar( $feed['message'] ) ? (string) $feed['message'] : '' ),
            'viewButton' => ! empty( $feed['viewButton'] ),
        );
    }

    public function feed_schema() {
        return sprintf(
            '%s fields: title (heading, plain text with tags), message (text with tags {field:<question name>}, {all_fields}, {form_name}, {site_name}, {entry_id}, {entry_url}; %s), viewButton (true: add a "View entry" link to the entry in WordPress; only when entries are stored).',
            $this->label(),
            '' !== $this->format_help() ? $this->format_help() : 'plain text'
        );
    }

    public function render_feed_rows() {
        ?>
        <tr>
            <th scope="row"><label for="wp-sjs-item-__KEY__-title"><?php esc_html_e( 'Heading', 'surveyjs' ); ?></label></th>
            <td><input type="text" id="wp-sjs-item-__KEY__-title" class="large-text" data-field="title" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="wp-sjs-item-__KEY__-message"><?php esc_html_e( 'Message', 'surveyjs' ); ?></label></th>
            <td>
                <textarea id="wp-sjs-item-__KEY__-message" class="large-text code" rows="5" data-field="message"></textarea>
                <p class="description"><?php echo esc_html( trim( __( 'Tags: {all_fields} (every answered question with its title), {form_name}, {site_name}, {entry_id}, {entry_url}.', 'surveyjs' ) . ' ' . $this->format_help() ) ); ?></p>
                <p class="description" data-field-tags hidden></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Button', 'surveyjs' ); ?></th>
            <td><label><input type="checkbox" data-field="viewButton" /> <?php esc_html_e( 'Add a "View entry" button that opens the entry in WordPress', 'surveyjs' ); ?></label></td>
        </tr>
        <?php
    }

    public function deliver( $feed, $credentials, $payload ) {
        return $this->post( (string) ( $credentials['url'] ?? '' ), $this->build( $feed, $payload, '' ) );
    }

    public function test( $feed, $credentials, $payload, $is_sample ) {
        $context = $is_sample ? __( 'Test message with sample answers from SurveyJS Forms.', 'surveyjs' ) : __( 'Test message with the latest entry from SurveyJS Forms.', 'surveyjs' );
        return $this->post( (string) ( $credentials['url'] ?? '' ), $this->build( $feed, $payload, $context ) );
    }

    private function build( $feed, $payload, $context ) {
        $defaults = $this->feed_defaults();
        $feed = array_merge( $defaults, $feed );
        $title = SurveyJS_Integrations::render_tags( '' !== trim( $feed['title'] ) ? $feed['title'] : $defaults['title'], $payload );
        $text = SurveyJS_Integrations::render_tags( '' !== trim( $feed['message'] ) ? $feed['message'] : '{all_fields}', $payload, array( $this, 'escape' ), array( $this, 'all_fields' ) );
        $entry_url = ! empty( $feed['viewButton'] ) ? (string) ( $payload['entry']['url'] ?? '' ) : '';
        return $this->message( trim( $title ), trim( $text ), $entry_url, $context );
    }

    public function all_fields( $payload ) {
        $lines = array();
        foreach ( is_array( $payload['fields'] ?? null ) ? $payload['fields'] : array() as $field ) {
            if ( '' !== (string) ( $field['text'] ?? '' ) ) {
                $lines[] = $this->field_line( $this->escape( SurveyJS_Integrations::plain( $field['title'] ) ), $this->escape( SurveyJS_Integrations::plain( $field['text'] ) ) );
            }
        }
        return $lines ? implode( "\n\n", $lines ) : $this->escape( __( 'No answers', 'surveyjs' ) );
    }

    // 401, 403, 404 and 410 mean the webhook was deleted or its channel is gone.
    protected function outcome( $code ) {
        return in_array( $code, array( 401, 403, 404, 410 ), true ) ? 'reauth' : self::outcome_for( $code );
    }

    protected function post( $url, $body ) {
        if ( ! preg_match( $this->url_pattern(), $url ) ) {
            return self::result( false, 0, __( 'The webhook URL is not valid. Replace it on Settings > Integrations.', 'surveyjs' ), 'reauth' );
        }
        $response = $this->request( 'POST', $url, array(
            'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
            'data_format' => 'body',
            'body'        => wp_json_encode( $body ),
        ) );
        if ( is_wp_error( $response ) ) {
            return self::result( false, 0, $response->get_error_message(), 'retry' );
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            return self::result( true, $code );
        }
        $answer = trim( wp_strip_all_tags( (string) wp_remote_retrieve_body( $response ) ) );
        $outcome = $this->outcome( $code );
        $error = trim( $code . ' ' . ( '' !== $answer ? mb_substr( $answer, 0, 100 ) : wp_remote_retrieve_response_message( $response ) ) );
        if ( 'reauth' === $outcome ) {
            /* translators: 1: error from the service, for example "404 no_service", 2: service name, for example Slack */
            $error = sprintf( __( '%1$s. The webhook was removed in %2$s or its channel is gone: add a new webhook URL on Settings > Integrations.', 'surveyjs' ), $error, $this->label() );
        }
        return self::result( false, $code, $error, $outcome, array( 'retry_after' => self::retry_after( $response ) ) );
    }
}
