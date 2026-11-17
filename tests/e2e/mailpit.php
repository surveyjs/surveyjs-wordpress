<?php
add_action( 'phpmailer_init', function ( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host        = 'host.docker.internal';
    $phpmailer->Port        = defined( 'SJS_E2E_SMTP_PORT' ) ? SJS_E2E_SMTP_PORT : 1025;
    $phpmailer->SMTPAuth    = false;
    $phpmailer->SMTPAutoTLS = false;
} );

add_filter( 'wp_mail_from', function ( $from ) {
    return 'wordpress@localhost' === $from ? 'wordpress@localhost.test' : $from;
} );
