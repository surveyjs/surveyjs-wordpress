<?php
add_filter(
    'pre_http_request',
    function ( $preempt, $args, $url ) {
        if ( 0 !== strpos( $url, 'https://mcp.surveyjs.io/' ) ) {
            return $preempt;
        }
        $request = json_decode( $args['body'], true );
        $payload = array(
            'tool'    => $request['params']['name'],
            'query'   => $request['params']['arguments'],
            'results' => array(
                array(
                    'title' => 'E2E stub article',
                    'url'   => 'https://surveyjs.io/e2e-stub',
                ),
            ),
        );
        $event = array(
            'jsonrpc' => '2.0',
            'id'      => $request['id'],
            'result'  => array( 'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( $payload ) ) ) ),
        );
        return array(
            'headers'  => array( 'content-type' => 'text/event-stream' ),
            'body'     => "event: message\ndata: " . wp_json_encode( $event ) . "\n\n",
            'response' => array( 'code' => 200, 'message' => 'OK' ),
            'cookies'  => array(),
            'filename' => null,
        );
    },
    10,
    3
);
