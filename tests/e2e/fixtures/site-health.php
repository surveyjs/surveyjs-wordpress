<?php
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.

$command = $argv[1] ?? '';
$request = null;

if ( 'fake' === $command ) {
    $code = (int) ( $argv[2] ?? 200 );
    if ( 'verify' === ( $argv[3] ?? '' ) ) {
        add_filter( 'https_local_ssl_verify', '__return_true' );
    }
    add_filter(
        'pre_http_request',
        function ( $preempt, $args, $url ) use ( $code, &$request ) {
            $request = array(
                'url'       => $url,
                'method'    => $args['method'],
                'sslverify' => $args['sslverify'],
            );
            return array(
                'headers'  => array(),
                'body'     => '',
                'response' => array( 'code' => $code, 'message' => '' ),
                'cookies'  => array(),
                'filename' => null,
            );
        },
        10,
        3
    );
} elseif ( 'direct-file' === $command ) {
    $dir = SurveyJS_Uploaded_Files::default_storage()->dir();
    wp_mkdir_p( $dir['path'] );
    SurveyJS_Uploaded_Files::default_storage()->protect( $dir['path'] );
    file_put_contents( $dir['path'] . '/e2e-direct-access.txt', 'e2e' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    echo wp_json_encode( array( 'url' => $dir['url'] . '/e2e-direct-access.txt' ) );
    exit;
} elseif ( 'direct-file-cleanup' === $command ) {
    wp_delete_file( SurveyJS_Uploaded_Files::default_storage()->dir()['path'] . '/e2e-direct-access.txt' );
    echo '{}';
    exit;
}

$result = SurveyJS_Site_Health::test();
echo wp_json_encode(
    array(
        'status'      => $result['status'],
        'label'       => $result['label'],
        'description' => $result['description'],
        'request'     => $request,
        'uploadsPath' => (string) wp_parse_url( SurveyJS_Uploaded_Files::default_storage()->dir()['url'], PHP_URL_PATH ),
    )
);
