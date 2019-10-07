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
	let partials = entities = entities.replace( /([a-z0-9]+)/g, '&#x$1;' );

	// Remove the hyphens between the HTML entities
	entities = entities.replace( /-/g, '' );

	// Sort the entities by length, so the longest emoji will be found first
	const emojiArray = entities.split( '\n' ).sort( ( a, b ) => {
		return b.length - a.length;
	} );

	// Convert the entities list to PHP array syntax
	entities = emojiArray
		.filter( val => val.length >= 8 );
	if ( process.env.DEBUG_TWEMOJI_FILES ) {
		fs.writeFileSync(
			`entities-${process.env.DEBUG_TWEMOJI_FILES}.txt`,
			entities.join( '\n' ) + '\n'
		);
		fs.writeFileSync(
			`entities-${process.env.DEBUG_TWEMOJI_FILES}-sorted.txt`,
			Array.from( entities ).sort().join( '\n' ) + '\n'
		);
	}
	entities = entities.join( '\', \'' );

	// Create a list of all characters used by the emoji list
	partials = partials.replace( /-/g, '\n' );

	// Set automatically removes duplicates
	const partialsSet = new Set( partials.split( '\n' ) );

	// Convert the partials list to PHP array syntax
	partials = Array.from( partialsSet )
		.filter( val => val.length >= 8 );
	if ( process.env.DEBUG_TWEMOJI_FILES ) {
		fs.writeFileSync(
			`partials-${process.env.DEBUG_TWEMOJI_FILES}.txt`,
			partials.join( '\n' ) + '\n'
		);
		fs.writeFileSync(
			`partials-${process.env.DEBUG_TWEMOJI_FILES}-sorted.txt`,
			Array.from( partials ).sort().join( '\n' ) + '\n'
		);
	}
	partials = partials.join( '\', \'' );

	let replacement = '// START: emoji arrays\n';
	replacement += `\t$entities = array( ${entities} );\n`;
	replacement += `\t$partials = array( ${partials} );\n`;
	replacement += '\t// END: emoji arrays';

	return replacement;
};
