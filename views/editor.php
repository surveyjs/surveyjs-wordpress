<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_Editor {

    function __construct() {
    }

    public static function render() {
        $surveyId = sanitize_key($_GET['id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
        $json = $wpdb->get_row($query)->json;

        $saveSurveyUri = add_query_arg(array('action' => 'WP_SJS_SaveSurvey'), admin_url('admin-ajax.php'));
        $renameSurveyUri = add_query_arg(array('action' => 'WP_SJS_RenameSurvey'), admin_url('admin-ajax.php'));
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
            <script>
                var surveyName = "<?php echo sanitize_text_field($_GET['name']) ?>";
                function setSurveyName(name) {
                    var $titleTitle = jQuery("#sjs_editor_title_show");
                    $titleTitle.find("span").text(name);
                }
                function startEdit() {
                    var $titleEditor = jQuery("#sjs_editor_title_edit");
                    var $titleTitle = jQuery("#sjs_editor_title_show");
                    $titleTitle.hide();
                    $titleEditor.show();
                    $titleEditor.find("input")[0].value = surveyName;
                    $titleEditor.find("input").focus();
                }
                function cancelEdit() {
                    var $titleEditor = jQuery("#sjs_editor_title_edit");
                    var $titleTitle = jQuery("#sjs_editor_title_show");
                    $titleEditor.hide();
                    $titleTitle.show();
                }
                function postEdit() {
                    cancelEdit();
                    var oldName = surveyName;
                    var $titleEditor = jQuery("#sjs_editor_title_edit");
                    surveyName = $titleEditor.find("input")[0].value;

                    setSurveyName(surveyName);

                    jQuery.ajax({
                        url:  "<?php echo esc_url($renameSurveyUri) ?>",
                        type: "POST",
                        data: { Id: '<?php echo $surveyId ?>', Name: surveyName },
                        success: function (data) {
                            // if(data.isSuccess) {
                            // }
                        }
                    });
                }
            </script>
            <div class="wrap">
                <div class="survey-page-header">
                    <div class="sv_main survey-page-header-content">
                        <button onclick="window.location = '/wp-admin/admin.php?page=sjs-main-menu'">&lt&nbspBack</button>
                    </div>
                </div>
                <h2 style="display:inline-block;"><?php _e( 'Survey: ', 'sjs' ); ?></h2>
                <h3 style="display:inline-block; margin: 0;">
                    <span id="sjs_editor_title_edit" style="display: none;">
                        <input style="border-top: none; border-left: none; border-right: none; outline: none;"/>
                        <a class="btn btn-success" onclick="postEdit()" href="#" style="margin:5px; margin-top: -5px; background-color: #1ab394; border-color: #1ab394;">Update</a>
                        <a class="btn btn-warning" onclick="cancelEdit()" href="#" style="margin:5px; margin-top: -5px;">Cancel</a>
                    </span>
                    <span id="sjs_editor_title_show">
                        <span style="padding-top: 1px; height: 39px; display: inline-block;"></span>
                        <a href="#" class="edit-survey-name" onclick="startEdit()" title="Change Name">
                            <img class="edit-icon" src="<?php echo plugin_dir_url( __FILE__ )?>../images/Edit_12x12.svg" style="width:24px; height:24px; margin-top: -5px;"/>
                        </a>
                    </span>
                </h3>
                <div id="sjs-editor-container"></div>
                <script>
                    var editorOptions = { showEmbededSurveyTab: false, showOptions: true, generateValidJSON : false };
                    var editor = new SurveyEditor.SurveyEditor("sjs-editor-container", editorOptions);
                    editor.showState = true;
                    editor.isAutoSave = true;
                    editor.saveSurveyFunc = function(saveNo, callback) {
                        var json = JSON.stringify(editor.getSurveyJSON());   
                        jQuery.ajax({
                            url:  "<?php echo esc_url($saveSurveyUri) ?>",
                            type: "POST",
                            data: { Id: '<?php echo $surveyId ?>', Json: json },
                            success: function (data) {
                                // if(data.isSuccess) {
                                // }
                                callback(saveNo, data.IsSuccess);
                            }
                        });
                    }
                    var json = "<?php echo $json; ?>";
                    editor.text = json;      
                </script>
            </div>
            <script>
                setSurveyName(surveyName);
            </script>
        <?php
    }
}

?>