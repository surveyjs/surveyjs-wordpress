var el = wp.element.createElement,
  registerBlockType = wp.blocks.registerBlockType,
  blockStyle = { backgroundColor: "#900", color: "#fff", padding: "20px" };

registerBlockType("sjs/sjs-gutenberg-block", {
  title: "SurveyJS: Insert Survey",

  icon: "universal-access-alt",

  category: "common",

  edit: function() {
    return el("p", { style: blockStyle }, "Hello SurveyJS .");
  },

  save: function() {
    return el("p", { style: blockStyle }, "Hello saved SurveyJS content.");
  }
});
