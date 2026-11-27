<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once( __DIR__ . "/../service_client.php");

class SurveyJS_Editor {

    function __construct() {
    }

    const JSON_TABS = array( 'survey', 'theme' );

    const JSON_GUIDE_URL = 'https://surveyjs.io/form-library/documentation/design-survey/create-a-simple-survey';

    public static function ui_builder_url() {
        return surveyjs_site_url( '/wordpress-ui-builder' );
    }

    private static function render_empty_hint() {
        if ( SurveyJS_Welcome::visual_editor() ) {
            return;
        }
        ?>
        <div class="wp-sjs-editor__empty" id="wp-sjs-empty-hint" hidden>
            <h2 class="wp-sjs-editor__empty-title"><?php esc_html_e( 'This form is empty', 'surveyjs' ); ?></h2>
            <p><?php esc_html_e( 'Add questions in the JSON editor. If you\'re new to SurveyJS JSON, read the Getting Started guide, or build the form visually in the free UI Builder.', 'surveyjs' ); ?></p>
            <p class="wp-sjs-editor__empty-actions">
                <a class="button wp-sjs-button-icon" href="<?php echo esc_url( self::JSON_GUIDE_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Getting Started guide', 'surveyjs' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span><span class="dashicons dashicons-external" aria-hidden="true"></span></a>
                <button type="button" class="button wp-sjs-button-icon" id="wp-sjs-empty-ui-builder"><?php esc_html_e( 'Open in free UI Builder', 'surveyjs' ); ?><span class="dashicons dashicons-external" aria-hidden="true"></span></button>
            </p>
        </div>
        <?php
    }

    public static function tabs( $survey ) {
        $tabs = array(
            'survey'  => __( 'Config', 'surveyjs' ),
            'theme'   => __( 'Theme', 'surveyjs' ),
            'email'   => __( 'Email', 'surveyjs' ),
            'integrations' => __( 'Integrations', 'surveyjs' ),
            'entries' => __( 'Entries', 'surveyjs' ),
            'restrictions' => __( 'Restrictions', 'surveyjs' ),
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

    // editor.js opens a JSON tab only once Monaco has loaded, so the page starts on the first tab for those too.
    private static function initial_tab( $tabs ) {
        $requested = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the tab shown first.
        $requested = 'webhooks' === $requested ? SurveyJS_Integrations::TAB : $requested;
        if ( isset( $tabs[ $requested ] ) && ! in_array( $requested, self::JSON_TABS, true ) ) {
            return $requested;
        }
        return (string) array_key_first( $tabs );
    }

    private static function hidden_unless( $tab, $initial_tab ) {
        if ( $tab !== $initial_tab ) {
            echo ' hidden';
        }
    }

    public static function render_item_header() {
        ?>
        <div class="wp-sjs-item__header">
            <label class="wp-sjs-item__active"><input type="checkbox" data-field="enabled" /> <?php esc_html_e( 'Active', 'surveyjs' ); ?></label>
            <button type="button" class="wp-sjs-item__title" data-action="toggle" aria-expanded="false" aria-controls="wp-sjs-item-__KEY__-body">
                <span class="wp-sjs-item__name" data-name></span>
                <span class="wp-sjs-item__summary" data-summary></span>
                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </button>
        </div>
        <?php
    }

    public static function render_item_actions() {
        ?>
        <p class="wp-sjs-item__actions">
            <button type="button" class="button-link button-link-delete" data-action="delete"><?php esc_html_e( 'Delete', 'surveyjs' ); ?></button>
        </p>
        <?php
    }

    public static function render_conditions_row() {
        ?>
        <tr>
            <th scope="row"><?php esc_html_e( 'Conditions', 'surveyjs' ); ?></th>
            <td class="wp-sjs-conditions" data-conditions>
                <p class="wp-sjs-conditions__always" data-when-empty><?php esc_html_e( 'Sent for every entry.', 'surveyjs' ); ?></p>
                <p data-when-rules>
                    <label for="wp-sjs-item-__KEY__-logic"><?php esc_html_e( 'Send when', 'surveyjs' ); ?></label>
                    <select id="wp-sjs-item-__KEY__-logic" data-condition-logic>
                        <option value="all"><?php esc_html_e( 'all conditions match', 'surveyjs' ); ?></option>
                        <option value="any"><?php esc_html_e( 'any condition matches', 'surveyjs' ); ?></option>
                    </select>
                </p>
                <ul class="wp-sjs-conditions__rules" data-rules></ul>
                <button type="button" class="button" data-action="add-rule"><?php esc_html_e( 'Add condition', 'surveyjs' ); ?></button>
                <p class="description"><?php esc_html_e( 'Checked against the submitted answers. "Contains" on a multiple-choice question checks the selected choices; text comparison ignores case.', 'surveyjs' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public static function render_log_row() {
        ?>
        <tr>
            <th scope="row"><?php esc_html_e( 'Recent requests', 'surveyjs' ); ?></th>
            <td>
                <p class="description" data-log-empty><?php esc_html_e( 'No requests yet.', 'surveyjs' ); ?></p>
                <table class="widefat striped wp-sjs-webhook-log" data-log hidden>
                    <thead><tr><th scope="col"><?php esc_html_e( 'Date', 'surveyjs' ); ?></th><th scope="col"><?php esc_html_e( 'Request', 'surveyjs' ); ?></th><th scope="col"><?php esc_html_e( 'Result', 'surveyjs' ); ?></th></tr></thead>
                    <tbody></tbody>
                </table>
            </td>
        </tr>
        <?php
    }

    private static function render_integrations_panel( $initial_tab ) {
        $services = SurveyJS_Integrations::services();
        ?>
        <div class="wp-sjs-editor__settings" data-panel="<?php echo esc_attr( SurveyJS_Integrations::TAB ); ?>"<?php self::hidden_unless( SurveyJS_Integrations::TAB, $initial_tab ); ?>>
            <input type="hidden" data-setting="integrations" data-setting-type="json" id="wp-sjs-integrations-value" />
            <h2 class="title"><?php esc_html_e( 'Integrations', 'surveyjs' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Send each entry to the apps you use. Connect an account once on Settings > Integrations, then add it here and choose what to send. Entries are sent in the background right after the submission; failed requests are retried after 5 minutes, 1 hour and 6 hours.', 'surveyjs' ); ?>
                <a href="<?php echo esc_url( SurveyJS_Integrations::settings_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open Settings > Integrations', 'surveyjs' ); ?></a>
            </p>
            <div class="wp-sjs-items" id="wp-sjs-integrations" data-delete-confirm="<?php esc_attr_e( 'Delete this integration?', 'surveyjs' ); ?>"></div>
            <?php if ( $services ) : ?>
                <p class="wp-sjs-integrations__add">
                    <label for="wp-sjs-integration-service" class="screen-reader-text"><?php esc_html_e( 'Service', 'surveyjs' ); ?></label>
                    <select id="wp-sjs-integration-service">
                        <?php foreach ( $services as $service ) : ?>
                            <option value="<?php echo esc_attr( $service->id() ); ?>"><?php echo esc_html( $service->label() ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button" id="wp-sjs-integration-add"><?php esc_html_e( 'Add integration', 'surveyjs' ); ?></button>
                </p>
            <?php endif; ?>
            <?php SurveyJS_Integrations::render_templates(); ?>

            <input type="hidden" data-setting="webhooks" data-setting-type="json" id="wp-sjs-webhooks-value" />
            <h2 class="title wp-sjs-integrations__webhooks"><?php esc_html_e( 'Webhooks', 'surveyjs' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Send each new entry as JSON to Zapier, Make, n8n, a CRM or your own code. Paste the webhook URL the service gives you ("Catch Hook" in Zapier, "Custom webhook" in Make, the "Webhook" node in n8n).', 'surveyjs' ); ?></p>
            <p class="description"><?php esc_html_e( 'Requests are sent in the background by WP-Cron. Failed ones are retried after 5 minutes and after 1 hour.', 'surveyjs' ); ?></p>
            <div class="wp-sjs-items" id="wp-sjs-webhooks" data-delete-confirm="<?php esc_attr_e( 'Delete this webhook?', 'surveyjs' ); ?>"></div>
            <p><button type="button" class="button" id="wp-sjs-webhook-add"><?php esc_html_e( 'Add webhook', 'surveyjs' ); ?></button></p>
            <details class="wp-sjs-webhook-format">
                <summary><?php esc_html_e( 'Request format', 'surveyjs' ); ?></summary>
                <p><?php esc_html_e( '"answers" has every question of the form by name, with the answer as text (empty when not answered): map these fields in Zapier, Make or n8n. "data" holds the answers as SurveyJS stores them, for code. "fields" adds the question titles. File answers are download links that work for 7 days (1 day when entries are not stored). "entry.id" and "entry.url" are null when entries are not stored. Test requests have "event": "test" and use the latest entry or sample answers.', 'surveyjs' ); ?></p>
                <pre><code>{
  "event": "entry.submitted",
  "form": { "id": 12, "name": "Contact" },
  "entry": {
    "id": 345,
    "submitted_at": "2026-01-31T10:00:00+00:00",
    "url": "https://example.com/wp-admin/admin.php?page=surveyjs_results&amp;id=12&amp;entry=345"
  },
  "site": { "name": "My Site", "url": "https://example.com/" },
  "answers": { "name": "Jane", "topics": "Sales, Support" },
  "data": { "name": "Jane", "topics": ["sales", "support"] },
  "fields": [
    { "name": "name", "title": "Your name", "text": "Jane" },
    { "name": "topics", "title": "Topics", "text": "Sales, Support" }
  ]
}</code></pre>
                <p><?php esc_html_e( 'Headers: X-SurveyJS-Event (entry.submitted or test) and X-SurveyJS-Delivery (the same for retries of one request).', 'surveyjs' ); ?></p>
            </details>
            <template id="wp-sjs-webhook-template">
                <div class="wp-sjs-item">
                    <?php self::render_item_header(); ?>
                    <div class="wp-sjs-item__body" id="wp-sjs-item-__KEY__-body" hidden>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="wp-sjs-item-__KEY__-name"><?php esc_html_e( 'Name', 'surveyjs' ); ?></label></th>
                                <td><input type="text" id="wp-sjs-item-__KEY__-name" class="regular-text" data-field="name" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wp-sjs-item-__KEY__-url"><?php esc_html_e( 'Webhook URL', 'surveyjs' ); ?></label></th>
                                <td>
                                    <input type="url" id="wp-sjs-item-__KEY__-url" class="large-text code" data-field="url" placeholder="https://" />
                                    <p class="description"><?php esc_html_e( 'Public HTTP or HTTPS addresses on ports 80, 443 or 8080 only. Local network addresses are blocked.', 'surveyjs' ); ?></p>
                                    <p><button type="button" class="button" data-action="test-webhook"><?php esc_html_e( 'Send test request', 'surveyjs' ); ?></button></p>
                                    <div data-test-result aria-live="polite"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Headers', 'surveyjs' ); ?></th>
                                <td>
                                    <ul class="wp-sjs-webhook-headers" data-headers></ul>
                                    <button type="button" class="button" data-action="add-header"><?php esc_html_e( 'Add header', 'surveyjs' ); ?></button>
                                    <p class="description"><?php esc_html_e( 'Optional headers for every request, such as an API key: Authorization with "Bearer <token>", the n8n Header Auth header or x-make-apikey for Make.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <?php self::render_conditions_row(); ?>
                            <?php self::render_log_row(); ?>
                        </table>
                        <?php self::render_item_actions(); ?>
                    </div>
                </div>
            </template>
        </div>
        <?php
    }

    private static function render_item_templates() {
        $operators = array(
            'equal'       => __( 'is', 'surveyjs' ),
            'notequal'    => __( 'is not', 'surveyjs' ),
            'contains'    => __( 'contains', 'surveyjs' ),
            'notcontains' => __( 'does not contain', 'surveyjs' ),
            'greater'     => __( 'is greater than', 'surveyjs' ),
            'less'        => __( 'is less than', 'surveyjs' ),
            'empty'       => __( 'is empty', 'surveyjs' ),
            'notempty'    => __( 'is not empty', 'surveyjs' ),
        );
        ?>
        <template id="wp-sjs-condition-template">
            <li class="wp-sjs-conditions__rule">
                <select data-rule="question" aria-label="<?php esc_attr_e( 'Question', 'surveyjs' ); ?>"></select>
                <select data-rule="operator" aria-label="<?php esc_attr_e( 'Comparison', 'surveyjs' ); ?>">
                    <?php foreach ( $operators as $operator => $label ) : ?>
                        <option value="<?php echo esc_attr( $operator ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" data-rule="value" aria-label="<?php esc_attr_e( 'Value', 'surveyjs' ); ?>" />
                <select data-rule="choice" aria-label="<?php esc_attr_e( 'Value', 'surveyjs' ); ?>" hidden></select>
                <button type="button" class="button-link button-link-delete" data-action="remove-rule"><?php esc_html_e( 'Remove', 'surveyjs' ); ?></button>
            </li>
        </template>
        <template id="wp-sjs-header-template">
            <li class="wp-sjs-webhook-headers__row">
                <input type="text" class="code" data-header="name" aria-label="<?php esc_attr_e( 'Header name', 'surveyjs' ); ?>" placeholder="<?php esc_attr_e( 'Name', 'surveyjs' ); ?>" />
                <input type="text" class="regular-text code" data-header="value" aria-label="<?php esc_attr_e( 'Header value', 'surveyjs' ); ?>" placeholder="<?php esc_attr_e( 'Value', 'surveyjs' ); ?>" />
                <button type="button" class="button-link button-link-delete" data-action="remove-header"><?php esc_html_e( 'Remove', 'surveyjs' ); ?></button>
            </li>
        </template>
        <?php
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
            'testWebhookUrl' => add_query_arg( array( 'action' => 'SurveyJS_TestWebhook' ), admin_url( 'admin-ajax.php' ) ),
            'resendUrl' => add_query_arg( array( 'action' => 'SurveyJS_ResendDelivery' ), admin_url( 'admin-ajax.php' ) ),
            'deliveryLog' => SurveyJS_Delivery_Queue::client_log( $survey->id ),
            'integrations' => SurveyJS_Integrations::editor_config(),
            'saveNonce' => wp_create_nonce( 'surveyjs-save-survey' ),
            'monacoUrl' => plugins_url( '../libs/monaco/', __FILE__ ),
            'themesUrl' => SurveyJS_Themes::url(),
            'surveyCssUrl' => plugins_url( '../libs/library/survey-core.min.css', __FILE__ ),
            'previewUrl' => add_query_arg( 'surveyjs_preview', '1', SurveyJS_SurveyJS::full_preview_url() ?: home_url( '/' ) ),
            'fullPreviewUrl' => SurveyJS_SurveyJS::full_preview_url(),
            'uiBuilderUrl' => self::ui_builder_url(),
            'gdpr'      => self::gdpr_agreement(),
            'privacyPolicyUrl' => get_privacy_policy_url(),
            'isNew'     => ! empty( $_GET['new'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only decides whether to show a hint.
            'nextSteps' => SurveyJS_Welcome::editor_next_steps(),
            'ai'        => SurveyJS_AI_Drafts::editor_config( $survey ),
        );
        $list_url = add_query_arg( array( 'page' => 'surveyjs-main-menu' ), admin_url( 'admin.php' ) );
        $captcha_configured = SurveyJS_Captcha::is_configured();
        $akismet_available = SurveyJS_Akismet::is_available();
        $site_retention_days = SurveyJS_Entry_Retention::site_days();
        $settings_url = add_query_arg( array( 'page' => 'surveyjs-settings' ), admin_url( 'admin.php' ) );
        $tabs = self::tabs( $survey );
        $initial_tab = self::initial_tab( $tabs );
        $has_config = isset( $tabs['survey'] );
        ?>
        <div class="wrap wp-sjs-editor" data-active-tab="<?php echo esc_attr( $initial_tab ); ?>">
            <?php // The form's own header bar, edge to edge like the plugin header: All Forms / form name on the left, actions on the right. ?>
            <div class="wp-sjs-editor__header">
                <nav class="wp-sjs-breadcrumbs wp-sjs-editor__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'surveyjs' ); ?>">
                    <ol>
                        <li><a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'All Forms', 'surveyjs' ); ?></a></li>
                        <li aria-current="page">
                            <?php // The name edits in place: click it (or Enter on it), type, then Enter or click away to save; Escape cancels. ?>
                            <h1 class="wp-heading-inline wp-sjs-editor__title">
                                <button type="button" class="wp-sjs-editor__title-button" id="wp-sjs-name-edit" title="<?php esc_attr_e( 'Click to rename', 'surveyjs' ); ?>"><span id="wp-sjs-name-text"><?php echo esc_html( $survey->name ); ?></span><span class="screen-reader-text"> <?php esc_html_e( '(rename)', 'surveyjs' ); ?></span></button>
                                <input type="text" class="wp-sjs-editor__title-input" id="wp-sjs-survey-name" aria-label="<?php esc_attr_e( 'Form name', 'surveyjs' ); ?>" hidden />
                            </h1>
                        </li>
                    </ol>
                </nav>
                <div class="wp-sjs-editor__actions">
                    <button type="button" class="button wp-sjs-button-icon wp-sjs-editor__full-preview" id="wp-sjs-full-preview" aria-controls="wp-sjs-preview-panel" aria-expanded="false"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Full preview', 'surveyjs' ); ?></button>
                    <button type="button" class="button wp-sjs-button-icon wp-sjs-editor__embed" id="wp-sjs-embed" aria-haspopup="dialog" aria-controls="wp-sjs-embed-panel" aria-expanded="false"><span class="dashicons dashicons-shortcode" aria-hidden="true"></span><?php esc_html_e( 'Embed', 'surveyjs' ); ?></button>
                    <a href="<?php echo esc_url( SurveyJS_Templates::save_url( $survey->id ) ); ?>" class="button wp-sjs-editor__save-template" id="wp-sjs-save-template"><?php esc_html_e( 'Save as Template', 'surveyjs' ); ?></a>
                    <button type="button" class="button button-primary wp-sjs-editor__save" id="wp-sjs-save"><?php esc_html_e( 'Save', 'surveyjs' ); ?></button>
                </div>
            </div>
            <?php // Notices from WordPress and other plugins go here, under the header bar and above the tabs. ?>
            <hr class="wp-header-end">
            <div id="wp-sjs-ai-draft-notice"></div>
            <div id="wp-sjs-editor-notice"></div>
            <?php SurveyJS_Embed::render_panel( $survey ); ?>

            <nav class="nav-tab-wrapper wp-sjs-editor__tabs">
                <?php foreach ( $tabs as $tab => $label ) : ?>
                    <a href="#" class="nav-tab<?php echo $tab === $initial_tab ? ' nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $tab ); ?>"><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="wp-sjs-editor__columns">
                <div class="wp-sjs-editor__pane" id="wp-sjs-json-pane"<?php echo in_array( $initial_tab, self::JSON_TABS, true ) ? '' : ' hidden'; ?>>
                    <div class="wp-sjs-editor__pane-header">
                        <button type="button" class="button-link wp-sjs-editor__ui-builder" id="wp-sjs-ui-builder"><?php esc_html_e( 'Open in UI Builder', 'surveyjs' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'surveyjs' ); ?></span></button>
                        <button type="button" class="button-link" id="wp-sjs-ui-builder-copy"><?php esc_html_e( 'Copy JSON', 'surveyjs' ); ?></button>
                        <?php SurveyJS_Upsell::render_editor_link(); ?>
                        <div class="wp-sjs-editor__ui-builder-status" id="wp-sjs-ui-builder-status" aria-live="polite"></div>
                    </div>
                    <div class="wp-sjs-editor__theme-mode" id="wp-sjs-theme-mode" hidden>
                        <div class="wp-sjs-editor__theme-line">
                            <label for="wp-sjs-theme-mode-adaptive"><input type="checkbox" id="wp-sjs-theme-mode-adaptive" data-setting="themeMode" data-checked-value="adaptive" data-unchecked-value="surveyjs" /> <?php esc_html_e( 'Adaptive: use your site\'s theme styles', 'surveyjs' ); ?></label>
                            <span id="wp-sjs-theme-preset-row">
                                <label class="screen-reader-text" for="wp-sjs-theme-preset"><?php esc_html_e( 'Theme', 'surveyjs' ); ?></label>
                                <select id="wp-sjs-theme-preset">
                                    <option value=""><?php esc_html_e( 'Custom', 'surveyjs' ); ?></option>
                                    <?php foreach ( SurveyJS_Themes::labels() as $slug => $label ) : ?>
                                        <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </span>
                            <span id="wp-sjs-theme-background-row">
                                <label for="wp-sjs-transparent-background"><input type="checkbox" id="wp-sjs-transparent-background" data-setting="transparentBackground" /> <?php esc_html_e( 'Transparent background', 'surveyjs' ); ?></label>
                            </span>
                            <span id="wp-sjs-theme-bg-image-row">
                                <button type="button" class="button" id="wp-sjs-bg-image" data-frame-title="<?php esc_attr_e( 'Background image', 'surveyjs' ); ?>" data-frame-button="<?php esc_attr_e( 'Use as background', 'surveyjs' ); ?>" disabled><?php esc_html_e( 'Background image', 'surveyjs' ); ?></button>
                                <button type="button" class="button-link button-link-delete" id="wp-sjs-bg-image-remove" hidden><?php esc_html_e( 'Remove', 'surveyjs' ); ?><span class="screen-reader-text"> <?php esc_html_e( 'background image', 'surveyjs' ); ?></span></button>
                            </span>
                            <span class="wp-sjs-editor__theme-actions">
                                <button type="button" class="button" id="wp-sjs-ai-convert" disabled><?php esc_html_e( 'Customize', 'surveyjs' ); ?></button>
                                <button type="button" class="button" id="wp-sjs-ai-paste" aria-haspopup="dialog" aria-controls="wp-sjs-ai-dialog"><?php esc_html_e( 'Match your site with AI', 'surveyjs' ); ?></button>
                            </span>
                        </div>
                    </div>
                    <div class="wp-sjs-editor__overlay">
                        <button type="button" class="button button-small wp-sjs-editor__format" id="wp-sjs-format"><?php esc_html_e( 'Format JSON', 'surveyjs' ); ?></button>
                    </div>
                    <div id="wp-sjs-monaco" class="wp-sjs-editor__monaco"><p class="wp-sjs-editor__loading"><?php esc_html_e( 'Loading editor…', 'surveyjs' ); ?></p></div>
                    <div class="wp-sjs-editor__pane-footer">
                    <div class="wp-sjs-editor__media" id="wp-sjs-media">
                        <button type="button" class="button" id="wp-sjs-insert-image" data-frame-title="<?php esc_attr_e( 'Get image URL', 'surveyjs' ); ?>" data-frame-button="<?php esc_attr_e( 'Get URL', 'surveyjs' ); ?>" disabled><span class="dashicons dashicons-format-image" aria-hidden="true"></span><?php esc_html_e( 'Get image URL', 'surveyjs' ); ?></button>
                        <?php if ( $has_config ) : ?>
                        <button type="button" class="button" id="wp-sjs-set-logo" data-frame-title="<?php esc_attr_e( 'Form logo', 'surveyjs' ); ?>" data-frame-button="<?php esc_attr_e( 'Use as logo', 'surveyjs' ); ?>" disabled><?php esc_html_e( 'Set logo', 'surveyjs' ); ?></button>
                        <label class="wp-sjs-editor__gdpr" id="wp-sjs-gdpr-agreement-label" for="wp-sjs-gdpr-agreement" title="<?php esc_attr_e( 'Adds a required consent checkbox with a link to your Privacy Policy page at the end of the form. Uncheck to remove it.', 'surveyjs' ); ?>"><input type="checkbox" id="wp-sjs-gdpr-agreement" /> <?php esc_html_e( 'GDPR agreement', 'surveyjs' ); ?></label>
                        <?php endif; ?>
                        <ul class="wp-sjs-editor__images" id="wp-sjs-image-list" aria-label="<?php esc_attr_e( 'Chosen images', 'surveyjs' ); ?>" data-url-label="<?php esc_attr_e( 'Image URL', 'surveyjs' ); ?>" data-copy-label="<?php esc_attr_e( 'Copy', 'surveyjs' ); ?>" data-copied-label="<?php esc_attr_e( 'Copied!', 'surveyjs' ); ?>" data-remove-label="<?php esc_attr_e( 'Remove from the list', 'surveyjs' ); ?>" hidden></ul>
                    </div>
                    <?php if ( $has_config ) : ?>
                    <div id="wp-sjs-lint" class="wp-sjs-lint"></div>
                    <?php endif; ?>
                    </div>
                    <dialog class="wp-sjs-embed wp-sjs-ai-dialog" id="wp-sjs-ai-dialog" aria-labelledby="wp-sjs-ai-dialog-title">
                        <div class="wp-sjs-embed__header">
                            <h2 id="wp-sjs-ai-dialog-title"><?php esc_html_e( 'Match your site with AI', 'surveyjs' ); ?></h2>
                            <button type="button" class="button-link wp-sjs-embed__close" data-close aria-label="<?php esc_attr_e( 'Close', 'surveyjs' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
                        </div>
                        <div class="wp-sjs-embed__body">
                            <p class="description"><?php esc_html_e( 'Your AI chat makes a theme from your site\'s colors, fonts, buttons and fields. The plugin sends nothing to AI services.', 'surveyjs' ); ?></p>
                            <h3><?php esc_html_e( '1. Copy the prompt and paste it into your AI chat', 'surveyjs' ); ?></h3>
                            <p><button type="button" class="button" id="wp-sjs-ai-prompt" disabled><?php esc_html_e( 'Copy prompt', 'surveyjs' ); ?></button></p>
                            <h3><label for="wp-sjs-ai-answer"><?php esc_html_e( '2. Paste the AI answer', 'surveyjs' ); ?></label></h3>
                            <textarea id="wp-sjs-ai-answer" class="large-text code" rows="8" placeholder="<?php esc_attr_e( 'The AI answer with the theme JSON', 'surveyjs' ); ?>"></textarea>
                            <div id="wp-sjs-ai-notice" aria-live="polite"></div>
                        </div>
                        <div class="wp-sjs-embed__footer">
                            <button type="button" class="button" id="wp-sjs-ai-cancel" data-close><?php esc_html_e( 'Cancel', 'surveyjs' ); ?></button>
                            <button type="button" class="button button-primary" id="wp-sjs-ai-apply"><?php esc_html_e( 'Apply theme', 'surveyjs' ); ?></button>
                        </div>
                    </dialog>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="entries"<?php self::hidden_unless( 'entries', $initial_tab ); ?>>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Store entries', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="storeEntries" /> <?php esc_html_e( 'Save submitted entries in the database', 'surveyjs' ); ?></label></td>
                        </tr>
                        <tr data-enabled-by="storeEntries">
                            <th scope="row"><?php esc_html_e( 'Partial entries', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="savePartialEntries" /> <?php esc_html_e( 'Save partial entries', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'Saves answers on each page change, so you see entries of people who did not finish. They appear under Partial and become regular entries on submit. Unfinished ones are deleted after 30 days unless you click Keep. Mention this in your privacy policy. Needs Store entries.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="wp-sjs-entry-retention"><?php esc_html_e( 'Delete old entries', 'surveyjs' ); ?></label></th>
                            <td>
                                <div class="wp-sjs-inline-fields">
                                <select id="wp-sjs-entry-retention" data-setting="entryRetention">
                                    <option value="default">
                                        <?php
                                        echo esc_html( $site_retention_days
                                            /* translators: %d: number of days */
                                            ? sprintf( _n( 'Use the site setting: delete after %d day', 'Use the site setting: delete after %d days', $site_retention_days, 'surveyjs' ), $site_retention_days )
                                            : __( 'Use the site setting: keep forever', 'surveyjs' ) );
                                        ?>
                                    </option>
                                    <option value="forever"><?php esc_html_e( 'Keep forever', 'surveyjs' ); ?></option>
                                    <option value="days"><?php esc_html_e( 'Delete after a number of days', 'surveyjs' ); ?></option>
                                </select>
                                <span data-enabled-if="entryRetention=days">
                                    <input type="number" id="wp-sjs-entry-retention-days" class="small-text" min="1" step="1" data-setting="entryRetentionDays" aria-label="<?php esc_attr_e( 'Days to keep entries', 'surveyjs' ); ?>" />
                                    <?php esc_html_e( 'days', 'surveyjs' ); ?>
                                </span>
                                </div>
                                <p class="description">
                                    <?php esc_html_e( 'Checked once a day. Old entries, including partial and spam ones, are deleted with their files and cannot be restored. GDPR requires keeping personal data only as long as you need it.', 'surveyjs' ); ?>
                                    <a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Site setting', 'surveyjs' ); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="restrictions"<?php self::hidden_unless( 'restrictions', $initial_tab ); ?>>
                    <div class="notice notice-warning inline" id="wp-sjs-restrictions-store-notice" hidden>
                        <p><?php esc_html_e( 'Entry limits work only when Store entries is on (Entries tab).', 'surveyjs' ); ?></p>
                    </div>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Entry limit', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="limitEntries" /> <?php esc_html_e( 'Limit the number of entries', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'At the limit, the form is replaced with the message below. Partial, spam and deleted entries do not count.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="limitEntries">
                            <th scope="row"><label for="wp-sjs-entry-limit"><?php esc_html_e( 'Number of entries', 'surveyjs' ); ?></label></th>
                            <td>
                                <div class="wp-sjs-inline-fields">
                                <input type="number" id="wp-sjs-entry-limit" class="small-text" min="1" step="1" data-setting="entryLimit" />
                                <select id="wp-sjs-entry-limit-period" data-setting="entryLimitPeriod" aria-label="<?php esc_attr_e( 'Limit period', 'surveyjs' ); ?>">
                                    <option value="total"><?php esc_html_e( 'in total', 'surveyjs' ); ?></option>
                                    <option value="day"><?php esc_html_e( 'per day', 'surveyjs' ); ?></option>
                                    <option value="week"><?php esc_html_e( 'per week', 'surveyjs' ); ?></option>
                                    <option value="month"><?php esc_html_e( 'per month', 'surveyjs' ); ?></option>
                                    <option value="year"><?php esc_html_e( 'per year', 'surveyjs' ); ?></option>
                                </select>
                                </div>
                                <p class="description"><?php esc_html_e( 'Periods start at midnight in the site time zone; weeks start on the day set in Settings > General.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="limitEntries">
                            <th scope="row"><label for="wp-sjs-entry-limit-message"><?php esc_html_e( 'Limit reached message', 'surveyjs' ); ?></label></th>
                            <td><textarea id="wp-sjs-entry-limit-message" class="large-text" rows="3" data-setting="entryLimitMessage"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Entries per person', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="limitPerRespondent" /> <?php esc_html_e( 'Limit how many times one person can submit the form', 'surveyjs' ); ?></label></td>
                        </tr>
                        <tr data-enabled-by="limitPerRespondent">
                            <th scope="row"><label for="wp-sjs-respondent-limit"><?php esc_html_e( 'Maximum per person', 'surveyjs' ); ?></label></th>
                            <td>
                                <div class="wp-sjs-inline-fields">
                                <input type="number" id="wp-sjs-respondent-limit" class="small-text" min="1" step="1" data-setting="respondentLimit" />
                                <label for="wp-sjs-respondent-by"><?php esc_html_e( 'Identify people by', 'surveyjs' ); ?></label>
                                <select id="wp-sjs-respondent-by" data-setting="respondentBy">
                                    <option value="user"><?php esc_html_e( 'User account', 'surveyjs' ); ?></option>
                                    <option value="ip"><?php esc_html_e( 'IP address', 'surveyjs' ); ?></option>
                                </select>
                                </div>
                                <p class="description"><?php esc_html_e( 'User account: reliable, but only logged-in users can submit; others see the login message.', 'surveyjs' ); ?></p>
                                <p class="description"><?php esc_html_e( 'IP address: no login needed, but a shared network (office, school, mobile carrier) counts as one person, and addresses can change. Only a one-way hash of the address is stored.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="limitPerRespondent">
                            <th scope="row"><label for="wp-sjs-respondent-limit-message"><?php esc_html_e( 'Limit reached message', 'surveyjs' ); ?></label></th>
                            <td><textarea id="wp-sjs-respondent-limit-message" class="large-text" rows="3" data-setting="respondentLimitMessage"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Schedule', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="scheduleEnabled" /> <?php esc_html_e( 'Open and close the form at set times', 'surveyjs' ); ?></label></td>
                        </tr>
                        <tr data-enabled-by="scheduleEnabled">
                            <th scope="row"><label for="wp-sjs-schedule-start"><?php esc_html_e( 'Opens', 'surveyjs' ); ?></label></th>
                            <td><input type="datetime-local" id="wp-sjs-schedule-start" data-setting="scheduleStart" /></td>
                        </tr>
                        <tr data-enabled-by="scheduleEnabled">
                            <th scope="row"><label for="wp-sjs-schedule-end"><?php esc_html_e( 'Closes', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="datetime-local" id="wp-sjs-schedule-end" data-setting="scheduleEnd" />
                                <p class="description">
                                    <?php
                                    /* translators: %s: site time zone, for example "Europe/Berlin" or "+02:00" */
                                    echo esc_html( sprintf( __( 'Leave a field empty for no start or no end. Site time zone: %s.', 'surveyjs' ), wp_timezone_string() ) );
                                    ?>
                                    <a href="<?php echo esc_url( admin_url( 'options-general.php#timezone_string' ) ); ?>"><?php esc_html_e( 'Change the time zone', 'surveyjs' ); ?></a>
                                </p>
                            </td>
                        </tr>
                        <tr data-enabled-by="scheduleEnabled">
                            <th scope="row"><label for="wp-sjs-schedule-pending-message"><?php esc_html_e( 'Before opening message', 'surveyjs' ); ?></label></th>
                            <td>
                                <textarea id="wp-sjs-schedule-pending-message" class="large-text" rows="3" data-setting="schedulePendingMessage"></textarea>
                                <p class="description"><?php esc_html_e( '{open_date} is replaced with the opening date and time.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="scheduleEnabled">
                            <th scope="row"><label for="wp-sjs-schedule-expired-message"><?php esc_html_e( 'After closing message', 'surveyjs' ); ?></label></th>
                            <td>
                                <textarea id="wp-sjs-schedule-expired-message" class="large-text" rows="3" data-setting="scheduleExpiredMessage"></textarea>
                                <p class="description"><?php esc_html_e( '{close_date} is replaced with the closing date and time.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Require login', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="requireLogin" /> <?php esc_html_e( 'Only logged-in users can fill in the form', 'surveyjs' ); ?></label></td>
                        </tr>
                        <tr data-enabled-by="requireLogin limitPerRespondent">
                            <th scope="row"><label for="wp-sjs-require-login-message"><?php esc_html_e( 'Login message', 'surveyjs' ); ?></label></th>
                            <td>
                                <textarea id="wp-sjs-require-login-message" class="large-text" rows="3" data-setting="requireLoginMessage"></textarea>
                                <p class="description"><?php esc_html_e( 'Shown with a Log in link instead of the form.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="description"><?php esc_html_e( 'Messages replace the form and can contain basic HTML. Restrictions are checked on page load and on submit, so they work on cached pages too.', 'surveyjs' ); ?></p>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="spam"<?php self::hidden_unless( 'spam', $initial_tab ); ?>>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'CAPTCHA', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="captchaEnabled" <?php disabled( ! $captcha_configured ); ?> /> <?php esc_html_e( 'Enable CAPTCHA', 'surveyjs' ); ?></label>
                                <?php if ( $captcha_configured ) : ?>
                                    <p class="description">
                                        <?php
                                        /* translators: %s: CAPTCHA type, for example "Cloudflare Turnstile" */
                                        echo esc_html( sprintf( __( 'Respondents pass %s before submitting. The service and keys are set in Settings.', 'surveyjs' ), SurveyJS_Captcha::type_label() ) );
                                        ?>
                                        <a href="<?php echo esc_url( SurveyJS_Captcha::settings_url() ); ?>"><?php esc_html_e( 'CAPTCHA settings', 'surveyjs' ); ?></a>
                                    </p>
                                <?php else : ?>
                                    <div class="notice notice-info inline">
                                        <p>
                                            <?php esc_html_e( 'CAPTCHA is not set up yet. Choose its type and enter the keys in Settings first.', 'surveyjs' ); ?>
                                            <a href="<?php echo esc_url( SurveyJS_Captcha::settings_url() ); ?>"><?php esc_html_e( 'Set up CAPTCHA in Settings', 'surveyjs' ); ?></a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <p class="description"><?php esc_html_e( 'A hidden honeypot field is always on and blocks simple spam bots.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Akismet', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="akismetEnabled" <?php disabled( ! $akismet_available ); ?> /> <?php esc_html_e( 'Check entries with Akismet', 'surveyjs' ); ?></label>
                                <?php if ( $akismet_available ) : ?>
                                    <p class="description"><?php esc_html_e( 'Sends the respondent\'s name, email, website, text answers, IP address and browser to Akismet. Flagged entries get the Spam status and send no emails; the respondent sees the usual confirmation. Review them in the Spam view and click Not Spam if needed. If entries are not stored, flagged ones are dropped.', 'surveyjs' ); ?></p>
                                <?php else : ?>
                                    <div class="notice notice-info inline">
                                        <p>
                                            <?php esc_html_e( 'Akismet is not active or not connected to an account.', 'surveyjs' ); ?>
                                            <a href="<?php echo esc_url( SurveyJS_Akismet::plugin_url() ); ?>"><?php esc_html_e( 'Set up Akismet', 'surveyjs' ); ?></a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Submission time', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="minSubmitTimeEnabled" /> <?php esc_html_e( 'Reject entries sent too soon after the form opens', 'surveyjs' ); ?></label>
                            </td>
                        </tr>
                        <tr data-enabled-by="minSubmitTimeEnabled">
                            <th scope="row"><label for="wp-sjs-min-submit-time"><?php esc_html_e( 'Minimum time', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="number" id="wp-sjs-min-submit-time" class="small-text" min="1" max="<?php echo esc_attr( SurveyJS_Submit_Time::MAX_SECONDS ); ?>" step="1" data-setting="minSubmitTime" />
                                <?php esc_html_e( 'seconds', 'surveyjs' ); ?>
                                <p class="description"><?php esc_html_e( 'Bots submit right after the page loads. People do not notice: if someone is faster, the form waits and then sends the entry.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="advanced"<?php self::hidden_unless( 'advanced', $initial_tab ); ?>>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Save & Resume', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="saveResume" /> <?php esc_html_e( 'Let respondents continue later', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'Answers are saved as the respondent types and restored when they return: in the browser for visitors, on your site for logged-in users (so they can switch devices). Deleted after 30 days or on submit.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Markdown', 'surveyjs' ); ?></th>
                            <td>
                                <label><input type="checkbox" data-setting="markdown" id="wp-sjs-markdown" /> <?php esc_html_e( 'Format form texts with Markdown', 'surveyjs' ); ?></label>
                                <p class="description"><?php esc_html_e( 'Allows **bold**, *italic*, lists and links in form texts. [text](https://example.com) links work even when this is off. Entries, CSV exports and emails show plain text.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="wp-sjs-editor__settings" data-panel="email"<?php self::hidden_unless( 'email', $initial_tab ); ?>>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Email notification', 'surveyjs' ); ?></th>
                            <td><label><input type="checkbox" data-setting="notificationEnabled" /> <?php esc_html_e( 'Send an email when the form is submitted', 'surveyjs' ); ?></label></td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-to"><?php esc_html_e( 'Send To', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-to" class="regular-text" data-setting="notificationTo" />
                                <button type="button" class="button" id="wp-sjs-send-test-email"><?php esc_html_e( 'Send test email', 'surveyjs' ); ?></button>
                                <p class="description"><?php esc_html_e( 'Separate addresses with commas.', 'surveyjs' ); ?></p>
                                <p id="wp-sjs-test-email-result" aria-live="polite"></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-cc"><?php esc_html_e( 'CC', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-cc" class="regular-text" data-setting="notificationCc" />
                                <p class="description"><?php esc_html_e( 'Separate addresses with commas.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-bcc"><?php esc_html_e( 'BCC', 'surveyjs' ); ?></label></th>
                            <td>
                                <input type="text" id="wp-sjs-notification-bcc" class="regular-text" data-setting="notificationBcc" />
                                <p class="description"><?php esc_html_e( 'Other recipients do not see these addresses. Separate them with commas.', 'surveyjs' ); ?></p>
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
                                <p class="description"><?php esc_html_e( '{all_fields}: all answers, one per line. {field:question_name}: the answer to that question. Also: {form_name}, {site_name}, {admin_email}.', 'surveyjs' ); ?></p>
                                <p class="description wp-sjs-field-tags" hidden></p>
                                <p><label><input type="checkbox" id="wp-sjs-notification-html" data-setting="notificationHtml" data-hint="wp-sjs-notification-html-hint" /> <?php esc_html_e( 'Use HTML content type', 'surveyjs' ); ?></label></p>
                                <p class="description" id="wp-sjs-notification-html-hint" hidden><?php esc_html_e( 'For example: <h2>New entry</h2><p>{all_fields}</p>. Plain line breaks become paragraphs. Scripts and <style> blocks are removed, so use inline styles.', 'surveyjs' ); ?></p>
                            </td>
                        </tr>
                        <tr data-enabled-by="notificationEnabled">
                            <th scope="row"><label for="wp-sjs-notification-attachments"><?php esc_html_e( 'File Attachments', 'surveyjs' ); ?></label></th>
                            <td>
                                <select id="wp-sjs-notification-attachments" data-setting="notificationAttachments"></select>
                                <p class="description"><?php esc_html_e( 'Files uploaded to this question are attached, up to 18 MB in total.', 'surveyjs' ); ?></p>
                                <div id="wp-sjs-notification-attachments-add" hidden>
                                    <p><label><input type="checkbox" id="wp-sjs-notification-attachments-add-question" /> <?php esc_html_e( 'Add a File Upload question to the form', 'surveyjs' ); ?></label></p>
                                    <p class="description"><?php esc_html_e( 'Adds “Attach files” to the end of the form. Or add a File Upload question yourself.', 'surveyjs' ); ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php do_action( 'surveyjs_editor_email_rows', 'notification', $survey ); ?>
                    </table>
                    <div role="group" aria-labelledby="wp-sjs-auto-reply-title">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row" id="wp-sjs-auto-reply-title"><?php esc_html_e( 'Auto-reply', 'surveyjs' ); ?></th>
                                <td>
                                    <label><input type="checkbox" data-setting="autoReplyEnabled" /> <?php esc_html_e( 'Send an auto-reply to the respondent', 'surveyjs' ); ?></label>
                                    <p class="description"><?php esc_html_e( 'Sent to the Reply-To address. Replies go to the Send To addresses, or to the admin email if the notification is off. Bots can enter someone else\'s address, so use spam protection too.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-auto-reply-cc"><?php esc_html_e( 'CC', 'surveyjs' ); ?></label></th>
                                <td>
                                    <input type="text" id="wp-sjs-auto-reply-cc" class="regular-text" data-setting="autoReplyCc" />
                                    <p class="description"><?php esc_html_e( 'Separate addresses with commas.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-auto-reply-bcc"><?php esc_html_e( 'BCC', 'surveyjs' ); ?></label></th>
                                <td>
                                    <input type="text" id="wp-sjs-auto-reply-bcc" class="regular-text" data-setting="autoReplyBcc" />
                                    <p class="description"><?php esc_html_e( 'Other recipients do not see these addresses. Separate them with commas.', 'surveyjs' ); ?></p>
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
                                    <p class="description"><?php esc_html_e( '{all_fields}: all answers, one per line. {field:question_name}: the answer to that question. Also: {form_name}, {site_name}, {admin_email}.', 'surveyjs' ); ?></p>
                                    <p class="description wp-sjs-field-tags" hidden></p>
                                    <p><label><input type="checkbox" id="wp-sjs-auto-reply-html" data-setting="autoReplyHtml" data-hint="wp-sjs-auto-reply-html-hint" /> <?php esc_html_e( 'Use HTML content type', 'surveyjs' ); ?></label></p>
                                    <p class="description" id="wp-sjs-auto-reply-html-hint" hidden><?php esc_html_e( 'For example: <h2>Thank you!</h2><p>{all_fields}</p>. Plain line breaks become paragraphs. Scripts and <style> blocks are removed, so use inline styles.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-auto-reply-attachments"><?php esc_html_e( 'File Attachments', 'surveyjs' ); ?></label></th>
                                <td>
                                    <select id="wp-sjs-auto-reply-attachments" data-setting="autoReplyAttachments"></select>
                                    <p class="description"><?php esc_html_e( 'Files uploaded to this question are attached, up to 18 MB in total.', 'surveyjs' ); ?></p>
                                    <div id="wp-sjs-auto-reply-attachments-add" hidden>
                                        <p><label><input type="checkbox" id="wp-sjs-auto-reply-attachments-add-question" /> <?php esc_html_e( 'Add a File Upload question to the form', 'surveyjs' ); ?></label></p>
                                        <p class="description"><?php esc_html_e( 'Adds “Attach files” to the end of the form. Or add a File Upload question yourself.', 'surveyjs' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <?php do_action( 'surveyjs_editor_email_rows', 'auto_reply', $survey ); ?>
                        </table>
                    </div>
                    <div role="group" aria-labelledby="wp-sjs-sender-title" data-enabled-by="notificationEnabled autoReplyEnabled">
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row" id="wp-sjs-sender-title"><?php esc_html_e( 'Sender', 'surveyjs' ); ?></th>
                                <td><p class="description"><?php esc_html_e( 'For the notification and the auto-reply.', 'surveyjs' ); ?></p></td>
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
                                    <p class="description"><?php esc_html_e( 'Use an address on your site\'s domain, otherwise the email may land in spam.', 'surveyjs' ); ?></p>
                                </td>
                            </tr>
                            <tr data-enabled-by="notificationEnabled autoReplyEnabled">
                                <th scope="row"><label for="wp-sjs-notification-reply-to"><?php esc_html_e( 'Reply-To', 'surveyjs' ); ?></label></th>
                                <td>
                                    <select id="wp-sjs-notification-reply-to" data-setting="notificationReplyTo"></select>
                                    <p class="description"><?php esc_html_e( 'The question with the respondent\'s email. Your replies and the auto-reply go to this address.', 'surveyjs' ); ?></p>
                                    <div id="wp-sjs-notification-reply-to-add" hidden>
                                        <p><label><input type="checkbox" id="wp-sjs-notification-reply-to-add-question" /> <?php esc_html_e( 'Add a Reply-To question (email) to the form', 'surveyjs' ); ?></label></p>
                                        <p class="description"><?php esc_html_e( 'Adds “Leave your email so we can get back to you” to the end of the form. Or add a Single-Line Input with the Email input type yourself.', 'surveyjs' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php do_action( 'surveyjs_editor_email_rows', 'end', $survey ); ?>
                </div>
                <?php
                self::render_integrations_panel( $initial_tab );
                self::render_item_templates();
                ?>
                <?php do_action( 'surveyjs_render_editor', $survey, $initial_tab ); ?>
                <div class="wp-sjs-editor__preview" id="wp-sjs-preview-panel">
                    <div class="wp-sjs-editor__preview-bar">
                        <p class="description wp-sjs-editor__preview-note"><?php esc_html_e( 'Depending on your site\'s theme, the form on a real page may look slightly different from this preview.', 'surveyjs' ); ?></p>
                        <button type="button" class="button wp-sjs-button-icon wp-sjs-editor__preview-close" id="wp-sjs-preview-close" hidden><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><?php esc_html_e( 'Close preview', 'surveyjs' ); ?></button>
                    </div>
                    <?php self::render_empty_hint(); ?>
                    <iframe id="wp-sjs-preview"class="wp-sjs-editor__preview-frame" title="<?php esc_attr_e( 'Form preview', 'surveyjs' ); ?>"></iframe>
                    <iframe id="wp-sjs-full-preview-frame" class="wp-sjs-editor__preview-frame wp-sjs-editor__preview-frame--full" title="<?php esc_attr_e( 'Form preview on a page of your site', 'surveyjs' ); ?>"></iframe>
                </div>
            </div>
        </div>
        <script type="application/json" id="wp-sjs-editor-config"><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
        <?php
    }
}

?>
