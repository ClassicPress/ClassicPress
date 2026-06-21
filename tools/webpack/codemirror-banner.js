/* jshint node:true */
/* jshint esversion: 6 */
const fs = require( 'fs' );
const path = require( 'path' );

const cmPkgPath = path.join(
	path.dirname( require.resolve( '@codemirror/state' ) ),
	'..',
	'package.json'
);
const codemirrorVersion = JSON.parse( fs.readFileSync( cmPkgPath, 'utf8' ) ).version;

if ( typeof codemirrorVersion !== 'string' ) {
	throw new Error( 'Could not read CodeMirror version from package.json' );
}

module.exports = `/*! This file is auto-generated from CodeMirror - v${ codemirrorVersion }

CodeMirror, copyright (c) by Marijn Haverbeke and others
Distributed under an MIT license: http://codemirror.net/LICENSE

This is CodeMirror (http://codemirror.net), a code editor
implemented in JavaScript on top of the browser's DOM.

You can find some technical background for some of the code below
at http://marijnhaverbeke.nl/blog/#cm-internals .
*/`;
