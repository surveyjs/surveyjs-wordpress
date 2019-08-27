<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_MySurveys {

    function __construct() {
    }

    public static function render() {
        $client = new SurveyJS_Client();
        $editSurveyUri = add_query_arg(array('page' => 'surveyjs_editor'), admin_url('admin.php'));
        $addSurveyUri = add_query_arg(array('action' => 'SurveyJS_AddSurvey'), admin_url('admin-ajax.php'));
        $duplicateSurveyUri = add_query_arg(array('action' => 'SurveyJS_DuplicateSurvey'), admin_url('admin-ajax.php'));
        $deleteSurveyUri = add_query_arg(array('action' => 'SurveyJS_DeleteSurvey'), admin_url('admin-ajax.php'));
        ?>
            <script>
                function addNewSurvey() {
                    jQuery.ajax({
                        url:  "<?php echo esc_url($addSurveyUri) ?>",
                        type: "POST",
                        data: { Name: "New Survey" },
                        success: function (data) {
                            window.location = "<?php echo esc_url($editSurveyUri) ?>&id=" + data.Id + "&name=New Survey";
                        }
                    });
                }
                function duplicateSurvey(id){
                    jQuery.ajax({
                        url: "<?php echo esc_url($duplicateSurveyUri) ?>",
                        type: "POST",
                        data: {
                            Id: id
                            // Name: // Get Name of Survey that was clicked
                            // JSON: // Get JSON of Survey that was clicked
                        },
                        success: function(data){
                           window.location = "";
                        }
                    })
                }

                function deleteSurvey(id) {
                    var res = confirm("Are you sure?");
                    if (!res) return;
                    jQuery.ajax({
                        url:  "<?php echo esc_url($deleteSurveyUri)  ?>",
                        type: "POST",
                        data: { Id: id },
                        success: function (data) {
                            window.location = "";
                        }
                    });
                }
            </script>
            <div class="wp-sjs-plugin">
                <div class="sv_main sv_frame sv_default_css">
                    <div class="sv_custom_header"></div>
                    <div class="sv_container">
                        <div class="sv_header">
                            <h3><?php _e( 'SurveyJS Wordpress plugin', 'sjs' ); ?></h2></h3>
                            <p>Below you can see the list of available surveys you can edit, run and see the results</p>
                        </div>
                        <div class="sv_body">
                            <div id="surveys-list" class="surveys-list">
                                <section>
                                    <button onclick="addNewSurvey()">Add Survey</button>
                                </section>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <td>Name</td>
                                            <td></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($client->getSurveys() as $surveyDefinition) {
                                            $editUrl = add_query_arg(array('page' => 'surveyjs_editor', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                            $duplicateUrl = add_query_arg(array('page' => 'sjs-main-menu', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                            $resultsUrl = add_query_arg(array('page' => 'surveyjs_results', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                        ?>
                                        <tr>
                                            <td><?php echo sanitize_text_field($surveyDefinition->name) ?></td>
                                            <td>
                                                <!-- <a class="sv_button_link" href="<?php echo sanitize_key($surveyDefinition->id) ?>">Run</a> -->
                                                <a class="sv_button_link" href="<?php echo esc_url($editUrl) ?>">Edit</a>
                                                <a class="sv_button_link" href="<?php echo esc_url($resultsUrl) ?>">Results</a>
                                                <span class="sv_button_link sv_button_delete" onclick="deleteSurvey(<?php echo sanitize_key($surveyDefinition->id) ?>)">Delete</span>
                                                <span class="sv_button_link sv_button_duplicate" onclick="duplicateSurvey(<?php echo sanitize_key($surveyDefinition->id) ?>)">Duplicate</span>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }
}

?>