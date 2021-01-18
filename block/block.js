var el = wp.element.createElement;
var registerBlockType = wp.blocks.registerBlockType;
var SelectControl = wp.components.SelectControl;
var InspectorControls = wp.editor.InspectorControls;
var blockStyle = { border: "2px solid #1ab394", padding: "20px" };
var surveysArray = this.surveys;
var surveys = {};

for (var i = 0; i < surveysArray.length; i++) {
  var survey = surveysArray[i];
  surveys[survey.id] = {
    name: survey.name,
    json: survey.json
  };
}

registerBlockType("sjs/gutenberg-block", {
  title: "SurveyJS",
  icon: "welcome-write-blog",
  category: "common",

  edit: function(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
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

    var pdfButtonOptions = [
      {
        label: "Never",
        value: "never"
      },
      {
        label: "Always",
        value: "always"
      },
      {
        label: "On Complete",
        value: "onComplete"
      }
    ];

    var inspectorControl = el(
      InspectorControls,
      {},
      el("h4", {}, "Embedding your Survey inside your website"),
      el("hr"),
      el(SelectControl, {
        label: "Choose survey to insert",
        defaultValue: "none",
        value: attributes.surveyid,
        options: options,
        onChange: function(value) {
          setAttributes({ surveyid: value });
        }
      }),
      el(SelectControl, {
        label: "Show Save To Pdf Button",
        defaultValue: "never",
        value: attributes.saveToPdf,
        options: pdfButtonOptions,
        onChange: function(value) {
          setAttributes({ saveToPdf: value });
        }
      }),
    );

    var surveyid = attributes.surveyid;
    var shortCodeString =
      surveyid === "none"
        ? "Please choose a survey in the Settings => Block menu"
        : '[Survey id="' + surveyid + '" ' +
          'name="' + surveys[surveyid].name + '" ' +
          'saveToPdf="' + attributes.saveToPdf + '" ' +
          '] ';

    setAttributes({ shortcode: shortCodeString });

    return el(
      "div",
      { style: blockStyle },
      el("h3", {}, "SurveyJS shortcode: "),
      el("div", {}, shortCodeString),
      inspectorControl
    );
  },

  save: function() {
    return null; // This block is rendered on PHP side.
  }
});
