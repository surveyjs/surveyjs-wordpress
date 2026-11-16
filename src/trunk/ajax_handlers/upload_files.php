<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( __DIR__ . "/ajax_handler.php");
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_UploadFiles extends SurveyJS_AJAX_Handler {

    const SUBDIR           = SurveyJS_Uploads_Storage::SUBDIR;
    const MAX_FILES        = 20;
    const PREVIEW_LIFETIME = DAY_IN_SECONDS;

    function __construct() {
        parent::__construct("SurveyJS_UploadFiles", true);
    }

    function callback() {
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;

        if(!check_ajax_referer( 'surveyjs-upload-files' )) exit;

        $survey_id = isset( $_POST['SurveyId'] ) ? absint( wp_unslash( $_POST['SurveyId'] ) ) : 0;
        if ( ! $this->is_upload_allowed( $survey_id ) ) {
            wp_send_json( array( 'error' => 'forbidden' ), 403 );
        }

        if ( empty( $_FILES ) ) {
            wp_send_json( array( 'error' => 'no_files' ), 400 );
        }

        if ( count( $_FILES ) > self::MAX_FILES ) {
            wp_send_json( array( 'error' => 'too_many_files' ), 400 );
        }

        $allowed  = self::allowed_extensions();
        $max_size = $this->max_file_size();
        $result   = array();

        foreach ( $_FILES as $uploadedfile ) {
            if ( ! is_array( $uploadedfile ) || ! isset( $uploadedfile['name'], $uploadedfile['tmp_name'] ) ) {
                continue;
            }
            $upload_error = isset( $uploadedfile['error'] ) ? (int) $uploadedfile['error'] : UPLOAD_ERR_OK;
            if ( UPLOAD_ERR_NO_FILE === $upload_error ) {
                continue;
            }
            if ( UPLOAD_ERR_INI_SIZE === $upload_error || UPLOAD_ERR_FORM_SIZE === $upload_error ) {
                wp_send_json( array( 'error' => 'file_too_large' ), 400 );
            }
            if ( UPLOAD_ERR_OK !== $upload_error || ! is_uploaded_file( $uploadedfile['tmp_name'] ) ) {
                wp_send_json( array( 'error' => 'upload_failed' ), 400 );
            }

            $originalname = (string) $uploadedfile['name'];
            $safename     = sanitize_file_name( $originalname );
            $ext          = strtolower( (string) pathinfo( $safename, PATHINFO_EXTENSION ) );

            if ( '' === $ext || ! in_array( $ext, $allowed, true ) ) {
                wp_send_json( array( 'error' => 'file_type_not_allowed' ), 400 );
            }

            if ( isset( $uploadedfile['size'] ) && (int) $uploadedfile['size'] > $max_size ) {
                wp_send_json( array( 'error' => 'file_too_large' ), 400 );
            }

            $checked = wp_check_filetype_and_ext( $uploadedfile['tmp_name'], $safename );
            if ( empty( $checked['ext'] ) || empty( $checked['type'] ) || strtolower( $checked['ext'] ) !== $ext ) {
                wp_send_json( array( 'error' => 'file_type_mismatch' ), 400 );
            }

            $key = SurveyJS_Uploaded_Files::store_upload(
                $uploadedfile['tmp_name'],
                $ext,
                array(
                    'form_id'       => $survey_id,
                    'original_name' => $originalname,
                    'mime'          => $checked['type'],
                    'size'          => (int) ( $uploadedfile['size'] ?? 0 ),
                )
            );
            if ( is_wp_error( $key ) ) {
                wp_send_json( array( 'error' => 'upload_failed' ), 400 );
            }

            $result[ $originalname ] = SurveyJS_Uploaded_Files::client_file( $key, time() + self::PREVIEW_LIFETIME );
        }

        wp_send_json( $result );
    }

    private function is_upload_allowed( $survey_id ) {
        if ( current_user_can( 'upload_files' ) ) {
            return true;
        }

        if ( ! $survey_id ) {
            return false;
        }

        $client = new SurveyJS_Client();
        $survey = $client->getSurvey( $survey_id );
        if ( ! $survey || ! isset( $survey->json ) ) {
            return false;
        }

        return SurveyJS_Client::survey_has_file_question( SurveyJS_Client::decode_survey_json( $survey->json ) );
    }

    public static function allowed_extensions() {
        $defaults = array(
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'odt', 'ods', 'odp', 'txt', 'csv',
        );

        $allowed = apply_filters( 'surveyjs_allowed_upload_extensions', $defaults );
        if ( ! is_array( $allowed ) ) {
            return $defaults;
        }

        $allowed = array_filter( array_map( 'strtolower', array_filter( $allowed, 'is_string' ) ) );
        return $allowed ? array_values( $allowed ) : $defaults;
    }

    private function max_file_size() {
        $ceiling = min( 10 * MB_IN_BYTES, (int) wp_max_upload_size() );
        if ( $ceiling <= 0 ) {
            $ceiling = 10 * MB_IN_BYTES;
        }

        $size = (int) apply_filters( 'surveyjs_max_upload_size', $ceiling );
        return $size > 0 ? $size : $ceiling;
    }
}
