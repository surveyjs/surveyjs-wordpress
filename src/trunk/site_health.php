<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Site_Health {

    const TEST  = 'surveyjs_uploads_private';
    const PROBE = 'surveyjs-access-check.txt';
    const NGINX_RULE = 'location ^~ %s { deny all; }';

    public static function init() {
        add_filter( 'site_status_tests', array( __CLASS__, 'register' ) );
    }

    public static function register( $tests ) {
        $tests['direct'][ self::TEST ] = array(
            'label' => __( 'SurveyJS Forms uploaded files', 'surveyjs' ),
            'test'  => array( __CLASS__, 'test' ),
        );
        return $tests;
    }

    public static function test() {
        $result = array(
            'label'       => __( 'Files uploaded to SurveyJS forms are not open to the public', 'surveyjs' ),
            'status'      => 'good',
            'badge'       => array(
                'label' => __( 'Security', 'surveyjs' ),
                'color' => 'blue',
            ),
            'description' => '<p>' . esc_html__( 'Respondents\' files are opened through a link that checks permissions. A direct link to the upload folder does not open them.', 'surveyjs' ) . '</p>',
            'actions'     => '',
            'test'        => self::TEST,
        );

        $storage = SurveyJS_Uploaded_Files::default_storage();
        $dir = $storage ? $storage->dir() : null;
        if ( null === $dir || '' === $dir['url'] ) {
            return $result;
        }

        if ( wp_mkdir_p( $dir['path'] ) ) {
            $storage->protect( $dir['path'] );
        }
        $code = self::probe( $dir );
        if ( is_wp_error( $code ) ) {
            $result['label'] = __( 'Access to files uploaded to SurveyJS forms could not be checked', 'surveyjs' );
            $result['description'] = '<p>' . esc_html( sprintf(
                /* translators: %s: error message */
                __( 'The site could not request a test file from its upload folder: %s', 'surveyjs' ),
                $code->get_error_message()
            ) ) . '</p>';
            return $result;
        }
        if ( 200 !== $code ) {
            return $result;
        }

        $result['status'] = 'recommended';
        $result['badge']['color'] = 'orange';
        $result['label'] = __( 'Files uploaded to SurveyJS forms can be opened by a direct link', 'surveyjs' );
        $intro = '<p>' . esc_html__( 'New answers link to files through a permission check, but anyone who has a direct link to the upload folder (for example, from an entry saved by an older version, a backup or a mail log) can open the file. The file names are random and cannot be guessed.', 'surveyjs' ) . '</p>';
        if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) || wp_is_stream( $dir['path'] ) ) {
            $result['description'] = $intro . '<p>' . esc_html__( 'On WordPress VIP, ask VIP Support to turn on Access-Controlled Files and add the vip_files_acl_file_visibility filter from the SurveyJS Forms readme, so that files in the surveyjs folder are private.', 'surveyjs' ) . '</p>';
            return $result;
        }
        $rule = sprintf( self::NGINX_RULE, trailingslashit( (string) wp_parse_url( $dir['url'], PHP_URL_PATH ) ) );
        $result['description'] = $intro
            . '<p>' . esc_html__( 'On Apache, the plugin blocks the folder with an .htaccess file. Nginx does not read .htaccess: add this rule to the server block of the site and reload Nginx.', 'surveyjs' ) . '</p>'
            . '<p><code>' . esc_html( $rule ) . '</code></p>'
            . '<p>' . esc_html__( 'In Local (localwp.com), add the rule to conf/nginx/site.conf.hbs in the site folder (not to site.conf, which Local overwrites) and restart the site.', 'surveyjs' ) . '</p>';
        return $result;
    }

    private static function probe( $dir ) {
        if ( ! function_exists( 'wp_tempnam' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $path = $dir['path'] . '/' . self::PROBE;
        $empty = wp_tempnam( self::PROBE );
        $written = $empty && copy( $empty, $path );
        if ( $empty ) {
            wp_delete_file( $empty );
        }
        if ( ! $written ) {
            return new WP_Error( 'surveyjs_probe_not_written', __( 'The test file could not be saved.', 'surveyjs' ) );
        }
        $response = wp_remote_head(
            $dir['url'] . '/' . self::PROBE,
            array(
                'timeout'     => 3,
                'redirection' => 0,
                // Same as core loopback requests: local sites (Local, Docker) often use self-signed certificates.
                'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
            )
        );
        wp_delete_file( $path );
        return is_wp_error( $response ) ? $response : (int) wp_remote_retrieve_response_code( $response );
    }
}
