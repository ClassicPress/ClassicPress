// vim: ft=javascript

const config = [];

const createPluginCommonJS = require( '@rollup/plugin-commonjs' );

const pluginCommonJS = createPluginCommonJS();

[ 'audiovideo', 'grid', 'models', 'views' ].forEach( artifact => {
	config.push( {
		input: `src/wp-includes/js/media/${artifact}.manifest.ejs`,
		output: {
			file: `src/wp-includes/js/media-${artifact}.js`,
			format: 'iife',
			strict: false,
		},
		plugins: [ pluginCommonJS ],
	} );
} );

module.exports = config;
