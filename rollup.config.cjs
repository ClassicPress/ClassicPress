// vim: ft=javascript

const config = [];

const pluginCommonJS = require( 'rollup-plugin-commonjs' );

[ 'audiovideo', 'grid', 'models', 'views' ].forEach( artifact => {
    config.push( {
        input: `src/wp-includes/js/media/${artifact}.manifest.js`,
        output: {
            file: `src/wp-includes/js/media-${artifact}.js`,
            format: 'iife',
        },
        plugins: [ pluginCommonJS() ],
    } );
} );

module.exports = config;
