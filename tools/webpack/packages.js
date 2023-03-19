/* jshint es3: false, esversion: 9 */
/**
 * Internal dependencies
 */
const { normalizeJoin, baseConfig, baseDir, camelCaseDash } = require( './shared' );
const { dependencies } = require( '../../package' );

module.exports = function( env = { environment: 'production', buildTarget: false } ) {
	const mode = env.environment;
	const suffix = mode === 'production' ? '.min' : '';
	let buildTarget = env.buildTarget + '/wp-includes';

	const WORDPRESS_NAMESPACE = '@wordpress/';
	const packages = Object.keys( dependencies )
		.filter( ( packageName ) =>
 			packageName.startsWith( WORDPRESS_NAMESPACE )
 		)
		.map( ( packageName ) => packageName.replace( WORDPRESS_NAMESPACE, '' ) );

	const config = {
		...baseConfig( env ),
		entry: packages.reduce( ( memo, packageName ) => {
			memo[ packageName] = {
				import: memo[ packageName ] = normalizeJoin( baseDir, `node_modules/@wordpress/${ packageName }` ),
				library: {
					name: ['wp', camelCaseDash( packageName ) ],
					type: 'window',
					export: undefined,
				},
			};

			return memo;
		}, {} ),
		output: {
			devtoolNamespace: 'wp',
			filename: `[name]${ suffix }.js`,
			path: normalizeJoin( baseDir, `${ buildTarget }/js/dist` ),
		},
	};

	return config;
};
