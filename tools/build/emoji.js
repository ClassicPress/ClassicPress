/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */

// The grunt tasks we are using do not support async replacement.
const request = require( 'sync-request' );

let grunt = null;

exports.setGruntReference = _grunt => {
	grunt = _grunt;
};

function callGitHubAPI( url ) {
	// Grunt error handling is bad
	try {

		grunt.log.writeln( 'GET ' + url );
		const res = request(
			'GET',
			url,
			{
				headers: { 'User-Agent': 'Request' },
				json: true
			}
		);

		const body = res.getBody( 'UTF-8' );
		const json = JSON.parse( body );

		if ( true === json.truncated ) {
			grunt.fatal(
				'Emojis not built due to truncated response from: '
				+ url
			);
		}

		return json;

	} catch ( e ) {
		if ( /rate limit exceeded/.test( e.message ) ) {
			const rateLimits = callGitHubAPI( 'https://api.github.com/rate_limit' );
			grunt.log.writeln(
				'GitHub API rate limit resets at: '.yellow
				+ new Date( rateLimits.rate.reset * 1000 )
			);
		}
		grunt.fatal( e.message );
	}
}

exports.replaceEmojiRegex = () => {
	grunt.log.writeln( 'Fetching list of Twemoji files...' );

	// Fetch a list of the files that Twemoji supplies

	const urlMaster = 'https://api.github.com/repos/twitter/twemoji/commits/6f3545b9';
	let res = callGitHubAPI( urlMaster );
	res = callGitHubAPI( res.commit.tree.url );

	let node;

	[ '2', 'svg' ].forEach( dir => {
		node = res.tree.find( node => node.path === dir );
		if ( ! node ) {
			grunt.fatal( "Directory '" + dir + "' not found" );
		}
		node = callGitHubAPI( node.url );
	} );

	// Convert the list of emoji names into PHP code

	const entityNames = node.tree.map( e => e.path );

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
		.filter( val => val.length >= 8 )
		.join( '\', \'' );

	// Create a list of all characters used by the emoji list
	partials = partials.replace( /-/g, ',' );

	// Set automatically removes duplicates
	const partialsSet = new Set( partials.split( '\n' ) );

	// Convert the partials list to PHP array syntax
	partials = Array.from( partialsSet )
		.filter( val => val.length >= 8 )
		.join( '\', \'' );

	let replacement = '// START: emoji arrays\n';
	replacement += `\t$entities = array( ${entities} );\n`;
	replacement += `\t$partials = array( ${partials} );\n`;
	replacement += '\t// END: emoji arrays';

	return replacement;
};
