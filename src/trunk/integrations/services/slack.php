<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Slack incoming webhooks: https://docs.slack.dev/messaging/sending-messages-using-incoming-webhooks/
class SurveyJS_Integration_Slack extends SurveyJS_Integration_Chat_Service {

    const SECTION_LIMIT = 2900;
    const MAX_SECTIONS = 20;
    const HEADER_LIMIT = 150;

    public function id() {
        return 'slack';
    }

    public function label() {
        return __( 'Slack', 'surveyjs' );
    }

    public function description() {
        return __( 'Post a message to a Slack channel for each entry.', 'surveyjs' );
    }

    public function help_html() {
        return '<p>' . wp_kses(
            sprintf(
                /* translators: %s: URL of the Slack apps page */
                __( 'How to get the webhook URL: open <a href="%s" target="_blank" rel="noopener noreferrer">your Slack apps</a>, click "Create New App" > "From scratch", turn on "Incoming Webhooks", click "Add New Webhook", choose the channel and copy the URL that starts with https://hooks.slack.com/services/. Each URL posts to one channel; add one connection per channel.', 'surveyjs' ),
                'https://api.slack.com/apps'
            ),
            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
        ) . '</p>';
    }

    protected function url_pattern() {
        return '#^https://hooks\.slack\.com/services/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+$#';
    }

    protected function url_prefix() {
        return 'https://hooks.slack.com/services/';
    }

    protected function format_help() {
        return __( 'Slack formatting works: *bold*, _italic_, ~strike~.', 'surveyjs' );
    }

    // mrkdwn treats & < > as control characters: <!channel> in an answer would notify the whole channel.
    public function escape( $text ) {
        return str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), (string) $text );
    }

    protected function field_line( $title, $answer ) {
        return '*' . $title . "*\n" . $answer;
    }

    protected function text_message( $text ) {
        return array( 'text' => $this->escape( $text ) );
    }

    // The top-level text is the notification preview, rendered as mrkdwn, so the heading is escaped there.
    protected function message( $title, $text, $entry_url, $context ) {
        $blocks = array(
            array(
                'type' => 'header',
                'text' => array( 'type' => 'plain_text', 'text' => mb_substr( '' !== $title ? $title : ' ', 0, self::HEADER_LIMIT ) ),
            ),
        );
        foreach ( array_slice( self::chunks( $text ), 0, self::MAX_SECTIONS ) as $chunk ) {
            $blocks[] = array(
                'type' => 'section',
                'text' => array( 'type' => 'mrkdwn', 'text' => $chunk ),
            );
        }
        if ( '' !== $entry_url ) {
            $blocks[] = array(
                'type'     => 'actions',
                'elements' => array(
                    array(
                        'type' => 'button',
                        'text' => array( 'type' => 'plain_text', 'text' => __( 'View entry', 'surveyjs' ) ),
                        'url'  => $entry_url,
                    ),
                ),
            );
        }
        if ( '' !== $context ) {
            $blocks[] = array(
                'type'     => 'context',
                'elements' => array( array( 'type' => 'mrkdwn', 'text' => $this->escape( $context ) ) ),
            );
        }
        return array(
            'text'   => $this->escape( $title ),
            'blocks' => $blocks,
        );
    }

    // A section takes up to 3000 characters; long answers are split at line breaks.
    private static function chunks( $text ) {
        if ( '' === $text ) {
            return array( ' ' );
        }
        $chunks = array();
        while ( mb_strlen( $text ) > self::SECTION_LIMIT ) {
            $head = mb_substr( $text, 0, self::SECTION_LIMIT );
            $cut = strrpos( $head, "\n" );
            $cut = false === $cut ? false : mb_strlen( substr( $head, 0, $cut ) );
            $cut = $cut ? $cut : self::SECTION_LIMIT;
            $chunks[] = mb_substr( $text, 0, $cut );
            $text = ltrim( mb_substr( $text, $cut ) );
        }
        $chunks[] = $text;
        return $chunks;
    }
}

SurveyJS_Integrations::register( 'SurveyJS_Integration_Slack' );
