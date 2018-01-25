<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_SettingsPage {

    function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_action( "admin_init", array( $this, 'init' ) );
    }

    public function init() {
        register_setting( 'sjs-settings-group', 'sjs-settings' );
        add_settings_section( 'sjs-global-section', __( 'Connection Settings', 'sjs' ), array($this, 'global_section'), 'sjs-settings-section' );
        add_settings_field( 'access-key', __( 'Access Key', 'sjs' ), array($this, 'access_key'), 'sjs-settings-section', 'sjs-global-section' );
    }

	public function global_section() {
		_e( 'All settings are applied to the SurveyJS plugin and all surveys.', 'sjs' );
		if ( isset( $_GET["settings-updated"] ) && $_GET["settings-updated"] ) {
			flush_rewrite_rules( true );
			echo "<div style='color: #179d82;'>" . __( 'Settings have been updated!', 'sjs' ) . "</div>";
		}
	}

	public function access_key() {
		$settings = (array) get_option( 'sjs-settings' );
		$access_key = '';
		if (isset($settings['access_key']))
		{
			$access_key = esc_attr( $settings['access_key'] );
		}
		echo "<input type='text' name='sjs-settings[access_key]' id='sjs-settings[access_key]' value='$access_key' />";
	}

	public static function sjs_render_settings() {
		?>
		<div class="wrap">
            <h2><?php _e( 'SurveyJS Settings', 'sjs' ); ?></h2>
            <form action="options.php" method="POST">
                <?php settings_fields( 'sjs-settings-group' ); ?>
                <?php do_settings_sections( 'sjs-settings-section' ); ?>
                <?php submit_button(); ?>
            </form>
        </div>
		<?php
	}
}

$sjsSettingsPage = new WP_SJS_SettingsPage();
?>
