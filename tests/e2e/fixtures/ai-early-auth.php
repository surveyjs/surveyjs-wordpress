<?php
// Mimics WPVibe: Application Passwords are checked on init, before WordPress knows the request is a REST request.
add_filter(
    'application_password_is_api_request',
    function ( $is_api_request ) {
        $path = (string) wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- test fixture.
        return $is_api_request || isset( $_GET['rest_route'] ) || 0 === strpos( $path, '/wp-json/' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test fixture.
    }
);
add_action(
    'init',
    function () {
        wp_get_current_user();
    },
    1
);
