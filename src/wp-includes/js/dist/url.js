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
function isURL(url) {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
}


;// ./node_modules/@wordpress/url/build-module/is-email.mjs
// packages/url/src/is-email.ts
var EMAIL_REGEXP = /^(mailto:)?[a-z0-9._%+-]+@[a-z0-9][a-z0-9.-]*\.[a-z]{2,63}$/i;
function isEmail(email) {
  return EMAIL_REGEXP.test(email);
}


;// ./node_modules/@wordpress/url/build-module/is-phone-number.mjs
// packages/url/src/is-phone-number.ts
var PHONE_REGEXP = /^(tel:)?(\+)?\d{6,15}$/;
function isPhoneNumber(phoneNumber) {
  phoneNumber = phoneNumber.replace(/[-.() ]/g, "");
  return PHONE_REGEXP.test(phoneNumber);
}


;// ./node_modules/@wordpress/url/build-module/get-protocol.mjs
// packages/url/src/get-protocol.ts
function getProtocol(url) {
  const matches = /^([^\s:]+:)/.exec(url);
  if (matches) {
    return matches[1];
  }
}


;// ./node_modules/@wordpress/url/build-module/is-valid-protocol.mjs
// packages/url/src/is-valid-protocol.ts
function isValidProtocol(protocol) {
  if (!protocol) {
    return false;
  }
  return /^[a-z\-.\+]+[0-9]*:$/i.test(protocol);
}


;// ./node_modules/@wordpress/url/build-module/get-authority.mjs
// packages/url/src/get-authority.ts
function getAuthority(url) {
  const matches = /^[^\/\s:]+:(?:\/\/)?\/?([^\/\s#?]+)[\/#?]{0,1}\S*$/.exec(
    url
  );
  if (matches) {
    return matches[1];
  }
}


;// ./node_modules/@wordpress/url/build-module/is-valid-authority.mjs
// packages/url/src/is-valid-authority.ts
function isValidAuthority(authority) {
  if (!authority) {
    return false;
  }
  return /^[^\s#?]+$/.test(authority);
}


;// ./node_modules/@wordpress/url/build-module/get-path.mjs
// packages/url/src/get-path.ts
function getPath(url) {
  const matches = /^[^\/\s:]+:(?:\/\/)?[^\/\s#?]+[\/]([^\s#?]+)[#?]{0,1}\S*$/.exec(url);
  if (matches) {
    return matches[1];
  }
}


;// ./node_modules/@wordpress/url/build-module/is-valid-path.mjs
// packages/url/src/is-valid-path.ts
function isValidPath(path) {
  if (!path) {
    return false;
  }
  return /^[^\s#?]+$/.test(path);
}


;// ./node_modules/@wordpress/url/build-module/get-query-string.mjs
// packages/url/src/get-query-string.ts
function getQueryString(url) {
  let query;
  try {
    query = new URL(url, "http://example.com").search.substring(1);
  } catch {
  }
  if (query) {
    return query;
  }
}


;// ./node_modules/@wordpress/url/build-module/build-query-string.mjs
// packages/url/src/build-query-string.ts
function buildQueryString(data) {
  let string = "";
  const stack = Object.entries(data);
  let pair;
  while (pair = stack.shift()) {
    let [key, value] = pair;
    const hasNestedData = Array.isArray(value) || value && value.constructor === Object;
    if (hasNestedData) {
      const valuePairs = Object.entries(value).reverse();
      for (const [member, memberValue] of valuePairs) {
        stack.unshift([`${key}[${member}]`, memberValue]);
      }
    } else if (value !== void 0) {
      if (value === null) {
        value = "";
      }
      string += "&" + [key, String(value)].map(encodeURIComponent).join("=");
    }
  }
  return string.substr(1);
}


;// ./node_modules/@wordpress/url/build-module/is-valid-query-string.mjs
// packages/url/src/is-valid-query-string.ts
function isValidQueryString(queryString) {
  if (!queryString) {
    return false;
  }
  return /^[^\s#?\/]+$/.test(queryString);
}


;// ./node_modules/@wordpress/url/build-module/get-path-and-query-string.mjs
// packages/url/src/get-path-and-query-string.ts

function getPathAndQueryString(url) {
  const path = getPath(url);
  const queryString = getQueryString(url);
  let value = "/";
  if (path) {
    value += path;
  }
  if (queryString) {
    value += `?${queryString}`;
  }
  return value;
}


;// ./node_modules/@wordpress/url/build-module/get-fragment.mjs
// packages/url/src/get-fragment.ts
function getFragment(url) {
  const matches = /^\S+?(#[^\s\?]*)/.exec(url);
  if (matches) {
    return matches[1];
  }
}


;// ./node_modules/@wordpress/url/build-module/is-valid-fragment.mjs
// packages/url/src/is-valid-fragment.ts
function isValidFragment(fragment) {
  if (!fragment) {
    return false;
  }
  return /^#[^\s#?\/]*$/.test(fragment);
}


;// ./node_modules/@wordpress/url/build-module/safe-decode-uri-component.mjs
// packages/url/src/safe-decode-uri-component.ts
function safeDecodeURIComponent(uriComponent) {
  try {
    return decodeURIComponent(uriComponent);
  } catch {
    return uriComponent;
  }
}


;// ./node_modules/@wordpress/url/build-module/get-query-args.mjs
// packages/url/src/get-query-args.ts


function setPath(object, path, value) {
  const length = path.length;
  const lastIndex = length - 1;
  for (let i = 0; i < length; i++) {
    let key = path[i];
    if (!key && Array.isArray(object)) {
      key = object.length.toString();
    }
    key = ["__proto__", "constructor", "prototype"].includes(key) ? key.toUpperCase() : key;
    const isNextKeyArrayIndex = !isNaN(Number(path[i + 1]));
    object[key] = i === lastIndex ? (
      // If at end of path, assign the intended value.
      value
    ) : (
      // Otherwise, advance to the next object in the path, creating
      // it if it does not yet exist.
      object[key] || (isNextKeyArrayIndex ? [] : {})
    );
    if (Array.isArray(object[key]) && !isNextKeyArrayIndex) {
      object[key] = { ...object[key] };
    }
    object = object[key];
  }
}
function getQueryArgs(url) {
  return (getQueryString(url) || "").replace(/\+/g, "%20").split("&").reduce((accumulator, keyValue) => {
    const separatorIndex = keyValue.indexOf("=");
    const hasValue = separatorIndex !== -1;
    const key = safeDecodeURIComponent(
      hasValue ? keyValue.slice(0, separatorIndex) : keyValue
    );
    if (key) {
      const value = hasValue ? safeDecodeURIComponent(
        keyValue.slice(separatorIndex + 1)
      ) : "";
      const segments = key.replace(/\]/g, "").split("[");
      setPath(accumulator, segments, value);
    }
    return accumulator;
  }, /* @__PURE__ */ Object.create(null));
}


;// ./node_modules/@wordpress/url/build-module/add-query-args.mjs
// packages/url/src/add-query-args.ts



function addQueryArgs(url = "", args) {
  if (!args || !Object.keys(args).length) {
    return url;
  }
  const fragment = getFragment(url) || "";
  let baseUrl = url.replace(fragment, "");
  const queryStringIndex = url.indexOf("?");
  if (queryStringIndex !== -1) {
    args = Object.assign(getQueryArgs(url), args);
    baseUrl = baseUrl.substr(0, queryStringIndex);
  }
  return baseUrl + "?" + buildQueryString(args) + fragment;
}


;// ./node_modules/@wordpress/url/build-module/get-query-arg.mjs
// packages/url/src/get-query-arg.ts

function getQueryArg(url, arg) {
  return getQueryArgs(url)[arg];
}


;// ./node_modules/@wordpress/url/build-module/has-query-arg.mjs
// packages/url/src/has-query-arg.ts

function hasQueryArg(url, arg) {
  return getQueryArg(url, arg) !== void 0;
}


;// ./node_modules/@wordpress/url/build-module/remove-query-args.mjs
// packages/url/src/remove-query-args.ts


function removeQueryArgs(url, ...args) {
  const fragment = url.replace(/^[^#]*/, "");
  url = url.replace(/#.*/, "");
  const queryStringIndex = url.indexOf("?");
  if (queryStringIndex === -1) {
    return url + fragment;
  }
  const query = getQueryArgs(url);
  const baseURL = url.substr(0, queryStringIndex);
  args.forEach((arg) => delete query[arg]);
  const queryString = buildQueryString(query);
  const updatedUrl = queryString ? baseURL + "?" + queryString : baseURL;
  return updatedUrl + fragment;
}


;// ./node_modules/@wordpress/url/build-module/prepend-http.mjs
// packages/url/src/prepend-http.ts

var USABLE_HREF_REGEXP = /^(?:[a-z]+:|#|\?|\.|\/)/i;
function prependHTTP(url) {
  if (!url) {
    return url;
  }
  url = url.trim();
  if (!USABLE_HREF_REGEXP.test(url) && !isEmail(url)) {
    return "http://" + url;
  }
  return url;
}


;// ./node_modules/@wordpress/url/build-module/safe-decode-uri.mjs
// packages/url/src/safe-decode-uri.ts
function safeDecodeURI(uri) {
  try {
    return decodeURI(uri);
  } catch {
    return uri;
  }
}


;// ./node_modules/@wordpress/url/build-module/filter-url-for-display.mjs
// packages/url/src/filter-url-for-display.ts
function filterURLForDisplay(url, maxLength = null) {
  if (!url) {
    return "";
  }
  let filteredURL = url.replace(/^[a-z\-.\+]+[0-9]*:(\/\/)?/i, "").replace(/^www\./i, "");
  if (filteredURL.match(/^[^\/]+\/$/)) {
    filteredURL = filteredURL.replace("/", "");
  }
  const fileRegexp = /\/([^\/?]+)\.(?:[\w]+)(?=\?|$)/;
  if (!maxLength || filteredURL.length <= maxLength || !filteredURL.match(fileRegexp)) {
    return filteredURL;
  }
  filteredURL = filteredURL.split("?")[0];
  const urlPieces = filteredURL.split("/");
  const file = urlPieces[urlPieces.length - 1];
  if (file.length <= maxLength) {
    return "…" + filteredURL.slice(-maxLength);
  }
  const index = file.lastIndexOf(".");
  const [fileName, extension] = [
    file.slice(0, index),
    file.slice(index + 1)
  ];
  const truncatedFile = fileName.slice(-3) + "." + extension;
  return (
    // A negative end would be read as an offset from the end of the string,
    // keeping most of the file name and returning more than `maxLength`.
    file.slice(0, Math.max(0, maxLength - truncatedFile.length - 1)) + "…" + truncatedFile
  );
}


;// ./node_modules/@wordpress/url/build-module/remove-accents.mjs
// packages/url/src/remove-accents.ts
var CHARS = {
  // Decompositions for Latin-1 Supplement.
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
  // Decompositions for Latin Extended-A.
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
  // Decompositions for Latin Extended-B.
  "Ə": "E",
  "ǝ": "e",
  "Ș": "S",
  "ș": "s",
  "Ț": "T",
  "ț": "t",
  // Euro sign.
  "€": "E",
  // GBP (Pound) sign.
  "£": "",
  // Vowels with diacritic (Vietnamese). Unmarked.
  "Ơ": "O",
  "ơ": "o",
  "Ư": "U",
  "ư": "u",
  // Grave accent.
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
  // Hook.
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
  // Tilde.
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
  // Acute accent.
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
  // Dot below.
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
  // Vowels with diacritic (Chinese, Hanyu Pinyin).
  "ɑ": "a",
  // Macron.
  "Ǖ": "U",
  "ǖ": "u",
  // Acute accent.
  "Ǘ": "U",
  "ǘ": "u",
  // Caron.
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
  // Grave accent.
  "Ǜ": "U",
  "ǜ": "u"
};
var CHARS_REGEXP = new RegExp(
  `[${Object.keys(CHARS).join("")}]`,
  "g"
);
function removeAccents(string) {
  return string.normalize("NFC").replace(CHARS_REGEXP, (char) => CHARS[char]);
}


;// ./node_modules/@wordpress/url/build-module/clean-for-slug.mjs
// packages/url/src/clean-for-slug.ts

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
          if (end === -1) {
            break;
          }
          i = end + 2;
        } else {
          depth = 1;
        }
      } else {
        result += char;
      }
    } else if (quote) {
      if (char === quote) {
        quote = "";
      }
    } else if (char === '"' || char === "'") {
      quote = char;
    } else if (char === "<") {
      depth++;
    } else if (char === ">") {
      depth--;
    }
  }
  return result;
}
function truncateEncoded(string, length) {
  let result = "";
  let encodedLength = 0;
  for (const char of string) {
    const codePoint = char.codePointAt(0) ?? 0;
    let charLength = 12;
    if (codePoint < 128) {
      charLength = 1;
    } else if (codePoint < 2048) {
      charLength = 6;
    } else if (codePoint < 65536) {
      charLength = 9;
    }
    if (encodedLength + charLength > length) {
      break;
    }
    result += char;
    encodedLength += charLength;
  }
  return result;
}
function cleanForSlug(string) {
  if (!string) {
    return "";
  }
  const lowercase = stripTags(removeAccents(string)).replace(/%(?![0-9a-fA-F]{2})/g, "").toLowerCase();
  return (
    // Like WordPress, keep what fits in 200 percent-encoded characters,
    // the length of the slug column.
    truncateEncoded(lowercase, 200).replace(/[\u00a0\u2011\u2013\u2014/]/g, "-").replace(
      /&nbsp;|&#8209;|&#160;|&ndash;|&#8211;|&mdash;|&#8212;/g,
      "-"
    ).replace(
      /[\u00ad\u00a1\u00bf\u00ab\u00bb\u2039\u203a\u2018-\u201f\u2022\u00a9\u00ae\u00b0\u2026\u2122\u00b4\u02ca\u0301\u0341\u0300\u0304\u030c\u200b-\u200f\u202a-\u202e\ufeff\ufffc]/g,
      ""
    ).replace(/[\u2000-\u200a\u2028\u2029\u202f]/g, "-").replace(/\u00d7/g, "x").replace(/&.+?;/g, "").replace(/\./g, "-").replace(/[^%a-z0-9 _\-\u0080-\uffff]/g, "").replace(/ +/g, "-").replace(/-+/g, "-").replace(/(^-+)|(-+$)/g, "")
  );
}


;// ./node_modules/@wordpress/url/build-module/get-filename.mjs
// packages/url/src/get-filename.ts
function getFilename(url) {
  let filename;
  if (!url) {
    return;
  }
  try {
    filename = new URL(url, "http://example.com").pathname.split("/").pop();
  } catch {
  }
  if (filename) {
    return filename;
  }
}


;// ./node_modules/@wordpress/url/build-module/normalize-path.mjs
// packages/url/src/normalize-path.ts

function normalizePath(path) {
  const separatorIndex = path.indexOf("?");
  if (separatorIndex === -1) {
    return path;
  }
  const base = path.slice(0, separatorIndex);
  const query = path.slice(separatorIndex + 1);
  if (!query) {
    return base;
  }
  return base + "?" + query.split("&").map((entry) => entry.split("=")).map((pair) => pair.map(safeDecodeURIComponent)).sort((a, b) => a[0].localeCompare(b[0])).map((pair) => pair.map(encodeURIComponent)).map((pair) => pair.join("=")).join("&");
}


;// ./node_modules/@wordpress/url/build-module/prepend-https.mjs
// packages/url/src/prepend-https.ts

function prependHTTPS(url) {
  if (!url) {
    return url;
  }
  if (url.startsWith("http://")) {
    return url;
  }
  url = prependHTTP(url);
  return url.replace(/^http:/, "https:");
}


;// ./node_modules/@wordpress/url/build-module/index.mjs
// packages/url/src/index.ts






























(window.wp = window.wp || {}).url = __webpack_exports__;
/******/ })()
;