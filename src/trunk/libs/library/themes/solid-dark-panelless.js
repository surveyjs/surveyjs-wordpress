/*!
 * surveyjs - Survey JavaScript library v2.4.0
 * Copyright (c) 2015-2025 Devsoft Baltic OÜ  - http://surveyjs.io/
 * License: MIT (http://www.opensource.org/licenses/mit-license.php)
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define("solid-dark-panelless", [], factory);
	else if(typeof exports === 'object')
		exports["solid-dark-panelless"] = factory();
	else
		root["SurveyTheme"] = root["SurveyTheme"] || {}, root["SurveyTheme"]["SolidDarkPanelless"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!********************************************!*\
  !*** ./src/themes/solid-dark-panelless.ts ***!
  \********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
    "themeName": "solid",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(53, 55, 63, 1)",
        "--sjs-general-backcolor-dark": "rgba(60, 63, 74, 1)",
        "--sjs-general-backcolor-dim": "rgba(53, 55, 63, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(46, 48, 55, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(51, 53, 61, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(125, 129, 143, 1)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(125, 130, 148, 1)",
        "--sjs-primary-backcolor": "rgba(58, 202, 211, 1)",
        "--sjs-primary-backcolor-light": "rgba(67, 70, 80, 1)",
        "--sjs-primary-backcolor-dark": "rgba(92, 224, 233, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 0px rgba(33, 35, 43, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(33, 35, 43, 1)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px -2px 0px 0px rgba(33, 35, 43, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(33, 35, 43, 1)",
        "--sjs-border-light": "rgba(65, 69, 83, 1)",
        "--sjs-border-default": "rgba(97, 101, 118, 1)",
        "--sjs-border-inside": "rgba(255, 255, 255, 0.08)",
        "--sjs-special-red": "rgba(254, 76, 108, 1)",
        "--sjs-special-red-light": "rgba(254, 76, 108, 0.1)",
        "--sjs-special-red-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-special-green": "rgba(36, 197, 164, 1)",
        "--sjs-special-green-light": "rgba(36, 197, 164, 0.1)",
        "--sjs-special-green-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-special-blue": "rgba(91, 151, 242, 1)",
        "--sjs-special-blue-light": "rgba(91, 151, 242, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-article-font-xx-large-textDecoration": "none",
        "--sjs-article-font-xx-large-fontWeight": "700",
        "--sjs-article-font-xx-large-fontStyle": "normal",
        "--sjs-article-font-xx-large-fontStretch": "normal",
        "--sjs-article-font-xx-large-letterSpacing": "0",
        "--sjs-article-font-xx-large-lineHeight": "64px",
        "--sjs-article-font-xx-large-paragraphIndent": "0px",
        "--sjs-article-font-xx-large-textCase": "none",
        "--sjs-article-font-x-large-textDecoration": "none",
        "--sjs-article-font-x-large-fontWeight": "700",
        "--sjs-article-font-x-large-fontStyle": "normal",
        "--sjs-article-font-x-large-fontStretch": "normal",
        "--sjs-article-font-x-large-letterSpacing": "0",
        "--sjs-article-font-x-large-lineHeight": "56px",
        "--sjs-article-font-x-large-paragraphIndent": "0px",
        "--sjs-article-font-x-large-textCase": "none",
        "--sjs-article-font-large-textDecoration": "none",
        "--sjs-article-font-large-fontWeight": "700",
        "--sjs-article-font-large-fontStyle": "normal",
        "--sjs-article-font-large-fontStretch": "normal",
        "--sjs-article-font-large-letterSpacing": "0",
        "--sjs-article-font-large-lineHeight": "40px",
        "--sjs-article-font-large-paragraphIndent": "0px",
        "--sjs-article-font-large-textCase": "none",
        "--sjs-article-font-medium-textDecoration": "none",
        "--sjs-article-font-medium-fontWeight": "700",
        "--sjs-article-font-medium-fontStyle": "normal",
        "--sjs-article-font-medium-fontStretch": "normal",
        "--sjs-article-font-medium-letterSpacing": "0",
        "--sjs-article-font-medium-lineHeight": "32px",
        "--sjs-article-font-medium-paragraphIndent": "0px",
        "--sjs-article-font-medium-textCase": "none",
        "--sjs-article-font-default-textDecoration": "none",
        "--sjs-article-font-default-fontWeight": "400",
        "--sjs-article-font-default-fontStyle": "normal",
        "--sjs-article-font-default-fontStretch": "normal",
        "--sjs-article-font-default-letterSpacing": "0",
        "--sjs-article-font-default-lineHeight": "28px",
        "--sjs-article-font-default-paragraphIndent": "0px",
        "--sjs-article-font-default-textCase": "none"
    }
});

__webpack_exports__ = __webpack_exports__["default"];
/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=solid-dark-panelless.js.map