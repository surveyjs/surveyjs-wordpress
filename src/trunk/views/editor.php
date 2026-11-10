<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_Editor {

    function __construct() {
    }

    public static function ui_builder_url() {
        return surveyjs_site_url( '/wordpress-ui-builder' );
    }

    public static function tabs( $survey ) {
        $tabs = array(
            'survey'  => __( 'Config', 'surveyjs' ),
            'theme'   => __( 'Theme', 'surveyjs' ),
            'email'   => __( 'Email', 'surveyjs' ),
            'entries' => __( 'Entries', 'surveyjs' ),
            'spam'     => __( 'Spam Protection', 'surveyjs' ),
            'advanced' => __( 'Advanced', 'surveyjs' ),
        );
        $tabs = apply_filters( 'surveyjs_editor_tabs', $tabs, $survey );
        if ( ! is_array( $tabs ) ) {
            return array();
        }
        if ( isset( $tabs['advanced'] ) ) {
            $advanced = $tabs['advanced'];
            unset( $tabs['advanced'] );
            $tabs['advanced'] = $advanced;
        }
        return array_filter( array_map( 'strval', $tabs ), 'strlen' );
    }

    private static function gdpr_agreement() {
        $privacy_url = get_privacy_policy_url();
        $privacy = $privacy_url
            ? '[' . __( 'Privacy Policy', 'surveyjs' ) . '](' . esc_url_raw( $privacy_url ) . ')'
            : __( 'Privacy Policy', 'surveyjs' );
        return array(
            /* translators: %s: "Privacy Policy", linked to the site's privacy policy page when there is one. */
            'text'  => sprintf( __( 'I consent to having this website store my submitted information so they can respond to my inquiry. See our %s.', 'surveyjs' ), $privacy ),
            'error' => __( 'Please give your consent to submit the form.', 'surveyjs' ),
        );
    }

    public static function render() {
        $client = new SurveyJS_Client();
        $survey = $client->getSurvey( absint( $_GET['id'] ?? 0 ) );
        if ( ! $survey ) {
            wp_die( esc_html__( 'Form not found.', 'surveyjs' ) );
        }

        $json = SurveyJS_Client::decode_survey_json( $survey->json, false );
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
            'theme'     => SurveyJS_Themes::decode( $survey->theme ),
            'settings'  => $settings,
            'settingPlaceholders' => SurveyJS_Form_Settings::fallbacks(),
            'saveUrl'   => add_query_arg( array( 'action' => 'SurveyJS_SaveSurvey' ), admin_url( 'admin-ajax.php' ) ),
            'testEmailUrl' => add_query_arg( array( 'action' => 'SurveyJS_SendTestEmail' ), admin_url( 'admin-ajax.php' ) ),
            'saveNonce' => wp_create_nonce( 'surveyjs-save-survey' ),
            'monacoUrl' => plugins_url( '../libs/monaco/', __FILE__ ),
            'themesUrl' => SurveyJS_Themes::url(),
            'surveyCssUrl' => plugins_url( '../libs/library/survey-core.min.css', __FILE__ ),
            'previewUrl' => add_query_arg( 'surveyjs_preview', '1', SurveyJS_SurveyJS::full_preview_url() ?: home_url( '/' ) ),
            'fullPreviewUrl' => SurveyJS_SurveyJS::full_preview_url(),
            'uiBuilderUrl' => self::ui_builder_url(),
            'gdpr'      => self::gdpr_agreement(),
            'privacyPolicyUrl' => get_privacy_policy_url(),
        );
        $list_url = add_query_arg( array( 'page' => 'surveyjs-main-menu' ), admin_url( 'admin.php' ) );
        $captcha_configured = SurveyJS_Captcha::is_configured();
        $tabs = self::tabs( $survey );
        $first_tab = (string) array_key_first( $tabs );
        ?>
        <div class="wrap wp-sjs-editor" data-active-tab="<?php echo esc_attr( $first_tab ); ?>">
            <hr class="wp-header-end">
            <div class="wp-sjs-editor__header">
                <a href="<?php echo esc_url( $list_url ); ?>" class="button"><?php esc_html_e( 'Back', 'surveyjs' ); ?></a>
                <div class="wp-sjs-editor__name" id="wp-sjs-name-view">
                    <h1 class="wp-heading-inline" id="wp-sjs-name-text"></h1>
                    <button type="button" class="button button-small" id="wp-sjs-name-edit" aria-label="<?php esc_attr_e( 'Edit form name', 'surveyjs' ); ?>"><?php esc_html_e( 'Edit', 'surveyjs' ); ?></button>
                </div>
                <div class="wp-sjs-editor__name" id="wp-sjs-name-form" hidden>
                    <input type="text" id="wp-sjs-survey-name" class="regular-text" aria-label="<?php esc_attr_e( 'Form name', 'surveyjs' ); ?>" />
                    <button type="button" class="button button-small" id="wp-sjs-name-save" aria-label="<?php esc_attr_e( 'Save name', 'surveyjs' ); ?>"><?php esc_html_e( 'Save', 'surveyjs' ); ?></button>
                    <button type="button" class="button-link" id="wp-sjs-name-cancel"><?php esc_html_e( 'Cancel', 'surveyjs' ); ?></button>
                </div>
                <a href="<?php echo esc_url( SurveyJS_Templates::save_url( $survey->id ) ); ?>" class="button wp-sjs-editor__save-template" id="wp-sjs-save-template"><?php esc_html_e( 'Save as Template', 'surveyjs' ); ?></a>
            </div>
            <div id="wp-sjs-editor-notice"></div>

            <nav class="nav-tab-wrapper wp-sjs-editor__tabs">
                <?php foreach ( $tabs as $tab => $label ) : ?>
                    <a href="#" class="nav-tab<?php echo $tab === $first_tab ? ' nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $tab ); ?>"><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
                <button type="button" class="button wp-sjs-editor__full-preview" id="wp-sjs-full-preview" aria-controls="wp-sjs-preview-panel" aria-expanded="false"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Open full preview', 'surveyjs' ); ?></button>
                <button type="button" class="button button-primary wp-sjs-editor__save" id="wp-sjs-save"><?php esc_html_e( 'Save', 'surveyjs' ); ?></button>
            </nav>

            <div class="wp-sjs-editor__columns">
                <div class="wp-sjs-editor__pane" id="wp-sjs-json-pane">
                    <div class="wp-sjs-editor__pane-header">
                        <span class="description"><?php esc_html_e( 'Prefer a visual editor? Edit this form in the free online UI Builder.', 'surveyjs' ); ?></span>
                        <button type="button" class="button wp-sjs-editor__ui-builder" id="wp-sjs-ui-builder"><?php esc_html_e( 'Open in free UI Builder', 'surveyjs' ); ?><span class="dashicons dashicons-external" aria-hidden="true"></span></button>
                        <?php SurveyJS_Upsell::render_editor_link(); ?>
                    </div>
                    <div class="wp-sjs-editor__theme-mode" id="wp-sjs-theme-mode" hidden>
                        <label for="wp-sjs-theme-mode-adaptive"><input type="checkbox" id="wp-sjs-theme-mode-adaptive" data-setting="themeMode" data-checked-value="adaptive" data-unchecked-value="surveyjs" /> <?php esc_html_e( 'Adaptive: use your site\'s theme styles', 'surveyjs' ); ?></label>
                        <button type="button" class="button" id="wp-sjs-ai-convert" disabled><?php esc_html_e( 'Customize Adaptive theme', 'surveyjs' ); ?></button>
                        <p class="description"><?php esc_html_e( 'When Adaptive is on, the form takes the colors, fonts, buttons and fields of your site\'s theme, and the theme JSON below is not used. Turn it off to pick a ready-made theme or edit the JSON here or in the free UI Builder. Customize Adaptive theme copies the Adaptive look into the theme JSON so you can edit it.', 'surveyjs' ); ?></p>
                        <span class="wp-sjs-editor__theme-preset">
                            <label for="wp-sjs-theme-preset"><?php esc_html_e( 'Theme', 'surveyjs' ); ?></label>
                            <select id="wp-sjs-theme-preset">
                                <option value=""><?php esc_html_e( 'Custom', 'surveyjs' ); ?></option>
                                <?php foreach ( SurveyJS_Themes::labels() as $slug => $label ) : ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                        <label for="wp-sjs-transparent-background"><input type="checkbox" id="wp-sjs-transparent-background" data-setting="transparentBackground" /> <?php esc_html_e( 'Transparent form background', 'surveyjs' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Transparent form background shows the page behind the form instead of the theme\'s background color. Turn it on if the form looks like a colored box on your page.', 'surveyjs' ); ?></p>
                        <div class="wp-sjs-editor__ai-theme">
                            <button type="button" class="button" id="wp-sjs-ai-prompt" disabled><?php esc_html_e( 'Copy prompt for AI', 'surveyjs' ); ?></button>
                            <button type="button" class="button" id="wp-sjs-ai-paste" aria-expanded="false" aria-controls="wp-sjs-ai-form"><?php esc_html_e( 'Paste theme from AI', 'surveyjs' ); ?></button>
                            <p class="description"><?php esc_html_e( 'Match your site with your own AI assistant: copy the prompt with your site\'s colors, fonts, buttons and fields, paste it into the AI chat, then paste the theme JSON it returns. The plugin sends nothing to any AI service.','surveyjs' ); ?></p>
                            <div class="wp-sjs-editor__ai-form" id="wp-sjs-ai-form" hidden>
                                <label for="wp-sjs-ai-answer" class="screen-reader-text"><?php esc_html_e( 'AI answer', 'surveyjs' ); ?></label>
                                <textarea id="wp-sjs-ai-answer" class="large-text code" rows="6" placeholder="<?php esc_attr_e( 'Paste the AI answer with the theme JSON here', 'surveyjs' ); ?>"></textarea>
                                <span class="wp-sjs-editor__ai-actions">
                                    <button type="button" class="button button-primary" id="wp-sjs-ai-apply"><?php esc_html_e( 'Apply theme', 'surveyjs' ); ?></button>
                                    <button type="button" class="button-link" id="wp-sjs-ai-cancel"><?php esc_html_e( 'Cancel', 'surveyjs' ); ?></button>
                                </span>
                            </div>
                            <div id="wp-sjs-ai-notice" aria-live="polite"></div>
                        </div>
                    </div>
                    <div class="wp-sjs-editor__media">
                        <button type="button" class="button" id="wp-sjs-insert-image" data-frame-title="<?php esc_attr_e( 'Insert image', 'surveyjs' ); ?>" data-frame-button="<?php esc_attr_e( 'Insert image URL', 'surveyjs' ); ?>" disabled><span class="dashicons dashicons-format-image" aria-hidden="true"></span><?php esc_html_e( 'Insert image', 'surveyjs' ); ?></button>
                        <button type="button" class="button" id="wp-sjs-set-logo" data-frame-title="<?php esc_attr_e( 'Form logo', 'surveyjs' ); ?>" data-frame-button="<?php esc_attr_e( 'Use as logo', 'surveyjs' ); ?>" disabled><?php esc_html_e( 'Set logo', 'surveyjs' ); ?></button>
                        <label class="wp-sjs-editor__gdpr" id="wp-sjs-gdpr-agreement-label" for="wp-sjs-gdpr-agreement" title="<?php esc_attr_e( 'Adds a required consent checkbox with a link to your Privacy Policy page at the end of the form. Uncheck to remove it.', 'surveyjs' ); ?>"><input type="checkbox" id="wp-sjs-gdpr-agreement" /> <?php esc_html_e( 'GDPR agreement', 'surveyjs' ); ?></label>
                        <span class="description"><?php esc_html_e( 'Pick or upload images in the Media Library. Insert image lists them below with their URLs to copy, for example into "imageLink" of an Image question or an Image Picker choice. Set logo shows an image in the form header. These images are public.', 'surveyjs' ); ?></span>
                        <ul class="wp-sjs-editor__images" id="wp-sjs-image-list" aria-label="<?php esc_attr_e( 'Chosen images', 'surveyjs' ); ?>" data-url-label="<?php esc_attr_e( 'Image URL', 'surveyjs' ); ?>" data-copy-label="<?php esc_attr_e( 'Copy', 'surveyjs' ); ?>" data-copied-label="<?php esc_attr_e( 'Copied!', 'surveyjs' ); ?>" data-remove-label="<?php esc_attr_e( 'Remove from the list', 'surveyjs' ); ?>" hidden></ul>
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
                        <tr data-enabled-by="storeEntries">
                            <th scope="row"><?php esc_html_e( 'Partial entries', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="savePartialEntries" /> <?php esc_html_e( 'Save partial entries', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'Answers are saved on your site each time the respondent goes to the next page, so you see entries of people who started the form but did not submit it. They are listed under Partial in the entries and become a regular entry when the form is submitted. Unfinished entries are deleted after 30 days unless you click Keep on them. Mention this in your privacy policy. Works only when Store entries is on.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="spam" hidden>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'CAPTCHA', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="captchaEnabled" <?php disabled( ! $captcha_configured ); ?> /> <?php esc_html_e( 'Enable CAPTCHA', 'surveyjs' ); ?></label>
                                <?php if ( $captcha_configured ) : ?>
                                    <p class="description">
                                        <?php
                                        /* translators: %s: CAPTCHA type, for example "Cloudflare Turnstile" */
                                        echo esc_html( sprintf( __( 'Respondents pass %s before the form is submitted. The service and keys are set in Settings.', 'surveyjs' ), SurveyJS_Captcha::type_label() ) );
                                        ?>
                                        <a href="<?php echo esc_url( SurveyJS_Captcha::settings_url() ); ?>"><?php esc_html_e( 'CAPTCHA settings', 'surveyjs' ); ?></a>
                                    </p>
                                <?php else : ?>
                                    <div class="notice notice-info inline">
                                        <p>
                                            <?php esc_html_e( 'CAPTCHA is not set up yet, so it cannot be turned on. Choose the CAPTCHA type and enter its keys in Settings, then come back here.', 'surveyjs' ); ?>
                                            <a href="<?php echo esc_url( SurveyJS_Captcha::settings_url() ); ?>"><?php esc_html_e( 'Set up CAPTCHA in Settings', 'surveyjs' ); ?></a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <p class="description"><?php esc_html_e( 'A hidden honeypot field is always on and blocks simple spam bots.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="advanced" hidden>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Save & Resume', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="saveResume" /> <?php esc_html_e( 'Let respondents continue later', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'Unfinished answers are saved as the respondent fills in the form and restored when they come back. Visitors\' answers are kept in their browser. Logged-in users\' answers are kept on your site, so they can continue on another device. Unfinished answers are deleted after 30 days or when the form is submitted.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Markdown', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="markdown" id="wp-sjs-markdown" /> <?php esc_html_e( 'Format form texts with Markdown', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'Titles, descriptions and other form texts can use **bold**, *italic*, lists and links. Links like [text](https://example.com) work even when this is off. Entries, CSV exports and emails show the texts without Markdown.', 'surveyjs' ); ?></p>
                            </td>
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
                        <?php do_action( 'surveyjs_editor_email_rows', 'notification', $survey ); ?>
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
                            <?php do_action( 'surveyjs_editor_email_rows', 'auto_reply', $survey ); ?>
                        </table>
                    </div>
                    <?php do_action( 'surveyjs_editor_email_rows', 'end', $survey ); ?>
                </div>
                <?php do_action( 'surveyjs_render_editor', $survey ); ?>
                <div class="wp-sjs-editor__preview" id="wp-sjs-preview-panel">
                    <div class="wp-sjs-editor__preview-bar">
                        <p class="description wp-sjs-editor__preview-note"><?php esc_html_e( 'Depending on your site\'s theme, the form on a real page may look slightly different from this preview.', 'surveyjs' ); ?></p>
                        <button type="button" class="button wp-sjs-editor__preview-close" id="wp-sjs-preview-close" hidden><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><?php esc_html_e( 'Close preview', 'surveyjs' ); ?></button>
                    </div>
                    <iframe id="wp-sjs-preview" class="wp-sjs-editor__preview-frame" title="<?php esc_attr_e( 'Form preview', 'surveyjs' ); ?>"></iframe>
                    <iframe id="wp-sjs-full-preview-frame" class="wp-sjs-editor__preview-frame wp-sjs-editor__preview-frame--full" title="<?php esc_attr_e( 'Form preview on a page of your site', 'surveyjs' ); ?>"></iframe>
                </div>
            </div>
        </div>
        <script type="application/json" id="wp-sjs-editor-config"><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
        <?php
    }
}

?>
