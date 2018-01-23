<?php
class wp_surveyjs_widget extends WP_Widget {

	// constructor
	function wp_surveyjs_widget() {
		parent::WP_Widget(false, $name = __('SurveyJS', 'wp_surveyjs_widget') );
        add_action('wp_enqueue_scripts', array(&$this, 'js'));
	}
    function js() {
        if (is_active_widget(false, false, $this->id_base, true)) {
			wp_register_style( 'survey.css', plugin_dir_url( __FILE__ ) . '_inc/survey.css', array(), null);
			wp_enqueue_style( 'survey.js');

			wp_register_script( 'knockout.js', plugin_dir_url( __FILE__ ) . '_inc/knockout.js', array(), null);
			wp_enqueue_script( 'knockout.js' );
			wp_register_script( 'survey.js', plugin_dir_url( __FILE__ ) . '_inc/survey.js', array(), null);
			wp_enqueue_script( 'survey.js' );
			wp_register_script( 'wp_survey.js', plugin_dir_url( __FILE__ ) . '_inc/wp_survey.js', array(), null);
			wp_enqueue_script( 'wp_survey.js' );
			wp_localize_script( 'wp_survey.js', 'wp_surveyajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        }           

      }

	// widget form creation
	function form($instance) {	
		// Check values
		if( $instance) {
			$surveyId = esc_attr($instance['surveyId']);
			$postId = esc_attr($instance['postId']);
			$resultId = esc_attr($instance['resultId']);
			$accessKey = esc_attr($instance['accessKey']);
		} else {
			$surveyId = '';
			$postId = '';
			$resultId = '';
			$accessKey = '';
		}
		$dataId = 'data-'.$this->get_field_id("widget_id").'';
		$windowDataVar = 'window["'.$dataId.'"]';
		$globalAccessKey = get_option("surveyjs_accessKey", "");
		if($globalAccessKey != "") {
			$accessKey = $globalAccessKey; 
		}
		?>
		<span id="<?php echo $this->get_field_id('noaccesskey'); ?>" style="display:none"><p>The access key is empty or incorrect. Please get it from <a href="https://www.dxsurvey.com">dxsurvey web site</a>.</p></span>
		<span id="<?php echo $this->get_field_id('nosurvey'); ?>" style="display:none"><p>There is no survey in your dxsurvey account. <a href="https://www.dxsurvey.com">Please create one</a>.</p></span>
		<label for="<?php echo $this->get_field_id('accessKey'); ?>"><?php _e('Access key:', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('accessKey'); ?>" name="<?php echo $this->get_field_name('accessKey'); ?>" type="text" value="<?php echo $accessKey; ?>" 
			onchange='surveyjs_loadSurveyList(this.value, <?php echo $windowDataVar; ?>)' />

		<label><?php _e('Select Survey:', 'wp_widget_plugin'); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('surveyjs_list'); ?>"  
			onchange='surveyjs_setSurveyIds(this.value, <?php echo $windowDataVar; ?>)'></select>

		<input id="<?php echo $this->get_field_id('surveyId'); ?>" name="<?php echo $this->get_field_name('surveyId'); ?> disabled " type="hidden" value="<?php echo $surveyId; ?>" />
		<input id="<?php echo $this->get_field_id('postId'); ?>" name="<?php echo $this->get_field_name('postId'); ?>" type="hidden" value="<?php echo $postId; ?>" />
		<input id="<?php echo $this->get_field_id('resultId'); ?>" name="<?php echo $this->get_field_name('resultId'); ?>" type="hidden" value="<?php echo $resultId; ?>" />
		 </p>
	     <?php
			echo '<script type="text/javascript">';
				echo ''.$windowDataVar.'={};';
				echo ''.$windowDataVar.'.list="'.$this->get_field_id("surveyjs_list").'";';
				echo ''.$windowDataVar.'.surveyId="'.$this->get_field_id("surveyId").'";';
				echo ''.$windowDataVar.'.postId="'.$this->get_field_id("postId").'";';
				echo ''.$windowDataVar.'.resultId="'.$this->get_field_id("resultId").'";';
				echo ''.$windowDataVar.'.errorAccessKey="'.$this->get_field_id("noaccesskey").'";';
				echo ''.$windowDataVar.'.errorNoSurvey="'.$this->get_field_id("nosurvey").'";';
				echo 'surveyjs_loadSurveyList("'.$accessKey.'", '.$windowDataVar.');';
			echo '</script>';
	}

	// widget update
	function update($new_instance, $old_instance) {
      $instance = $old_instance;
      // Fields
      $instance['surveyId'] = strip_tags($new_instance['surveyId']);
	  $instance['postId'] = strip_tags($new_instance['postId']);
	  $instance['resultId'] = strip_tags($new_instance['resultId']);
	  $accessKey = strip_tags($new_instance['accessKey']);
	  if(isset($accessKey)) {
		  update_option("surveyjs_accessKey", $accessKey);
	  }
     return $instance;
	}

	// widget display
	function widget($args, $instance) {
        extract( $args );
        // these are the widget options
        $surveyId = $instance['surveyId'];
        echo $before_widget;
        // Display the widget
		$id = explode("-", $this->get_field_id("widget_id"));
        echo '<div id="surveycontent'.$id[2].'"></div>';

        if ( $surveyId ) {
        echo '<script type="text/javascript">';
			echo 'surveyjs_LoadSurvey('.$id[2].', "'.$surveyId.'", "surveycontent'.$id[2].'");';
        echo '</script>';
        }
        echo $after_widget;	
	}
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_surveyjs_widget");'));