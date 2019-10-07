/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */

const request = require( 'sync-request' );

let grunt = null;

exports.setGruntReference = _grunt => {
	grunt = _grunt;
};

function callGitHubAPI( url ) {
	var res = request(
		'GET',
		url,
		{
			headers: { 'User-Agent': 'Request' },
			json: true
		}
	);

	if ( res.statusCode >= 300 ) {
		grunt.fatal( 'Unable to fetch Twemoji file resource at:' + url );
	}

	var json = JSON.parse( res.getBody( 'UTF-8' ) );
	if ( 'message' in json ) {
		grunt.fatal( 'API rate limit exceeded, try again later.' );
	} else if ( true === json.truncated ) {
		grunt.fatal( 'Emojis not built due to truncated response from: ' + url );
	} else {
		return json;
	}
}

function createEmojiArray( emoji ) {
	let entityNames = [];
	let entities;
	let emojiArray;
	let partials;
	let partialsSet;
	let regex;

	for ( var k = 0; k < emoji.tree.length; k++ ) {
		entityNames.push( emoji.tree[k].path );
	}

	entities = entityNames.join( '\n' );

	// Tidy up the file list
	entities = entities.replace( /\.svg/g, '' );
	entities = entities.replace( /^$/g, '' );

	// Convert the emoji entities to HTML entities
	partials = entities = entities.replace( /([a-z0-9]+)/g, '&#x$1;' );

	// Remove the hyphens between the HTML entities
	entities = entities.replace( /-/g, '' );

	// Sort the entities list by length, so the longest emoji will be found first
	emojiArray = entities.split( '\n' ).sort( ( a, b ) => {
		return b.length - a.length;
	} );

	// Convert the entities list to PHP array syntax
	entities = `'${emojiArray.filter( val => val.length >= 8 ? val : false ).join( '\', \'' )}'`;

	// Create a list of all characters used by the emoji list
	partials = partials.replace( /-/g, ',' );

	// Set automatically removes duplicates
	partialsSet = new Set( partials.split( '\n' ) );

	// Convert the partials list to PHP array syntax
	partials = `'${Array.from( partialsSet ).filter( val => val.length >= 8 ? val : false ).join( '\', \'' )}'`;

	regex = '// START: emoji arrays\n';
	regex += `\t$entities = array( ${entities} );\n`;
	regex += `\t$partials = array( ${partials} );\n`;
	regex += '\t// END: emoji arrays';

	return regex;
}

exports.replaceEmojiRegex = () => {
	let res;
	let master;
	let twoUrl;
	let svgUrl;

	grunt.log.writeln( 'Fetching list of Twemoji files...' );

	// Fetch a list of the files that Twemoji supplies
	master = 'https://api.github.com/repos/twitter/twemoji/commits/6f3545b9';
	res = callGitHubAPI( master );

	res = callGitHubAPI( res.commit.tree.url );
	for ( var i = 0; i < res.tree.length; i++ ) {
		if ( '2' === res.tree[i].path ) {
			twoUrl = res.tree[i].url;
		}
	}

	res = callGitHubAPI( twoUrl );
	for ( var j = 0; j < res.tree.length; j++ ) {
		if ( 'svg' === res.tree[j].path ) {
			svgUrl = res.tree[j].url;
		}
	}

	res = callGitHubAPI( svgUrl );

	return createEmojiArray( res );
};
