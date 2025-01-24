/*!
 * surveyjs - Survey JavaScript library v1.12.20
 * Copyright (c) 2015-2025 Devsoft Baltic OÜ  - http://surveyjs.io/
 * License: MIT (http://www.opensource.org/licenses/mit-license.php)
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define("index", [], factory);
	else if(typeof exports === 'object')
		exports["index"] = factory();
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
/******/ 	return __webpack_require__(__webpack_require__.s = "./packages/survey-core/src/themes/index.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./packages/survey-core/src/themes/borderless-dark-panelless.ts":
/*!**********************************************************************!*\
  !*** ./packages/survey-core/src/themes/borderless-dark-panelless.ts ***!
  \**********************************************************************/
/*! exports provided: default, BorderlessDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessDarkPanelless", function() { return BorderlessDarkPanelless; });
var Theme = {
    "themeName": "borderless",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(43, 48, 63, 1)",
        "--sjs-general-backcolor-dark": "rgba(50, 55, 72, 1)",
        "--sjs-general-backcolor-dim": "rgba(43, 48, 63, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(36, 41, 55, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(40, 46, 61, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(114, 120, 137, 1)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(114, 120, 137, 1)",
        "--sjs-primary-backcolor": "rgba(114, 187, 255, 1)",
        "--sjs-primary-backcolor-light": "rgba(114, 187, 255, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(151, 205, 255, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-border-light": "rgba(55, 62, 79, 1)",
        "--sjs-border-default": "rgba(65, 72, 90, 1)",
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
var BorderlessDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/borderless-dark.ts":
/*!************************************************************!*\
  !*** ./packages/survey-core/src/themes/borderless-dark.ts ***!
  \************************************************************/
/*! exports provided: default, BorderlessDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessDark", function() { return BorderlessDark; });
var Theme = {
    "themeName": "borderless",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(43, 48, 63, 1)",
        "--sjs-general-backcolor-dark": "rgba(50, 55, 72, 1)",
        "--sjs-general-backcolor-dim": "rgba(33, 37, 51, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(36, 41, 55, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(40, 46, 61, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(114, 120, 137, 1)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(114, 120, 137, 1)",
        "--sjs-primary-backcolor": "rgba(114, 187, 255, 1)",
        "--sjs-primary-backcolor-light": "rgba(114, 187, 255, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(151, 205, 255, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-border-light": "rgba(55, 62, 79, 1)",
        "--sjs-border-default": "rgba(65, 72, 90, 1)",
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
var BorderlessDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/borderless-light-panelless.ts":
/*!***********************************************************************!*\
  !*** ./packages/survey-core/src/themes/borderless-light-panelless.ts ***!
  \***********************************************************************/
/*! exports provided: default, BorderlessLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessLightPanelless", function() { return BorderlessLightPanelless; });
var Theme = {
    "themeName": "borderless",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(231, 240, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(220, 232, 252, 1)",
        "--sjs-general-backcolor-dim": "rgba(231, 240, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(220, 232, 252, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(133, 154, 186, 1)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(133, 154, 186, 1)",
        "--sjs-primary-backcolor": "rgba(35, 101, 200, 1)",
        "--sjs-primary-backcolor-light": "rgba(35, 101, 200, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(26, 86, 175, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-border-light": "rgba(220, 229, 241, 1)",
        "--sjs-border-default": "rgba(179, 200, 229, 1)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var BorderlessLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/borderless-light.ts":
/*!*************************************************************!*\
  !*** ./packages/survey-core/src/themes/borderless-light.ts ***!
  \*************************************************************/
/*! exports provided: default, BorderlessLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessLight", function() { return BorderlessLight; });
var Theme = {
    "themeName": "borderless",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(241, 246, 255, 1)",
        "--sjs-general-backcolor-dim": "rgba(231, 240, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(238, 245, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(223, 233, 250, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(133, 154, 186, 1)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(133, 154, 186, 1)",
        "--sjs-primary-backcolor": "rgba(35, 101, 200, 1)",
        "--sjs-primary-backcolor-light": "rgba(35, 101, 200, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(26, 86, 175, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-border-light": "rgba(220, 229, 241, 1)",
        "--sjs-border-default": "rgba(179, 200, 229, 1)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var BorderlessLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/contrast-dark-panelless.ts":
/*!********************************************************************!*\
  !*** ./packages/survey-core/src/themes/contrast-dark-panelless.ts ***!
  \********************************************************************/
/*! exports provided: default, ContrastDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastDarkPanelless", function() { return ContrastDarkPanelless; });
var Theme = {
    "themeName": "contrast",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dark": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(255, 255, 255, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 1)",
        "--sjs-primary-backcolor-dark": "rgba(53, 53, 53, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-large": "0px 6px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(0, 0, 0, 1),0px -2px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1),0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-border-light": "rgba(232, 192, 51, 1)",
        "--sjs-border-default": "rgba(0, 0, 0, 1)",
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
var ContrastDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/contrast-dark.ts":
/*!**********************************************************!*\
  !*** ./packages/survey-core/src/themes/contrast-dark.ts ***!
  \**********************************************************/
/*! exports provided: default, ContrastDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastDark", function() { return ContrastDark; });
var Theme = {
    "themeName": "contrast",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dark": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim": "rgba(0, 0, 0, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(255, 255, 255, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 1)",
        "--sjs-primary-backcolor": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 1)",
        "--sjs-primary-backcolor-dark": "rgba(53, 53, 53, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-large": "0px 6px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(0, 0, 0, 1),0px -2px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1),0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-border-light": "rgba(232, 192, 51, 1)",
        "--sjs-border-default": "rgba(0, 0, 0, 1)",
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
var ContrastDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/contrast-light-panelless.ts":
/*!*********************************************************************!*\
  !*** ./packages/survey-core/src/themes/contrast-light-panelless.ts ***!
  \*********************************************************************/
/*! exports provided: default, ContrastLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastLightPanelless", function() { return ContrastLightPanelless; });
var Theme = {
    "themeName": "contrast",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(255, 216, 77, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 216, 77, 1)",
        "--sjs-primary-backcolor-dark": "rgba(83, 83, 83, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-large": "0px 6px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(0, 0, 0, 1),0px -2px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1),0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.2)",
        "--sjs-border-default": "rgba(0, 0, 0, 1)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var ContrastLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/contrast-light.ts":
/*!***********************************************************!*\
  !*** ./packages/survey-core/src/themes/contrast-light.ts ***!
  \***********************************************************/
/*! exports provided: default, ContrastLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastLight", function() { return ContrastLight; });
var Theme = {
    "themeName": "contrast",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 216, 77, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(255, 216, 77, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 1)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor": "rgba(0, 0, 0, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 216, 77, 1)",
        "--sjs-primary-backcolor-dark": "rgba(83, 83, 83, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-large": "0px 6px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(0, 0, 0, 1),0px -2px 0px 2px rgba(0, 0, 0, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 1),0px 0px 0px 0px rgba(0, 0, 0, 1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.2)",
        "--sjs-border-default": "rgba(0, 0, 0, 1)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var ContrastLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/default-dark-panelless.ts":
/*!*******************************************************************!*\
  !*** ./packages/survey-core/src/themes/default-dark-panelless.ts ***!
  \*******************************************************************/
/*! exports provided: default, DefaultDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultDarkPanelless", function() { return DefaultDarkPanelless; });
var Theme = {
    "themeName": "default",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dark": "rgba(52, 52, 52, 1)",
        "--sjs-general-backcolor-dim": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(43, 43, 43, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(46, 46, 46, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.07)",
        "--sjs-primary-backcolor-dark": "rgba(255, 170, 24, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 1px 2px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "inset 0px 1px 2px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.08)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.12)",
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
var DefaultDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/default-dark.ts":
/*!*********************************************************!*\
  !*** ./packages/survey-core/src/themes/default-dark.ts ***!
  \*********************************************************/
/*! exports provided: default, DefaultDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultDark", function() { return DefaultDark; });
var Theme = {
    "themeName": "default",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dark": "rgba(52, 52, 52, 1)",
        "--sjs-general-backcolor-dim": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(43, 43, 43, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(46, 46, 46, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.07)",
        "--sjs-primary-backcolor-dark": "rgba(255, 170, 24, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 1px 2px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.35)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "inset 0px 1px 2px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.08)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.12)",
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
var DefaultDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/default-light-panelless.ts":
/*!********************************************************************!*\
  !*** ./packages/survey-core/src/themes/default-light-panelless.ts ***!
  \********************************************************************/
/*! exports provided: default, DefaultLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultLightPanelless", function() { return DefaultLightPanelless; });
var Theme = {
    "themeName": "default",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(249, 249, 249, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(243, 243, 243, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(25, 179, 148, 1)",
        "--sjs-primary-backcolor-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(20, 164, 139, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 1px 2px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 1px 2px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.09)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.16)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var DefaultLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/default-light.ts":
/*!**********************************************************!*\
  !*** ./packages/survey-core/src/themes/default-light.ts ***!
  \**********************************************************/
/*! exports provided: default, DefaultLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultLight", function() { return DefaultLight; });
var Theme = {
    "themeName": "default",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dim": "rgba(243, 243, 243, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(249, 249, 249, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(243, 243, 243, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(25, 179, 148, 1)",
        "--sjs-primary-backcolor-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(20, 164, 139, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 1px 2px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 1px 2px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.09)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.16)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var DefaultLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/doubleborder-dark-panelless.ts":
/*!************************************************************************!*\
  !*** ./packages/survey-core/src/themes/doubleborder-dark-panelless.ts ***!
  \************************************************************************/
/*! exports provided: default, DoubleBorderDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderDarkPanelless", function() { return DoubleBorderDarkPanelless; });
var Theme = {
    "themeName": "doubleborder",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(52, 52, 52, 1)",
        "--sjs-general-backcolor-dark": "rgba(58, 58, 58, 1)",
        "--sjs-general-backcolor-dim": "rgba(52, 52, 52, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(47, 47, 47, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(53, 53, 53, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(120, 156, 210, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(146, 181, 235, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(255, 255, 255, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(255, 255, 255, 0.1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.1)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.1)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.1)",
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
var DoubleBorderDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/doubleborder-dark.ts":
/*!**************************************************************!*\
  !*** ./packages/survey-core/src/themes/doubleborder-dark.ts ***!
  \**************************************************************/
/*! exports provided: default, DoubleBorderDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderDark", function() { return DoubleBorderDark; });
var Theme = {
    "themeName": "doubleborder",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(52, 52, 52, 1)",
        "--sjs-general-backcolor-dark": "rgba(58, 58, 58, 1)",
        "--sjs-general-backcolor-dim": "rgba(47, 47, 47, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(47, 47, 47, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(53, 53, 53, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(120, 156, 210, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(146, 181, 235, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(255, 255, 255, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(255, 255, 255, 0.1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.1)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.1)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.1)",
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
var DoubleBorderDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/doubleborder-light-panelless.ts":
/*!*************************************************************************!*\
  !*** ./packages/survey-core/src/themes/doubleborder-light-panelless.ts ***!
  \*************************************************************************/
/*! exports provided: default, DoubleBorderLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderLightPanelless", function() { return DoubleBorderLightPanelless; });
var Theme = {
    "themeName": "doubleborder",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(239, 239, 239, 1)",
        "--sjs-general-backcolor-dim": "rgba(245, 245, 245, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(237, 237, 237, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(76, 100, 137, 1)",
        "--sjs-primary-backcolor-light": "rgba(76, 100, 137, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(62, 83, 115, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(0, 0, 0, 0.07)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.07)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(0, 0, 0, 0.08),0px 2px 6px 0px rgba(0, 0, 0, 0.04)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.08)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.1)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.1)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var DoubleBorderLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/doubleborder-light.ts":
/*!***************************************************************!*\
  !*** ./packages/survey-core/src/themes/doubleborder-light.ts ***!
  \***************************************************************/
/*! exports provided: default, DoubleBorderLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderLight", function() { return DoubleBorderLight; });
var Theme = {
    "themeName": "doubleborder",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(239, 239, 239, 1)",
        "--sjs-general-backcolor-dim": "rgba(245, 245, 245, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(237, 237, 237, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(76, 100, 137, 1)",
        "--sjs-primary-backcolor-light": "rgba(76, 100, 137, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(62, 83, 115, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 2px rgba(0, 0, 0, 0.07)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.07)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(0, 0, 0, 0.08),0px 2px 6px 0px rgba(0, 0, 0, 0.04)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.08)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.1)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.1)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var DoubleBorderLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/flat-dark-panelless.ts":
/*!****************************************************************!*\
  !*** ./packages/survey-core/src/themes/flat-dark-panelless.ts ***!
  \****************************************************************/
/*! exports provided: default, FlatDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatDarkPanelless", function() { return FlatDarkPanelless; });
var Theme = {
    "themeName": "flat",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dark": "rgba(56, 56, 56, 1)",
        "--sjs-general-backcolor-dim": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(43, 43, 43, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(52, 52, 52, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(92, 214, 49, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.07)",
        "--sjs-primary-backcolor-dark": "rgba(130, 237, 92, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.09)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.09)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(255, 255, 255, 0.12),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.07)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.07)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.07)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.07)",
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
var FlatDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/flat-dark.ts":
/*!******************************************************!*\
  !*** ./packages/survey-core/src/themes/flat-dark.ts ***!
  \******************************************************/
/*! exports provided: default, FlatDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatDark", function() { return FlatDark; });
var Theme = {
    "themeName": "flat",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dark": "rgba(56, 56, 56, 1)",
        "--sjs-general-backcolor-dim": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(43, 43, 43, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(52, 52, 52, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(92, 214, 49, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.07)",
        "--sjs-primary-backcolor-dark": "rgba(130, 237, 92, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.09)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.09)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(255, 255, 255, 0.12),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.07)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.07)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.07)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.07)",
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
var FlatDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/flat-light-panelless.ts":
/*!*****************************************************************!*\
  !*** ./packages/survey-core/src/themes/flat-light-panelless.ts ***!
  \*****************************************************************/
/*! exports provided: default, FlatLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatLightPanelless", function() { return FlatLightPanelless; });
var Theme = {
    "themeName": "flat",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(246, 246, 246, 1)",
        "--sjs-general-backcolor-dark": "rgba(235, 235, 235, 1)",
        "--sjs-general-backcolor-dim": "rgba(246, 246, 246, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(235, 235, 235, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(85, 181, 52, 1)",
        "--sjs-primary-backcolor-light": "rgba(85, 181, 52, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(64, 159, 31, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(0, 0, 0, 0.1),0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(0, 0, 0, 0.12)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.12)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.12)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.12)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var FlatLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/flat-light.ts":
/*!*******************************************************!*\
  !*** ./packages/survey-core/src/themes/flat-light.ts ***!
  \*******************************************************/
/*! exports provided: default, FlatLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatLight", function() { return FlatLight; });
var Theme = {
    "themeName": "flat",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(246, 246, 246, 1)",
        "--sjs-general-backcolor-dark": "rgba(235, 235, 235, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(235, 235, 235, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(85, 181, 52, 1)",
        "--sjs-primary-backcolor-light": "rgba(85, 181, 52, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(64, 159, 31, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(0, 0, 0, 0.1),0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(0, 0, 0, 0.12)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.12)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.12)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.12)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var FlatLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/index.ts":
/*!**************************************************!*\
  !*** ./packages/survey-core/src/themes/index.ts ***!
  \**************************************************/
/*! exports provided: DefaultLight, DefaultDark, DefaultLightPanelless, DefaultDarkPanelless, SharpLight, SharpDark, SharpLightPanelless, SharpDarkPanelless, BorderlessLight, BorderlessDark, BorderlessLightPanelless, BorderlessDarkPanelless, FlatLight, FlatDark, FlatLightPanelless, FlatDarkPanelless, PlainLight, PlainDark, PlainLightPanelless, PlainDarkPanelless, DoubleBorderLight, DoubleBorderDark, DoubleBorderLightPanelless, DoubleBorderDarkPanelless, LayeredLight, LayeredDark, LayeredLightPanelless, LayeredDarkPanelless, SolidLight, SolidDark, SolidLightPanelless, SolidDarkPanelless, ThreeDimensionalLight, ThreeDimensionalDark, ThreeDimensionalLightPanelless, ThreeDimensionalDarkPanelless, ContrastLight, ContrastDark, ContrastLightPanelless, ContrastDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultLight", function() { return DefaultLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultDark", function() { return DefaultDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultLightPanelless", function() { return DefaultLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultDarkPanelless", function() { return DefaultDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpLight", function() { return SharpLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpDark", function() { return SharpDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpLightPanelless", function() { return SharpLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpDarkPanelless", function() { return SharpDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessLight", function() { return BorderlessLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessDark", function() { return BorderlessDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessLightPanelless", function() { return BorderlessLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BorderlessDarkPanelless", function() { return BorderlessDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatLight", function() { return FlatLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatDark", function() { return FlatDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatLightPanelless", function() { return FlatLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "FlatDarkPanelless", function() { return FlatDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainLight", function() { return PlainLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainDark", function() { return PlainDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainLightPanelless", function() { return PlainLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainDarkPanelless", function() { return PlainDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderLight", function() { return DoubleBorderLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderDark", function() { return DoubleBorderDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderLightPanelless", function() { return DoubleBorderLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DoubleBorderDarkPanelless", function() { return DoubleBorderDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredLight", function() { return LayeredLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredDark", function() { return LayeredDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredLightPanelless", function() { return LayeredLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredDarkPanelless", function() { return LayeredDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidLight", function() { return SolidLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidDark", function() { return SolidDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidLightPanelless", function() { return SolidLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidDarkPanelless", function() { return SolidDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalLight", function() { return ThreeDimensionalLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalDark", function() { return ThreeDimensionalDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalLightPanelless", function() { return ThreeDimensionalLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalDarkPanelless", function() { return ThreeDimensionalDarkPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastLight", function() { return ContrastLight; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastDark", function() { return ContrastDark; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastLightPanelless", function() { return ContrastLightPanelless; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ContrastDarkPanelless", function() { return ContrastDarkPanelless; });
/* harmony import */ var _default_light__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./default-light */ "./packages/survey-core/src/themes/default-light.ts");
/* harmony import */ var _default_dark__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./default-dark */ "./packages/survey-core/src/themes/default-dark.ts");
/* harmony import */ var _default_light_panelless__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./default-light-panelless */ "./packages/survey-core/src/themes/default-light-panelless.ts");
/* harmony import */ var _default_dark_panelless__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./default-dark-panelless */ "./packages/survey-core/src/themes/default-dark-panelless.ts");
/* harmony import */ var _sharp_light__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./sharp-light */ "./packages/survey-core/src/themes/sharp-light.ts");
/* harmony import */ var _sharp_dark__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./sharp-dark */ "./packages/survey-core/src/themes/sharp-dark.ts");
/* harmony import */ var _sharp_light_panelless__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./sharp-light-panelless */ "./packages/survey-core/src/themes/sharp-light-panelless.ts");
/* harmony import */ var _sharp_dark_panelless__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./sharp-dark-panelless */ "./packages/survey-core/src/themes/sharp-dark-panelless.ts");
/* harmony import */ var _borderless_light__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./borderless-light */ "./packages/survey-core/src/themes/borderless-light.ts");
/* harmony import */ var _borderless_dark__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./borderless-dark */ "./packages/survey-core/src/themes/borderless-dark.ts");
/* harmony import */ var _borderless_light_panelless__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./borderless-light-panelless */ "./packages/survey-core/src/themes/borderless-light-panelless.ts");
/* harmony import */ var _borderless_dark_panelless__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./borderless-dark-panelless */ "./packages/survey-core/src/themes/borderless-dark-panelless.ts");
/* harmony import */ var _flat_light__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./flat-light */ "./packages/survey-core/src/themes/flat-light.ts");
/* harmony import */ var _flat_dark__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./flat-dark */ "./packages/survey-core/src/themes/flat-dark.ts");
/* harmony import */ var _flat_light_panelless__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./flat-light-panelless */ "./packages/survey-core/src/themes/flat-light-panelless.ts");
/* harmony import */ var _flat_dark_panelless__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./flat-dark-panelless */ "./packages/survey-core/src/themes/flat-dark-panelless.ts");
/* harmony import */ var _plain_light__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./plain-light */ "./packages/survey-core/src/themes/plain-light.ts");
/* harmony import */ var _plain_dark__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./plain-dark */ "./packages/survey-core/src/themes/plain-dark.ts");
/* harmony import */ var _plain_light_panelless__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./plain-light-panelless */ "./packages/survey-core/src/themes/plain-light-panelless.ts");
/* harmony import */ var _plain_dark_panelless__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./plain-dark-panelless */ "./packages/survey-core/src/themes/plain-dark-panelless.ts");
/* harmony import */ var _doubleborder_light__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./doubleborder-light */ "./packages/survey-core/src/themes/doubleborder-light.ts");
/* harmony import */ var _doubleborder_dark__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./doubleborder-dark */ "./packages/survey-core/src/themes/doubleborder-dark.ts");
/* harmony import */ var _doubleborder_light_panelless__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./doubleborder-light-panelless */ "./packages/survey-core/src/themes/doubleborder-light-panelless.ts");
/* harmony import */ var _doubleborder_dark_panelless__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./doubleborder-dark-panelless */ "./packages/survey-core/src/themes/doubleborder-dark-panelless.ts");
/* harmony import */ var _layered_light__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./layered-light */ "./packages/survey-core/src/themes/layered-light.ts");
/* harmony import */ var _layered_dark__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./layered-dark */ "./packages/survey-core/src/themes/layered-dark.ts");
/* harmony import */ var _layered_light_panelless__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./layered-light-panelless */ "./packages/survey-core/src/themes/layered-light-panelless.ts");
/* harmony import */ var _layered_dark_panelless__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./layered-dark-panelless */ "./packages/survey-core/src/themes/layered-dark-panelless.ts");
/* harmony import */ var _solid_light__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./solid-light */ "./packages/survey-core/src/themes/solid-light.ts");
/* harmony import */ var _solid_dark__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./solid-dark */ "./packages/survey-core/src/themes/solid-dark.ts");
/* harmony import */ var _solid_light_panelless__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./solid-light-panelless */ "./packages/survey-core/src/themes/solid-light-panelless.ts");
/* harmony import */ var _solid_dark_panelless__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./solid-dark-panelless */ "./packages/survey-core/src/themes/solid-dark-panelless.ts");
/* harmony import */ var _threedimensional_light__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ./threedimensional-light */ "./packages/survey-core/src/themes/threedimensional-light.ts");
/* harmony import */ var _threedimensional_dark__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ./threedimensional-dark */ "./packages/survey-core/src/themes/threedimensional-dark.ts");
/* harmony import */ var _threedimensional_light_panelless__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./threedimensional-light-panelless */ "./packages/survey-core/src/themes/threedimensional-light-panelless.ts");
/* harmony import */ var _threedimensional_dark_panelless__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ./threedimensional-dark-panelless */ "./packages/survey-core/src/themes/threedimensional-dark-panelless.ts");
/* harmony import */ var _contrast_light__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! ./contrast-light */ "./packages/survey-core/src/themes/contrast-light.ts");
/* harmony import */ var _contrast_dark__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! ./contrast-dark */ "./packages/survey-core/src/themes/contrast-dark.ts");
/* harmony import */ var _contrast_light_panelless__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! ./contrast-light-panelless */ "./packages/survey-core/src/themes/contrast-light-panelless.ts");
/* harmony import */ var _contrast_dark_panelless__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! ./contrast-dark-panelless */ "./packages/survey-core/src/themes/contrast-dark-panelless.ts");

var DefaultLight = _default_light__WEBPACK_IMPORTED_MODULE_0__["default"];

var DefaultDark = _default_dark__WEBPACK_IMPORTED_MODULE_1__["default"];

var DefaultLightPanelless = _default_light_panelless__WEBPACK_IMPORTED_MODULE_2__["default"];

var DefaultDarkPanelless = _default_dark_panelless__WEBPACK_IMPORTED_MODULE_3__["default"];

var SharpLight = _sharp_light__WEBPACK_IMPORTED_MODULE_4__["default"];

var SharpDark = _sharp_dark__WEBPACK_IMPORTED_MODULE_5__["default"];

var SharpLightPanelless = _sharp_light_panelless__WEBPACK_IMPORTED_MODULE_6__["default"];

var SharpDarkPanelless = _sharp_dark_panelless__WEBPACK_IMPORTED_MODULE_7__["default"];

var BorderlessLight = _borderless_light__WEBPACK_IMPORTED_MODULE_8__["default"];

var BorderlessDark = _borderless_dark__WEBPACK_IMPORTED_MODULE_9__["default"];

var BorderlessLightPanelless = _borderless_light_panelless__WEBPACK_IMPORTED_MODULE_10__["default"];

var BorderlessDarkPanelless = _borderless_dark_panelless__WEBPACK_IMPORTED_MODULE_11__["default"];

var FlatLight = _flat_light__WEBPACK_IMPORTED_MODULE_12__["default"];

var FlatDark = _flat_dark__WEBPACK_IMPORTED_MODULE_13__["default"];

var FlatLightPanelless = _flat_light_panelless__WEBPACK_IMPORTED_MODULE_14__["default"];

var FlatDarkPanelless = _flat_dark_panelless__WEBPACK_IMPORTED_MODULE_15__["default"];

var PlainLight = _plain_light__WEBPACK_IMPORTED_MODULE_16__["default"];

var PlainDark = _plain_dark__WEBPACK_IMPORTED_MODULE_17__["default"];

var PlainLightPanelless = _plain_light_panelless__WEBPACK_IMPORTED_MODULE_18__["default"];

var PlainDarkPanelless = _plain_dark_panelless__WEBPACK_IMPORTED_MODULE_19__["default"];

var DoubleBorderLight = _doubleborder_light__WEBPACK_IMPORTED_MODULE_20__["default"];

var DoubleBorderDark = _doubleborder_dark__WEBPACK_IMPORTED_MODULE_21__["default"];

var DoubleBorderLightPanelless = _doubleborder_light_panelless__WEBPACK_IMPORTED_MODULE_22__["default"];

var DoubleBorderDarkPanelless = _doubleborder_dark_panelless__WEBPACK_IMPORTED_MODULE_23__["default"];

var LayeredLight = _layered_light__WEBPACK_IMPORTED_MODULE_24__["default"];

var LayeredDark = _layered_dark__WEBPACK_IMPORTED_MODULE_25__["default"];

var LayeredLightPanelless = _layered_light_panelless__WEBPACK_IMPORTED_MODULE_26__["default"];

var LayeredDarkPanelless = _layered_dark_panelless__WEBPACK_IMPORTED_MODULE_27__["default"];

var SolidLight = _solid_light__WEBPACK_IMPORTED_MODULE_28__["default"];

var SolidDark = _solid_dark__WEBPACK_IMPORTED_MODULE_29__["default"];

var SolidLightPanelless = _solid_light_panelless__WEBPACK_IMPORTED_MODULE_30__["default"];

var SolidDarkPanelless = _solid_dark_panelless__WEBPACK_IMPORTED_MODULE_31__["default"];

var ThreeDimensionalLight = _threedimensional_light__WEBPACK_IMPORTED_MODULE_32__["default"];

var ThreeDimensionalDark = _threedimensional_dark__WEBPACK_IMPORTED_MODULE_33__["default"];

var ThreeDimensionalLightPanelless = _threedimensional_light_panelless__WEBPACK_IMPORTED_MODULE_34__["default"];

var ThreeDimensionalDarkPanelless = _threedimensional_dark_panelless__WEBPACK_IMPORTED_MODULE_35__["default"];

var ContrastLight = _contrast_light__WEBPACK_IMPORTED_MODULE_36__["default"];

var ContrastDark = _contrast_dark__WEBPACK_IMPORTED_MODULE_37__["default"];

var ContrastLightPanelless = _contrast_light_panelless__WEBPACK_IMPORTED_MODULE_38__["default"];

var ContrastDarkPanelless = _contrast_dark_panelless__WEBPACK_IMPORTED_MODULE_39__["default"];


/***/ }),

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


/***/ }),

/***/ "./packages/survey-core/src/themes/layered-dark.ts":
/*!*********************************************************!*\
  !*** ./packages/survey-core/src/themes/layered-dark.ts ***!
  \*********************************************************/
/*! exports provided: default, LayeredDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredDark", function() { return LayeredDark; });
var Theme = {
    "themeName": "layered",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(39, 40, 50, 1)",
        "--sjs-general-backcolor-dark": "rgba(46, 47, 58, 1)",
        "--sjs-general-backcolor-dim": "rgba(30, 31, 40, 1)",
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
var LayeredDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/layered-light-panelless.ts":
/*!********************************************************************!*\
  !*** ./packages/survey-core/src/themes/layered-light-panelless.ts ***!
  \********************************************************************/
/*! exports provided: default, LayeredLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredLightPanelless", function() { return LayeredLightPanelless; });
var Theme = {
    "themeName": "layered",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(244, 244, 244, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(241, 237, 248, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(225, 220, 233, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.5)",
        "--sjs-primary-backcolor": "rgba(122, 100, 168, 1)",
        "--sjs-primary-backcolor-light": "rgba(122, 100, 168, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(105, 84, 152, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.05),0px 8px 16px 0px rgba(0, 0, 0, 0.1),0px 2px 4px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.05),0px 0px 0px 0px rgba(0, 0, 0, 0.1),0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 0px 0px 1px rgba(0, 0, 0, 0.05),inset 0px 1px 4px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.05),inset 0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.09)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.16)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var LayeredLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/layered-light.ts":
/*!**********************************************************!*\
  !*** ./packages/survey-core/src/themes/layered-light.ts ***!
  \**********************************************************/
/*! exports provided: default, LayeredLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "LayeredLight", function() { return LayeredLight; });
var Theme = {
    "themeName": "layered",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(244, 244, 244, 1)",
        "--sjs-general-backcolor-dim": "rgba(216, 207, 236, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(241, 237, 248, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(225, 220, 233, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.5)",
        "--sjs-primary-backcolor": "rgba(122, 100, 168, 1)",
        "--sjs-primary-backcolor-light": "rgba(122, 100, 168, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(105, 84, 152, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.05),0px 8px 16px 0px rgba(0, 0, 0, 0.1),0px 2px 4px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.05),0px 0px 0px 0px rgba(0, 0, 0, 0.1),0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 0px 0px 1px rgba(0, 0, 0, 0.05),inset 0px 1px 4px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.05),inset 0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.09)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.16)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var LayeredLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/plain-dark-panelless.ts":
/*!*****************************************************************!*\
  !*** ./packages/survey-core/src/themes/plain-dark-panelless.ts ***!
  \*****************************************************************/
/*! exports provided: default, PlainDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainDarkPanelless", function() { return PlainDarkPanelless; });
var Theme = {
    "themeName": "plain",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dark": "rgba(47, 47, 47, 1)",
        "--sjs-general-backcolor-dim": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(47, 47, 47, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(85, 171, 250, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.12)",
        "--sjs-primary-backcolor-dark": "rgba(126, 193, 255, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(255, 255, 255, 0.12),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.12)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.12)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.12)",
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
var PlainDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/plain-dark.ts":
/*!*******************************************************!*\
  !*** ./packages/survey-core/src/themes/plain-dark.ts ***!
  \*******************************************************/
/*! exports provided: default, PlainDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainDark", function() { return PlainDark; });
var Theme = {
    "themeName": "plain",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dark": "rgba(47, 47, 47, 1)",
        "--sjs-general-backcolor-dim": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(36, 36, 36, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(47, 47, 47, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(85, 171, 250, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.12)",
        "--sjs-primary-backcolor-dark": "rgba(126, 193, 255, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(255, 255, 255, 0.12),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.12)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.12)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.12)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.12)",
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
var PlainDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/plain-light-panelless.ts":
/*!******************************************************************!*\
  !*** ./packages/survey-core/src/themes/plain-light-panelless.ts ***!
  \******************************************************************/
/*! exports provided: default, PlainLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainLightPanelless", function() { return PlainLightPanelless; });
var Theme = {
    "themeName": "plain",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(243, 243, 243, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(37, 137, 229, 1)",
        "--sjs-primary-backcolor-light": "rgba(37, 137, 229, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(21, 119, 209, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.05)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.15)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.15)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var PlainLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/plain-light.ts":
/*!********************************************************!*\
  !*** ./packages/survey-core/src/themes/plain-light.ts ***!
  \********************************************************/
/*! exports provided: default, PlainLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "PlainLight", function() { return PlainLight; });
var Theme = {
    "themeName": "plain",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(243, 243, 243, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(37, 137, 229, 1)",
        "--sjs-primary-backcolor-light": "rgba(37, 137, 229, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(21, 119, 209, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.05)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(0, 0, 0, 0.15)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.15)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.15)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.15)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var PlainLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/sharp-dark-panelless.ts":
/*!*****************************************************************!*\
  !*** ./packages/survey-core/src/themes/sharp-dark-panelless.ts ***!
  \*****************************************************************/
/*! exports provided: default, SharpDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpDarkPanelless", function() { return SharpDarkPanelless; });
var Theme = {
    "themeName": "sharp",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dark": "rgba(58, 58, 58, 1)",
        "--sjs-general-backcolor-dim": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(33, 33, 33, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(47, 47, 47, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.5)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.8)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.55)",
        "--sjs-primary-backcolor": "rgba(16, 226, 255, 1)",
        "--sjs-primary-backcolor-light": "rgba(0, 0, 0, 0.35)",
        "--sjs-primary-backcolor-dark": "rgba(129, 240, 255, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.28)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.28)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(255, 255, 255, 0.25),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.22)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.22)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.22)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.22)",
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
var SharpDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/sharp-dark.ts":
/*!*******************************************************!*\
  !*** ./packages/survey-core/src/themes/sharp-dark.ts ***!
  \*******************************************************/
/*! exports provided: default, SharpDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpDark", function() { return SharpDark; });
var Theme = {
    "themeName": "sharp",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dark": "rgba(58, 58, 58, 1)",
        "--sjs-general-backcolor-dim": "rgba(27, 27, 27, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(33, 33, 33, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(47, 47, 47, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.5)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.8)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.55)",
        "--sjs-primary-backcolor": "rgba(16, 226, 255, 1)",
        "--sjs-primary-backcolor-light": "rgba(0, 0, 0, 0.35)",
        "--sjs-primary-backcolor-dark": "rgba(129, 240, 255, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(255, 255, 255, 0.28)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.28)",
        "--sjs-shadow-medium": "0px 0px 0px 1px rgba(255, 255, 255, 0.25),0px 2px 6px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(255, 255, 255, 0.22)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(255, 255, 255, 0.22)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.22)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.22)",
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
var SharpDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/sharp-light-panelless.ts":
/*!******************************************************************!*\
  !*** ./packages/survey-core/src/themes/sharp-light-panelless.ts ***!
  \******************************************************************/
/*! exports provided: default, SharpLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpLightPanelless", function() { return SharpLightPanelless; });
var Theme = {
    "themeName": "sharp",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(228, 228, 228, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(238, 238, 238, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(220, 220, 220, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.6)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.6)",
        "--sjs-primary-backcolor": "rgba(103, 58, 176, 1)",
        "--sjs-primary-backcolor-light": "rgba(103, 58, 176, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(69, 24, 142, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(0, 0, 0, 0.25)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.25)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.25)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.25)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var SharpLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/sharp-light.ts":
/*!********************************************************!*\
  !*** ./packages/survey-core/src/themes/sharp-light.ts ***!
  \********************************************************/
/*! exports provided: default, SharpLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SharpLight", function() { return SharpLight; });
var Theme = {
    "themeName": "sharp",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(228, 228, 228, 1)",
        "--sjs-general-backcolor-dim": "rgba(224, 224, 224, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(238, 238, 238, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(220, 220, 220, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.6)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.6)",
        "--sjs-primary-backcolor": "rgba(103, 58, 176, 1)",
        "--sjs-primary-backcolor-light": "rgba(103, 58, 176, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(69, 24, 142, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 0px 0px 1px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 1px rgba(0, 0, 0, 0.25)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.25)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.25)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.25)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var SharpLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/solid-dark-panelless.ts":
/*!*****************************************************************!*\
  !*** ./packages/survey-core/src/themes/solid-dark-panelless.ts ***!
  \*****************************************************************/
/*! exports provided: default, SolidDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidDarkPanelless", function() { return SolidDarkPanelless; });
var Theme = {
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
};
/* harmony default export */ __webpack_exports__["default"] = (Theme);
var SolidDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/solid-dark.ts":
/*!*******************************************************!*\
  !*** ./packages/survey-core/src/themes/solid-dark.ts ***!
  \*******************************************************/
/*! exports provided: default, SolidDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidDark", function() { return SolidDark; });
var Theme = {
    "themeName": "solid",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(53, 55, 63, 1)",
        "--sjs-general-backcolor-dark": "rgba(60, 63, 74, 1)",
        "--sjs-general-backcolor-dim": "rgba(38, 40, 47, 1)",
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
};
/* harmony default export */ __webpack_exports__["default"] = (Theme);
var SolidDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/solid-light-panelless.ts":
/*!******************************************************************!*\
  !*** ./packages/survey-core/src/themes/solid-light-panelless.ts ***!
  \******************************************************************/
/*! exports provided: default, SolidLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidLightPanelless", function() { return SolidLightPanelless; });
var Theme = {
    "themeName": "solid",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(243, 243, 243, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(245, 245, 245, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(234, 234, 234, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.43)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(46, 172, 180, 1)",
        "--sjs-primary-backcolor-light": "rgba(46, 172, 180, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(23, 156, 165, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 2px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.09)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.16)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var SolidLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/solid-light.ts":
/*!********************************************************!*\
  !*** ./packages/survey-core/src/themes/solid-light.ts ***!
  \********************************************************/
/*! exports provided: default, SolidLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SolidLight", function() { return SolidLight; });
var Theme = {
    "themeName": "solid",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dark": "rgba(243, 243, 243, 1)",
        "--sjs-general-backcolor-dim": "rgba(46, 172, 180, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(245, 245, 245, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(234, 234, 234, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.43)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.8)",
        "--sjs-primary-backcolor": "rgba(46, 172, 180, 1)",
        "--sjs-primary-backcolor-light": "rgba(46, 172, 180, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(23, 156, 165, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(0, 0, 0, 0.2)",
        "--sjs-shadow-medium": "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-large": "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "inset 0px 2px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.09)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.16)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var SolidLight = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/threedimensional-dark-panelless.ts":
/*!****************************************************************************!*\
  !*** ./packages/survey-core/src/themes/threedimensional-dark-panelless.ts ***!
  \****************************************************************************/
/*! exports provided: default, ThreeDimensionalDarkPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalDarkPanelless", function() { return ThreeDimensionalDarkPanelless; });
var Theme = {
    "themeName": "threedimensional",
    "colorPalette": "dark",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(38, 38, 38, 1)",
        "--sjs-general-backcolor-dark": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim": "rgba(28, 28, 28, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(58, 58, 58, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(243, 87, 134, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.05)",
        "--sjs-primary-backcolor-dark": "rgba(250, 118, 157, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 2px rgba(64, 64, 64, 1),0px 0px 0px 2px rgba(64, 64, 64, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(64, 64, 64, 1),0px 0px 0px 0px rgba(64, 64, 64, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(64, 64, 64, 1),0px 8px 0px 2px rgba(64, 64, 64, 1)",
        "--sjs-shadow-large": "0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(64, 64, 64, 1),0px 2px 0px 2px rgba(64, 64, 64, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(64, 64, 64, 1),0px 0px 0px 0px rgba(64, 64, 64, 1)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.12)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.12)",
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
var ThreeDimensionalDarkPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/threedimensional-dark.ts":
/*!******************************************************************!*\
  !*** ./packages/survey-core/src/themes/threedimensional-dark.ts ***!
  \******************************************************************/
/*! exports provided: default, ThreeDimensionalDark */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalDark", function() { return ThreeDimensionalDark; });
var Theme = {
    "themeName": "threedimensional",
    "colorPalette": "dark",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(38, 38, 38, 1)",
        "--sjs-general-backcolor-dark": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim": "rgba(28, 28, 28, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(48, 48, 48, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(58, 58, 58, 1)",
        "--sjs-general-forecolor": "rgba(255, 255, 255, 0.78)",
        "--sjs-general-forecolor-light": "rgba(255, 255, 255, 0.42)",
        "--sjs-general-dim-forecolor": "rgba(255, 255, 255, 0.79)",
        "--sjs-general-dim-forecolor-light": "rgba(255, 255, 255, 0.45)",
        "--sjs-primary-backcolor": "rgba(243, 87, 134, 1)",
        "--sjs-primary-backcolor-light": "rgba(255, 255, 255, 0.05)",
        "--sjs-primary-backcolor-dark": "rgba(250, 118, 157, 1)",
        "--sjs-primary-forecolor": "rgba(32, 32, 32, 1)",
        "--sjs-primary-forecolor-light": "rgba(32, 32, 32, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(48, 48, 48, 1)",
        "--sjs-secondary-forecolor-light": "rgba(48, 48, 48, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 2px rgba(64, 64, 64, 1),0px 0px 0px 2px rgba(64, 64, 64, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(64, 64, 64, 1),0px 0px 0px 0px rgba(64, 64, 64, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(64, 64, 64, 1),0px 8px 0px 2px rgba(64, 64, 64, 1)",
        "--sjs-shadow-large": "0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(64, 64, 64, 1),0px 2px 0px 2px rgba(64, 64, 64, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(64, 64, 64, 1),0px 0px 0px 0px rgba(64, 64, 64, 1)",
        "--sjs-border-light": "rgba(255, 255, 255, 0.12)",
        "--sjs-border-default": "rgba(255, 255, 255, 0.12)",
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
var ThreeDimensionalDark = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/threedimensional-light-panelless.ts":
/*!*****************************************************************************!*\
  !*** ./packages/survey-core/src/themes/threedimensional-light-panelless.ts ***!
  \*****************************************************************************/
/*! exports provided: default, ThreeDimensionalLightPanelless */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalLightPanelless", function() { return ThreeDimensionalLightPanelless; });
var Theme = {
    "themeName": "threedimensional",
    "colorPalette": "light",
    "isPanelless": true,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dark": "rgba(242, 242, 242, 1)",
        "--sjs-general-backcolor-dim": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(242, 242, 242, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(217, 74, 100, 1)",
        "--sjs-primary-backcolor-light": "rgba(217, 74, 100, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(189, 61, 84, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 2px rgba(228, 228, 228, 1),0px 0px 0px 2px rgba(228, 228, 228, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(228, 228, 228, 1),0px 0px 0px 0px rgba(228, 228, 228, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(228, 228, 228, 1),0px 8px 0px 2px rgba(228, 228, 228, 1)",
        "--sjs-shadow-large": "0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(228, 228, 228, 1),0px -2px 0px 2px rgba(228, 228, 228, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(228, 228, 228, 1),0px 0px 0px 0px rgba(228, 228, 228, 1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.08)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.08)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var ThreeDimensionalLightPanelless = Theme;


/***/ }),

/***/ "./packages/survey-core/src/themes/threedimensional-light.ts":
/*!*******************************************************************!*\
  !*** ./packages/survey-core/src/themes/threedimensional-light.ts ***!
  \*******************************************************************/
/*! exports provided: default, ThreeDimensionalLight */
/*! ModuleConcatenation bailout: Module is an entry point */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ThreeDimensionalLight", function() { return ThreeDimensionalLight; });
var Theme = {
    "themeName": "threedimensional",
    "colorPalette": "light",
    "isPanelless": false,
    "cssVariables": {
        "--sjs-general-backcolor": "rgba(248, 248, 248, 1)",
        "--sjs-general-backcolor-dark": "rgba(242, 242, 242, 1)",
        "--sjs-general-backcolor-dim": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-light": "rgba(255, 255, 255, 1)",
        "--sjs-general-backcolor-dim-dark": "rgba(242, 242, 242, 1)",
        "--sjs-general-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-general-dim-forecolor": "rgba(0, 0, 0, 0.91)",
        "--sjs-general-dim-forecolor-light": "rgba(0, 0, 0, 0.45)",
        "--sjs-primary-backcolor": "rgba(217, 74, 100, 1)",
        "--sjs-primary-backcolor-light": "rgba(217, 74, 100, 0.1)",
        "--sjs-primary-backcolor-dark": "rgba(189, 61, 84, 1)",
        "--sjs-primary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-base-unit": "8px",
        "--sjs-corner-radius": "4px",
        "--sjs-secondary-backcolor": "rgba(255, 152, 20, 1)",
        "--sjs-secondary-backcolor-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-secondary-backcolor-semi-light": "rgba(255, 152, 20, 0.25)",
        "--sjs-secondary-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-secondary-forecolor-light": "rgba(255, 255, 255, 0.25)",
        "--sjs-shadow-small": "0px 2px 0px 2px rgba(228, 228, 228, 1),0px 0px 0px 2px rgba(228, 228, 228, 1)",
        "--sjs-shadow-small-reset": "0px 0px 0px 0px rgba(228, 228, 228, 1),0px 0px 0px 0px rgba(228, 228, 228, 1)",
        "--sjs-shadow-medium": "0px 0px 0px 2px rgba(228, 228, 228, 1),0px 8px 0px 2px rgba(228, 228, 228, 1)",
        "--sjs-shadow-large": "0px 0px 0px 0px rgba(0, 0, 0, 0.1)",
        "--sjs-shadow-inner": "0px 0px 0px 2px rgba(228, 228, 228, 1),0px -2px 0px 2px rgba(228, 228, 228, 1)",
        "--sjs-shadow-inner-reset": "0px 0px 0px 0px rgba(228, 228, 228, 1),0px 0px 0px 0px rgba(228, 228, 228, 1)",
        "--sjs-border-light": "rgba(0, 0, 0, 0.08)",
        "--sjs-border-default": "rgba(0, 0, 0, 0.08)",
        "--sjs-border-inside": "rgba(0, 0, 0, 0.16)",
        "--sjs-special-red": "rgba(229, 10, 62, 1)",
        "--sjs-special-red-light": "rgba(229, 10, 62, 0.1)",
        "--sjs-special-red-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-green": "rgba(25, 179, 148, 1)",
        "--sjs-special-green-light": "rgba(25, 179, 148, 0.1)",
        "--sjs-special-green-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-blue": "rgba(67, 127, 217, 1)",
        "--sjs-special-blue-light": "rgba(67, 127, 217, 0.1)",
        "--sjs-special-blue-forecolor": "rgba(255, 255, 255, 1)",
        "--sjs-special-yellow": "rgba(255, 152, 20, 1)",
        "--sjs-special-yellow-light": "rgba(255, 152, 20, 0.1)",
        "--sjs-special-yellow-forecolor": "rgba(255, 255, 255, 1)",
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
var ThreeDimensionalLight = Theme;


/***/ })

/******/ });
});
//# sourceMappingURL=index.js.map