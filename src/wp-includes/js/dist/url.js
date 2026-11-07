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
  addQueryArgs: function() { return /* reexport */ addQueryArgs; },
  buildQueryString: function() { return /* reexport */ buildQueryString; },
  cleanForSlug: function() { return /* reexport */ cleanForSlug; },
  filterURLForDisplay: function() { return /* reexport */ filterURLForDisplay; },
  getAuthority: function() { return /* reexport */ getAuthority; },
  getFilename: function() { return /* reexport */ getFilename; },
  getFragment: function() { return /* reexport */ getFragment; },
  getPath: function() { return /* reexport */ getPath; },
  getPathAndQueryString: function() { return /* reexport */ getPathAndQueryString; },
  getProtocol: function() { return /* reexport */ getProtocol; },
  getQueryArg: function() { return /* reexport */ getQueryArg; },
  getQueryArgs: function() { return /* reexport */ getQueryArgs; },
  getQueryString: function() { return /* reexport */ getQueryString; },
  hasQueryArg: function() { return /* reexport */ hasQueryArg; },
  isEmail: function() { return /* reexport */ isEmail; },
  isPhoneNumber: function() { return /* reexport */ isPhoneNumber; },
  isURL: function() { return /* reexport */ isURL; },
  isValidAuthority: function() { return /* reexport */ isValidAuthority; },
  isValidFragment: function() { return /* reexport */ isValidFragment; },
  isValidPath: function() { return /* reexport */ isValidPath; },
  isValidProtocol: function() { return /* reexport */ isValidProtocol; },
  isValidQueryString: function() { return /* reexport */ isValidQueryString; },
  normalizePath: function() { return /* reexport */ normalizePath; },
  prependHTTP: function() { return /* reexport */ prependHTTP; },
  prependHTTPS: function() { return /* reexport */ prependHTTPS; },
  removeQueryArgs: function() { return /* reexport */ removeQueryArgs; },
  safeDecodeURI: function() { return /* reexport */ safeDecodeURI; },
  safeDecodeURIComponent: function() { return /* reexport */ safeDecodeURIComponent; }
});

;// ./node_modules/@wordpress/url/build-module/is-url.mjs
// packages/url/src/is-url.ts
/**
* Determines whether the given string looks like a URL.
*
* @param url The string to scrutinise.
*
* @example
* ```js
* const isURL = isURL( 'https://wordpress.org' ); // true
* ```
*
* @see https://url.spec.whatwg.org/
* @see https://url.spec.whatwg.org/#valid-url-string
*
* @return Whether or not it looks like a URL.
*/
function isURL(url) {
	try {
		new URL(url);
		return true;
	} catch {
		return false;
	}
}


//# sourceMappingURL=is-url.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-email.mjs
// packages/url/src/is-email.ts
const EMAIL_REGEXP = /^(mailto:)?[a-z0-9._%+-]+@[a-z0-9][a-z0-9.-]*\.[a-z]{2,63}$/i;
/**
* Determines whether the given string looks like an email.
*
* @param email The string to scrutinise.
*
* @example
* ```js
* const isEmail = isEmail( 'hello@wordpress.org' ); // true
* ```
*
* @return Whether or not it looks like an email.
*/
function isEmail(email) {
	return EMAIL_REGEXP.test(email);
}


//# sourceMappingURL=is-email.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-phone-number.mjs
// packages/url/src/is-phone-number.ts
const PHONE_REGEXP = /^(tel:)?(\+)?\d{6,15}$/;
/**
* Determines whether the given string looks like a phone number.
*
* @param phoneNumber The string to scrutinize.
*
* @example
* ```js
* const isPhoneNumber = isPhoneNumber('+1 (555) 123-4567'); // true
* ```
*
* @return Whether or not it looks like a phone number.
*/
function isPhoneNumber(phoneNumber) {
	phoneNumber = phoneNumber.replace(/[-.() ]/g, "");
	return PHONE_REGEXP.test(phoneNumber);
}


//# sourceMappingURL=is-phone-number.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-protocol.mjs
// packages/url/src/get-protocol.ts
/**
* Returns the protocol part of the URL.
*
* @param url The full URL.
*
* @example
* ```js
* const protocol1 = getProtocol( 'tel:012345678' ); // 'tel:'
* const protocol2 = getProtocol( 'https://wordpress.org' ); // 'https:'
* ```
*
* @return  The protocol part of the URL.
*/
function getProtocol(url) {
	const matches = /^([^\s:]+:)/.exec(url);
	if (matches) return matches[1];
}


//# sourceMappingURL=get-protocol.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-valid-protocol.mjs
// packages/url/src/is-valid-protocol.ts
/**
* Tests if a url protocol is valid.
*
* @param protocol The url protocol.
*
* @example
* ```js
* const isValid = isValidProtocol( 'https:' ); // true
* const isNotValid = isValidProtocol( 'https :' ); // false
* ```
*
* @return True if the argument is a valid protocol (e.g. http:, tel:).
*/
function isValidProtocol(protocol) {
	if (!protocol) return false;
	return /^[a-z\-.\+]+[0-9]*:$/i.test(protocol);
}


//# sourceMappingURL=is-valid-protocol.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-authority.mjs
// packages/url/src/get-authority.ts
/**
* Returns the authority part of the URL.
*
* @param url The full URL.
*
* @example
* ```js
* const authority1 = getAuthority( 'https://wordpress.org/help/' ); // 'wordpress.org'
* const authority2 = getAuthority( 'https://localhost:8080/test/' ); // 'localhost:8080'
* ```
*
* @return The authority part of the URL.
*/
function getAuthority(url) {
	const matches = /^[^\/\s:]+:(?:\/\/)?\/?([^\/\s#?]+)[\/#?]{0,1}\S*$/.exec(url);
	if (matches) return matches[1];
}


//# sourceMappingURL=get-authority.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-valid-authority.mjs
// packages/url/src/is-valid-authority.ts
/**
* Checks for invalid characters within the provided authority.
*
* @param authority A string containing the URL authority.
*
* @example
* ```js
* const isValid = isValidAuthority( 'wordpress.org' ); // true
* const isNotValid = isValidAuthority( 'wordpress#org' ); // false
* ```
*
* @return True if the argument contains a valid authority.
*/
function isValidAuthority(authority) {
	if (!authority) return false;
	return /^[^\s#?]+$/.test(authority);
}


//# sourceMappingURL=is-valid-authority.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-path.mjs
// packages/url/src/get-path.ts
/**
* Returns the path part of the URL.
*
* @param url The full URL.
*
* @example
* ```js
* const path1 = getPath( 'http://localhost:8080/this/is/a/test?query=true' ); // 'this/is/a/test'
* const path2 = getPath( 'https://wordpress.org/help/faq/' ); // 'help/faq'
* ```
*
* @return The path part of the URL.
*/
function getPath(url) {
	const matches = /^[^\/\s:]+:(?:\/\/)?[^\/\s#?]+[\/]([^\s#?]+)[#?]{0,1}\S*$/.exec(url);
	if (matches) return matches[1];
}


//# sourceMappingURL=get-path.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-valid-path.mjs
// packages/url/src/is-valid-path.ts
/**
* Checks for invalid characters within the provided path.
*
* @param path The URL path.
*
* @example
* ```js
* const isValid = isValidPath( 'test/path/' ); // true
* const isNotValid = isValidPath( '/invalid?test/path/' ); // false
* ```
*
* @return True if the argument contains a valid path
*/
function isValidPath(path) {
	if (!path) return false;
	return /^[^\s#?]+$/.test(path);
}


//# sourceMappingURL=is-valid-path.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-query-string.mjs
// packages/url/src/get-query-string.ts
/**
* Returns the query string part of the URL.
*
* @param url The full URL.
*
* @example
* ```js
* const queryString = getQueryString( 'http://localhost:8080/this/is/a/test?query=true#fragment' ); // 'query=true'
* ```
*
* @return The query string part of the URL.
*/
function getQueryString(url) {
	let query;
	try {
		query = new URL(url, "http://example.com").search.substring(1);
	} catch {}
	if (query) return query;
}


//# sourceMappingURL=get-query-string.mjs.map
;// ./node_modules/@wordpress/url/build-module/build-query-string.mjs
// packages/url/src/build-query-string.ts
/**
* Generates URL-encoded query string using input query data.
*
* It is intended to behave equivalent as PHP's `http_build_query`, configured
* with encoding type PHP_QUERY_RFC3986 (spaces as `%20`).
*
* @example
* ```js
* const queryString = buildQueryString( {
*    simple: 'is ok',
*    arrays: [ 'are', 'fine', 'too' ],
*    objects: {
*       evenNested: {
*          ok: 'yes',
*       },
*    },
* } );
* // "simple=is%20ok&arrays%5B0%5D=are&arrays%5B1%5D=fine&arrays%5B2%5D=too&objects%5BevenNested%5D%5Bok%5D=yes"
* ```
*
* @param data Data to encode.
*
* @return Query string.
*/
function buildQueryString(data) {
	let string = "";
	const stack = Object.entries(data);
	let pair;
	while (pair = stack.shift()) {
		let [key, value] = pair;
		if (Array.isArray(value) || value && value.constructor === Object) {
			const valuePairs = Object.entries(value).reverse();
			for (const [member, memberValue] of valuePairs) stack.unshift([`${key}[${member}]`, memberValue]);
		} else if (value !== void 0) {
			if (value === null) value = "";
			string += "&" + [key, String(value)].map(encodeURIComponent).join("=");
		}
	}
	return string.substr(1);
}


//# sourceMappingURL=build-query-string.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-valid-query-string.mjs
// packages/url/src/is-valid-query-string.ts
/**
* Checks for invalid characters within the provided query string.
*
* @param queryString The query string.
*
* @example
* ```js
* const isValid = isValidQueryString( 'query=true&another=false' ); // true
* const isNotValid = isValidQueryString( 'query=true?another=false' ); // false
* ```
*
* @return True if the argument contains a valid query string.
*/
function isValidQueryString(queryString) {
	if (!queryString) return false;
	return /^[^\s#?\/]+$/.test(queryString);
}


//# sourceMappingURL=is-valid-query-string.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-path-and-query-string.mjs

// packages/url/src/get-path-and-query-string.ts
/**
* Returns the path part and query string part of the URL.
*
* @param url The full URL.
*
* @example
* ```js
* const pathAndQueryString1 = getPathAndQueryString( 'http://localhost:8080/this/is/a/test?query=true' ); // '/this/is/a/test?query=true'
* const pathAndQueryString2 = getPathAndQueryString( 'https://wordpress.org/help/faq/' ); // '/help/faq'
* ```
*
* @return The path part and query string part of the URL.
*/
function getPathAndQueryString(url) {
	const path = getPath(url);
	const queryString = getQueryString(url);
	let value = "/";
	if (path) value += path;
	if (queryString) value += `?${queryString}`;
	return value;
}


//# sourceMappingURL=get-path-and-query-string.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-fragment.mjs
// packages/url/src/get-fragment.ts
/**
* Returns the fragment part of the URL.
*
* @param url The full URL
*
* @example
* ```js
* const fragment1 = getFragment( 'http://localhost:8080/this/is/a/test?query=true#fragment' ); // '#fragment'
* const fragment2 = getFragment( 'https://wordpress.org#another-fragment?query=true' ); // '#another-fragment'
* ```
*
* @return The fragment part of the URL.
*/
function getFragment(url) {
	const matches = /^\S+?(#[^\s\?]*)/.exec(url);
	if (matches) return matches[1];
}


//# sourceMappingURL=get-fragment.mjs.map
;// ./node_modules/@wordpress/url/build-module/is-valid-fragment.mjs
// packages/url/src/is-valid-fragment.ts
/**
* Checks for invalid characters within the provided fragment.
*
* @param fragment The url fragment.
*
* @example
* ```js
* const isValid = isValidFragment( '#valid-fragment' ); // true
* const isNotValid = isValidFragment( '#invalid-#fragment' ); // false
* ```
*
* @return True if the argument contains a valid fragment.
*/
function isValidFragment(fragment) {
	if (!fragment) return false;
	return /^#[^\s#?\/]*$/.test(fragment);
}


//# sourceMappingURL=is-valid-fragment.mjs.map
;// ./node_modules/@wordpress/url/build-module/safe-decode-uri-component.mjs
// packages/url/src/safe-decode-uri-component.ts
/**
* Safely decodes a URI component with `decodeURIComponent`. Returns the URI component unmodified if
* `decodeURIComponent` throws an error.
*
* @param uriComponent URI component to decode.
*
* @return Decoded URI component if possible.
*/
function safeDecodeURIComponent(uriComponent) {
	try {
		return decodeURIComponent(uriComponent);
	} catch {
		return uriComponent;
	}
}


//# sourceMappingURL=safe-decode-uri-component.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-query-args.mjs


// packages/url/src/get-query-args.ts
/**
* Sets a value in object deeply by a given array of path segments. Mutates the
* object reference.
*
* @param object Object in which to assign.
* @param path   Path segment at which to set value.
* @param value  Value to set.
*/
function setPath(object, path, value) {
	const length = path.length;
	const lastIndex = length - 1;
	for (let i = 0; i < length; i++) {
		let key = path[i];
		if (!key && Array.isArray(object)) key = object.length.toString();
		key = [
			"__proto__",
			"constructor",
			"prototype"
		].includes(key) ? key.toUpperCase() : key;
		const isNextKeyArrayIndex = !isNaN(Number(path[i + 1]));
		object[key] = i === lastIndex ? value : object[key] || (isNextKeyArrayIndex ? [] : {});
		if (Array.isArray(object[key]) && !isNextKeyArrayIndex) object[key] = { ...object[key] };
		object = object[key];
	}
}
/**
* Returns an object of query arguments of the given URL. If the given URL is
* invalid or has no querystring, an empty object is returned.
*
* @param url URL.
*
* @example
* ```js
* const foo = getQueryArgs( 'https://wordpress.org?foo=bar&bar=baz' );
* // { "foo": "bar", "bar": "baz" }
* ```
*
* @return Query args object.
*/
function getQueryArgs(url) {
	return (getQueryString(url) || "").replace(/\+/g, "%20").split("&").reduce((accumulator, keyValue) => {
		const separatorIndex = keyValue.indexOf("=");
		const hasValue = separatorIndex !== -1;
		const key = safeDecodeURIComponent(hasValue ? keyValue.slice(0, separatorIndex) : keyValue);
		if (key) {
			const value = hasValue ? safeDecodeURIComponent(keyValue.slice(separatorIndex + 1)) : "";
			setPath(accumulator, key.replace(/\]/g, "").split("["), value);
		}
		return accumulator;
	}, Object.create(null));
}


//# sourceMappingURL=get-query-args.mjs.map
;// ./node_modules/@wordpress/url/build-module/add-query-args.mjs



// packages/url/src/add-query-args.ts
/**
* Appends arguments as querystring to the provided URL. If the URL already
* includes query arguments, the arguments are merged with (and take precedent
* over) the existing set.
*
* @param url  URL to which arguments should be appended. If omitted,
*             only the resulting querystring is returned.
* @param args Query arguments to apply to URL.
*
* @example
* ```js
* const newURL = addQueryArgs( 'https://google.com', { q: 'test' } ); // https://google.com/?q=test
* ```
*
* @return URL with arguments applied.
*/
function addQueryArgs(url = "", args) {
	if (!args || !Object.keys(args).length) return url;
	const fragment = getFragment(url) || "";
	let baseUrl = url.replace(fragment, "");
	const queryStringIndex = url.indexOf("?");
	if (queryStringIndex !== -1) {
		args = Object.assign(getQueryArgs(url), args);
		baseUrl = baseUrl.substr(0, queryStringIndex);
	}
	return baseUrl + "?" + buildQueryString(args) + fragment;
}


//# sourceMappingURL=add-query-args.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-query-arg.mjs

// packages/url/src/get-query-arg.ts
/**
* Returns a single query argument of the url
*
* @param url URL.
* @param arg Query arg name.
*
* @example
* ```js
* const foo = getQueryArg( 'https://wordpress.org?foo=bar&bar=baz', 'foo' ); // bar
* ```
*
* @return Query arg value.
*/
function getQueryArg(url, arg) {
	return getQueryArgs(url)[arg];
}


//# sourceMappingURL=get-query-arg.mjs.map
;// ./node_modules/@wordpress/url/build-module/has-query-arg.mjs

// packages/url/src/has-query-arg.ts
/**
* Determines whether the URL contains a given query arg.
*
* @param url URL.
* @param arg Query arg name.
*
* @example
* ```js
* const hasBar = hasQueryArg( 'https://wordpress.org?foo=bar&bar=baz', 'bar' ); // true
* ```
*
* @return Whether or not the URL contains the query arg.
*/
function hasQueryArg(url, arg) {
	return getQueryArg(url, arg) !== void 0;
}


//# sourceMappingURL=has-query-arg.mjs.map
;// ./node_modules/@wordpress/url/build-module/remove-query-args.mjs


// packages/url/src/remove-query-args.ts
/**
* Removes arguments from the query string of the url
*
* @param url  URL.
* @param args Query Args.
*
* @example
* ```js
* const newUrl = removeQueryArgs( 'https://wordpress.org?foo=bar&bar=baz&baz=foobar', 'foo', 'bar' ); // https://wordpress.org?baz=foobar
* ```
*
* @return Updated URL.
*/
function removeQueryArgs(url, ...args) {
	const fragment = url.replace(/^[^#]*/, "");
	url = url.replace(/#.*/, "");
	const queryStringIndex = url.indexOf("?");
	if (queryStringIndex === -1) return url + fragment;
	const query = getQueryArgs(url);
	const baseURL = url.substr(0, queryStringIndex);
	args.forEach((arg) => delete query[arg]);
	const queryString = buildQueryString(query);
	return (queryString ? baseURL + "?" + queryString : baseURL) + fragment;
}


//# sourceMappingURL=remove-query-args.mjs.map
;// ./node_modules/@wordpress/url/build-module/prepend-http.mjs

// packages/url/src/prepend-http.ts
const USABLE_HREF_REGEXP = /^(?:[a-z]+:|#|\?|\.|\/)/i;
/**
* Prepends "http://" to a url, if it looks like something that is meant to be a TLD.
*
* @param url The URL to test.
*
* @example
* ```js
* const actualURL = prependHTTP( 'wordpress.org' ); // http://wordpress.org
* ```
*
* @return The updated URL.
*/
function prependHTTP(url) {
	if (!url) return url;
	url = url.trim();
	if (!USABLE_HREF_REGEXP.test(url) && !isEmail(url)) return "http://" + url;
	return url;
}


//# sourceMappingURL=prepend-http.mjs.map
;// ./node_modules/@wordpress/url/build-module/safe-decode-uri.mjs
// packages/url/src/safe-decode-uri.ts
/**
* Safely decodes a URI with `decodeURI`. Returns the URI unmodified if
* `decodeURI` throws an error.
*
* @param uri URI to decode.
*
* @example
* ```js
* const badUri = safeDecodeURI( '%z' ); // does not throw an Error, simply returns '%z'
* ```
*
* @return Decoded URI if possible.
*/
function safeDecodeURI(uri) {
	try {
		return decodeURI(uri);
	} catch {
		return uri;
	}
}


//# sourceMappingURL=safe-decode-uri.mjs.map
;// ./node_modules/@wordpress/url/build-module/filter-url-for-display.mjs
// packages/url/src/filter-url-for-display.ts
/**
* Returns a URL for display.
*
* @param url       Original URL.
* @param maxLength URL length.
*
* @example
* ```js
* const displayUrl = filterURLForDisplay( 'https://www.wordpress.org/gutenberg/' ); // wordpress.org/gutenberg
* const imageUrl = filterURLForDisplay( 'https://www.wordpress.org/wp-content/uploads/img.png', 20 ); // …ent/uploads/img.png
* ```
*
* @return Displayed URL.
*/
function filterURLForDisplay(url, maxLength = null) {
	if (!url) return "";
	let filteredURL = url.replace(/^[a-z\-.\+]+[0-9]*:(\/\/)?/i, "").replace(/^www\./i, "");
	if (filteredURL.match(/^[^\/]+\/$/)) filteredURL = filteredURL.replace("/", "");
	if (!maxLength || filteredURL.length <= maxLength || !filteredURL.match(/\/([^\/?]+)\.(?:[\w]+)(?=\?|$)/)) return filteredURL;
	filteredURL = filteredURL.split("?")[0];
	const urlPieces = filteredURL.split("/");
	const file = urlPieces[urlPieces.length - 1];
	if (file.length <= maxLength) return "…" + filteredURL.slice(-maxLength);
	const index = file.lastIndexOf(".");
	const [fileName, extension] = [file.slice(0, index), file.slice(index + 1)];
	const truncatedFile = fileName.slice(-3) + "." + extension;
	return file.slice(0, Math.max(0, maxLength - truncatedFile.length - 1)) + "…" + truncatedFile;
}


//# sourceMappingURL=filter-url-for-display.mjs.map
;// ./node_modules/@wordpress/url/build-module/remove-accents.mjs
// packages/url/src/remove-accents.ts
/**
* The letters `remove_accents()` in WordPress core converts to basic Latin,
* copied from its `$chars` table. The locale-specific rules (German, Danish,
* Catalan, Serbian and Bosnian) are left out, since they depend on the site
* locale.
*/
const CHARS = {
	"ª": "a",
	"º": "o",
	"À": "A",
	"Á": "A",
	"Â": "A",
	"Ã": "A",
	"Ä": "A",
	"Å": "A",
	"Æ": "AE",
	"Ç": "C",
	"È": "E",
	"É": "E",
	"Ê": "E",
	"Ë": "E",
	"Ì": "I",
	"Í": "I",
	"Î": "I",
	"Ï": "I",
	"Ð": "D",
	"Ñ": "N",
	"Ò": "O",
	"Ó": "O",
	"Ô": "O",
	"Õ": "O",
	"Ö": "O",
	"Ù": "U",
	"Ú": "U",
	"Û": "U",
	"Ü": "U",
	"Ý": "Y",
	"Þ": "TH",
	"ß": "s",
	"à": "a",
	"á": "a",
	"â": "a",
	"ã": "a",
	"ä": "a",
	"å": "a",
	"æ": "ae",
	"ç": "c",
	"è": "e",
	"é": "e",
	"ê": "e",
	"ë": "e",
	"ì": "i",
	"í": "i",
	"î": "i",
	"ï": "i",
	"ð": "d",
	"ñ": "n",
	"ò": "o",
	"ó": "o",
	"ô": "o",
	"õ": "o",
	"ö": "o",
	"ø": "o",
	"ù": "u",
	"ú": "u",
	"û": "u",
	"ü": "u",
	"ý": "y",
	"þ": "th",
	"ÿ": "y",
	"Ø": "O",
	"Ā": "A",
	"ā": "a",
	"Ă": "A",
	"ă": "a",
	"Ą": "A",
	"ą": "a",
	"Ć": "C",
	"ć": "c",
	"Ĉ": "C",
	"ĉ": "c",
	"Ċ": "C",
	"ċ": "c",
	"Č": "C",
	"č": "c",
	"Ď": "D",
	"ď": "d",
	"Đ": "D",
	"đ": "d",
	"Ē": "E",
	"ē": "e",
	"Ĕ": "E",
	"ĕ": "e",
	"Ė": "E",
	"ė": "e",
	"Ę": "E",
	"ę": "e",
	"Ě": "E",
	"ě": "e",
	"Ĝ": "G",
	"ĝ": "g",
	"Ğ": "G",
	"ğ": "g",
	"Ġ": "G",
	"ġ": "g",
	"Ģ": "G",
	"ģ": "g",
	"Ĥ": "H",
	"ĥ": "h",
	"Ħ": "H",
	"ħ": "h",
	"Ĩ": "I",
	"ĩ": "i",
	"Ī": "I",
	"ī": "i",
	"Ĭ": "I",
	"ĭ": "i",
	"Į": "I",
	"į": "i",
	"İ": "I",
	"ı": "i",
	"Ĳ": "IJ",
	"ĳ": "ij",
	"Ĵ": "J",
	"ĵ": "j",
	"Ķ": "K",
	"ķ": "k",
	"ĸ": "k",
	"Ĺ": "L",
	"ĺ": "l",
	"Ļ": "L",
	"ļ": "l",
	"Ľ": "L",
	"ľ": "l",
	"Ŀ": "L",
	"ŀ": "l",
	"Ł": "L",
	"ł": "l",
	"Ń": "N",
	"ń": "n",
	"Ņ": "N",
	"ņ": "n",
	"Ň": "N",
	"ň": "n",
	"ŉ": "n",
	"Ŋ": "N",
	"ŋ": "n",
	"Ō": "O",
	"ō": "o",
	"Ŏ": "O",
	"ŏ": "o",
	"Ő": "O",
	"ő": "o",
	"Œ": "OE",
	"œ": "oe",
	"Ŕ": "R",
	"ŕ": "r",
	"Ŗ": "R",
	"ŗ": "r",
	"Ř": "R",
	"ř": "r",
	"Ś": "S",
	"ś": "s",
	"Ŝ": "S",
	"ŝ": "s",
	"Ş": "S",
	"ş": "s",
	"Š": "S",
	"š": "s",
	"Ţ": "T",
	"ţ": "t",
	"Ť": "T",
	"ť": "t",
	"Ŧ": "T",
	"ŧ": "t",
	"Ũ": "U",
	"ũ": "u",
	"Ū": "U",
	"ū": "u",
	"Ŭ": "U",
	"ŭ": "u",
	"Ů": "U",
	"ů": "u",
	"Ű": "U",
	"ű": "u",
	"Ų": "U",
	"ų": "u",
	"Ŵ": "W",
	"ŵ": "w",
	"Ŷ": "Y",
	"ŷ": "y",
	"Ÿ": "Y",
	"Ź": "Z",
	"ź": "z",
	"Ż": "Z",
	"ż": "z",
	"Ž": "Z",
	"ž": "z",
	"ſ": "s",
	"Ə": "E",
	"ǝ": "e",
	"Ș": "S",
	"ș": "s",
	"Ț": "T",
	"ț": "t",
	"€": "E",
	"£": "",
	"Ơ": "O",
	"ơ": "o",
	"Ư": "U",
	"ư": "u",
	"Ầ": "A",
	"ầ": "a",
	"Ằ": "A",
	"ằ": "a",
	"Ề": "E",
	"ề": "e",
	"Ồ": "O",
	"ồ": "o",
	"Ờ": "O",
	"ờ": "o",
	"Ừ": "U",
	"ừ": "u",
	"Ỳ": "Y",
	"ỳ": "y",
	"Ả": "A",
	"ả": "a",
	"Ẩ": "A",
	"ẩ": "a",
	"Ẳ": "A",
	"ẳ": "a",
	"Ẻ": "E",
	"ẻ": "e",
	"Ể": "E",
	"ể": "e",
	"Ỉ": "I",
	"ỉ": "i",
	"Ỏ": "O",
	"ỏ": "o",
	"Ổ": "O",
	"ổ": "o",
	"Ở": "O",
	"ở": "o",
	"Ủ": "U",
	"ủ": "u",
	"Ử": "U",
	"ử": "u",
	"Ỷ": "Y",
	"ỷ": "y",
	"Ẫ": "A",
	"ẫ": "a",
	"Ẵ": "A",
	"ẵ": "a",
	"Ẽ": "E",
	"ẽ": "e",
	"Ễ": "E",
	"ễ": "e",
	"Ỗ": "O",
	"ỗ": "o",
	"Ỡ": "O",
	"ỡ": "o",
	"Ữ": "U",
	"ữ": "u",
	"Ỹ": "Y",
	"ỹ": "y",
	"Ấ": "A",
	"ấ": "a",
	"Ắ": "A",
	"ắ": "a",
	"Ế": "E",
	"ế": "e",
	"Ố": "O",
	"ố": "o",
	"Ớ": "O",
	"ớ": "o",
	"Ứ": "U",
	"ứ": "u",
	"Ạ": "A",
	"ạ": "a",
	"Ậ": "A",
	"ậ": "a",
	"Ặ": "A",
	"ặ": "a",
	"Ẹ": "E",
	"ẹ": "e",
	"Ệ": "E",
	"ệ": "e",
	"Ị": "I",
	"ị": "i",
	"Ọ": "O",
	"ọ": "o",
	"Ộ": "O",
	"ộ": "o",
	"Ợ": "O",
	"ợ": "o",
	"Ụ": "U",
	"ụ": "u",
	"Ự": "U",
	"ự": "u",
	"Ỵ": "Y",
	"ỵ": "y",
	"ɑ": "a",
	"Ǖ": "U",
	"ǖ": "u",
	"Ǘ": "U",
	"ǘ": "u",
	"Ǎ": "A",
	"ǎ": "a",
	"Ǐ": "I",
	"ǐ": "i",
	"Ǒ": "O",
	"ǒ": "o",
	"Ǔ": "U",
	"ǔ": "u",
	"Ǚ": "U",
	"ǚ": "u",
	"Ǜ": "U",
	"ǜ": "u"
};
const CHARS_REGEXP = new RegExp(`[${Object.keys(CHARS).join("")}]`, "g");
/**
* Converts accented letters to basic Latin letters, like `remove_accents()`
* in WordPress core without a locale.
*
* @param string The string to process.
*
* @return The processed string.
*/
function removeAccents(string) {
	return string.normalize("NFC").replace(CHARS_REGEXP, (char) => CHARS[char]);
}


//# sourceMappingURL=remove-accents.mjs.map
;// ./node_modules/@wordpress/url/build-module/clean-for-slug.mjs

// packages/url/src/clean-for-slug.ts
/**
* Strips HTML tags like PHP's `strip_tags()`: a `<` not followed by ASCII
* whitespace opens a tag, even an unclosed one, nested `<` need as many `>`,
* a `>` inside quotes doesn't close a tag, and a comment ends at `-->`.
*
* @param string The string to process.
*
* @return The string without HTML tags.
*/
function stripTags(string) {
	let result = "";
	let depth = 0;
	let quote = "";
	for (let i = 0; i < string.length; i++) {
		const char = string[i];
		if (depth === 0) {
			if (char === "<" && !/[ \t\n\v\f\r]/.test(string[i + 1] ?? "")) {
				if (string.startsWith("<!--", i)) {
					const end = string.indexOf("-->", i + 2);
					if (end === -1) break;
					i = end + 2;
				} else depth = 1;
			} else result += char;
		} else if (quote) {
			if (char === quote) quote = "";
		} else if (char === "\"" || char === "'") quote = char;
		else if (char === "<") depth++;
		else if (char === ">") depth--;
	}
	return result;
}
/**
* Truncates a string like PHP's `utf8_uri_encode()` with a length: keeps the
* characters whose percent-encoded form fits in the length, counting each
* ASCII character as one and any other character as three per UTF-8 byte,
* without splitting a character.
*
* @param string The string to process.
* @param length The maximum length of the percent-encoded string.
*
* @return The truncated string.
*/
function truncateEncoded(string, length) {
	let result = "";
	let encodedLength = 0;
	for (const char of string) {
		const codePoint = char.codePointAt(0) ?? 0;
		let charLength = 12;
		if (codePoint < 128) charLength = 1;
		else if (codePoint < 2048) charLength = 6;
		else if (codePoint < 65536) charLength = 9;
		if (encodedLength + charLength > length) break;
		result += char;
		encodedLength += charLength;
	}
	return result;
}
/**
* Performs some basic cleanup of a string for use as a post slug.
*
* This replicates what `sanitize_title()` does in WordPress core when saving
* a post, without the `sanitize_title` filters and the locale-specific
* transliterations of `remove_accents()`, so it can only approximate what the
* slug will be. It works on decoded characters, while WordPress returns the
* slug percent-encoded, and keeps percent-encoded octets as they are. Like
* WordPress, it keeps what fits in 200 percent-encoded characters.
*
* Converts accented letters to basic Latin letters, and strips HTML tags and
* entities. Converts spaces, periods, forward slashes, dashes and wide spaces
* to hyphens. Strips the punctuation
* and invisible characters WordPress strips, and any other ASCII character
* that isn't a letter, a number, an underscore or a hyphen. Keeps other
* characters, such as letters of other scripts and emoji. Converts the
* string to lowercase.
*
* @param string Title or slug to be processed.
*
* @return Processed string.
*/
function cleanForSlug(string) {
	if (!string) return "";
	return truncateEncoded(stripTags(removeAccents(string)).replace(/%(?![0-9a-fA-F]{2})/g, "").toLowerCase(), 200).replace(/[\u00a0\u2011\u2013\u2014/]/g, "-").replace(/&nbsp;|&#8209;|&#160;|&ndash;|&#8211;|&mdash;|&#8212;/g, "-").replace(/[\u00ad\u00a1\u00bf\u00ab\u00bb\u2039\u203a\u2018-\u201f\u2022\u00a9\u00ae\u00b0\u2026\u2122\u00b4\u02ca\u0301\u0341\u0300\u0304\u030c\u200b-\u200f\u202a-\u202e\ufeff\ufffc]/g, "").replace(/[\u2000-\u200a\u2028\u2029\u202f]/g, "-").replace(/\u00d7/g, "x").replace(/&.+?;/g, "").replace(/\./g, "-").replace(/[^%a-z0-9 _\-\u0080-\uffff]/g, "").replace(/ +/g, "-").replace(/-+/g, "-").replace(/(^-+)|(-+$)/g, "");
}


//# sourceMappingURL=clean-for-slug.mjs.map
;// ./node_modules/@wordpress/url/build-module/get-filename.mjs
// packages/url/src/get-filename.ts
/**
* Returns the filename part of the URL.
*
* @param url The full URL.
*
* @example
* ```js
* const filename1 = getFilename( 'http://localhost:8080/this/is/a/test.jpg' ); // 'test.jpg'
* const filename2 = getFilename( '/this/is/a/test.png' ); // 'test.png'
* ```
*
* @return The filename part of the URL.
*/
function getFilename(url) {
	let filename;
	if (!url) return;
	try {
		filename = new URL(url, "http://example.com").pathname.split("/").pop();
	} catch {}
	if (filename) return filename;
}


//# sourceMappingURL=get-filename.mjs.map
;// ./node_modules/@wordpress/url/build-module/normalize-path.mjs

// packages/url/src/normalize-path.ts
/**
* Given a path, returns a normalized path where equal query parameter values
* will be treated as identical, regardless of order they appear in the original
* text.
*
* @param path Original path.
*
* @return Normalized path.
*/
function normalizePath(path) {
	const separatorIndex = path.indexOf("?");
	if (separatorIndex === -1) return path;
	const base = path.slice(0, separatorIndex);
	const query = path.slice(separatorIndex + 1);
	if (!query) return base;
	return base + "?" + query.split("&").map((entry) => entry.split("=")).map((pair) => pair.map(safeDecodeURIComponent)).sort((a, b) => a[0].localeCompare(b[0])).map((pair) => pair.map(encodeURIComponent)).map((pair) => pair.join("=")).join("&");
}


//# sourceMappingURL=normalize-path.mjs.map
;// ./node_modules/@wordpress/url/build-module/prepend-https.mjs

// packages/url/src/prepend-https.ts
/**
* Prepends "https://" to a url, if it looks like something that is meant to be a TLD.
*
* Note: this will not replace "http://" with "https://".
*
* @param url The URL to test.
*
* @example
* ```js
* const actualURL = prependHTTPS( 'wordpress.org' ); // https://wordpress.org
* ```
*
* @return The updated URL.
*/
function prependHTTPS(url) {
	if (!url) return url;
	if (url.startsWith("http://")) return url;
	url = prependHTTP(url);
	return url.replace(/^http:/, "https:");
}


//# sourceMappingURL=prepend-https.mjs.map
;// ./node_modules/@wordpress/url/build-module/index.mjs






























(window.wp = window.wp || {}).url = __webpack_exports__;
/******/ })()
;