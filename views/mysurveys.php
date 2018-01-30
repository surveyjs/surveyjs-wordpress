<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_MySurveys {

    function __construct() {
    }

    public static function render() {
        if (WP_SJS_SettingsPage::get_access_key() != "") {
            $client = new WP_Service_Client();
            $editNewUrl = add_query_arg(array('page' => 'wp_surveyjs_editor'), admin_url('admin.php'));
            ?>
                <script>
                    function addNewSurvey() {
                        var xhr = new XMLHttpRequest();
                        xhr.open(
                            "GET",
                            "https://surveyjs.io/api/MySurveys/create?accessKey=" + '<?php echo WP_SJS_SettingsPage::get_access_key() ?>' + "&name=" + 'New Survey'
                        );
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.onload = function() {
                        var result = xhr.response ? JSON.parse(xhr.response) : null;
                            if(xhr.status === 200) {
                                window.location = "<?php echo $editNewUrl ?>&id=" + result.Id;
                            }
                        };
                        xhr.send();
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
                                $url = add_query_arg(array('page' => 'wp_surveyjs_editor', 'id' => $surveyDefinition->Id), admin_url('admin.php'));
                            ?>
                            <tr>
                                <td><?php echo $surveyDefinition->Name ?></td>
                                <td>
                                    <!-- <a href="<?php echo $surveyDefinition->Id ?>">Run</a> | -->
                                    <a href="<?php echo $url ?>">Edit</a> |
                                    <a href="https://surveyjs.io/Service/SurveyResults/<?php echo $surveyDefinition->Id ?>">Results</a>
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php
        } else {
            ?>
                <h3>
                    This plugin stores surveys on the <a href="https://surveyjs.io/Service/MySurveys/">surveyjs.io</a> site.
                    You need to <a href="https://surveyjs.io/Account/Register/">register</a> and then
                    "Get Access Key" on the <a href="<?php echo add_query_arg(array('page' => 'sjs-settings'), admin_url('admin.php')) ?>">Settings</a> page.
                    After that you will be able to create surveyjs and insert them on your wordpress site.
                </h3>
            <?php
        }
    }
}

?>