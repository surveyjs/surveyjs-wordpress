<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_MySurveys {

    function __construct() {
    }

    public static function render() {
        $client = new WP_Service_Client();
        $editSurveyUri = add_query_arg(array('page' => 'wp_surveyjs_editor'), admin_url('admin.php'));
        $addSurveyUri = add_query_arg(array('action' => 'WP_SJS_AddSurvey'), admin_url('admin-ajax.php'));
        $deleteSurveyUri = add_query_arg(array('action' => 'WP_SJS_DeleteSurvey'), admin_url('admin-ajax.php'));
        ?>
            <script>
                function addNewSurvey() {
                    jQuery.ajax({
                        url:  "<?php echo $addSurveyUri ?>",
                        type: "POST",
                        data: { Name: "New Survey" },
                        success: function (data) {
                            window.location = "<?php echo $editSurveyUri ?>&id=" + data.Id;
                        }
                    });
                }
                function deleteSurvey(id) {
                    var res = confirm("Are you sure?");
                    if (!res) return;
                    jQuery.ajax({
                        url:  "<?php echo $deleteSurveyUri  ?>",
                        type: "POST",
                        data: { Id: id },
                        success: function (data) {
                            window.location = "";
                        }
                    });
                }
            </script>
            <div class="wrap">
                <h2 style="display: inline-block; margin-top: 10px;"><?php _e( 'Available surveys list', 'sjs' ); ?></h2>
                <button class="button" style="display: inline-block; float: right; margin-top: 20px;" onclick="addNewSurvey()">Add Survey</button>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <td>Name</td>
                            <td>Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($client->getSurveys() as $surveyDefinition) {
                            $editUrl = add_query_arg(array('page' => 'wp_surveyjs_editor', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                            $resultsUrl = add_query_arg(array('page' => 'wp_surveyjs_results', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                        ?>
                        <tr>
                            <td><?php echo $surveyDefinition->name ?></td>
                            <td>
                                <!-- <a href="<?php echo $surveyDefinition->id ?>">Run</a> | -->
                                <a href="<?php echo $editUrl ?>">Edit</a> |
                                <a href="<?php echo $resultsUrl ?>">Results</a> |
                                <button onclick="deleteSurvey(<?php echo $surveyDefinition->id ?>)" style="margin-left:10px;color:red;">Delete</button>
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php
    }
}

?>