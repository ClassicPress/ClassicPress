/* jshint es3: false, esversion: 9 */
/* global __dirname*/
/**
 * External dependencies
 */
const TerserPlugin = require( 'terser-webpack-plugin' );
const StripSourceMapURLPlugin = require('./strip-sourcemap');
const { join } = require( 'path' );

const baseDir = join( __dirname, '../../' );

const baseConfig = ( env ) => {
	const mode = env.environment;

	const config = {
		target: 'browserslist',
		mode,
		plugins: [
			new StripSourceMapURLPlugin( env.minify ),
		],
		optimization: {
			moduleIds: 'deterministic',
			minimize: env.minify,
			minimizer: [
				new TerserPlugin( {
					parallel: true,
					terserOptions: {
						output: {
							comments: /translators:/i,
							keep_quoted_props: true,
						},
						compress: {
							passes: 2,
						},
						mangle: {
							reserved: [ '__', '_n', '_nx', '_x' ],
						},
					},
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

	return config;
};

const normalizeJoin = ( ...paths ) => join( ...paths ).replace( /\\/g, '/' );

const camelCaseDash = ( string ) => {
	return string.replace( /-([a-z])/g, ( _, letter ) => letter.toUpperCase() );
};

module.exports = {
	baseDir,
	baseConfig,
	normalizeJoin,
	camelCaseDash,
};
