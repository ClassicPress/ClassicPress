const path         = require( 'path' );
const SOURCE_DIR   = 'src/';
const mediaConfig  = {};
const mediaBuilds  = [ 'audiovideo', 'grid', 'models', 'views' ];
const webpack      = require( 'webpack' );


mediaBuilds.forEach(( build ) => {
	const path = `${SOURCE_DIR}wp-includes/js/media`;
	mediaConfig[ build ] = `./${path}/${build}.manifest.js`;
});

module.exports = {
	mode: 'production',
	cache: true,
	entry: mediaConfig,
	output: {
		path: path.join( __dirname, 'src/wp-includes/js' ),
		filename: 'media-[name].js'
	},
	plugins: [
		new webpack.optimize.ModuleConcatenationPlugin()
	],
	optimization: {
		// The media JavaScript files are minified by uglify during the build.
		// Prevent minifying them in the source repository and avoid doing this
		// work twice.
		minimize: false
	}
};
