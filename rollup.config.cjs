// vim: ft=javascript

const createPluginCommonJS = require( '@rollup/plugin-commonjs' );

const pluginCommonJS = createPluginCommonJS();

module.exports = [ 'audiovideo', 'grid', 'models', 'views' ].map( artifact => {
	return {
		input: `src/wp-includes/js/media/${artifact}.manifest.ejs`,
		output: {
			file: `src/wp-includes/js/media-${artifact}.js`,
			format: 'iife',
			strict: false,
		},
		plugins: [ pluginCommonJS ],
	};
} );
