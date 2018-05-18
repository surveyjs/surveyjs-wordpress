<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_Results {

    function __construct() {
    }

    public static function render() {
        $surveyId = $_GET['id'];
        $surveyName = $_GET['name'];

        ?>
            <?php echo $surveyId; ?>;
            <?php echo $surveyName; ?>;

            <table id="wpSjsResultsTable" class="table table-striped table-bordered"></table>

            <script>
                var table = $("#resultsTable").DataTable({
                    columns: self.columns(),
                    data: self.results()
                });
            </script>
        <?php
        
    }
}

?>