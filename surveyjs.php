<?php
/*
Plugin Name: SurveyJS
Plugin URI: https://surveyjs.io/wordpress  TODO need home page for the plugin
Description: JavaScript Survey Engine
Version: 1.0.3
Author: Devsoft Baltic OÜ
Author URI: http://devsoftbaltic.com/
License: http://editor.surveyjs.io/license.html TODO
*/

/* TODO NEED TO ADD LICENCE TEXT*/ 

/*  Copyright year  autor  (email: _email)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include( "pages/mysurveys.php" );

add_action("admin_menu", "setup_menu");
function setup_menu() {
    add_menu_page( 'My Surveys', 'SurveyJS', 'manage_options', 'my-surveys', 'init' );
}

add_action("media_buttons", "surveyjs_media_button");
function surveyjs_media_button($context) {
    echo '<a href="#" id="insert-survey" class="button">Add survey</a>';
}

add_action( 'wp_enqueue_media', 'include_surveyjs_media_button_js_file' );
function include_surveyjs_media_button_js_file() {
	wp_enqueue_script( 'media_button', plugin_dir_url( __FILE__ ) . 'assets/insert-survey-dialog.js', array('jquery'), '1.0', true );
}

function init(){
    generateMySurveysPage();
}
?>
