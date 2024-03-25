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
		$settings = (array) get_option( 'sjs-settings' );
		if ( isset( $settings['license_key'] ) ) {
			return esc_js( $settings['license_key'] );
		}
		return "";
	}

    public function init() {
		register_setting( 'sjs-settings-group', 'sjs-settings' );

		add_settings_section( 'sjs-license-key-section', __( 'Set License Key', 'sjs' ), array($this, 'sjs_license_key_section'), 'sjs-settings-page' );
		add_settings_field( 'license_key', __( 'License Key', 'sjs' ), array($this, 'set_license_key_render'), 'sjs-settings-page', 'sjs-license-key-section' );
    }

	public function sjs_license_key_section() {
		_e( 'setting your license key', 'sjs' );
		if ( isset( $_GET["settings-updated"] ) && sanitize_text_field($_GET["settings-updated"]) ) {
			flush_rewrite_rules( true );
			echo "<div style='color: #179d82;'>" . __( 'Successfully updated!', 'sjs' ) . "</div>";
		}
	}

	public function set_license_key_render() {
		$settings = (array) get_option( 'sjs-settings' );

		if (isset($settings['license_key']))
		{
			$license_key = esc_attr( $settings['license_key'] );
		}
		$value = '';
		if ($license_key )
		{
			echo "<input type='text' placeholder='put the license key here...' name='sjs-settings[license_key]' id='sjs-settings[license_key]' value='$license_key' style='width: 350px;'/>";
		} else {
			echo "<input type='text' placeholder='put the license key here...' name='sjs-settings[license_key]' id='sjs-settings[license_key]' style='width: 350px;'/>";
		}
	}

	public static function sjs_render_settings() {
		?>
		<div class="wrap">
            <h2><?php _e( 'SurveyJS Settings', 'sjs' ); ?></h2>
            <form action="options.php" method="POST">
                <?php settings_fields( 'sjs-settings-group' ); ?>
                <?php do_settings_sections( 'sjs-settings-page' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
		<?php
	}
}

$sjsSettingsPage = new SurveyJS_SettingsPage();
?>
