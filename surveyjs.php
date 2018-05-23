<?php
/*
Plugin Name: SurveyJS
Plugin URI: https://wordpress.org/plugins/surveyjs
Description: Easy to use, drag & drop Survey Builder with myriad options.
Version: 0.5
Author: Devsoft Baltic OÜ
Author URI: http://devsoftbaltic.com/
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
    function wps_install() {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        createMySurveysTable();
        createResultsTable();
    }

    function createMySurveysTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sjs_my_surveys';

        var_dump( $table_name );

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            json text,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        dbDelta( $sql );
    }

    function createResultsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sjs_results';

        var_dump( $table_name );

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            surveyId mediumint(9) NOT NULL,
            json text,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        dbDelta( $sql );
    }

    function wps_uninstall() {

    }
    register_activation_hook( __FILE__, 'wps_install');
    register_deactivation_hook( __FILE__, 'wps_uninstall');

    include("ajax_handlers/insert_survey.php");
    new WP_InsertSurveyHandler();
    include("ajax_handlers/save_survey.php");
    new WP_SaveSurvey();
    include("ajax_handlers/rename_survey.php");
    new WP_RenameSurvey();
    include("ajax_handlers/add_survey.php");
    new WP_AddSurvey();
    include("ajax_handlers/delete_survey.php");
    new WP_DeleteSurvey();
    include("ajax_handlers/get_survey_json.php");
    new WP_GetSurveyJson();
    include("ajax_handlers/save_result.php");
    new WP_SaveResult();

    include("initializer.php");
    new WP_SurveyJS();
?>
