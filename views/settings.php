<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_SettingsPage {

    function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_action( "admin_init", array( $this, 'init' ) );
	}

	public static function get_theme() {
		$settings = (array) get_option( 'sjs-settings' );
		if ( isset( $settings['theme'] ) ) {
			return esc_js( $settings['theme'] );
		}
		return 'default';
	}

	public static function get_allow_paddings() {
		$settings = (array) get_option( 'sjs-settings' );
		if ( isset( $settings['allow_paddings'] ) ) {
			return esc_js( $settings['allow_paddings'] );
		}
		return 0;
	}

    public function init() {
		register_setting( 'sjs-settings-group', 'sjs-settings' );
			
		add_settings_section( 'sjs-themes-section', __( 'Tnemes', 'sjs' ), array($this, 'sjs_themes_section'), 'sjs-settings-page' );
		add_settings_field( 'theme', __( 'Current Theme', 'sjs' ), array($this, 'theme_render'), 'sjs-settings-page', 'sjs-themes-section' );
		add_settings_field( 'allow-paddings', __( 'Allow Paddings', 'sjs' ), array($this, 'allow_paddings_render'), 'sjs-settings-page', 'sjs-themes-section' );
    }

	public function sjs_themes_section() {
		_e( 'SurveyJS themes configurations', 'sjs' );
		if ( isset( $_GET["settings-updated"] ) && $_GET["settings-updated"] ) {
			flush_rewrite_rules( true );
			echo "<div style='color: #179d82;'>" . __( 'Successfully updated!', 'sjs' ) . "</div>";
		}
	}

	public function theme_render() {
		$settings = (array) get_option( 'sjs-settings' );
		$themes = array('default', 'orange', 'darkblue', 'darkrose', 'stone', 'winter', 'winterstone');

		if (isset($settings['theme']))
		{
			if ($settings['theme'] == '') { $settings['theme'] = 'default'; }
			$current_theme = esc_attr( $settings['theme'] );
		}

		?>
			<select name='sjs-settings[theme]' id='sjs-settings[theme]'>
				<?php
					foreach ($themes as $theme) {
						?>
							<option <?php if ($current_theme == $theme) echo 'selected' ?> >
								<?php echo $theme ?>
							</option>
						<?php
					}
				?>
			</select>
		<?php
	}

	public function allow_paddings_render() {
		$settings = (array) get_option( 'sjs-settings' );
		$allow_paddings = '0';
		if (isset($settings['allow_paddings']))
		{
			$allow_paddings = esc_attr( $settings['allow_paddings'] );
		}
		$checked = '';
		if ($allow_paddings == '1')
		{
			$checked = " checked='checked'";
		}
		echo "<input type='checkbox' name='sjs-settings[allow_paddings]' id='sjs-settings[allow_paddings]' value='1'$checked />";
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

$sjsSettingsPage = new WP_SJS_SettingsPage();
?>
