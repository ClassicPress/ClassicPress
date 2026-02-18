const packagesConfig = require( './tools/webpack/packages' );

module.exports = function( env = { buildTarget: false, minify: false } ) {
	if ( ! env.buildTarget ) {
		return false;
	}

	return packagesConfig( env );
};
