<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_SettingsPage {

    function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_action( "admin_init", array( $this, 'init' ) );
	}

	public static function get_license_key() {
		$settings = (array) get_option( 'surveyjs-settings' );
		if ( isset( $settings['license_key'] ) ) {
			return $settings['license_key'];
		}
		return "";
	}

    public function init() {
		register_setting( 'surveyjs-settings-group', 'surveyjs-settings', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );

		add_settings_section( 'sjs-license-key-section', esc_html__( 'Set License Key', 'surveyjs' ), array($this, 'sjs_license_key_section'), 'surveyjs-settings-page' );
		add_settings_field( 'license_key', esc_html__( 'License Key', 'surveyjs' ), array($this, 'set_license_key_render'), 'surveyjs-settings-page', 'sjs-license-key-section' );
    }

	public function sanitize_settings( $input ) {
		$sanitized = array();
		if ( isset( $input['license_key'] ) ) {
			$sanitized['license_key'] = sanitize_text_field( $input['license_key'] );
		}
		return $sanitized;
	}

	public function sjs_license_key_section() {
		esc_html_e( 'setting your license key', 'surveyjs' );
		if ( isset( $_GET["settings-updated"] ) && sanitize_text_field($_GET["settings-updated"]) ) {
			flush_rewrite_rules( true );
			echo "<div style='color: #179d82;'>" . esc_html__( 'Successfully updated!', 'surveyjs' ) . "</div>";
		}
	}

	public function set_license_key_render() {
		$settings = (array) get_option( 'surveyjs-settings' );

		if (isset($settings['license_key']))
		{
			$license_key = esc_attr( $settings['license_key'] );
		}
		$value = '';
		if ($license_key )
		{
			echo "<input type='text' placeholder='put the license key here...' name='surveyjs-settings[license_key]' id='surveyjs-settings[license_key]' value='" . esc_attr( $license_key ) . "' style='width: 350px;'/>";
		} else {
			echo "<input type='text' placeholder='put the license key here...' name='surveyjs-settings[license_key]' id='surveyjs-settings[license_key]' style='width: 350px;'/>";
		}
	}

	public static function surveyjs_render_settings() {
		?>
		<div class="wrap">
            <h2><?php esc_html_e( 'SurveyJS Settings', 'surveyjs' ); ?></h2>
            <form action="options.php" method="POST">
                <?php settings_fields( 'surveyjs-settings-group' ); ?>
                <?php do_settings_sections( 'surveyjs-settings-page' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
		<?php
	}
}

$sjsSettingsPage = new SurveyJS_SettingsPage();
?>
