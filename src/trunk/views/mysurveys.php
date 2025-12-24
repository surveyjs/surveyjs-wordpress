<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_MySurveys {

    function __construct() {
    }

    public static function render() {
        $client = new SurveyJS_Client();
        $editSurveyUri = add_query_arg(array('page' => 'surveyjs_editor'), admin_url('admin.php'));
        $addSurveyUri = add_query_arg(array('action' => 'SurveyJS_AddSurvey'), admin_url('admin-ajax.php'));
        $deleteSurveyUri = add_query_arg(array('action' => 'SurveyJS_DeleteSurvey'), admin_url('admin-ajax.php'));
        $cloneSurveyUri = add_query_arg(array('action' => 'SurveyJS_CloneSurvey'), admin_url('admin-ajax.php'));
        $addSurveyNonce = wp_create_nonce('surveyjs-add-survey');
        $deleteSurveyNonce = wp_create_nonce("delete-survey-ajax-referer");
        $cloneSurveyNonce = wp_create_nonce('surveyjs-clone-survey');
        ?>
            <script>
                function addNewSurvey() {
                    jQuery.ajax({
                        url:  "<?php echo esc_url($addSurveyUri) ?>",
                        type: "POST",
                        data: { Name: "New Survey", _wpnonce: '<?php echo esc_js($addSurveyNonce); ?>' },
                        success: function (data) {
                            window.location = "<?php echo esc_url($editSurveyUri) ?>&id=" + data.Id + "&name=New Survey";
                        }
                    });
                }
                function deleteSurvey(id) {
                    var res = confirm("This action CANNOT be undone! Are you ABSOLUTELY sure?");
                    if (!res) return;
                    jQuery.ajax({
                        url:  "<?php echo esc_url($deleteSurveyUri)  ?>",
                        type: "POST",
                        data: { Id: id, _wpnonce: '<?php echo esc_js($deleteSurveyNonce); ?>' },
                        success: function (data) {
                            window.location = "";
                        }
                    });
                }
                function cloneSurvey(id) {
                    jQuery.ajax({
                        url:  "<?php echo esc_url($cloneSurveyUri)  ?>",
                        type: "POST",
                        data: { SurveyParentId: id, _wpnonce: '<?php echo esc_js($cloneSurveyNonce); ?>' },
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
                            <h3><?php esc_html_e( 'SurveyJS Wordpress plugin', 'surveyjs' ); ?></h3>
                            <p>Below you can see the list of available surveys you can edit, run and see the results</p>
                        </div>
                        <div class="sv_body">
                            <div id="surveys-list" class="surveys-list">
                                <section>
                                    <button style='min-width: 100px; color: white;background-color: #1ab394;border: none;padding: 6px;border-radius: 5px;' onclick="addNewSurvey()">Add Survey</button>
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
                                            $resultsUrl = add_query_arg(array('page' => 'surveyjs_results', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html($surveyDefinition->name) ?></td>
                                            <td>
                                                <!-- <a class="sv_button_link" href="<?php echo sanitize_key($surveyDefinition->id) ?>">Run</a> -->
                                                <a class="sv_button_link" href="<?php echo esc_url($editUrl) ?>">Edit</a>
                                                <a class="sv_button_link" href="<?php echo esc_url($resultsUrl) ?>">Results</a>
                                                <span class="sv_button_link" onclick="cloneSurvey(<?php echo sanitize_key($surveyDefinition->id) ?>)">Clone</span>
                                                <span class="sv_button_link sv_button_delete" onclick="deleteSurvey(<?php echo sanitize_key($surveyDefinition->id) ?>)">Delete</span>
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