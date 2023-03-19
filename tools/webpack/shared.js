/* jshint es3: false, esversion: 9 */
/**
 * External dependencies
 */
const TerserPlugin = require( 'terser-webpack-plugin' );
const { join } = require( 'path' );

const baseDir = join( __dirname, '../../' );

const baseConfig = ( env ) => {
	const mode = env.environment;

	const config = {
		target: 'browserslist',
		mode,
		optimization: {
			moduleIds: mode === 'production' ? 'deterministic' : 'named',
			minimizer: [
				new TerserPlugin( {
					extractComments: false,
				} ),
			]
		},
		module: {
			rules: [
				{
					test: /\.js$/,
					use: [ 'source-map-loader' ],
					enforce: 'pre',
				},
			],
		},
		resolve: {
			modules: [
				baseDir,
				'node_modules',
			],
		},
		stats: 'errors-only',
	};

	if ( mode === 'development' ) {
		config.mode = 'production';
		config.optimization = {
			minimize: false,
			moduleIds: 'deterministic',
		};
	}

	return config;
};

const normalizeJoin = ( ...paths ) => join( ...paths ).replace( /\\/g, '/' );

const camelCaseDash = ( string ) => {
	return string.replace( /-([a-z])/g, ( _, letter ) => letter.toUpperCase() );
}

module.exports = {
	baseDir,
	baseConfig,
	normalizeJoin,
	camelCaseDash,
};
