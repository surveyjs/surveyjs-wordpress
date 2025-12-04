<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Results {

    function __construct() {
    }

    public static function render() {
        global $wpdb;
        $surveyId = sanitize_key($_GET['id']);
        $table_name = $wpdb->prefix . 'sjs_results';
        $query = "SELECT id, json FROM " . $table_name . " WHERE surveyId=" . $surveyId;
        $surveyResults = $wpdb->get_results($query);

        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
        $surveyJson = $wpdb->get_row($query)->json;
        
        $surveyName = sanitize_text_field($_GET['name']);

        $deleteResultUri = add_query_arg(array('action' => 'SurveyJS_DeleteResult'), admin_url('admin-ajax.php'));
        $deleteResultNonce = wp_create_nonce('surveyjs-delete-result');

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
                            <div id="columnFormat" class="sjs-radiogroup" role="radiogroup" aria-label="Column Header Format" aria-describedby="columnFormatHelp">
                                <div id="columnFormatDescription">
                                    Column Header Format:
                                <div id="columnFormatHelp" class="sjs-sr-only">Choose how table column headers are shown: question title or question name.</div>
                                </div>
                                <label for="columnFormat-title" class="sjs-radio-option" tabindex="0">
                                    <input id="columnFormat-title" class="sjs-radio-input" type="radio" name="columnFormat" value="title" checked aria-checked="true">Question Title
                                </label>
                                <label for="columnFormat-name" class="sjs-radio-option" tabindex="0">
                                    <input id="columnFormat-name" class="sjs-radio-input" type="radio" name="columnFormat" value="name" aria-checked="false">Question Name
                                </label>

                                <div id="columnFormatLive" role="status" aria-live="polite" class="sjs-column-format-live"></div>
                            </div>
                            <table id="wpSjsResultsTable" class="table table-striped table-bordered"></table>
                        </div>
                    </div>
                </div>
                                <div id="surveyElement"></div>
                        </div>  
                        <script>
                var $ = jQuery;
                var surveyJson = "<?php echo $surveyJson; ?>";
                var survey = new Survey.Model(JSON.parse(surveyJson));

                var columnFormat = (function() { return ($('input[name="columnFormat"]:checked').val() || 'title'); })();

                function createColumns() {
                    var cols = survey.getAllQuestions().map(function(q) {
                        return {
                            data: q.name,
                            sTitle: (columnFormat === 'title') ? ((q.title || "").trim(" ") || q.name) : q.name,
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

                    cols.push({
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

                    return cols;
                }

                var columns = createColumns();

                // var windowSurvey = new Survey.PopupSurveyModel(surveyJson);
                // windowSurvey.survey.mode = "display";
                // windowSurvey.survey.title = "<?php echo $surveyName; ?>";
                // windowSurvey.show();


                $(document).on("click", ".wp-sjs-plugin", function(e) {
                    var rowEl = $(e.target).parents("tr");
                    var row_object = table.row(rowEl).data();
                    var survey = new Survey.Model(surveyJson);
                    survey.data = row_object;

                    var handlePopupClose = () => {
                        $("#surveyElement").remove();
                    }

                    $("#surveyElement").PopupSurvey({
                        model: survey,
                        isExpanded: true,
                        closeOnCompleteTimeout: -1,
                        allowClose: true,
                        onClose: handlePopupClose
                    });
                });

 
                <?php
                    echo 'var results = ', json_encode($surveyResults), ';';
                ?>

                function decodeHtml(str) {
                    var textarea = document.createElement("textarea");
                    textarea.innerHTML = str;
                    return textarea.innerText;
                }

                var data = results.map(function(result) {
                    var replacedResult  = decodeHtml(result.json.replace(/\\\"/g, "\"").replace(/\\\\/g, "\\").replace(/\\'/g, "'"));                    
                    var dataItem = JSON.parse(replacedResult || "{}");
                    dataItem.resultId = result.id;
                    return dataItem;
                });

                var table;

                function initTable() {
                    table = $("#wpSjsResultsTable").DataTable({
                        dom: 'Bfrtip',
                        buttons: [
                            'copy', 'csv', 'excel', /*'pdf',*/ 'print'
                        ],
                        columns: columns,
                        data: data
                    });
                }

                initTable();

                // Initialize aria-checked and active classes on labels
                function updateAriaAndActive() {
                    $('#columnFormat input[name="columnFormat"]').each(function() {
                        var $input = $(this);
                        var checked = $input.is(':checked');
                        $input.attr('aria-checked', checked ? 'true' : 'false');
                        var id = $input.attr('id');
                        var $label = $('label[for="' + id + '"]');
                        if (checked) {
                            $label.addClass('sjs-active');
                        } else {
                            $label.removeClass('sjs-active');
                        }
                    });
                }

                updateAriaAndActive();

                var columnFormatChangeTimeout = null;
                $('#columnFormat input[name="columnFormat"]').on('change', function() {
                    columnFormat = $(this).val();
                    updateAriaAndActive();

                    if (columnFormatChangeTimeout) clearTimeout(columnFormatChangeTimeout);
                    columnFormatChangeTimeout = setTimeout(function() {
                        // preserve state
                        var page = table.page();
                        var order = table.order();
                        var search = table.search();
                        var length = table.page.len();

                        try {
                            table.destroy();
                        } catch (e) {
                            // ignore if already destroyed
                        }

                        columns = createColumns();
                        initTable();

                        // restore state
                        table.page.len(length);
                        table.order(order);
                        table.search(search);
                        table.page(page).draw(false);

                        // announce change after reinit
                        var labelText = $('label[for="columnFormat-' + (columnFormat === 'title' ? 'title' : 'name') + '"]').text().trim();
                        var $live = $('#columnFormatLive');
                        // force re-announcement
                        $live.text('');
                        setTimeout(function() {
                            $live.text('Column header format set to ' + labelText + '.');
                        }, 50);
                    }, 200);
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
                        data: { ResultId: data.resultId, _wpnonce: '<?php echo $deleteResultNonce; ?>' },
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