<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Form_Settings {

    public static function defaults() {
        return apply_filters( 'surveyjs_form_settings_defaults', self::base_defaults() );
    }

    public static function base_defaults() {
        return array(
            'storeEntries'          => true,
            'saveResume'            => false,
            'markdown'              => false,
            'savePartialEntries'    => false,
            'notificationEnabled'   => false,
            'notificationTo'        => '',
            'notificationCc'        => '',
            'notificationBcc'       => '',
            'notificationFromName'  => '',
            'notificationFromEmail' => '',
            'notificationReplyTo'   => '',
            'notificationSubject'   => '',
            'notificationMessage'   => '',
            'notificationHtml'      => false,
            'notificationAttachments' => '',
            'autoReplyEnabled'      => false,
            'autoReplyCc'           => '',
            'autoReplyBcc'          => '',
            'autoReplySubject'      => '',
            'autoReplyMessage'      => '',
            'autoReplyHtml'         => false,
            'autoReplyAttachments'  => '',
            'themeMode'             => 'adaptive',
            'transparentBackground' => false,
            'captchaEnabled'        => false,
            'akismetEnabled'        => false,
            'minSubmitTimeEnabled'  => false,
            'minSubmitTime'         => SurveyJS_Submit_Time::DEFAULT_SECONDS,
            'limitEntries'          => false,
            'entryLimit'            => 100,
            'entryLimitPeriod'      => 'total',
            'entryLimitMessage'     => '',
            'limitPerRespondent'    => false,
            'respondentLimit'       => 1,
            'respondentBy'          => 'user',
            'respondentLimitMessage' => '',
            'scheduleEnabled'       => false,
            'scheduleStart'         => '',
            'scheduleEnd'           => '',
            'schedulePendingMessage' => '',
            'scheduleExpiredMessage' => '',
            'requireLogin'          => false,
            'requireLoginMessage'   => '',
            'entryRetention'        => 'default',
            'entryRetentionDays'    => 90,
        );
    }

    public static function for_new_form( $form = array(), $theme_mode = 'adaptive' ) {
        $settings = array(
            'notificationEnabled'  => false,
            'minSubmitTimeEnabled' => true,
            'themeMode'            => $theme_mode,
        );
        foreach ( SurveyJS_Survey_Values::survey_questions( $form ) as $question ) {
            $type = $question['type'] ?? '';
            if ( empty( $settings['notificationReplyTo'] ) && 'text' === $type && 'email' === ( $question['inputType'] ?? '' ) ) {
                $settings['notificationReplyTo'] = $question['name'];
            }
            if ( empty( $settings['notificationAttachments'] ) && 'file' === $type ) {
                $settings['notificationAttachments'] = $question['name'];
            }
        }
        return self::sanitize( $settings );
    }

    public static function theme_modes() {
        return array( 'adaptive', 'surveyjs' );
    }

    public static function fallbacks() {
        return array(
            'notificationTo'        => get_option( 'admin_email' ),
            'notificationFromName'  => '{site_name}',
            'notificationFromEmail' => self::default_from_email(),
            /* translators: Default email subject. Keep {form_name} as is: it is replaced with the form name. */
            'notificationSubject'   => __( 'New entry: {form_name}', 'surveyjs' ),
            'notificationMessage'   => '{all_fields}',
            /* translators: Default auto-reply subject. Keep {site_name} as is: it is replaced with the site title. */
            'autoReplySubject'      => __( 'Thanks for contacting {site_name}', 'surveyjs' ),
            /* translators: Default auto-reply message. Keep {all_fields} as is: it is replaced with the answers. */
            'autoReplyMessage'      => __( "Thanks for your message. We received it and will get back to you soon.\n\nYour answers:\n{all_fields}", 'surveyjs' ),
            'entryLimitMessage'     => __( 'Sorry, this form is no longer accepting entries.', 'surveyjs' ),
            'respondentLimitMessage' => __( 'You have already submitted this form. Thank you!', 'surveyjs' ),
            /* translators: Default message before a scheduled form opens. Keep {open_date} as is: it is replaced with the opening date and time. */
            'schedulePendingMessage' => __( 'This form opens on {open_date}. Please come back then.', 'surveyjs' ),
            /* translators: Default message after a scheduled form closed. Keep {close_date} as is: it is replaced with the closing date and time. */
            'scheduleExpiredMessage' => __( 'Sorry, this form closed on {close_date} and is no longer accepting entries.', 'surveyjs' ),
            'requireLoginMessage'   => __( 'Please log in to fill in this form.', 'surveyjs' ),
        );
    }

    public static function default_from_email() {
        $host = strtolower( (string) wp_parse_url( network_home_url(), PHP_URL_HOST ) );
        return 'wordpress@' . preg_replace( '#^www\.#', '', $host );
    }

    public static function value( $settings, $key ) {
        $value = trim( (string) ( $settings[ $key ] ?? '' ) );
        return '' !== $value ? $value : ( self::fallbacks()[ $key ] ?? '' );
    }

    private static function stored( $survey ) {
        $stored = json_decode( (string) ( $survey->settings ?? '' ), true );
        return is_array( $stored ) ? $stored : array();
    }

    public static function get( $survey ) {
        $stored = array_intersect_key( self::stored( $survey ), self::defaults() );
        if ( ! isset( $stored['themeMode'] ) && ! empty( array_filter( SurveyJS_Client::decode_survey_json( $survey->theme ?? '' ) ) ) ) {
            // Forms styled before the adaptive theme existed keep their SurveyJS theme.
            $stored['themeMode'] = 'surveyjs';
        }
        return array_merge( self::defaults(), $stored );
    }

    // Pass $survey when the result replaces its stored settings: keys no plugin declares right now
    // (e.g. Pro's while Pro is inactive) are kept instead of being dropped.
    public static function sanitize( $input, $survey = null ) {
        $input = array_merge( self::defaults(), is_array( $input ) ? $input : array() );
        $sanitized = apply_filters( 'surveyjs_form_settings_sanitize', self::sanitize_base( $input ), $input );
        $sanitized = array_intersect_key( is_array( $sanitized ) ? $sanitized : array(), self::defaults() );
        return $survey ? $sanitized + array_diff_key( self::stored( $survey ), self::defaults() ) : $sanitized;
    }

    private static function sanitize_base( $input ) {
        return array(
            'storeEntries'          => ! empty( $input['storeEntries'] ),
            'saveResume'            => ! empty( $input['saveResume'] ),
            'markdown'              => ! empty( $input['markdown'] ),
            'savePartialEntries'    => ! empty( $input['savePartialEntries'] ),
            'notificationEnabled'   => ! empty( $input['notificationEnabled'] ),
            'notificationTo'        => sanitize_text_field( $input['notificationTo'] ),
            'notificationCc'        => sanitize_text_field( $input['notificationCc'] ),
            'notificationBcc'       => sanitize_text_field( $input['notificationBcc'] ),
            'notificationFromName'  => sanitize_text_field( $input['notificationFromName'] ),
            'notificationFromEmail' => sanitize_text_field( $input['notificationFromEmail'] ),
            'notificationReplyTo'   => sanitize_text_field( $input['notificationReplyTo'] ),
            'notificationSubject'   => sanitize_text_field( $input['notificationSubject'] ),
            'notificationMessage'   => self::sanitize_message( $input['notificationMessage'], $input['notificationHtml'] ),
            'notificationHtml'      => ! empty( $input['notificationHtml'] ),
            'notificationAttachments' => sanitize_text_field( $input['notificationAttachments'] ),
            'autoReplyEnabled'      => ! empty( $input['autoReplyEnabled'] ),
            'autoReplyCc'           => sanitize_text_field( $input['autoReplyCc'] ),
            'autoReplyBcc'          => sanitize_text_field( $input['autoReplyBcc'] ),
            'autoReplySubject'      => sanitize_text_field( $input['autoReplySubject'] ),
            'autoReplyMessage'      => self::sanitize_message( $input['autoReplyMessage'], $input['autoReplyHtml'] ),
            'autoReplyHtml'         => ! empty( $input['autoReplyHtml'] ),
            'autoReplyAttachments'  => sanitize_text_field( $input['autoReplyAttachments'] ),
            'themeMode'             => in_array( $input['themeMode'], self::theme_modes(), true ) ? $input['themeMode'] : 'adaptive',
            'transparentBackground' => ! empty( $input['transparentBackground'] ),
            'captchaEnabled'        => ! empty( $input['captchaEnabled'] ),
            'akismetEnabled'        => ! empty( $input['akismetEnabled'] ),
            'minSubmitTimeEnabled'  => ! empty( $input['minSubmitTimeEnabled'] ),
            'minSubmitTime'         => SurveyJS_Submit_Time::sanitize_seconds( $input['minSubmitTime'] ),
            'limitEntries'          => ! empty( $input['limitEntries'] ),
            'entryLimit'            => max( 1, absint( $input['entryLimit'] ) ),
            'entryLimitPeriod'      => self::choice( $input['entryLimitPeriod'], SurveyJS_Restrictions::PERIODS ),
            'entryLimitMessage'     => self::sanitize_message( $input['entryLimitMessage'], true ),
            'limitPerRespondent'    => ! empty( $input['limitPerRespondent'] ),
            'respondentLimit'       => max( 1, absint( $input['respondentLimit'] ) ),
            'respondentBy'          => self::choice( $input['respondentBy'], SurveyJS_Restrictions::RESPONDENT_TYPES ),
            'respondentLimitMessage' => self::sanitize_message( $input['respondentLimitMessage'], true ),
            'scheduleEnabled'       => ! empty( $input['scheduleEnabled'] ),
            'scheduleStart'         => SurveyJS_Restrictions::sanitize_datetime( $input['scheduleStart'] ),
            'scheduleEnd'           => SurveyJS_Restrictions::sanitize_datetime( $input['scheduleEnd'] ),
            'schedulePendingMessage' => self::sanitize_message( $input['schedulePendingMessage'], true ),
            'scheduleExpiredMessage' => self::sanitize_message( $input['scheduleExpiredMessage'], true ),
            'requireLogin'          => ! empty( $input['requireLogin'] ),
            'requireLoginMessage'   => self::sanitize_message( $input['requireLoginMessage'], true ),
            'entryRetention'        => self::choice( $input['entryRetention'], SurveyJS_Entry_Retention::MODES ),
            'entryRetentionDays'    => max( 1, absint( $input['entryRetentionDays'] ) ),
        );
    }

    private static function choice( $value, $choices ) {
        return in_array( $value, $choices, true ) ? $value : $choices[0];
    }

    private static function sanitize_message( $message, $html ) {
        return ! empty( $html ) ? wp_kses_post( $message ) : sanitize_textarea_field( $message );
    }
}
