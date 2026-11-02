var el = wp.element.createElement;
var registerBlockType = wp.blocks.registerBlockType;
var ComboboxControl = wp.components.ComboboxControl;
var Placeholder = wp.components.Placeholder;
var InspectorControls = wp.blockEditor.InspectorControls;
var ExternalLink = wp.components.ExternalLink;
var blockIcon = "welcome-write-blog";
var surveysArray = this.surveys;
var editFormUrl = this.surveyjsBlock.editUrl;
var legacyIds = this.surveyjsBlock.legacyIds || {};
var surveys = {};

for (var i = 0; i < surveysArray.length; i++) {
  var survey = surveysArray[i];
  surveys[survey.id] = {
    name: survey.name
  };
}

var options = surveysArray.map(function(survey) {
  return {
    label: survey.name,
    value: String(survey.id)
  };
});

function resolveSurveyId(surveyid) {
  return surveys[surveyid] || !legacyIds[surveyid] ? surveyid : String(legacyIds[surveyid]);
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
    var surveyid = resolveSurveyId(attributes.surveyid);
    var selected = surveys[surveyid] ? String(surveyid) : null;

    function onSurveyChange(value) {
      setAttributes({
        surveyid: value || "none",
        shortcode: value ? getShortcode(value) : ""
      });
    }

    function formSelector(label) {
      return el(ComboboxControl, {
        label: label,
        placeholder: "Search forms...",
        value: selected,
        options: options,
        onChange: onSurveyChange,
        __nextHasNoMarginBottom: true
      });
    }

    var inspectorControl = el(
      InspectorControls,
      {},
      el("h4", {}, "Embed a form on your site"),
      el("hr"),
      formSelector("Choose form to insert"),
      selected && el(
        "p",
        {},
        el(ExternalLink, { href: editFormUrl + "&id=" + encodeURIComponent(selected) }, "Edit this form")
      )
    );

    return el(
      Placeholder,
      { icon: blockIcon, label: "SurveyJS", instructions: "Choose form to insert" },
      formSelector(),
      inspectorControl
    );
  },

  save: function() {
    return null; // This block is rendered on PHP side.
  }
});
