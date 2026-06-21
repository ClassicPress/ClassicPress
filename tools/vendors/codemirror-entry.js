// ─────────────────────────────────────────────────────────────────────────────
// CodeMirror 6 — replaces the CM5 side-effect loader
// Exposes window.wp.CodeMirror as a collection of extension factories
// so consuming code can compose editors the CM6 way.
// ─────────────────────────────────────────────────────────────────────────────

// Core
import { EditorState, Compartment } from '@codemirror/state';
import {
	EditorView,
	keymap,
	lineNumbers,
	highlightActiveLine,
	highlightActiveLineGutter,
	drawSelection,
	dropCursor,
	rectangularSelection,
	crosshairCursor,
	highlightSpecialChars,
	scrollPastEnd,
	placeholder
} from '@codemirror/view';

// Commands / keymaps
import {
	defaultKeymap,
	history,
	historyKeymap,
	indentWithTab,
	toggleComment
} from '@codemirror/commands';

// Search
import {
	search,
	searchKeymap,
	highlightSelectionMatches
} from '@codemirror/search';

// Autocomplete (replaces hint addons)
import {
	autocompletion,
	completionKeymap,
	closeBrackets,
	closeBracketsKeymap
} from '@codemirror/autocomplete';

// Lint (replaces lint addons)
import { linter, lintKeymap, lintGutter } from '@codemirror/lint';

// Language support
import {
	syntaxHighlighting,
	defaultHighlightStyle,
	bracketMatching,
	foldGutter,
	foldKeymap,
	indentOnInput
} from '@codemirror/language';

// Official language packages
import { css }        from '@codemirror/lang-css';
import { html }       from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { json }       from '@codemirror/lang-json';
import { markdown }   from '@codemirror/lang-markdown';
import { php }        from '@codemirror/lang-php';
import { sql }        from '@codemirror/lang-sql';
import { xml }        from '@codemirror/lang-xml';
import { yaml }       from '@codemirror/lang-yaml';
import { sass }       from '@codemirror/lang-sass';

// Legacy modes (nginx, shell, diff, http, clike, etc.)
import { StreamLanguage } from '@codemirror/language';
import { nginx }          from '@codemirror/legacy-modes/mode/nginx';
import { shell }          from '@codemirror/legacy-modes/mode/shell';
import { diff }           from '@codemirror/legacy-modes/mode/diff';
import { http }           from '@codemirror/legacy-modes/mode/http';

// ─────────────────────────────────────────────────────────────────────────────
// Language map — replaces CM5 mode strings
// ─────────────────────────────────────────────────────────────────────────────
const languages = {
	css:        () => css(),
	html:       () => html(),
	javascript: () => javascript(),
	json:       () => json(),
	markdown:   () => markdown(),
	php:        () => php(),
	sql:        () => sql(),
	xml:        () => xml(),
	yaml:       () => yaml(),
	sass:       () => sass(),
	scss:       () => sass( { indented: false } ),
	// Legacy-mode wrappers
	nginx:      () => StreamLanguage.define( nginx ),
	shell:      () => StreamLanguage.define( shell ),
	diff:       () => StreamLanguage.define( diff ),
	http:       () => StreamLanguage.define( http ),
	// htmlmixed, gfm: html() and markdown() cover these natively in CM6
	htmlmixed:  () => html(),
	gfm:        () => markdown( { addKeymap: false } )
};

// ─────────────────────────────────────────────────────────────────────────────
// Extension bundles — replaces CM5 addon require() calls
// ─────────────────────────────────────────────────────────────────────────────

/** Equivalent of CM5 lineNumbers, activeLine, activeLineGutter */
const displayExtensions = [
	lineNumbers(),
	highlightActiveLine(),
	highlightActiveLineGutter(),
	highlightSpecialChars(),
	drawSelection(),
	dropCursor()
];

/** Equivalent of CM5 fold addons */
const foldExtensions = [
	foldGutter(),
	keymap.of( foldKeymap )
];

/** Equivalent of CM5 history / undo */
const historyExtensions = [
	history(),
	keymap.of( historyKeymap )
];

/** Equivalent of CM5 search, jump-to-line, match-highlighter, matchesonscrollbar */
const searchExtensions = [
	search( { top: true } ),
	highlightSelectionMatches(),
	keymap.of( searchKeymap )
];

/** Equivalent of CM5 hint addons + autocomplete */
const hintExtensions = [
	autocompletion(),
	keymap.of( [ ...completionKeymap, ...closeBracketsKeymap ] ),
	closeBrackets()
];

/** Equivalent of CM5 comment addon */
const commentExtensions = [
	keymap.of( [
		{ key: 'Mod-/', run: toggleComment }
	] )
];

/** Equivalent of CM5 edit addons (matchbrackets, closebrackets, trailingspace, etc.) */
const editExtensions = [
	bracketMatching(),
	indentOnInput(),
	rectangularSelection(),
	crosshairCursor(),
	keymap.of( [ indentWithTab ] )
];

/** Equivalent of CM5 scroll addons */
const scrollExtensions = [
	scrollPastEnd()
];

/** Equivalent of CM5 lint addons — consumers add their own linter source */
const lintExtensions = [
	lintGutter(),
	keymap.of( lintKeymap )
];

/** Syntax highlighting — replaces CM5 theme/default */
const highlightExtensions = [
	syntaxHighlighting( defaultHighlightStyle, { fallback: true } )
];

/** All safe default extensions bundled (mirrors what the CM5 file loaded) */
const defaultExtensions = [
	...displayExtensions,
	...foldExtensions,
	...historyExtensions,
	...searchExtensions,
	...hintExtensions,
	...commentExtensions,
	...editExtensions,
	...scrollExtensions,
	...highlightExtensions,
	keymap.of( defaultKeymap )
];

// ─────────────────────────────────────────────────────────────────────────────
// Factory — create an editor (replaces CodeMirror.fromTextArea / new CodeMirror)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Create a CodeMirror 6 EditorView attached to a DOM element.
 *
 * @param {Object} config
 * @param {HTMLElement}  config.parent	  - Container element.
 * @param {string}	  [config.doc]		- Initial content.
 * @param {string}	  [config.language]   - Language key from the languages map above.
 * @param {boolean}	 [config.readOnly]   - Whether the editor is read-only.
 * @param {Array}	   [config.extensions] - Additional extensions to merge in.
 * @returns {EditorView}
 */
function createEditor( { parent, doc = '', language = null, readOnly = false, extensions = [] } ) {
	const langExtension = language && languages[ language ] ? [ languages[ language ]() ] : [];

	const state = EditorState.create( {
		doc,
		extensions: [
			...defaultExtensions,
			...langExtension,
			...extensions,
			EditorView.editable.of( ! readOnly ),
			EditorState.readOnly.of( readOnly )
		]
	} );

	return new EditorView( { state, parent } );
}

/**
 * Migrate a <textarea> to a CM6 editor (replaces CodeMirror.fromTextArea).
 *
 * @param {HTMLTextAreaElement} textarea
 * @param {Object}			  options   - Same as createEditor config, minus `parent`.
 * @returns {{ view: EditorView, syncToTextarea: Function }}
 */
function fromTextArea( textarea, options = {} ) {
	const parent = document.createElement( 'div' );
	textarea.parentNode.insertBefore( parent, textarea );
	textarea.style.display = 'none';

	const view = createEditor( {
		...options,
		parent,
		doc: textarea.value,
		extensions: [
			...( options.extensions || [] ),
			// Keep textarea in sync for form submission
			EditorView.updateListener.of( ( update ) => {
				if ( update.docChanged ) {
					textarea.value = update.state.doc.toString();
				}
			} )
		]
	} );

	return {
		view,
		syncToTextarea: () => {
			textarea.value = view.state.doc.toString();
		}
	};
}

// ─────────────────────────────────────────────────────────────────────────────
// Expose on window.wp.CodeMirror — mirrors the CM5 export shape where possible
// ─────────────────────────────────────────────────────────────────────────────

if ( ! window.wp ) {
	window.wp = {};
}

window.wp.CodeMirror = {
	// Editor creation
	createEditor,
	fromTextArea,

	// Extension bundles — consumers can pick what they need
	extensions: {
		default:   defaultExtensions,
		display:   displayExtensions,
		fold:      foldExtensions,
		history:   historyExtensions,
		search:    searchExtensions,
		hint:      hintExtensions,
		comment:   commentExtensions,
		edit:      editExtensions,
		scroll:    scrollExtensions,
		lint:      lintExtensions,
		highlight: highlightExtensions
	},

	// Language factory map — consumers call e.g. wp.CodeMirror.languages.css()
	languages,

	// CM6 primitives — for advanced consumers who build their own state/view
	EditorState,
	EditorView,
	Compartment,
	linter,
	placeholder,
	keymap
};
