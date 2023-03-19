const packagesConfig = require( './tools/webpack/packages' );

module.exports = function( env = { buildTarget: false } ) {
	if ( ! env.buildTarget ) {
		return false;
	}

	const config = [
		packagesConfig( env ),
	];

	return config;
};
