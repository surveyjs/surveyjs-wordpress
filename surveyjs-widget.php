<?php
/*
Plugin Name: surveyjs-widget
Plugin URI: http:/surveyjs.org
Description: json based survey.
Version: 0.9.12
Author: Andrew Telnov
Author URI: http:/surveyjs.org
License: MIT
*/

define('PLUGIN_WIDGET_DIR', plugin_dir_path( __FILE__ ) );
require_once(PLUGIN_WIDGET_DIR . 'widget.php' );
require_once(PLUGIN_WIDGET_DIR . 'surveyjs-ajax.php' );