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
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  actions: function() { return /* binding */ actions; },
  addAction: function() { return /* binding */ addAction; },
  addFilter: function() { return /* binding */ addFilter; },
  applyFilters: function() { return /* binding */ applyFilters; },
  applyFiltersAsync: function() { return /* binding */ applyFiltersAsync; },
  createHooks: function() { return /* reexport */ createHooks; },
  currentAction: function() { return /* binding */ currentAction; },
  currentFilter: function() { return /* binding */ currentFilter; },
  defaultHooks: function() { return /* binding */ defaultHooks; },
  didAction: function() { return /* binding */ didAction; },
  didFilter: function() { return /* binding */ didFilter; },
  doAction: function() { return /* binding */ doAction; },
  doActionAsync: function() { return /* binding */ doActionAsync; },
  doingAction: function() { return /* binding */ doingAction; },
  doingFilter: function() { return /* binding */ doingFilter; },
  filters: function() { return /* binding */ filters; },
  hasAction: function() { return /* binding */ hasAction; },
  hasFilter: function() { return /* binding */ hasFilter; },
  removeAction: function() { return /* binding */ removeAction; },
  removeAllActions: function() { return /* binding */ removeAllActions; },
  removeAllFilters: function() { return /* binding */ removeAllFilters; },
  removeFilter: function() { return /* binding */ removeFilter; }
});

;// ./node_modules/@wordpress/hooks/build-module/validateNamespace.mjs
// packages/hooks/src/validateNamespace.ts
/**
* Validate a namespace string.
*
* @param namespace The namespace to validate - should take the form
*                  `vendor/plugin/function`.
*
* @return Whether the namespace is valid.
*/
function validateNamespace(namespace) {
	if ("string" !== typeof namespace || "" === namespace) {
		console.error("The namespace must be a non-empty string.");
		return false;
	}
	if (!/^[a-zA-Z][a-zA-Z0-9_.\-\/]*$/.test(namespace)) {
		console.error("The namespace can only contain numbers, letters, dashes, periods, underscores and slashes.");
		return false;
	}
	return true;
}


//# sourceMappingURL=validateNamespace.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/validateHookName.mjs
// packages/hooks/src/validateHookName.ts
/**
* Validate a hookName string.
*
* @param hookName The hook name to validate. Should be a non empty string containing
*                 only numbers, letters, dashes, periods and underscores. Also,
*                 the hook name cannot begin with `__`.
*
* @return Whether the hook name is valid.
*/
function validateHookName(hookName) {
	if ("string" !== typeof hookName || "" === hookName) {
		console.error("The hook name must be a non-empty string.");
		return false;
	}
	if (/^__/.test(hookName)) {
		console.error("The hook name cannot begin with `__`.");
		return false;
	}
	if (!/^[a-zA-Z][a-zA-Z0-9_.-]*$/.test(hookName)) {
		console.error("The hook name can only contain numbers, letters, dashes, periods and underscores.");
		return false;
	}
	return true;
}


//# sourceMappingURL=validateHookName.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createAddHook.mjs


// packages/hooks/src/createAddHook.ts
/**
* Returns a function which, when invoked, will add a hook.
*
* @param hooks    Hooks instance.
* @param storeKey
*
* @return  Function that adds a new hook.
*/
function createAddHook(hooks, storeKey) {
	return function addHook(hookName, namespace, callback, priority = 10) {
		const hooksStore = hooks[storeKey];
		if (!validateHookName(hookName)) return;
		if (!validateNamespace(namespace)) return;
		if ("function" !== typeof callback) {
			console.error("The hook callback must be a function.");
			return;
		}
		if ("number" !== typeof priority) {
			console.error("If specified, the hook priority must be a number.");
			return;
		}
		const handler = {
			callback,
			priority,
			namespace
		};
		if (hooksStore[hookName]) {
			const handlers = hooksStore[hookName].handlers;
			let i;
			for (i = handlers.length; i > 0; i--) if (priority >= handlers[i - 1].priority) break;
			if (i === handlers.length) handlers[i] = handler;
			else handlers.splice(i, 0, handler);
			hooksStore.__current.forEach((hookInfo) => {
				if (hookInfo.name === hookName && hookInfo.currentIndex >= i) hookInfo.currentIndex++;
			});
		} else hooksStore[hookName] = {
			handlers: [handler],
			runs: 0
		};
		if (hookName !== "hookAdded") hooks.doAction("hookAdded", hookName, namespace, callback, priority);
	};
}


//# sourceMappingURL=createAddHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createRemoveHook.mjs


// packages/hooks/src/createRemoveHook.ts
function createRemoveHook(hooks, storeKey, removeAll = false) {
	return function removeHook(hookName, namespace) {
		const hooksStore = hooks[storeKey];
		if (!validateHookName(hookName)) return;
		if (!removeAll && !validateNamespace(namespace)) return;
		if (!hooksStore[hookName]) return 0;
		let handlersRemoved = 0;
		if (removeAll) {
			handlersRemoved = hooksStore[hookName].handlers.length;
			hooksStore[hookName] = {
				runs: hooksStore[hookName].runs,
				handlers: []
			};
		} else {
			const handlers = hooksStore[hookName].handlers;
			for (let i = handlers.length - 1; i >= 0; i--) if (handlers[i].namespace === namespace) {
				handlers.splice(i, 1);
				handlersRemoved++;
				hooksStore.__current.forEach((hookInfo) => {
					if (hookInfo.name === hookName && hookInfo.currentIndex >= i) hookInfo.currentIndex--;
				});
			}
		}
		if (hookName !== "hookRemoved") hooks.doAction("hookRemoved", hookName, namespace);
		return handlersRemoved;
	};
}


//# sourceMappingURL=createRemoveHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createHasHook.mjs
// packages/hooks/src/createHasHook.ts
/**
* Returns a function which, when invoked, will return whether any handlers are
* attached to a particular hook.
*
* @param hooks    Hooks instance.
* @param storeKey
*
* @return  Function that returns whether any handlers are
*                   attached to a particular hook and optional namespace.
*/
function createHasHook(hooks, storeKey) {
	return function hasHook(hookName, namespace) {
		const hooksStore = hooks[storeKey];
		if ("undefined" !== typeof namespace) return hookName in hooksStore && hooksStore[hookName].handlers.some((hook) => hook.namespace === namespace);
		return hookName in hooksStore;
	};
}


//# sourceMappingURL=createHasHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createRunHook.mjs
// packages/hooks/src/createRunHook.ts
/**
* Returns a function which, when invoked, will execute all callbacks
* registered to a hook of the specified type, optionally returning the final
* value of the call chain.
*
* @param hooks          Hooks instance.
* @param storeKey
* @param returnFirstArg Whether each hook callback is expected to return its first argument.
* @param async          Whether the hook callback should be run asynchronously
*
* @return Function that runs hook callbacks.
*/
function createRunHook(hooks, storeKey, returnFirstArg, async) {
	return function runHook(hookName, ...args) {
		const hooksStore = hooks[storeKey];
		if (!hooksStore[hookName]) hooksStore[hookName] = {
			handlers: [],
			runs: 0
		};
		hooksStore[hookName].runs++;
		const handlers = hooksStore[hookName].handlers;
		if (false) // removed by dead control flow
{}
		if (!handlers || !handlers.length) return returnFirstArg ? args[0] : void 0;
		const hookInfo = {
			name: hookName,
			currentIndex: 0
		};
		async function asyncRunner() {
			try {
				hooksStore.__current.add(hookInfo);
				let result = returnFirstArg ? args[0] : void 0;
				while (hookInfo.currentIndex < handlers.length) {
					result = await handlers[hookInfo.currentIndex].callback.apply(null, args);
					if (returnFirstArg) args[0] = result;
					hookInfo.currentIndex++;
				}
				return returnFirstArg ? result : void 0;
			} finally {
				hooksStore.__current.delete(hookInfo);
			}
		}
		function syncRunner() {
			try {
				hooksStore.__current.add(hookInfo);
				let result = returnFirstArg ? args[0] : void 0;
				while (hookInfo.currentIndex < handlers.length) {
					result = handlers[hookInfo.currentIndex].callback.apply(null, args);
					if (returnFirstArg) args[0] = result;
					hookInfo.currentIndex++;
				}
				return returnFirstArg ? result : void 0;
			} finally {
				hooksStore.__current.delete(hookInfo);
			}
		}
		return (async ? asyncRunner : syncRunner)();
	};
}


//# sourceMappingURL=createRunHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createCurrentHook.mjs
// packages/hooks/src/createCurrentHook.ts
/**
* Returns a function which, when invoked, will return the name of the
* currently running hook, or `null` if no hook of the given type is currently
* running.
*
* @param hooks    Hooks instance.
* @param storeKey
*
* @return Function that returns the current hook name or null.
*/
function createCurrentHook(hooks, storeKey) {
	return function currentHook() {
		const hooksStore = hooks[storeKey];
		return Array.from(hooksStore.__current).at(-1)?.name ?? null;
	};
}


//# sourceMappingURL=createCurrentHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createDoingHook.mjs
// packages/hooks/src/createDoingHook.ts
/**
* Returns a function which, when invoked, will return whether a hook is
* currently being executed.
*
* @param hooks    Hooks instance.
* @param storeKey
*
* @return Function that returns whether a hook is currently
*                     being executed.
*/
function createDoingHook(hooks, storeKey) {
	return function doingHook(hookName) {
		const hooksStore = hooks[storeKey];
		if ("undefined" === typeof hookName) return hooksStore.__current.size > 0;
		return Array.from(hooksStore.__current).some((hook) => hook.name === hookName);
	};
}


//# sourceMappingURL=createDoingHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createDidHook.mjs

// packages/hooks/src/createDidHook.ts
/**
* Returns a function which, when invoked, will return the number of times a
* hook has been called.
*
* @param hooks    Hooks instance.
* @param storeKey
*
* @return  Function that returns a hook's call count.
*/
function createDidHook(hooks, storeKey) {
	return function didHook(hookName) {
		const hooksStore = hooks[storeKey];
		if (!validateHookName(hookName)) return;
		return hooksStore[hookName] && hooksStore[hookName].runs ? hooksStore[hookName].runs : 0;
	};
}


//# sourceMappingURL=createDidHook.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/createHooks.mjs







// packages/hooks/src/createHooks.ts
/**
* Internal class for constructing hooks. Use `createHooks()` function
*
* Note, it is necessary to expose this class to make its type public.
*
* @private
*/
var _Hooks = class {
	actions;
	filters;
	addAction;
	addFilter;
	removeAction;
	removeFilter;
	hasAction;
	hasFilter;
	removeAllActions;
	removeAllFilters;
	doAction;
	doActionAsync;
	applyFilters;
	applyFiltersAsync;
	currentAction;
	currentFilter;
	doingAction;
	doingFilter;
	didAction;
	didFilter;
	constructor() {
		this.actions = Object.create(null);
		this.actions.__current = /* @__PURE__ */ new Set();
		this.filters = Object.create(null);
		this.filters.__current = /* @__PURE__ */ new Set();
		this.addAction = createAddHook(this, "actions");
		this.addFilter = createAddHook(this, "filters");
		this.removeAction = createRemoveHook(this, "actions");
		this.removeFilter = createRemoveHook(this, "filters");
		this.hasAction = createHasHook(this, "actions");
		this.hasFilter = createHasHook(this, "filters");
		this.removeAllActions = createRemoveHook(this, "actions", true);
		this.removeAllFilters = createRemoveHook(this, "filters", true);
		this.doAction = createRunHook(this, "actions", false, false);
		this.doActionAsync = createRunHook(this, "actions", false, true);
		this.applyFilters = createRunHook(this, "filters", true, false);
		this.applyFiltersAsync = createRunHook(this, "filters", true, true);
		this.currentAction = createCurrentHook(this, "actions");
		this.currentFilter = createCurrentHook(this, "filters");
		this.doingAction = createDoingHook(this, "actions");
		this.doingFilter = createDoingHook(this, "filters");
		this.didAction = createDidHook(this, "actions");
		this.didFilter = createDidHook(this, "filters");
	}
};
/**
* Returns an instance of the hooks object.
*
* @return A Hooks instance.
*/
function createHooks() {
	return new _Hooks();
}


//# sourceMappingURL=createHooks.mjs.map
;// ./node_modules/@wordpress/hooks/build-module/index.mjs

// packages/hooks/src/index.ts
const defaultHooks = createHooks();
const { addAction, addFilter, removeAction, removeFilter, hasAction, hasFilter, removeAllActions, removeAllFilters, doAction, doActionAsync, applyFilters, applyFiltersAsync, currentAction, currentFilter, doingAction, doingFilter, didAction, didFilter, actions, filters } = defaultHooks;


//# sourceMappingURL=index.mjs.map
(window.wp = window.wp || {}).hooks = __webpack_exports__;
/******/ })()
;