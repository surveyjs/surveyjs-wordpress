/*!
 * surveyjs - Survey JavaScript library v1.12.17
 * Copyright (c) 2015-2024 Devsoft Baltic OÜ  - http://surveyjs.io/
 * License: MIT (http://www.opensource.org/licenses/mit-license.php)
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define("layered-dark-panelless", [], factory);
	else if(typeof exports === 'object')
		exports["layered-dark-panelless"] = factory();
	else
		root["SurveyTheme"] = factory();
})(this, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./packages/survey-core/src/themes/layered-dark-panelless.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./packages/survey-core/src/themes/layered-dark-panelless.ts":
/*!*******************************************************************!*\
  !*** ./packages/survey-core/src/themes/layered-dark-panelless.ts ***!
  \*******************************************************************/
/*! exports provided: default, LayeredDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredDarkPanelless", function() { return LayeredDarkPanelless; });
var Theme = {
    "themeName": "layered",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(39, 40, 50, 1)",
        "--sjs-general-backcolor-dark": "rgba(46, 47, 58, 1)",
        "--sjs-general-backcolor-dim": "rgba(39, 40, 50, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(32, 33, 43, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(37, 38, 48, 1)",
        "--sjs-general-forecolor": "rgba(213, 215, 238, 1)",
        "--sjs-general-forecolor-light": "rgba(117, 120, 140, 1)",
        "--sjs-general-dim-forecolor": "rgba(213, 215, 238, 1)",
        "--sjs-general-dim-forecolor-light": "rgba(117, 119, 141, 1)",
        "--sjs-primary-backcolor": "rgba(164, 127, 243, 1)",
        "--sjs-primary-backcolor-light": "rgba(164, 127, 243, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(192, 165, 251, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.1),0px 8px 16px 0px rgba(0, 0, 0, 0.15),0px 2px 4px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.1),0px 0px 0px 0px rgba(0, 0, 0, 0.15),0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-medium": "inset 0px 0px 0px 1px rgba(255, 255, 255, 0.05),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.03),inset 0px 1px 4px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.03),inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-border-light": "rgba(54, 56, 69, 1)",
        "--sjs-border-default": "rgba(67, 69, 85, 1)",
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
};
/* harmony default export */ __webpack_exports__["default"] = (Theme);
var LayeredDarkPanelless = Theme;


/***/ })

/******/ });
});
//# sourceMappingURL=layered-dark-panelless.js.map