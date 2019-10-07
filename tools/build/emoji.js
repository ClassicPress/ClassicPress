/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */

const fs = require( 'fs' );

// The grunt tasks we are using do not support async replacement.
const request = require( 'sync-request' );

let grunt = null;

exports.setGruntReference = _grunt => {
	grunt = _grunt;
};

// See https://github.com/ClassicPress/ClassicPress-APIs/tree/master/twemoji
function callTwemojiFilesAPI( url ) {
	// Grunt error handling is bad
	try {
		grunt.log.writeln( 'GET ' + url );
		const res = request( 'GET', url, { json: true } );

		const body = res.getBody( 'UTF-8' );
		const json = JSON.parse( body );

		return json;
	} catch ( e ) {
		grunt.fatal( e.message );
	}
}

exports.replaceEmojiRegex = () => {
	grunt.log.writeln( 'Fetching list of Twemoji files...' );

	// Fetch a list of the files that Twemoji supplies

	const entityNames = callTwemojiFilesAPI(
		'https://api-v1.classicpress.net/twemoji/6f3545b9_2_svg.json'
	);

	// Convert the list of emoji names into PHP code

	let entities = entityNames.join( '\n' );

	// Tidy up the file list
	entities = entities.replace( /\.svg/g, '' );
	entities = entities.replace( /^$/g, '' );

	// Convert the emoji entities to HTML entities
	entities = entities.replace( /([a-z0-9]+)/g, '&#x$1;' );
	let partials = entities;

	// Remove the hyphens between the HTML entities
	entities = entities.replace( /-/g, '' );

	// Sort the entities by length, so the longest emoji will be found first
	// Secondary sort by JavaScript default sort order for consistency
	const emojiArray = entities.split( '\n' ).sort( ( a, b ) => {
		if ( b.length > a.length ) {
			return 1;
		} else if ( a.length > b.length ) {
			return -1;
		} else if ( b > a ) {
			return 1;
		} else if ( a > b ) {
			return -1;
		} else {
			return 0;
		}
	} );

	// Convert the entities list to PHP array syntax
	entities = emojiArray
		.filter( val => val.length >= 8 );
	if ( process.env.DEBUG_TWEMOJI_FILES ) {
		fs.writeFileSync(
			`entities-${process.env.DEBUG_TWEMOJI_FILES}.txt`,
			entities.join( '\n' ) + '\n'
		);
	}
	entities = entities.join( '\', \'' );

	// Create a list of all characters used by the emoji list
	partials = partials.replace( /-/g, '\n' );

	// Set automatically removes duplicates
	const partialsSet = new Set( partials.split( '\n' ) );

	// Convert the partials list to PHP array syntax
	partials = Array.from( partialsSet )
		.filter( val => val.length >= 8 )
		.sort();
	if ( process.env.DEBUG_TWEMOJI_FILES ) {
		fs.writeFileSync(
			`partials-${process.env.DEBUG_TWEMOJI_FILES}.txt`,
			partials.join( '\n' ) + '\n'
		);
	}
	partials = partials.join( '\', \'' );

	let replacement = '// START: emoji arrays\n';
	replacement += `\t$entities = array( '${entities}' );\n`;
	replacement += `\t$partials = array( '${partials}' );\n`;
	replacement += '\t// END: emoji arrays';

	return replacement;
};
