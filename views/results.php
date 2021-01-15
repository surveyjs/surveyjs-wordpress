<?php
if (!defined('ABSPATH')) exit;

class SurveyJS_Results {
    function __construct() {
    }


    public static function render() {
        global $wpdb;
        $surveyId = sanitize_key($_GET['id']);
        $table_name = $wpdb->prefix . 'sjs_results';
        $query = "SELECT id, json FROM " . $table_name . " WHERE surveyId=" . $surveyId;
        $surveyResults = json_encode( $wpdb->get_results($query) );

        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
        $surveyJson = $wpdb->get_row($query)->json;
        
        $surveyName = sanitize_text_field($_GET['name']);

        $deleteResultUri = add_query_arg(array('action' => 'SurveyJS_DeleteResult'), admin_url('admin-ajax.php'));

        ?>
            <div class="wp-sjs-plugin">
                <div class="survey-page-header">
                    <div class="sv_main survey-page-header-content">
                        <button style="min-width: 80px;color: white;background-color: #1ab394;border: none;padding: 6px;border-radius: 5px;margin-top: 10px;" onclick="window.location = '/wp-admin/admin.php?page=sjs-main-menu'">&lt&nbspBack</button>
                    </div>
                </div>
                <div class="sv_main sv_frame sv_default_css">
                    <div class="sv_custom_header"></div>
                    <div class="sv_container">
                        <div class="sv_header">
                            <h3 class="results-header"><?php echo $surveyName; ?> survey results</h3>
                        </div>
                        <div class="sv_body">
                            <table id="wpSjsResultsTable" class="table table-striped table-bordered"></table>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                var $ = jQuery;
                var surveyJson = "<?php echo $surveyJson; ?>";
                var survey = new Survey.Model(JSON.parse(surveyJson));

                var columns = survey.getAllQuestions().map(function(q) {
                    return {
                        data: q.name,
                        sTitle: (q.title || "").trim(" ") || q.name,
                        mRender: function(data, type, row) {
                        survey.data = row;
                        var displayValue = q.displayValue;
                        return (
                            (typeof displayValue === "string"
                            ? displayValue
                            : JSON.stringify(displayValue)) || ""
                        );
                        }
                    };
                });

                columns.push({
                    targets: -1,
                    data: null,
                    sortable: false,
                    className: "center",
                    defaultContent:
                    `
                     <button id='showInSurvey' style='min-width: 150px; margin-right: 50px; color: white;background-color: #1ab394;border: none;padding: 6px;border-radius: 5px;'>Show in Survey</button>
                     <button id='deleteResult' style='min-width: 150px; color: white;background-color: #d9534f;border: none;padding: 6px;border-radius: 5px;'>Delete result </button>
                    `
                });

                var windowSurvey = new Survey.SurveyWindow(surveyJson);
                windowSurvey.survey.mode = "display";
                windowSurvey.survey.title = "<?php echo $surveyName; ?>";
                windowSurvey.show();

                $(document).on("click", "#showInSurvey", function(e) {
                    var row_object = table.row(this).data();
                    windowSurvey.survey.data = row_object;
                    windowSurvey.isExpanded = true;
                });

                var results = <?php echo $surveyResults; ?>;
                
                var data = results.map(function(result) {
                    var dataItem = JSON.parse(result.json.replace(/\\\"/g, "\"").replace(/\\\\/g, "\\").replace(/\\'/g, "'") || "{}");
                    dataItem.resultId = result.id;
                    return dataItem;
                });

                var table = $("#wpSjsResultsTable").DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', /*'pdf',*/ 'print'
                    ],
                    columns: columns,
                    data: data
                });

                $('#wpSjsResultsTable tbody').on('click', '#deleteResult', function (e) {
                    e.preventDefault();
                    var confirmResult = confirm("This action CANNOT be undone! Are you ABSOLUTELY sure?");
                    if (!confirmResult) return;
                    var row = table.row( $(this).parents('tr') );
                    var data = row.data();
                    jQuery.ajax({
                        url:  "<?php echo esc_url($deleteResultUri) ?>",
                        type: "POST",
                        data: { ResultId: data.resultId },
                        success: function (data) {
                            row.remove();
                            table.draw();
                        }
                    });

                });
            </script>
        <?php
        
    }
}
?>