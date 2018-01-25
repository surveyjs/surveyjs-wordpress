<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_MySurveys {

    function __construct() {
    }

    function render() {
        $client = new WP_Service_Client();
        ?>
            <div class="wrap">
                <h2><?php _e( 'Available surveys list', 'sjs' ); ?></h2>
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
                        ?>
                        <tr>
                            <td><?php echo $surveyDefinition->Name ?></td>
                            <td>
                                <!-- <a href="<?php echo $surveyDefinition->Id ?>">Run</a> | -->
                                <a href="<?php echo $surveyDefinition->Id ?>">Edit</a> |
                                <a href="<?php echo $surveyDefinition->Id ?>">Results</a>
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