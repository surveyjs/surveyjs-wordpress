function surveyjs_SaveData(widgetId, json) {
    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
         url: wp_surveyajax.ajaxurl,
 
        data: {
            action: 'surveyjs_onComplete',
            widgetId: widgetId,
            jsonValue: JSON.stringify(json)
        },
 
        success: function(data, textStatus, XMLHttpRequest) {
            alert('data:' + data);
        },
 
        error: function(MLHttpRequest, textStatus, errorThrown) {
            alert("error:" + errorThrown);
        }
 
    });
}

function surveyjs_LoadSurvey(widgetId, surveyId, divId) {
    var survey = new Survey.Survey({ surveyId: surveyId}, divId);
     survey["widgetId"] = widgetId;
	survey.onComplete.add(function(s) {
            surveyjs_SaveData(s["widgetId"], s.data);
    }); 
}