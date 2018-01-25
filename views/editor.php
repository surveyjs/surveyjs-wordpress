<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_SJS_Editor {

    function __construct() {
    }

    public static function render() {
        ?>
            <div class="wrap">
                <h2><?php _e( 'Survey Editor', 'sjs' ); ?></h2>
                <div id="sjs-editor-container"></div>
                <script>
                    var editorOptions = { };
                    var editor = new SurveyEditor.SurveyEditor("sjs-editor-container", editorOptions);
                </script>
            </div>
        <?php
    }
}

?>