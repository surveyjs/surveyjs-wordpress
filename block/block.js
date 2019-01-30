var el = wp.element.createElement;
var registerBlockType = wp.blocks.registerBlockType;
var SelectControl = wp.components.SelectControl;
var InspectorControls = wp.editor.InspectorControls;
var blockStyle = { backgroundColor: "#900", color: "#fff", padding: "20px" };
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

  icon: "universal-access-alt",

  category: "common",

  edit: function(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var options = [];

    surveysArray.forEach(function(survey) {
      options.push({
        label: survey.name,
        value: survey.id
      });
    });

    var inspectorControl = el(
      InspectorControls,
      {},
      el("h4", {}, "Embedding your Survey inside your website"),
      el("hr"),
      el(SelectControl, {
        label: "Choose survey to insert",
        value: attributes.surveyid,
        options: options,
        onChange: function(value) {
          setAttributes({ surveyid: value });
        }
      })
    );

    var surveyid = attributes.surveyid;
    var shortCodeString = surveyid
      ? '[Survey id="' + surveyid + '" name="' + surveys[surveyid].name + '"] '
      : "Please choose a survey";

    setAttributes({ shortcode: shortCodeString });

    return el(
      "p",
      { style: blockStyle },
      shortCodeString,
      el("div", {}),
      inspectorControl
    );
  },

  save: function() {
    return null; // This block is rendered on PHP side.
  }
});
