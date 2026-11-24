<?php
// mu-plugin for the webhooks spec: the spec's endpoint runs on the Docker host, which is a private address that
// wp_safe_remote_post() refuses, on a port it does not allow.
$surveyjs_e2e_webhook_port = (int) '{{PORT}}';

add_filter(
    'http_request_host_is_external',
    function ( $external, $host ) {
        return $external || 'host.docker.internal' === $host;
    },
    10,
    2
);

add_filter(
    'http_allowed_safe_ports',
    function ( $ports ) use ( $surveyjs_e2e_webhook_port ) {
        $ports[] = $surveyjs_e2e_webhook_port;
        return $ports;
    }
);
