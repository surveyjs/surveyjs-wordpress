<?php
// The CLI runs as root while the plugin files belong to the web server user, which would make WordPress ask for FTP details.
define( 'FS_METHOD', 'direct' );
require '/var/www/html/wp-load.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath -- the path is absolute; php reads this script from stdin, so __DIR__ is the working directory, not this folder.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

$command = $argv[1] ?? '';
$result = array();

if ( 'enable' === $command ) {
    update_option( SurveyJS_AI::ENABLED_OPTION, '1' === ( $argv[2] ?? '1' ) ? '1' : '0' );
} elseif ( 'plugins' === $command ) {
    $result = array(
        'active'  => array_values( (array) get_option( 'active_plugins' ) ),
        'adapter' => SurveyJS_AI::adapter_file(),
    );
} elseif ( 'deactivate' === $command ) {
    deactivate_plugins( array_slice( $argv, 2 ), true );
} elseif ( 'activate' === $command ) {
    foreach ( array_slice( $argv, 2 ) as $file ) {
        activate_plugin( $file, '', false, true );
    }
} elseif ( 'remove-adapter' === $command ) {
    $file = SurveyJS_AI::adapter_file();
    if ( '' !== $file ) {
        deactivate_plugins( $file, true );
        WP_Filesystem();
        $result['deleted'] = delete_plugins( array( $file ) );
    }
} elseif ( 'form' === $command ) {
    $form = SurveyJS_Forms::find( (int) $argv[2] );
    $draft = SurveyJS_AI_Drafts::get( (int) $argv[2] );
    $result = array(
        'name'     => $form ? $form->name : null,
        'json'     => $form ? $form->json : null,
        'settings' => $form ? SurveyJS_Form_Settings::get( $form ) : null,
        'draft'    => $draft,
    );
} elseif ( 'exec' === $command ) {
    wp_set_current_user( get_user_by( 'login', $argv[2] )->ID );
    $output = wp_get_ability( $argv[3] )->execute( json_decode( $argv[4] ?? 'null', true ) );
    $result = is_wp_error( $output ) ? array( 'error' => $output->get_error_code() ) : $output;
} elseif ( 'connections' === $command ) {
    $result = SurveyJS_AI::connections( get_user_by( 'login', $argv[2] )->ID );
} elseif ( 'revoke-all' === $command ) {
    SurveyJS_AI::revoke_all_connections();
} elseif ( 'delete-form' === $command ) {
    SurveyJS_Forms::delete( (int) $argv[2] );
} elseif ( 'write' === $command ) {
    update_option( SurveyJS_AI::WRITE_OPTION, '1' === ( $argv[2] ?? '1' ) ? '1' : '0' );
} elseif ( 'option-set' === $command ) {
    update_option( $argv[2], json_decode( $argv[3], true ), false );
    SurveyJS_AI_Chat::flush_cache();
} elseif ( 'option-delete' === $command ) {
    delete_option( $argv[2] );
    SurveyJS_AI_Chat::flush_cache();
} elseif ( 'option-get' === $command ) {
    $result = get_option( $argv[2], null );
} elseif ( 'create-form' === $command ) {
    $result = array(
        'id' => SurveyJS_Forms::create( array(
            'name'     => $argv[2],
            'json'     => $argv[3],
            'settings' => wp_json_encode( SurveyJS_Form_Settings::for_new_form( json_decode( $argv[3], true ) ) ),
        ) ),
    );
} elseif ( 'add-entry' === $command ) {
    global $wpdb;
    $wpdb->insert( SurveyJS_Entries::table(), array( 'form_id' => (int) $argv[2], 'json' => $argv[3], 'created_at' => current_time( 'mysql' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- test fixture.
    $result = array( 'id' => (int) $wpdb->insert_id );
} elseif ( 'fake-plugin' === $command ) {
    $dir = WP_PLUGIN_DIR . '/' . $argv[2];
    wp_mkdir_p( $dir );
    file_put_contents( $dir . '/plugin.php', "<?php\n/*\n * Plugin Name: " . $argv[3] . "\n * Version: " . $argv[4] . "\n */\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture.
    wp_clean_plugins_cache();
} elseif ( 'remove-plugin-dir' === $command ) {
    WP_Filesystem();
    global $wp_filesystem;
    $wp_filesystem->delete( WP_PLUGIN_DIR . '/' . $argv[2], true );
    wp_clean_plugins_cache();
} elseif ( 'permalinks' === $command ) {
    update_option( 'permalink_structure', $argv[2] );
    flush_rewrite_rules( true ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- test fixture switching permalinks.
} elseif ( 'revoke-except' === $command ) {
    $user = get_user_by( 'login', $argv[2] );
    $keep = (array) json_decode( $argv[3], true );
    foreach ( SurveyJS_AI::connections( $user->ID ) as $item ) {
        if ( ! in_array( $item['uuid'], $keep, true ) ) {
            WP_Application_Passwords::delete_application_password( $user->ID, $item['uuid'] );
        }
    }
} elseif ( 'names' === $command ) {
    $result = array_map( 'intval', wp_list_pluck( SurveyJS_Forms::names(), 'id' ) );
} elseif ( 'shortcode' === $command ) {
    $result = array( 'html' => do_shortcode( '[surveyjs id="' . (int) $argv[2] . '"]' ) );
} elseif ( 'user-meta-delete' === $command ) {
    delete_user_meta( get_user_by( 'login', $argv[2] )->ID, $argv[3] );
} elseif ( 'chat' === $command ) {
    $user = get_user_by( 'login', $argv[3] );
    $result = json_decode( (string) get_post_meta( (int) $argv[2], SurveyJS_AI_Chat::HISTORY_META . $user->ID, true ), true );
}

echo wp_json_encode( $result );
