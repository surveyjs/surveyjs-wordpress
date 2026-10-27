<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_Editor {

    function __construct() {
    }

    public static function render() {
        $client = new SurveyJS_Client();
        $survey = $client->getSurvey( absint( $_GET['id'] ?? 0 ) );
        if ( ! $survey ) {
            wp_die( esc_html__( 'Survey not found.', 'surveyjs' ) );
        }

        $json = SurveyJS_Client::decode_survey_json( $survey->json, false );
        $theme = SurveyJS_Client::decode_survey_json( $survey->theme, false );
        $config = array(
            'id'        => (int) $survey->id,
            'name'      => $survey->name,
            'json'      => empty( $json ) ? new stdClass() : $json,
            'theme'     => empty( $theme ) ? new stdClass() : $theme,
            'saveUrl'   => add_query_arg( array( 'action' => 'SurveyJS_SaveSurvey' ), admin_url( 'admin-ajax.php' ) ),
            'saveNonce' => wp_create_nonce( 'surveyjs-save-survey' ),
            'monacoUrl' => plugins_url( '../libs/monaco/', __FILE__ ),
            'uiBuilderUrl' => apply_filters( 'surveyjs_ui_builder_url', 'https://surveyjs.io/wordpress-ui-builder' ),
        );
        $list_url = add_query_arg( array( 'page' => 'surveyjs-main-menu' ), admin_url( 'admin.php' ) );
        ?>
        <div class="wrap wp-sjs-editor">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Survey', 'surveyjs' ); ?></h1>
            <hr class="wp-header-end">

            <div class="wp-sjs-editor__toolbar">
                <input type="text" id="wp-sjs-survey-name" class="regular-text" aria-label="<?php esc_attr_e( 'Survey name', 'surveyjs' ); ?>" />
                <a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Back', 'surveyjs' ); ?></a>
                <button type="button" class="button button-primary" id="wp-sjs-save"><?php esc_html_e( 'Save', 'surveyjs' ); ?></button>
                <button type="button" class="button wp-sjs-editor__ui-builder" id="wp-sjs-ui-builder"><?php esc_html_e( 'Open in UI Builder', 'surveyjs' ); ?><span class="dashicons dashicons-external" aria-hidden="true"></span></button>
            </div>
            <div id="wp-sjs-editor-notice"></div>

            <nav class="nav-tab-wrapper wp-sjs-editor__tabs">
                <a href="#" class="nav-tab nav-tab-active" data-tab="survey"><?php esc_html_e( 'Survey JSON', 'surveyjs' ); ?></a>
                <a href="#" class="nav-tab" data-tab="theme"><?php esc_html_e( 'Theme JSON', 'surveyjs' ); ?></a>
            </nav>

            <div class="wp-sjs-editor__columns">
                <div class="wp-sjs-editor__pane">
                    <div class="wp-sjs-editor__overlay">
                        <button type="button" class="button button-small wp-sjs-editor__format" id="wp-sjs-format"><?php esc_html_e( 'Format', 'surveyjs' ); ?></button>
                    </div>
                    <div id="wp-sjs-monaco" class="wp-sjs-editor__monaco"><p class="wp-sjs-editor__loading"><?php esc_html_e( 'Loading editor…', 'surveyjs' ); ?></p></div>
                    <div id="wp-sjs-lint" class="wp-sjs-lint"></div>
                </div>
                <div class="wp-sjs-editor__preview">
                    <div id="wp-sjs-preview"></div>
                </div>
            </div>
        </div>
        <script type="application/json" id="wp-sjs-editor-config"><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
        <?php
    }
}

?>
