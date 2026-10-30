var el = wp.element.createElement;
var registerBlockType = wp.blocks.registerBlockType;
var SelectControl = wp.components.SelectControl;
var Placeholder = wp.components.Placeholder;
var InspectorControls = wp.blockEditor.InspectorControls;
var ExternalLink = wp.components.ExternalLink;
var blockIcon = "welcome-write-blog";
var surveysArray = this.surveys;
var editFormUrl = this.surveyjsBlock.editUrl;
var surveys = {};

for (var i = 0; i < surveysArray.length; i++) {
  var survey = surveysArray[i];
  surveys[survey.id] = {
    name: survey.name,
    json: survey.json
  };
}

function getShortcode(surveyid) {
  return '[surveyjs id="' + surveyid + '" title="' + surveys[surveyid].name.replace(/["\[\]]/g, "") + '"] ';
}

registerBlockType("sjs/gutenberg-block", {
  title: "SurveyJS Forms",

  icon: blockIcon,

  category: "common",

  edit: function(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var surveyid = attributes.surveyid;
    var options = [
      {
        label: "Choose a form...",
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
      el("h4", {}, "Embed a form on your site"),
      el("hr"),
      el(SelectControl, {
        label: "Choose form to insert",
        value: surveyid,
        options: options,
        onChange: onSurveyChange
      }),
      surveys[surveyid] && el(
        "p",
        {},
        el(ExternalLink, { href: editFormUrl + "&id=" + encodeURIComponent(surveyid) }, "Edit this form")
      )
    );

    return el(
      Placeholder,
      { icon: blockIcon, label: "SurveyJS", instructions: "Choose form to insert" },
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
