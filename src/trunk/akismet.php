<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Akismet {

    const NAME_PATTERN = '/name/i';

    public static function api_key() {
        return is_callable( array( 'Akismet', 'get_api_key' ) ) ? trim( (string) Akismet::get_api_key() ) : '';
    }

    public static function is_available() {
        return is_callable( array( 'Akismet', 'http_post' ) ) && '' !== self::api_key();
    }

    public static function is_enabled( $settings ) {
        return ! empty( $settings['akismetEnabled'] ) && self::is_available();
    }

    public static function plugin_url() {
        return is_callable( array( 'Akismet_Admin', 'get_page_url' ) ) ? Akismet_Admin::get_page_url() : admin_url( 'plugin-install.php?s=akismet&tab=search&type=term' );
    }

    public static function is_spam( $survey, $settings, $data ) {
        $data = is_array( $data ) ? $data : array();
        $questions = SurveyJS_Survey_Values::survey_questions( SurveyJS_Client::decode_survey_json( $survey->json ) );
        $user = wp_get_current_user();
        $request = array_merge( array(
            'blog'         => home_url(),
            'blog_lang'    => get_locale(),
            'blog_charset' => get_option( 'blog_charset' ),
            'user_ip'      => (string) SurveyJS_Entries::remote_ip(),
            'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '', // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__ -- sent to Akismet from an uncached AJAX request.
            'permalink'    => (string) wp_get_referer(),
            'comment_type' => 'contact-form',
            'user_role'    => $user->exists() ? implode( ',', $user->roles ) : '',
        ), self::fields( $questions, $settings, $data ) );
        $request = (array) apply_filters( 'surveyjs_akismet_request', $request, $survey, $data );
        $response = Akismet::http_post( http_build_query( $request ), 'comment-check' );
        return is_array( $response ) && 'true' === trim( (string) ( $response[1] ?? '' ) );
    }

    public static function fields( $questions, $settings, $data ) {
        $email = self::text_answer( $data[ $settings['notificationReplyTo'] ?? '' ] ?? '' );
        $names = array();
        $urls = array();
        $content = array();
        foreach ( $questions as $question ) {
            $type = $question['type'] ?? '';
            if ( ! in_array( $type, array( 'text', 'comment', 'multipletext' ), true ) || ! isset( $data[ $question['name'] ] ) ) {
                continue;
            }
            $answer = self::text_answer( $data[ $question['name'] ] );
            if ( '' === $answer ) {
                continue;
            }
            $input_type = $question['inputType'] ?? '';
            if ( 'email' === $input_type ) {
                $email = '' === $email && is_email( $answer ) ? $answer : $email;
            } elseif ( 'url' === $input_type ) {
                $urls[] = $answer;
            } elseif ( 'comment' !== $type && preg_match( self::NAME_PATTERN, $question['name'] . ' ' . SurveyJS_Survey_Values::question_title( $question ) ) ) {
                $names[] = $answer;
            } else {
                $content[] = $answer;
            }
        }
        return array_filter( array(
            'comment_author'       => implode( ' ', $names ),
            'comment_author_email' => is_email( $email ) ? $email : '',
            'comment_author_url'   => $urls ? $urls[0] : '',
            'comment_content'      => implode( "\n\n", $content ),
        ), 'strlen' );
    }

    private static function text_answer( $value ) {
        if ( is_array( $value ) ) {
            return implode( ' ', array_filter( array_map( array( __CLASS__, 'text_answer' ), $value ), 'strlen' ) );
        }
        return is_scalar( $value ) ? trim( (string) $value ) : '';
    }

    public static function import( &$settings, &$notes ) {
        if ( self::is_available() ) {
            $settings['akismetEnabled'] = true;
            return;
        }
        $notes[] = __( 'The form used Akismet. The Akismet check is off: activate the Akismet plugin and connect it to your Akismet account, then turn on the check on the Spam Protection tab.', 'surveyjs' );
    }
}
