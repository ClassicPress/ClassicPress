/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	let __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	// define getter/value functions for harmony exports
/******/ 	__webpack_require__.d = function(exports, definition) {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); };
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ 	
/************************************************************************/
let __webpack_exports__ = {};
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ domReady; }
/* harmony export */ });
// packages/dom-ready/src/index.ts
/**
* Specify a function to execute when the DOM is fully loaded.
*
* @param callback A function to execute after the DOM is ready.
*
* @example
* ```js
* import domReady from '@wordpress/dom-ready';
*
* domReady( function() {
* 	//do something after DOM loads.
* } );
* ```
*/
function domReady(callback) {
	if (typeof document === "undefined") return;
	if (document.readyState === "complete" || document.readyState === "interactive") {
		callback();
		return;
	}
	document.addEventListener("DOMContentLoaded", callback);
}


//# sourceMappingURL=index.mjs.map
(window.wp = window.wp || {}).domReady = __webpack_exports__;
/******/ })()
;