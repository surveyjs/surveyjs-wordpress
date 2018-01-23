function surveyjs_loadSurveyList(accessKey, surveyVar) {
    if(accessKey) {
        var url = "https://dxsurvey.com/api/MySurveys/getActive?accessKey=" + accessKey;
    } else {
        showError(surveyVar, surveyVar.errorAccessKey);
    }
    jQuery.getJSON(url, function (data) {
        surveyVar.surveys = data;
        var option;
        var initIndex = -1;
        var id = jQuery('#' + surveyVar.surveyId).val();
        for (var i = 0; i < data.length; i++) {
            option += '<option value="' + i + '">' + data[i].Name + '</option>';
            if(id && data[i].Id == id) {
                initIndex = i;
            }
        }
        jQuery('#' + surveyVar.list).empty();
        jQuery('#' + surveyVar.list).append(option);
        if(initIndex >= 0) {
            jQuery('#' + surveyVar.list).val(initIndex);
            jQuery('#' + surveyVar.list).trigger("change");
        }
        var errorEl = data.length == 0 ? surveyVar.errorNoSurvey : null; 
        showError(surveyVar, errorEl);    
        jQuery('#' + surveyVar.list).prop("disabled", data.length == 0);
    }).error(function(jqXHR, textStatus, errorThrown) {
        jQuery('#' + surveyVar.list).empty();
        showError(surveyVar, surveyVar.errorAccessKey);
        jQuery('#' + surveyVar.list).prop( "disabled", true);
    });
}

function showError(surveyVar, errorEl) {
    jQuery('#' + surveyVar.errorAccessKey).hide();
    jQuery('#' + surveyVar.errorNoSurvey).hide();
    if(errorEl) jQuery('#' + errorEl).show();
}

function surveyjs_setSurveyIds(index, surveyVar) {
    var item = surveyVar.surveys[index]; 
    jQuery('#' + surveyVar.surveyId).val(item.Id);
    jQuery('#' + surveyVar.postId).val(item.PostId);
    jQuery('#' + surveyVar.resultId).val(item.ResultId);
}
