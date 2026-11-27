<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_SettingsPage {

    const PAGE = 'surveyjs-settings-page';
    const OPTION_GROUP = 'surveyjs-settings-group';
    const OPTION = 'surveyjs-settings';

    // The settings are split into tabs. A tab's form posts only its own fields, so saving one tab must keep the
    // values of the others: the keys of this plugin's option that each tab owns (the rest of the option is kept as
    // saved), and the sections each tab shows. Sections of other plugins (the Add-on) go to the Add-on tab.
    const TAB_KEYS = array(
        'general' => array( 'store_ip', SurveyJS_Form_Stats::SETTING, 'entry_retention_days', 'uninstall_data' ),
        'spam'    => array( 'captcha_type', 'captcha_site_key', 'captcha_secret_key', 'captcha_threshold' ),
    );
    const TAB_SECTIONS = array(
        'sjs-data-section'    => 'general',
        'sjs-captcha-section' => 'spam',
    );
    const ADDON_TAB = 'addon';
    // Tabs whose settings only work together (the CAPTCHA service and its keys) keep a Save button; with
    // JavaScript the settings of the other tabs are saved as soon as they change.
    const MANUAL_TABS = array( 'spam' );
    const TAB_FIELD = 'sjs_settings_tab';

    // The tab being saved, set while options.php saves this page's form.
    private static $saving_tab = '';

    function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_action( "admin_init", array( $this, 'init' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
        // After core's option_update_filter (priority 10), which adds the options of register_setting() to the list.
        add_filter( 'allowed_options', array( __CLASS__, 'allowed_options' ), 100 );
	}

	// options.php saves every option of the group and empties the ones the form did not post, so on a tab only the
	// options of that tab are saved: this plugin's option on General and Spam Protection, the others (the Add-on's)
	// on the Add-on tab. It only narrows the list: options.php checks the nonce of the form before it saves anything.
	public static function allowed_options( $allowed ) {
		$tab = sanitize_key( wp_unslash( $_POST[ self::TAB_FIELD ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- only narrows what options.php saves after its own nonce check.
		$page = sanitize_text_field( wp_unslash( $_POST['option_page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $tab || self::OPTION_GROUP !== $page || empty( $allowed[ self::OPTION_GROUP ] ) ) {
			return $allowed;
		}
		self::$saving_tab = $tab;
		$allowed[ self::OPTION_GROUP ] = array_values( array_filter( (array) $allowed[ self::OPTION_GROUP ], function ( $option ) use ( $tab ) {
			return self::ADDON_TAB === $tab ? self::OPTION !== $option : self::OPTION === $option;
		} ) );
		return $allowed;
	}

	// Tab slug => label of the tabs with settings fields; the Add-on tab only when another plugin added a section.
	private static function field_tabs() {
		global $wp_settings_sections;
		$tabs = array(
			'general' => __( 'General', 'surveyjs' ),
			'spam'    => __( 'Spam Protection', 'surveyjs' ),
		);
		foreach ( array_keys( (array) ( $wp_settings_sections[ self::PAGE ] ?? array() ) ) as $id ) {
			if ( ! isset( self::TAB_SECTIONS[ $id ] ) ) {
				$tabs[ self::ADDON_TAB ] = __( 'Add-on', 'surveyjs' );
			}
		}
		return $tabs;
	}

	private static function section_tab( $id ) {
		return self::TAB_SECTIONS[ $id ] ?? self::ADDON_TAB;
	}

	public static function enqueue_style() {
		if ( 'surveyjs-settings' === sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the page.
			wp_enqueue_style( 'wps-adm--settings.css', plugins_url( 'settings.css', __FILE__ ), array( 'common', 'forms' ), (string) filemtime( __DIR__ . '/settings.css' ) );
			wp_enqueue_script( 'wps-adm--settings.js', plugins_url( 'settings.js', __FILE__ ), array(), (string) filemtime( __DIR__ . '/settings.js' ), true );
			wp_add_inline_script( 'wps-adm--settings.js', 'window.surveyjsSettingsL10n = ' . wp_json_encode( array(
				'saved'  => __( 'Settings saved.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- core's own string, translated with WordPress.
				'failed' => __( 'The settings were not saved. Reload the page and try again.', 'surveyjs' ),
				'save'   => __( 'Save', 'surveyjs' ),
			) ) . ';', 'before' );
		}
	}

	// do_settings_sections(), with each section's settings in a card of its own (this plugin's sections and the
	// Add-on's); the section title and text stay on the page background, above the card.
	private static function render_sections( $page, $tab ) {
		global $wp_settings_sections, $wp_settings_fields;
		foreach ( (array) ( $wp_settings_sections[ $page ] ?? array() ) as $section ) {
			if ( self::section_tab( $section['id'] ) !== $tab ) {
				continue;
			}
			echo '<div class="wp-sjs-settings__section" id="wp-sjs-settings-' . esc_attr( $section['id'] ) . '">';
			if ( $section['title'] ) {
				// settings.js puts the Save button of a manual tab next to the title.
				echo '<div class="wp-sjs-settings__head"><h2>' . $section['title'] . '</h2></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when the section is added, as in do_settings_sections().
			}
			if ( $section['callback'] ) {
				ob_start();
				call_user_func( $section['callback'], $section );
				$intro = trim( (string) ob_get_clean() );
				if ( '' !== $intro ) {
					echo '<div class="wp-sjs-settings__intro">' . $intro . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the section callback escapes its own output, as in do_settings_sections().
				}
			}
			if ( ! empty( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				echo '<div class="card wp-sjs-settings__card"><table class="form-table" role="presentation">';
				do_settings_fields( $page, $section['id'] );
				echo '</table></div>';
			}
			echo '</div>';
		}
	}

	public static function option( $key, $default = false ) {
		$settings = (array) get_option( 'surveyjs-settings' );
		return array_key_exists( $key, $settings ) ? ! empty( $settings[ $key ] ) : $default;
	}

	public static function retention_days() {
		$settings = (array) get_option( self::OPTION );
		return absint( $settings['entry_retention_days'] ?? 0 );
	}

    public function init() {
		register_setting( 'surveyjs-settings-group', 'surveyjs-settings', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );

		add_settings_section( 'sjs-data-section', esc_html__( 'Entries and Data', 'surveyjs' ), '__return_false', 'surveyjs-settings-page' );
		add_settings_field( 'store_ip', esc_html__( 'IP addresses', 'surveyjs' ), array( $this, 'render_checkbox' ), 'surveyjs-settings-page', 'sjs-data-section', array(
			'key'         => 'store_ip',
			'label'       => __( 'Store the IP address of the person who submits a form', 'surveyjs' ),
			'description' => __( 'An IP address is personal data. Leave this off unless you need it and your privacy policy mentions it.', 'surveyjs' ),
		) );
		add_settings_field( SurveyJS_Form_Stats::SETTING, esc_html__( 'Form views', 'surveyjs' ), array( $this, 'render_checkbox' ), 'surveyjs-settings-page', 'sjs-data-section', array(
			'key'         => SurveyJS_Form_Stats::SETTING,
			'default'     => true,
			'label'       => __( 'Count form views to show conversion rates', 'surveyjs' ),
			'description' => __( 'Counts how many times visitors see each form and how many of them submit it. Only a daily number per form is stored: no cookies, no IP addresses or other personal data. Views and submissions by logged-in users who can edit posts are not counted.', 'surveyjs' ),
		) );
		add_settings_field( 'entry_retention_days', esc_html__( 'Delete old entries', 'surveyjs' ), array( $this, 'retention_render' ), 'surveyjs-settings-page', 'sjs-data-section', array( 'label_for' => 'sjs-entry-retention-days' ) );
		add_settings_field( 'uninstall_data', esc_html__( 'Uninstall', 'surveyjs' ), array( $this, 'render_checkbox' ), 'surveyjs-settings-page', 'sjs-data-section', array(
			'key'         => 'uninstall_data',
			'label'       => __( 'Delete all forms, entries, uploaded files and settings when the plugin is deleted', 'surveyjs' ),
			'description' => __( 'Leave this off to keep your data if you reinstall the plugin later.', 'surveyjs' ),
		) );
		add_settings_section( 'sjs-captcha-section', esc_html__( 'CAPTCHA', 'surveyjs' ), array( $this, 'captcha_section' ), 'surveyjs-settings-page' );
		add_settings_field( 'captcha_type', esc_html__( 'Type', 'surveyjs' ), array( $this, 'captcha_type_render' ), 'surveyjs-settings-page', 'sjs-captcha-section', array( 'label_for' => 'sjs-captcha-type' ) );
		add_settings_field( 'captcha_site_key', esc_html__( 'Site Key', 'surveyjs' ), array( $this, 'captcha_site_key_render' ), 'surveyjs-settings-page', 'sjs-captcha-section', array( 'label_for' => 'sjs-captcha-site-key' ) );
		add_settings_field( 'captcha_secret_key', esc_html__( 'Secret Key', 'surveyjs' ), array( $this, 'captcha_secret_key_render' ), 'surveyjs-settings-page', 'sjs-captcha-section', array( 'label_for' => 'sjs-captcha-secret-key' ) );
		add_settings_field( 'captcha_threshold', esc_html__( 'Score Threshold', 'surveyjs' ), array( $this, 'captcha_threshold_render' ), 'surveyjs-settings-page', 'sjs-captcha-section', array( 'label_for' => 'sjs-captcha-threshold' ) );

		do_action( 'surveyjs_register_settings', self::PAGE, self::OPTION_GROUP );
    }

	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$current = (array) get_option( 'surveyjs-settings' );
		$sanitized = array();
		if ( isset( $current['license_key'] ) ) {
			$sanitized['license_key'] = sanitize_text_field( $current['license_key'] );
		}
		$sanitized['store_ip'] = ! empty( $input['store_ip'] );
		$sanitized[ SurveyJS_Form_Stats::SETTING ] = ! empty( $input[ SurveyJS_Form_Stats::SETTING ] );
		$sanitized['entry_retention_days'] = absint( $input['entry_retention_days'] ?? ( $current['entry_retention_days'] ?? 0 ) );
		$sanitized['uninstall_data'] = ! empty( $input['uninstall_data'] );
		$type = sanitize_key( $input['captcha_type'] ?? ( $current['captcha_type'] ?? 'none' ) );
		$sanitized['captcha_type'] = array_key_exists( $type, SurveyJS_Captcha::types() ) ? $type : 'none';
		$sanitized['captcha_site_key'] = sanitize_text_field( $input['captcha_site_key'] ?? ( $current['captcha_site_key'] ?? '' ) );
		$secret = sanitize_text_field( $input['captcha_secret_key'] ?? '' );
		$sanitized['captcha_secret_key'] = '' !== $secret ? $secret : sanitize_text_field( $current['captcha_secret_key'] ?? '' );
		$sanitized['captcha_threshold'] = SurveyJS_Captcha::sanitize_threshold( $input['captcha_threshold'] ?? ( $current['captcha_threshold'] ?? SurveyJS_Captcha::DEFAULT_THRESHOLD ) );
		// Saved from one tab: the keys of the other tabs keep their saved values (or stay unset, so their defaults
		// apply), as their unchecked checkboxes are simply not in this form.
		if ( isset( self::TAB_KEYS[ self::$saving_tab ] ) ) {
			foreach ( self::TAB_KEYS as $tab => $keys ) {
				if ( $tab === self::$saving_tab ) {
					continue;
				}
				foreach ( $keys as $key ) {
					if ( array_key_exists( $key, $current ) ) {
						$sanitized[ $key ] = $current[ $key ];
					} else {
						unset( $sanitized[ $key ] );
					}
				}
			}
		}
		return $sanitized;
	}

	public function captcha_section() {
		echo '<p>' . esc_html__( 'Protect forms from spam bots. Choose a service, paste the keys from its admin console, then turn on CAPTCHA for a form on its Spam Protection tab.', 'surveyjs' ) . '</p>';
		echo '<p>' . wp_kses(
			sprintf(
				/* translators: 1: Google reCAPTCHA admin console URL, 2: Cloudflare Turnstile dashboard URL, 3: hCaptcha dashboard URL */
				__( 'Get the keys in the <a href="%1$s" target="_blank" rel="noopener noreferrer">Google reCAPTCHA admin console</a>, the <a href="%2$s" target="_blank" rel="noopener noreferrer">Cloudflare Turnstile dashboard</a> or the <a href="%3$s" target="_blank" rel="noopener noreferrer">hCaptcha dashboard</a>. reCAPTCHA v2 and v3 use different keys.', 'surveyjs' ),
				'https://www.google.com/recaptcha/admin',
				'https://dash.cloudflare.com/?to=/:account/turnstile', // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- link to the Cloudflare dashboard, not an asset.
				'https://dashboard.hcaptcha.com/sites'
			),
			array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
		) . '</p>';
	}

	public function captcha_type_render() {
		$type = SurveyJS_Captcha::settings()['type'];
		echo "<select name='surveyjs-settings[captcha_type]' id='sjs-captcha-type'>";
		foreach ( SurveyJS_Captcha::types() as $value => $label ) {
			echo "<option value='" . esc_attr( $value ) . "'" . selected( $type, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public function captcha_site_key_render() {
		echo "<input type='text' name='surveyjs-settings[captcha_site_key]' id='sjs-captcha-site-key' value='" . esc_attr( SurveyJS_Captcha::settings()['siteKey'] ) . "' autocomplete='off'/>";
	}

	public function captcha_secret_key_render() {
		$secret = SurveyJS_Captcha::settings()['secretKey'];
		$placeholder = '' !== $secret ? str_repeat( '•', 8 ) . substr( $secret, -4 ) : '';
		echo "<input type='password' name='surveyjs-settings[captcha_secret_key]' id='sjs-captcha-secret-key' value='' placeholder='" . esc_attr( $placeholder ) . "' autocomplete='new-password'/>";
		if ( '' !== $secret ) {
			echo '<p class="description">' . esc_html__( 'The secret key is saved. Leave the field empty to keep it, or paste a new key to replace it.', 'surveyjs' ) . '</p>';
		}
	}

	public function captcha_threshold_render() {
		$settings = SurveyJS_Captcha::settings();
		echo "<input type='number' name='surveyjs-settings[captcha_threshold]' id='sjs-captcha-threshold' value='" . esc_attr( $settings['threshold'] ) . "' min='0' max='1' step='0.1'" . disabled( 'recaptcha_v3' !== $settings['type'], true, false ) . '/>';
		echo '<p class="description">' . esc_html__( 'reCAPTCHA v3 only. Each submission gets a score from 0.0 (bot) to 1.0 (human); submissions below this score are rejected. Default: 0.5.', 'surveyjs' ) . '</p>';
	}

	public function retention_render() {
		$days = self::retention_days();
		?>
		<input type="number" name="surveyjs-settings[entry_retention_days]" id="sjs-entry-retention-days" value="<?php echo esc_attr( $days ? $days : '' ); ?>" min="1" step="1" aria-describedby="sjs-entry-retention-days-description" />
		<?php esc_html_e( 'days', 'surveyjs' ); ?>
		<p class="description" id="sjs-entry-retention-days-description"><?php esc_html_e( 'Entries older than this are deleted automatically once a day, with their uploaded files, including partial and spam entries. Deleted entries cannot be restored. Leave empty to keep entries forever. Each form can use its own period on its Entries tab.', 'surveyjs' ); ?></p>
		<?php
	}

	public function render_checkbox( $args ) {
		$name = 'surveyjs-settings[' . $args['key'] . ']';
		?>
		<label for="<?php echo esc_attr( $name ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( self::option( $args['key'], $args['default'] ?? false ) ); ?> />
			<?php echo esc_html( $args['label'] ); ?>
		</label>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}



	public static function surveyjs_render_settings() {
		$field_tabs = self::field_tabs();
		$tabs = $field_tabs + array(
			SurveyJS_Connections::TAB => __( 'Integrations', 'surveyjs' ),
			SurveyJS_AI::TAB      => __( 'AI MCP', 'surveyjs' ),
			SurveyJS_AI_Chat::TAB => __( 'AI Client', 'surveyjs' ),
		);
		$tab = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the tab to show.
		$tab = isset( $tabs[ $tab ] ) ? $tab : 'general';
		?>
		<div class="wrap">
            <?php // Notices from WordPress and other plugins go here, above the title, on every plugin page. ?>
            <hr class="wp-header-end">
            <h1><?php esc_html_e( 'Settings', 'surveyjs' ); ?></h1>
            <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Settings sections', 'surveyjs' ); ?>">
                <?php foreach ( $tabs as $slug => $label ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'surveyjs-settings', 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab<?php echo $slug === $tab ? ' nav-tab-active' : ''; ?>"<?php echo $slug === $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>
            <?php if ( SurveyJS_AI::TAB === $tab ) : ?>
                <?php SurveyJS_AI::render_tab(); ?>
            <?php elseif ( SurveyJS_Connections::TAB === $tab ) : ?>
                <?php SurveyJS_Connections::render_tab(); ?>
            <?php elseif ( SurveyJS_AI_Chat::TAB === $tab ) : ?>
                <?php SurveyJS_AI_Chat::render_tab(); ?>
            <?php else : ?>
                <?php if ( ! empty( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
                    <div class="notice notice-success is-dismissible" data-sjs-snackbar><p><?php esc_html_e( 'Successfully updated!', 'surveyjs' ); ?></p></div>
                <?php endif; ?>
                <form action="options.php" method="POST" id="wp-sjs-settings-form" data-sjs-save="<?php echo esc_attr( in_array( $tab, self::MANUAL_TABS, true ) ? 'manual' : 'auto' ); ?>">
                    <?php settings_fields( 'surveyjs-settings-group' ); ?>
                    <input type="hidden" name="<?php echo esc_attr( self::TAB_FIELD ); ?>" value="<?php echo esc_attr( $tab ); ?>" />
                    <?php self::render_sections( 'surveyjs-settings-page', $tab ); ?>
                    <?php submit_button(); ?>
                </form>
            <?php endif; ?>
        </div>
		<?php
	}
}

$sjsSettingsPage = new SurveyJS_SettingsPage();
?>
