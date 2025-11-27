/*!
 * surveyjs - Survey JavaScript library v2.4.0
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
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/themes/borderless-dark-panelless.ts":
/*!*************************************************!*\
  !*** ./src/themes/borderless-dark-panelless.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/borderless-dark.ts":
/*!***************************************!*\
  !*** ./src/themes/borderless-dark.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/borderless-light-panelless.ts":
/*!**************************************************!*\
  !*** ./src/themes/borderless-light-panelless.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/borderless-light.ts":
/*!****************************************!*\
  !*** ./src/themes/borderless-light.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/contrast-dark-panelless.ts":
/*!***********************************************!*\
  !*** ./src/themes/contrast-dark-panelless.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
        "--sjs-special-red": "rgba(156, 2, 39, 1)",
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


/***/ }),

/***/ "./src/themes/contrast-dark.ts":
/*!*************************************!*\
  !*** ./src/themes/contrast-dark.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
        "--sjs-special-red": "rgba(156, 2, 39, 1)",
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


/***/ }),

/***/ "./src/themes/contrast-light-panelless.ts":
/*!************************************************!*\
  !*** ./src/themes/contrast-light-panelless.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
        "--sjs-special-red": "rgba(156, 2, 39, 1)",
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
});


/***/ }),

/***/ "./src/themes/contrast-light.ts":
/*!**************************************!*\
  !*** ./src/themes/contrast-light.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
        "--sjs-special-red": "rgba(156, 2, 39, 1)",
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
});


/***/ }),

/***/ "./src/themes/default-dark-panelless.ts":
/*!**********************************************!*\
  !*** ./src/themes/default-dark-panelless.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/default-dark.ts":
/*!************************************!*\
  !*** ./src/themes/default-dark.ts ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/default-light-panelless.ts":
/*!***********************************************!*\
  !*** ./src/themes/default-light-panelless.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/default-light.ts":
/*!*************************************!*\
  !*** ./src/themes/default-light.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/doubleborder-dark-panelless.ts":
/*!***************************************************!*\
  !*** ./src/themes/doubleborder-dark-panelless.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/doubleborder-dark.ts":
/*!*****************************************!*\
  !*** ./src/themes/doubleborder-dark.ts ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/doubleborder-light-panelless.ts":
/*!****************************************************!*\
  !*** ./src/themes/doubleborder-light-panelless.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/doubleborder-light.ts":
/*!******************************************!*\
  !*** ./src/themes/doubleborder-light.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/flat-dark-panelless.ts":
/*!*******************************************!*\
  !*** ./src/themes/flat-dark-panelless.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/flat-dark.ts":
/*!*********************************!*\
  !*** ./src/themes/flat-dark.ts ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/flat-light-panelless.ts":
/*!********************************************!*\
  !*** ./src/themes/flat-light-panelless.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/flat-light.ts":
/*!**********************************!*\
  !*** ./src/themes/flat-light.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/layered-dark-panelless.ts":
/*!**********************************************!*\
  !*** ./src/themes/layered-dark-panelless.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/layered-dark.ts":
/*!************************************!*\
  !*** ./src/themes/layered-dark.ts ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/layered-light-panelless.ts":
/*!***********************************************!*\
  !*** ./src/themes/layered-light-panelless.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/layered-light.ts":
/*!*************************************!*\
  !*** ./src/themes/layered-light.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/plain-dark-panelless.ts":
/*!********************************************!*\
  !*** ./src/themes/plain-dark-panelless.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/plain-dark.ts":
/*!**********************************!*\
  !*** ./src/themes/plain-dark.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/plain-light-panelless.ts":
/*!*********************************************!*\
  !*** ./src/themes/plain-light-panelless.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/plain-light.ts":
/*!***********************************!*\
  !*** ./src/themes/plain-light.ts ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/sharp-dark-panelless.ts":
/*!********************************************!*\
  !*** ./src/themes/sharp-dark-panelless.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/sharp-dark.ts":
/*!**********************************!*\
  !*** ./src/themes/sharp-dark.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/sharp-light-panelless.ts":
/*!*********************************************!*\
  !*** ./src/themes/sharp-light-panelless.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/sharp-light.ts":
/*!***********************************!*\
  !*** ./src/themes/sharp-light.ts ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/solid-dark-panelless.ts":
/*!********************************************!*\
  !*** ./src/themes/solid-dark-panelless.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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


/***/ }),

/***/ "./src/themes/solid-dark.ts":
/*!**********************************!*\
  !*** ./src/themes/solid-dark.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/solid-light-panelless.ts":
/*!*********************************************!*\
  !*** ./src/themes/solid-light-panelless.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/solid-light.ts":
/*!***********************************!*\
  !*** ./src/themes/solid-light.ts ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/threedimensional-dark-panelless.ts":
/*!*******************************************************!*\
  !*** ./src/themes/threedimensional-dark-panelless.ts ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/threedimensional-dark.ts":
/*!*********************************************!*\
  !*** ./src/themes/threedimensional-dark.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/threedimensional-light-panelless.ts":
/*!********************************************************!*\
  !*** ./src/themes/threedimensional-light-panelless.ts ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ }),

/***/ "./src/themes/threedimensional-light.ts":
/*!**********************************************!*\
  !*** ./src/themes/threedimensional-light.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
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
});


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
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
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*****************************!*\
  !*** ./src/themes/index.ts ***!
  \*****************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BorderlessDark: () => (/* reexport safe */ _borderless_dark__WEBPACK_IMPORTED_MODULE_9__["default"]),
/* harmony export */   BorderlessDarkPanelless: () => (/* reexport safe */ _borderless_dark_panelless__WEBPACK_IMPORTED_MODULE_11__["default"]),
/* harmony export */   BorderlessLight: () => (/* reexport safe */ _borderless_light__WEBPACK_IMPORTED_MODULE_8__["default"]),
/* harmony export */   BorderlessLightPanelless: () => (/* reexport safe */ _borderless_light_panelless__WEBPACK_IMPORTED_MODULE_10__["default"]),
/* harmony export */   ContrastDark: () => (/* reexport safe */ _contrast_dark__WEBPACK_IMPORTED_MODULE_37__["default"]),
/* harmony export */   ContrastDarkPanelless: () => (/* reexport safe */ _contrast_dark_panelless__WEBPACK_IMPORTED_MODULE_39__["default"]),
/* harmony export */   ContrastLight: () => (/* reexport safe */ _contrast_light__WEBPACK_IMPORTED_MODULE_36__["default"]),
/* harmony export */   ContrastLightPanelless: () => (/* reexport safe */ _contrast_light_panelless__WEBPACK_IMPORTED_MODULE_38__["default"]),
/* harmony export */   DefaultDark: () => (/* reexport safe */ _default_dark__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   DefaultDarkPanelless: () => (/* reexport safe */ _default_dark_panelless__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   DefaultLight: () => (/* reexport safe */ _default_light__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   DefaultLightPanelless: () => (/* reexport safe */ _default_light_panelless__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   DoubleBorderDark: () => (/* reexport safe */ _doubleborder_dark__WEBPACK_IMPORTED_MODULE_21__["default"]),
/* harmony export */   DoubleBorderDarkPanelless: () => (/* reexport safe */ _doubleborder_dark_panelless__WEBPACK_IMPORTED_MODULE_23__["default"]),
/* harmony export */   DoubleBorderLight: () => (/* reexport safe */ _doubleborder_light__WEBPACK_IMPORTED_MODULE_20__["default"]),
/* harmony export */   DoubleBorderLightPanelless: () => (/* reexport safe */ _doubleborder_light_panelless__WEBPACK_IMPORTED_MODULE_22__["default"]),
/* harmony export */   FlatDark: () => (/* reexport safe */ _flat_dark__WEBPACK_IMPORTED_MODULE_13__["default"]),
/* harmony export */   FlatDarkPanelless: () => (/* reexport safe */ _flat_dark_panelless__WEBPACK_IMPORTED_MODULE_15__["default"]),
/* harmony export */   FlatLight: () => (/* reexport safe */ _flat_light__WEBPACK_IMPORTED_MODULE_12__["default"]),
/* harmony export */   FlatLightPanelless: () => (/* reexport safe */ _flat_light_panelless__WEBPACK_IMPORTED_MODULE_14__["default"]),
/* harmony export */   LayeredDark: () => (/* reexport safe */ _layered_dark__WEBPACK_IMPORTED_MODULE_25__["default"]),
/* harmony export */   LayeredDarkPanelless: () => (/* reexport safe */ _layered_dark_panelless__WEBPACK_IMPORTED_MODULE_27__["default"]),
/* harmony export */   LayeredLight: () => (/* reexport safe */ _layered_light__WEBPACK_IMPORTED_MODULE_24__["default"]),
/* harmony export */   LayeredLightPanelless: () => (/* reexport safe */ _layered_light_panelless__WEBPACK_IMPORTED_MODULE_26__["default"]),
/* harmony export */   PlainDark: () => (/* reexport safe */ _plain_dark__WEBPACK_IMPORTED_MODULE_17__["default"]),
/* harmony export */   PlainDarkPanelless: () => (/* reexport safe */ _plain_dark_panelless__WEBPACK_IMPORTED_MODULE_19__["default"]),
/* harmony export */   PlainLight: () => (/* reexport safe */ _plain_light__WEBPACK_IMPORTED_MODULE_16__["default"]),
/* harmony export */   PlainLightPanelless: () => (/* reexport safe */ _plain_light_panelless__WEBPACK_IMPORTED_MODULE_18__["default"]),
/* harmony export */   SharpDark: () => (/* reexport safe */ _sharp_dark__WEBPACK_IMPORTED_MODULE_5__["default"]),
/* harmony export */   SharpDarkPanelless: () => (/* reexport safe */ _sharp_dark_panelless__WEBPACK_IMPORTED_MODULE_7__["default"]),
/* harmony export */   SharpLight: () => (/* reexport safe */ _sharp_light__WEBPACK_IMPORTED_MODULE_4__["default"]),
/* harmony export */   SharpLightPanelless: () => (/* reexport safe */ _sharp_light_panelless__WEBPACK_IMPORTED_MODULE_6__["default"]),
/* harmony export */   SolidDark: () => (/* reexport safe */ _solid_dark__WEBPACK_IMPORTED_MODULE_29__["default"]),
/* harmony export */   SolidDarkPanelless: () => (/* reexport safe */ _solid_dark_panelless__WEBPACK_IMPORTED_MODULE_31__["default"]),
/* harmony export */   SolidLight: () => (/* reexport safe */ _solid_light__WEBPACK_IMPORTED_MODULE_28__["default"]),
/* harmony export */   SolidLightPanelless: () => (/* reexport safe */ _solid_light_panelless__WEBPACK_IMPORTED_MODULE_30__["default"]),
/* harmony export */   ThreeDimensionalDark: () => (/* reexport safe */ _threedimensional_dark__WEBPACK_IMPORTED_MODULE_33__["default"]),
/* harmony export */   ThreeDimensionalDarkPanelless: () => (/* reexport safe */ _threedimensional_dark_panelless__WEBPACK_IMPORTED_MODULE_35__["default"]),
/* harmony export */   ThreeDimensionalLight: () => (/* reexport safe */ _threedimensional_light__WEBPACK_IMPORTED_MODULE_32__["default"]),
/* harmony export */   ThreeDimensionalLightPanelless: () => (/* reexport safe */ _threedimensional_light_panelless__WEBPACK_IMPORTED_MODULE_34__["default"]),
/* harmony export */   __surveyjs_internal_themes_hash: () => (/* binding */ __surveyjs_internal_themes_hash),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _default_light__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./default-light */ "./src/themes/default-light.ts");
/* harmony import */ var _default_dark__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./default-dark */ "./src/themes/default-dark.ts");
/* harmony import */ var _default_light_panelless__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./default-light-panelless */ "./src/themes/default-light-panelless.ts");
/* harmony import */ var _default_dark_panelless__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./default-dark-panelless */ "./src/themes/default-dark-panelless.ts");
/* harmony import */ var _sharp_light__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./sharp-light */ "./src/themes/sharp-light.ts");
/* harmony import */ var _sharp_dark__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./sharp-dark */ "./src/themes/sharp-dark.ts");
/* harmony import */ var _sharp_light_panelless__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./sharp-light-panelless */ "./src/themes/sharp-light-panelless.ts");
/* harmony import */ var _sharp_dark_panelless__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./sharp-dark-panelless */ "./src/themes/sharp-dark-panelless.ts");
/* harmony import */ var _borderless_light__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./borderless-light */ "./src/themes/borderless-light.ts");
/* harmony import */ var _borderless_dark__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./borderless-dark */ "./src/themes/borderless-dark.ts");
/* harmony import */ var _borderless_light_panelless__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./borderless-light-panelless */ "./src/themes/borderless-light-panelless.ts");
/* harmony import */ var _borderless_dark_panelless__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./borderless-dark-panelless */ "./src/themes/borderless-dark-panelless.ts");
/* harmony import */ var _flat_light__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./flat-light */ "./src/themes/flat-light.ts");
/* harmony import */ var _flat_dark__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./flat-dark */ "./src/themes/flat-dark.ts");
/* harmony import */ var _flat_light_panelless__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./flat-light-panelless */ "./src/themes/flat-light-panelless.ts");
/* harmony import */ var _flat_dark_panelless__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./flat-dark-panelless */ "./src/themes/flat-dark-panelless.ts");
/* harmony import */ var _plain_light__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./plain-light */ "./src/themes/plain-light.ts");
/* harmony import */ var _plain_dark__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./plain-dark */ "./src/themes/plain-dark.ts");
/* harmony import */ var _plain_light_panelless__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./plain-light-panelless */ "./src/themes/plain-light-panelless.ts");
/* harmony import */ var _plain_dark_panelless__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./plain-dark-panelless */ "./src/themes/plain-dark-panelless.ts");
/* harmony import */ var _doubleborder_light__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./doubleborder-light */ "./src/themes/doubleborder-light.ts");
/* harmony import */ var _doubleborder_dark__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./doubleborder-dark */ "./src/themes/doubleborder-dark.ts");
/* harmony import */ var _doubleborder_light_panelless__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./doubleborder-light-panelless */ "./src/themes/doubleborder-light-panelless.ts");
/* harmony import */ var _doubleborder_dark_panelless__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./doubleborder-dark-panelless */ "./src/themes/doubleborder-dark-panelless.ts");
/* harmony import */ var _layered_light__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./layered-light */ "./src/themes/layered-light.ts");
/* harmony import */ var _layered_dark__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./layered-dark */ "./src/themes/layered-dark.ts");
/* harmony import */ var _layered_light_panelless__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./layered-light-panelless */ "./src/themes/layered-light-panelless.ts");
/* harmony import */ var _layered_dark_panelless__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./layered-dark-panelless */ "./src/themes/layered-dark-panelless.ts");
/* harmony import */ var _solid_light__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./solid-light */ "./src/themes/solid-light.ts");
/* harmony import */ var _solid_dark__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./solid-dark */ "./src/themes/solid-dark.ts");
/* harmony import */ var _solid_light_panelless__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./solid-light-panelless */ "./src/themes/solid-light-panelless.ts");
/* harmony import */ var _solid_dark_panelless__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./solid-dark-panelless */ "./src/themes/solid-dark-panelless.ts");
/* harmony import */ var _threedimensional_light__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ./threedimensional-light */ "./src/themes/threedimensional-light.ts");
/* harmony import */ var _threedimensional_dark__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ./threedimensional-dark */ "./src/themes/threedimensional-dark.ts");
/* harmony import */ var _threedimensional_light_panelless__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./threedimensional-light-panelless */ "./src/themes/threedimensional-light-panelless.ts");
/* harmony import */ var _threedimensional_dark_panelless__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ./threedimensional-dark-panelless */ "./src/themes/threedimensional-dark-panelless.ts");
/* harmony import */ var _contrast_light__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! ./contrast-light */ "./src/themes/contrast-light.ts");
/* harmony import */ var _contrast_dark__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! ./contrast-dark */ "./src/themes/contrast-dark.ts");
/* harmony import */ var _contrast_light_panelless__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! ./contrast-light-panelless */ "./src/themes/contrast-light-panelless.ts");
/* harmony import */ var _contrast_dark_panelless__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! ./contrast-dark-panelless */ "./src/themes/contrast-dark-panelless.ts");








































var __surveyjs_internal_themes_hash = true;

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({ DefaultLight: _default_light__WEBPACK_IMPORTED_MODULE_0__["default"], DefaultDark: _default_dark__WEBPACK_IMPORTED_MODULE_1__["default"], DefaultLightPanelless: _default_light_panelless__WEBPACK_IMPORTED_MODULE_2__["default"], DefaultDarkPanelless: _default_dark_panelless__WEBPACK_IMPORTED_MODULE_3__["default"], SharpLight: _sharp_light__WEBPACK_IMPORTED_MODULE_4__["default"], SharpDark: _sharp_dark__WEBPACK_IMPORTED_MODULE_5__["default"], SharpLightPanelless: _sharp_light_panelless__WEBPACK_IMPORTED_MODULE_6__["default"], SharpDarkPanelless: _sharp_dark_panelless__WEBPACK_IMPORTED_MODULE_7__["default"], BorderlessLight: _borderless_light__WEBPACK_IMPORTED_MODULE_8__["default"], BorderlessDark: _borderless_dark__WEBPACK_IMPORTED_MODULE_9__["default"], BorderlessLightPanelless: _borderless_light_panelless__WEBPACK_IMPORTED_MODULE_10__["default"], BorderlessDarkPanelless: _borderless_dark_panelless__WEBPACK_IMPORTED_MODULE_11__["default"], FlatLight: _flat_light__WEBPACK_IMPORTED_MODULE_12__["default"], FlatDark: _flat_dark__WEBPACK_IMPORTED_MODULE_13__["default"], FlatLightPanelless: _flat_light_panelless__WEBPACK_IMPORTED_MODULE_14__["default"], FlatDarkPanelless: _flat_dark_panelless__WEBPACK_IMPORTED_MODULE_15__["default"], PlainLight: _plain_light__WEBPACK_IMPORTED_MODULE_16__["default"], PlainDark: _plain_dark__WEBPACK_IMPORTED_MODULE_17__["default"], PlainLightPanelless: _plain_light_panelless__WEBPACK_IMPORTED_MODULE_18__["default"], PlainDarkPanelless: _plain_dark_panelless__WEBPACK_IMPORTED_MODULE_19__["default"], DoubleBorderLight: _doubleborder_light__WEBPACK_IMPORTED_MODULE_20__["default"], DoubleBorderDark: _doubleborder_dark__WEBPACK_IMPORTED_MODULE_21__["default"], DoubleBorderLightPanelless: _doubleborder_light_panelless__WEBPACK_IMPORTED_MODULE_22__["default"], DoubleBorderDarkPanelless: _doubleborder_dark_panelless__WEBPACK_IMPORTED_MODULE_23__["default"], LayeredLight: _layered_light__WEBPACK_IMPORTED_MODULE_24__["default"], LayeredDark: _layered_dark__WEBPACK_IMPORTED_MODULE_25__["default"], LayeredLightPanelless: _layered_light_panelless__WEBPACK_IMPORTED_MODULE_26__["default"], LayeredDarkPanelless: _layered_dark_panelless__WEBPACK_IMPORTED_MODULE_27__["default"], SolidLight: _solid_light__WEBPACK_IMPORTED_MODULE_28__["default"], SolidDark: _solid_dark__WEBPACK_IMPORTED_MODULE_29__["default"], SolidLightPanelless: _solid_light_panelless__WEBPACK_IMPORTED_MODULE_30__["default"], SolidDarkPanelless: _solid_dark_panelless__WEBPACK_IMPORTED_MODULE_31__["default"], ThreeDimensionalLight: _threedimensional_light__WEBPACK_IMPORTED_MODULE_32__["default"], ThreeDimensionalDark: _threedimensional_dark__WEBPACK_IMPORTED_MODULE_33__["default"], ThreeDimensionalLightPanelless: _threedimensional_light_panelless__WEBPACK_IMPORTED_MODULE_34__["default"], ThreeDimensionalDarkPanelless: _threedimensional_dark_panelless__WEBPACK_IMPORTED_MODULE_35__["default"], ContrastLight: _contrast_light__WEBPACK_IMPORTED_MODULE_36__["default"], ContrastDark: _contrast_dark__WEBPACK_IMPORTED_MODULE_37__["default"], ContrastLightPanelless: _contrast_light_panelless__WEBPACK_IMPORTED_MODULE_38__["default"], ContrastDarkPanelless: _contrast_dark_panelless__WEBPACK_IMPORTED_MODULE_39__["default"], __surveyjs_internal_themes_hash: __surveyjs_internal_themes_hash });

})();

/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=index.js.map