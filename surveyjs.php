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
?>
<?php
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
?>
<?php
    include("ajax_handlers/insert_survey.php");
    new WP_InsertSurveyHandler();
    include("ajax_handlers/get_access_key.php");
    new WP_GetAccessKeyHandler();
    include("ajax_handlers/post_token.php");
    new WP_PostToken();

    include("initializer.php");
    new WP_SurveyJS();
?>
