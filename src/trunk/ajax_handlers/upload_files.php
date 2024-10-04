<?php

include_once("ajax_handler.php");
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

function get_file_url( $file = __FILE__ ) {
    $file_path = str_replace( "\\", "/", str_replace( str_replace( "/", "\\", WP_CONTENT_DIR ), "", $file ) );
    if ( $file_path )
        return content_url( $file_path );
    return false;
}

function upload_user_file( $file = array(),$path) {
    $result = [];
    if(!empty($file)) 
    {
        $uploaded=move_uploaded_file($file['tmp_name'],$path.$file['name']);
    if($uploaded) 
    {
        $relativePath = substr($path, strlen($_SERVER['DOCUMENT_ROOT'] . "/wp-content"));
        $result["url"] = get_file_url($relativePath.$file['name']);
    } else {
        $result["error"] = $file['error'];
    }
    }
    return $result;
}

class SurveyJS_UploadFiles extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_UploadFiles", false);  
    }

    
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = [];
            $upload_dir=wp_upload_dir();
            $path=$upload_dir['basedir'].'/surveyjs/';  //upload dir.
            if(!is_dir($path)) { mkdir($path); }

            foreach ($_FILES as $key=>$value) {
                $uploadedfile = $value;
                $path_info = pathinfo($uploadedfile["name"]);
                $originalname = $uploadedfile["name"];
                $filename = uniqid(rand(), true) . "." . $path_info['extension'];

                
                $uploadedfile["name"] = $filename;
                
                // $movefile = upload_user_file( $uploadedfile, $path);

                $upload_overrides = array( 'test_form' => false );
                $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

                if ( !$movefile || isset( $movefile['error'] ) ) {
                    wp_send_json( array('error' => $movefile['error']) );
                    return;
                }

                $result[$originalname] = $movefile['url'];
            }

            wp_send_json( $result );
        }
    }
}

?>