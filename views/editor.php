<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_Editor {

    function __construct() {
    }

    public static function render() {
        $surveyId = $_GET['id'];
        ?>
            <style>
                #sjs-editor-container .svd_container .card {
                    max-width: none;
                }
                #sjs-editor-container .svd_container select {
                    height: 34px;
                    line-height: 20px;
                    padding: 6px 12px;
                }
            </style>
            <div class="wrap">
                <h2><?php _e( 'Survey Editor', 'sjs' ); ?></h2>
                <div id="sjs-editor-container"></div>
                <script>
                    var editorOptions = { showEmbededSurveyTab: true, showOptions: true, generateValidJSON : false };
                    var editor = new SurveyEditor.SurveyEditor("sjs-editor-container", editorOptions);
                    editor.showState = true;
                    editor.isAutoSave = true;
                    editor.saveSurveyFunc = function(saveNo, callback) {
                        jQuery.ajax({
                            url: "https://surveyjs.io/api/MySurveys/changeJson?accessKey=<?php echo WP_SJS_SettingsPage::get_access_key() ?>",
                            type: "POST",
                            data: { Id: '<?php echo $surveyId ?>', Text: editor.text, Json : editor.text },
                            success: function (data) {
                                // if(data.isSuccess) {
                                // }
                                callback(saveNo, data.isSuccess);
                            }
                        });
                    }
                    editor.loadSurvey('<?php echo $surveyId ?>');
                </script>
            </div>
        <?php
    }
}

?>