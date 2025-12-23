<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Editor {

    function __construct() {
    }

    public static function render() {
        $surveyId = sanitize_key($_GET['id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
        $json = $wpdb->get_row($query)->json;
        $themeJson = $wpdb->get_row($query)->theme;

        $saveSurveyUri = add_query_arg(array('action' => 'SurveyJS_SaveSurvey'), admin_url('admin-ajax.php'));
        $renameSurveyUri = add_query_arg(array('action' => 'SurveyJS_RenameSurvey'), admin_url('admin-ajax.php'));
        $uploadFileUri = add_query_arg(array('action' => 'SurveyJS_UploadFile'), admin_url('admin-ajax.php'));
        $saveSurveyNonce = wp_create_nonce('surveyjs-save-survey');
        $renameSurveyNonce = wp_create_nonce('surveyjs-rename-survey');
        $uploadFileNonce = wp_create_nonce('surveyjs-upload-file');
        $lk = sanitize_text_field(SurveyJS_SettingsPage::get_license_key());
        ?>
            <style>
                /* #sjs-editor-container .svd_container .card {
                    max-width: none;
                }
                #sjs-editor-container .svd_container select {
                    height: 34px;
                    line-height: 20px;
                    padding: 6px 12px;
                } */
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
                        data: { Id: '<?php echo $surveyId ?>', Name: surveyName, _wpnonce: '<?php echo $renameSurveyNonce; ?>' },
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
                        <button style="min-width: 80px;color: white;background-color: #1ab394;border: none;padding: 6px;border-radius: 5px;margin-top: 10px;" onclick="window.location = '/wp-admin/admin.php?page=surveyjs-main-menu'">&lt&nbspBack</button>
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
                <div id="sjs-editor-container" style="height: 77vh;"></div>
                <script type="text/babel">
                    function SurveyCreatorRenderComponent() {
                        var lk = '<?php echo $lk ?>';
                        Survey.slk(lk);
                        var editorOptions = { showThemeTab: true, showLogicTab: true, showTranslationTab: true, showEmbededSurveyTab: false, showOptions: true, generateValidJSON : false };
                        const editor = new SurveyCreator.SurveyCreator(editorOptions);
                        editor.showState = true;
                        editor.isAutoSave = true;
                        editor.saveSurveyFunc = function(saveNo, callback) {
                            var json = JSON.stringify(editor.getSurveyJSON());   
                            var theme = JSON.stringify(editor.theme); 
                            jQuery.ajax({
                                url:  "<?php echo esc_url($saveSurveyUri) ?>",
                                type: "POST",
                                data: { Id: '<?php echo $surveyId ?>', Json: json, Theme: theme, _wpnonce: '<?php echo $saveSurveyNonce; ?>' },
                                success: function (data) {
                                    // if(data.isSuccess) {
                                    // }
                                    callback(saveNo, data.IsSuccess === 1);
                                }
                            });
                        };
                        editor.saveThemeFunc = function(saveNo, callback) {
                            var json = JSON.stringify(editor.getSurveyJSON());   
                            var theme = JSON.stringify(editor.theme); 
                            jQuery.ajax({
                                url:  "<?php echo esc_url($saveSurveyUri) ?>",
                                type: "POST",
                                data: { Id: '<?php echo $surveyId ?>', Json: json, Theme: theme, _wpnonce: '<?php echo $saveSurveyNonce; ?>' },
                                success: function (data) {
                                    // if(data.isSuccess) {
                                    // }
                                    callback(saveNo, data.IsSuccess === 1);
                                }
                            });
                        };
                        editor.onUploadFile.add(function(editor, options) {
                            var formData = new FormData();
                            options.files.forEach(function(file) {
                                formData.append("file", file);
                            });
                            formData.append("_wpnonce", "<?php echo $uploadFileNonce; ?>");
                            jQuery.ajax({
                                url: "<?php echo esc_url($uploadFileUri) ?>",
                                type: "POST",
                                xhr: function () {
                                    var myXhr = jQuery.ajaxSettings.xhr();
                                    if (myXhr.upload) {
                                        myXhr.upload.addEventListener('progress', function (event) {
                                            var percent = 0;
                                            var position = event.loaded || event.position;
                                            var total = event.total;
                                        }, false);
                                    }
                                    return myXhr;
                                },
                                success: function (data) {
                                    options.callback("success", data["url"]);
                                },
                                error: function (error) {
                                },
                                async: true,
                                data: formData,
                                cache: false,
                                contentType: false,
                                processData: false,
                                timeout: 60000
                            });
                        });
                        editor.onSurveyInstanceCreated.add(function(creatot, options) {
                            const reason = options.reason;
                            const survey = options.survey;
                            if (reason === "test" || reason === "theme") {
                                survey.getAllQuestions().forEach((q)=>{q.storeDataAsText = true});
                            }
                        })
                        const creator = editor;
                        var json = '<?php echo htmlspecialchars_decode($json); ?>';
                        creator.text = json;
                        //creator.JSON = surveyJSON;
                        const themeJSON = '<?php echo htmlspecialchars_decode($themeJson); ?>';
                        if (themeJSON) {
                            creator.theme = JSON.parse(themeJSON);
                        }
                        return (<SurveyCreator.SurveyCreatorComponent creator={creator} />);
                    }

                    const container = document.getElementById("sjs-editor-container");

                    const shadowRoot = container.attachShadow({ mode: 'open' });
                    const rootElement = document.createElement("div");
                    rootElement.classList.add("sjs-editor-shadow-root");
                    rootElement.style.height = "100%";
                    const style = document.createElement("style");
                    const surveyStyles = document.createElement('link');
                    surveyStyles.setAttribute('rel', 'stylesheet');
                    surveyStyles.setAttribute('href', "<?php echo plugins_url('../libs/library/survey-core.min.css', __FILE__) ?>");
                    const creatorStyles = document.createElement('link');
                    creatorStyles.setAttribute('rel', 'stylesheet');
                    creatorStyles.setAttribute('href', "<?php echo plugins_url('../libs/creator/survey-creator-core.min.css', __FILE__) ?>");
                    shadowRoot.appendChild(surveyStyles);
                    shadowRoot.appendChild(creatorStyles);
                    shadowRoot.appendChild(rootElement);

                    const root = ReactDOM.createRoot(rootElement);
                    root.render(<SurveyCreatorRenderComponent />);
                </script>
            </div>
            <script>
                setSurveyName(surveyName);
            </script>
        <?php
    }
}

?>