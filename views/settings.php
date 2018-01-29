<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_SettingsPage {

    function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_action( "admin_init", array( $this, 'init' ) );
	}
	
	public static function get_access_key() {
		$settings = (array) get_option( 'sjs-settings' );
		if ( isset( $settings['access_key'] ) ) {
			return esc_js( $settings['access_key'] );
		}
		return '';
	}
	public static function set_access_key($access_key) {
		$settings = (array) get_option( 'sjs-settings' );
		$settings['access_key'] = $access_key;
		update_option( 'sjs-settings', $settings );
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
		
        add_settings_section( 'sjs-connection-section', __( 'Connection', 'sjs' ), array($this, 'sjs_connection_section'), 'sjs-settings-page' );
		add_settings_field( 'access-key', __( 'Access Key', 'sjs' ), array($this, 'access_key_render'), 'sjs-settings-page', 'sjs-connection-section' );
		
		add_settings_section( 'sjs-themes-section', __( 'Tnemes', 'sjs' ), array($this, 'sjs_themes_section'), 'sjs-settings-page' );
		add_settings_field( 'theme', __( 'Current Theme', 'sjs' ), array($this, 'theme_render'), 'sjs-settings-page', 'sjs-themes-section' );
		add_settings_field( 'allow-paddings', __( 'Allow Paddings', 'sjs' ), array($this, 'allow_paddings_render'), 'sjs-settings-page', 'sjs-themes-section' );
    }

	public function sjs_connection_section() {
		_e( 'Connection settings for your surveyjs.io account', 'sjs' );
		if ( isset( $_GET["settings-updated"] ) && $_GET["settings-updated"] ) {
			flush_rewrite_rules( true );
			echo "<div style='color: #179d82;'>" . __( 'Successfully updated!', 'sjs' ) . "</div>";
		}
	}

	public function sjs_themes_section() {
		_e( 'SurveyJS themes configurations', 'sjs' );
		if ( isset( $_GET["settings-updated"] ) && $_GET["settings-updated"] ) {
			flush_rewrite_rules( true );
			echo "<div style='color: #179d82;'>" . __( 'Successfully updated!', 'sjs' ) . "</div>";
		}
	}

	public function access_key_render() {
		$settings = (array) get_option( 'sjs-settings' );
		$access_key = '';
		if (isset($settings['access_key']))
		{
			$access_key = esc_attr( $settings['access_key'] );
		}
		$url = add_query_arg(array('action' => 'WP_SJS_GetAccessKey', 'TB_iframe' => 'true'), admin_url('admin-ajax.php'));
        ?>
			<input type='text' name='sjs-settings[access_key]' id='sjs-settings[access_key]' value='<?php echo $access_key; ?>' />
			<a onclick="tb_click.call(this); return false;" href="<?php echo $url; ?>" class="button" title="<?php _e('Get Access Key', WP_SurveyJS::$prefix); ?>">
				<?php _e('Get Access Key', WP_SurveyJS::$prefix); ?>
			</a>
        <?php
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
