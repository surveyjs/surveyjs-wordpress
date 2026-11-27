<?php
require __DIR__ . '/wp-stubs.php';
require __DIR__ . '/../../../src/trunk/service_client.php';
require __DIR__ . '/../../../src/trunk/submit_time.php';
require __DIR__ . '/../../../src/trunk/survey_values.php';
require __DIR__ . '/../../../src/trunk/form_settings.php';

function test_settings( $stored ) {
    return SurveyJS_Form_Settings::get( (object) array( 'settings' => json_encode( $stored ), 'theme' => '' ) );
}

test_run( array(
    'settings-wrong-types' => function () {
        $settings = test_settings( array(
            'notificationReplyTo'     => array( 'email' ),
            'notificationAttachments' => array( 'name' => 'files' ),
            'notificationTo'          => 'owner@example.com',
            'webhooks'                => 'https://example.com',
            'integrations'            => array( 'not a feed', array( 'service' => 'slack' ) ),
            'storeEntries'            => false,
        ) );
        return array(
            'notificationReplyTo'     => $settings['notificationReplyTo'],
            'notificationAttachments' => $settings['notificationAttachments'],
            'notificationTo'          => $settings['notificationTo'],
            'webhooks'                => $settings['webhooks'],
            'integrations'            => array_values( $settings['integrations'] ),
            'storeEntries'            => $settings['storeEntries'],
        );
    },
    'settings-not-an-object' => function () {
        $settings = SurveyJS_Form_Settings::get( (object) array( 'settings' => '"just a string"', 'theme' => '' ) );
        return $settings === SurveyJS_Form_Settings::defaults();
    },
    'question-name-not-scalar' => function () {
        $questions = SurveyJS_Survey_Values::survey_questions( array(
            'pages' => array( array( 'elements' => array(
                array( 'type' => 'text', 'name' => array( 'oops' ) ),
                array( 'type' => 'text', 'name' => (object) array( 'a' => 1 ) ),
                array( 'type' => 'text', 'name' => 'email' ),
                'not an element',
            ) ) ),
        ) );
        $data = array( 'email' => 'a@example.com' );
        $values = array();
        foreach ( $questions as $question ) {
            $values[] = $data[ $question['name'] ] ?? null;
        }
        return $values;
    },
) );
