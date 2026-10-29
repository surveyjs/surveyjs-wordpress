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
        $settings = SurveyJS_Form_Settings::get( $survey );
        foreach ( SurveyJS_Form_Settings::fallbacks() as $key => $fallback ) {
            if ( '' === trim( (string) $settings[ $key ] ) ) {
                $settings[ $key ] = $fallback;
            }
        }
        $config = array(
            'id'        => (int) $survey->id,
            'name'      => $survey->name,
            'json'      => empty( $json ) ? new stdClass() : $json,
            'theme'     => empty( $theme ) ? new stdClass() : $theme,
            'settings'  => $settings,
            'settingPlaceholders' => SurveyJS_Form_Settings::fallbacks(),
            'saveUrl'   => add_query_arg( array( 'action' => 'SurveyJS_SaveSurvey' ), admin_url( 'admin-ajax.php' ) ),
            'testEmailUrl' => add_query_arg( array( 'action' => 'SurveyJS_SendTestEmail' ), admin_url( 'admin-ajax.php' ) ),
            'saveNonce' => wp_create_nonce( 'surveyjs-save-survey' ),
            'monacoUrl' => plugins_url( '../libs/monaco/', __FILE__ ),
            'uiBuilderUrl' => apply_filters( 'surveyjs_ui_builder_url', defined( 'SURVEYJS_UI_BUILDER_URL' ) ? SURVEYJS_UI_BUILDER_URL : 'https://surveyjs.io/wordpress-ui-builder' ),
        );
        $list_url = add_query_arg( array( 'page' => 'surveyjs-main-menu' ), admin_url( 'admin.php' ) );
        ?>
        <div class="wrap wp-sjs-editor">
            <hr class="wp-header-end">
            <div class="wp-sjs-editor__header">
                <a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Back', 'surveyjs' ); ?></a>
                <div class="wp-sjs-editor__name" id="wp-sjs-name-view">
                    <h1 class="wp-heading-inline" id="wp-sjs-name-text"></h1>
                    <button type="button" class="button button-small" id="wp-sjs-name-edit" aria-label="<?php esc_attr_e( 'Edit survey name', 'surveyjs' ); ?>"><?php esc_html_e( 'Edit', 'surveyjs' ); ?></button>
                </div>
                <div class="wp-sjs-editor__name" id="wp-sjs-name-form" hidden>
                    <input type="text" id="wp-sjs-survey-name" class="regular-text" aria-label="<?php esc_attr_e( 'Survey name', 'surveyjs' ); ?>" />
                    <button type="button" class="button button-small" id="wp-sjs-name-save" aria-label="<?php esc_attr_e( 'Save name', 'surveyjs' ); ?>"><?php esc_html_e( 'Save', 'surveyjs' ); ?></button>
                    <button type="button" class="button-link" id="wp-sjs-name-cancel"><?php esc_html_e( 'Cancel', 'surveyjs' ); ?></button>
                </div>
            </div>
            <div id="wp-sjs-editor-notice"></div>

            <nav class="nav-tab-wrapper wp-sjs-editor__tabs">
                <a href="#" class="nav-tab nav-tab-active" data-tab="survey"><?php esc_html_e( 'Config', 'surveyjs' ); ?></a>
                <a href="#" class="nav-tab" data-tab="theme"><?php esc_html_e( 'Theme', 'surveyjs' ); ?></a>
                <a href="#" class="nav-tab" data-tab="email"><?php esc_html_e( 'Email Notification', 'surveyjs' ); ?></a>
                <a href="#" class="nav-tab" data-tab="entries"><?php esc_html_e( 'Entries', 'surveyjs' ); ?></a>
                <button type="button" class="button button-primary wp-sjs-editor__save" id="wp-sjs-save"><?php esc_html_e( 'Save', 'surveyjs' ); ?></button>
            </nav>

            <div class="wp-sjs-editor__columns">
                <div class="wp-sjs-editor__pane" id="wp-sjs-json-pane">
                    <div class="wp-sjs-editor__pane-header">
                        <span class="description"><?php esc_html_e( 'Prefer a visual editor? Edit this form in the free online UI Builder.', 'surveyjs' ); ?></span>
                        <button type="button" class="button wp-sjs-editor__ui-builder" id="wp-sjs-ui-builder"><?php esc_html_e( 'Open in UI Builder', 'surveyjs' ); ?><span class="dashicons dashicons-external" aria-hidden="true"></span></button>
                    </div>
                    <div class="wp-sjs-editor__overlay">
                        <button type="button" class="button button-small wp-sjs-editor__format" id="wp-sjs-format"><?php esc_html_e( 'Format', 'surveyjs' ); ?></button>
                    </div>
                    <div id="wp-sjs-monaco" class="wp-sjs-editor__monaco"><p class="wp-sjs-editor__loading"><?php esc_html_e( 'Loading editor…', 'surveyjs' ); ?></p></div>
                    <div id="wp-sjs-lint" class="wp-sjs-lint"></div>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="entries" hidden>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Store entries', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="storeEntries" /> <?php esc_html_e( 'Save submitted entries in the database', 'surveyjs' ); ?></label></td>
                        </tr>
                    </table>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="email" hidden>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Send notification', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="notificationEnabled" /> <?php esc_html_e( 'Send an email when the form is submitted', 'surveyjs' ); ?></label></td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-to"><?php esc_html_e( 'Send To', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-to" class="regular-text" data-setting="notificationTo" />
                                <button type="button" class="button" id="wp-sjs-send-test-email"><?php esc_html_e( 'Send test email', 'surveyjs' ); ?></button>
                                <p class="description"><?php esc_html_e( 'Who receives the email. Separate multiple addresses with commas.', 'surveyjs' ); ?></p>
                                <p id="wp-sjs-test-email-result" aria-live="polite"></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled autoReplyEnabled">
                            <th scope="row"><label for="wp-sjs-notification-from-name"><?php esc_html_e( 'From Name', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-from-name" class="regular-text" data-setting="notificationFromName" />
                                <p class="description"><?php esc_html_e( 'Sender name shown in the inbox. {site_name} is replaced with the site name.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled autoReplyEnabled">
                            <th scope="row"><label for="wp-sjs-notification-from-email"><?php esc_html_e( 'From Email', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-from-email" class="regular-text" data-setting="notificationFromEmail" />
                                <p class="description"><?php esc_html_e( 'Address the email is sent from. Use an address on your site\'s domain, otherwise the email may land in spam.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled autoReplyEnabled">
                            <th scope="row"><label for="wp-sjs-notification-reply-to"><?php esc_html_e( 'Reply-To', 'surveyjs' ); ?></label></th>
                            <td>
                                <select id="wp-sjs-notification-reply-to" data-setting="notificationReplyTo"></select>
                                <p class="description"><?php esc_html_e( 'The question where the respondent enters their email. When you reply to the email, the reply goes to this address. The auto-reply is also sent to it.', 'surveyjs' ); ?></p>
                                <div id="wp-sjs-notification-reply-to-add" hidden>
                                    <p><label><input type="checkbox" id="wp-sjs-notification-reply-to-add-question" /> <?php esc_html_e( 'Add a Reply-To question (email) to the form', 'surveyjs' ); ?></label></p>
                                    <p class="description"><?php esc_html_e( 'Adds the question “Leave your email so we can get back to you” to the end of the form. You can also add a Single-Line Input with the Email input type yourself.', 'surveyjs' ); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-subject"><?php esc_html_e( 'Subject', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-subject" class="regular-text" data-setting="notificationSubject" />
                                <p class="description"><?php esc_html_e( '{form_name} is replaced with the form name.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-message"><?php esc_html_e( 'Message', 'surveyjs' ); ?></label></th>
                            <td>
                                <textarea id="wp-sjs-notification-message" class="large-text" rows="8" data-setting="notificationMessage"></textarea>
                                <p class="description"><?php esc_html_e( '{all_fields} is replaced with all answers, one per line. {field:question_name} is replaced with the answer to that question. Other tags: {form_name}, {site_name}, {admin_email}', 'surveyjs' ); ?></p>
                                <p class="description wp-sjs-field-tags" hidden></p>
                                <p><label><input type="checkbox" id="wp-sjs-notification-html" data-setting="notificationHtml" data-hint="wp-sjs-notification-html-hint" /> <?php esc_html_e( 'Use HTML content type', 'surveyjs' ); ?></label></p>
                                <p class="description" id="wp-sjs-notification-html-hint" hidden><?php esc_html_e( 'Write the message in HTML, for example: <h2>New entry</h2><p>{all_fields}</p>. Line breaks in plain text become paragraphs. Scripts and <style> blocks are removed, so use inline styles (style="...").', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-attachments"><?php esc_html_e( 'File Attachments', 'surveyjs' ); ?></label></th>
                            <td>
                                <select id="wp-sjs-notification-attachments" data-setting="notificationAttachments"></select>
                                <p class="description"><?php esc_html_e( 'Files the respondent uploaded in this question are attached to the email, up to 18 MB in total. Larger files are not attached.', 'surveyjs' ); ?></p>
                                <div id="wp-sjs-notification-attachments-add" hidden>
                                    <p><label><input type="checkbox" id="wp-sjs-notification-attachments-add-question" /> <?php esc_html_e( 'Add a File Upload question to the form', 'surveyjs' ); ?></label></p>
                                    <p class="description"><?php esc_html_e( 'Adds the question “Attach files” to the end of the form. You can also add a File Upload question yourself.', 'surveyjs' ); ?></p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div role="group" aria-labelledby="wp-sjs-auto-reply-title">
                        <h2 class="title" id="wp-sjs-auto-reply-title"><?php esc_html_e( 'Auto-reply', 'surveyjs' ); ?></h2>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Send auto-reply', 'surveyjs' ); ?></th>
                                <td>
                                    <label><input type="checkbox" data-setting="autoReplyEnabled" /> <?php esc_html_e( 'Send an auto-reply to the respondent', 'surveyjs' ); ?></label>
                                    <p class="description"><?php esc_html_e( 'Sent to the address the respondent entered in the Reply-To question. When the respondent replies, the reply goes to the Send To addresses, or to the site admin email if the email notification is off. Bots can enter someone else\'s address, so turn this on together with spam protection.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-auto-reply-subject"><?php esc_html_e( 'Subject', 'surveyjs' ); ?></label></th>
                                <td>
                                    <input type="text" id="wp-sjs-auto-reply-subject" class="regular-text" data-setting="autoReplySubject" />
                                    <p class="description"><?php esc_html_e( '{site_name} is replaced with the site name.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-auto-reply-message"><?php esc_html_e( 'Message', 'surveyjs' ); ?></label></th>
                                <td>
                                    <textarea id="wp-sjs-auto-reply-message" class="large-text" rows="8" data-setting="autoReplyMessage"></textarea>
                                    <p class="description"><?php esc_html_e( '{all_fields} is replaced with all answers, one per line. {field:question_name} is replaced with the answer to that question. Other tags: {form_name}, {site_name}, {admin_email}', 'surveyjs' ); ?></p>
                                    <p class="description wp-sjs-field-tags" hidden></p>
                                    <p><label><input type="checkbox" id="wp-sjs-auto-reply-html" data-setting="autoReplyHtml" data-hint="wp-sjs-auto-reply-html-hint" /> <?php esc_html_e( 'Use HTML content type', 'surveyjs' ); ?></label></p>
                                    <p class="description" id="wp-sjs-auto-reply-html-hint" hidden><?php esc_html_e( 'Write the message in HTML, for example: <h2>Thank you!</h2><p>{all_fields}</p>. Line breaks in plain text become paragraphs. Scripts and <style> blocks are removed, so use inline styles (style="...").', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-auto-reply-attachments"><?php esc_html_e( 'File Attachments', 'surveyjs' ); ?></label></th>
                                <td>
                                    <select id="wp-sjs-auto-reply-attachments" data-setting="autoReplyAttachments"></select>
                                    <p class="description"><?php esc_html_e( 'Files the respondent uploaded in this question are attached to the email, up to 18 MB in total. Larger files are not attached.', 'surveyjs' ); ?></p>
                                    <div id="wp-sjs-auto-reply-attachments-add" hidden>
                                        <p><label><input type="checkbox" id="wp-sjs-auto-reply-attachments-add-question" /> <?php esc_html_e( 'Add a File Upload question to the form', 'surveyjs' ); ?></label></p>
                                        <p class="description"><?php esc_html_e( 'Adds the question “Attach files” to the end of the form. You can also add a File Upload question yourself.', 'surveyjs' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
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
