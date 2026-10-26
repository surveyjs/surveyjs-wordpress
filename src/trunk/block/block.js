var el = wp.element.createElement;
var registerBlockType = wp.blocks.registerBlockType;
var SelectControl = wp.components.SelectControl;
var Placeholder = wp.components.Placeholder;
var InspectorControls = wp.blockEditor.InspectorControls;
var blockIcon = "welcome-write-blog";
var surveysArray = this.surveys;
var surveys = {};

for (var i = 0; i < surveysArray.length; i++) {
  var survey = surveysArray[i];
  surveys[survey.id] = {
    name: survey.name,
    json: survey.json
  };
}

function getShortcode(surveyid) {
  return '[Survey id="' + surveyid + '" name="' + surveys[surveyid].name + '"] ';
}

registerBlockType("sjs/gutenberg-block", {
  title: "SurveyJS",

  icon: blockIcon,

  category: "common",

  edit: function(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var surveyid = attributes.surveyid;
    var options = [
      {
        label: "choose the survey...",
        value: "none"
      }
    ];

    surveysArray.forEach(function(survey) {
      options.push({
        label: survey.name,
        value: survey.id
      });
    });

    function onSurveyChange(value) {
      setAttributes({
        surveyid: value,
        shortcode: value === "none" ? "" : getShortcode(value)
      });
    }

    var inspectorControl = el(
      InspectorControls,
      {},
      el("h4", {}, "Embedding your Survey inside your website"),
      el("hr"),
      el(SelectControl, {
        label: "Choose survey to insert",
        value: surveyid,
        options: options,
        onChange: onSurveyChange
      })
    );

    return el(
      Placeholder,
      { icon: blockIcon, label: "SurveyJS", instructions: "Choose survey to insert" },
      el(SelectControl, {
        value: surveys[surveyid] ? surveyid : "none",
        options: options,
        onChange: onSurveyChange,
        __nextHasNoMarginBottom: true
      }),
      inspectorControl
    );
  },

  save: function() {
    return null; // This block is rendered on PHP side.
  }
});
