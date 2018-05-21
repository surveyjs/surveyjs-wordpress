<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_Results {

    function __construct() {
    }


    public static function render() {
        global $wpdb;
        $surveyId = $_GET['id'];
        $table_name = $wpdb->prefix . 'sjs_results';
        $query = "SELECT json FROM " . $table_name . " WHERE surveyId=" . $surveyId;
        $surveyResults = json_encode( $wpdb->get_results($query) );

        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
        $surveyJson = $wpdb->get_row($query)->json;
        
        $surveyName = $_GET['name'];

        ?>
            <h3><?php echo $surveyName; ?> survey results</h3>
            <table id="wpSjsResultsTable" class="table table-striped table-bordered"></table>

            <script>
                var surveyJson = "<?php echo $surveyJson; ?>";
                var survey = new Survey.Model(JSON.parse(surveyJson));

                var colums = survey.getAllQuestions().map(function(q) {
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

                var results = <?php echo $surveyResults; ?>;
                var data = results.map(function(result) {
                    return JSON.parse(result.json.replace(/\\/g, "") || "{}");
                });

                var table = $("#wpSjsResultsTable").DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', /*'pdf',*/ 'print'
                    ],
                    columns: colums,
                    data: data
                });
            </script>
        <?php
        
    }
}

?>