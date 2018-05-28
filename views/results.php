<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_Results {

    function __construct() {
    }


    public static function render() {
        global $wpdb;
        $surveyId = sanitize_key($_GET['id']);
        $table_name = $wpdb->prefix . 'sjs_results';
        $query = "SELECT json FROM " . $table_name . " WHERE surveyId=" . $surveyId;
        $surveyResults = json_encode( $wpdb->get_results($query) );

        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
        $surveyJson = $wpdb->get_row($query)->json;
        
        $surveyName = sanitize_text_field($_GET['name']);

        ?>
            <div class="wp-sjs-plugin">
                <div class="survey-page-header">
                    <div class="sv_main survey-page-header-content">
                        <button onclick="window.location = '/wp-admin/admin.php?page=sjs-main-menu'">&lt&nbspBack</button>
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
                    defaultContent:
                    "<button style='min-width: 150px;'>Show in Survey</button>"
                });


                var windowSurvey = new Survey.SurveyWindow(surveyJson);
                windowSurvey.survey.mode = "display";
                windowSurvey.survey.title = "<?php echo $surveyName; ?>";
                windowSurvey.show();

                $(document).on("click", "#wpSjsResultsTable td", function(e) {
                    var row_object = table.row(this).data();
                    windowSurvey.survey.data = row_object;
                    windowSurvey.isExpanded = true;
                });

                var results = <?php echo $surveyResults; ?>;
                var data = results.map(function(result) {
                    return JSON.parse(result.json.replace(/\\/g, "") || "{}");
                });

                var table = $("#wpSjsResultsTable").DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', /*'pdf',*/ 'print'
                    ],
                    columns: columns,
                    data: data
                });
            </script>
        <?php
        
    }
}

?>