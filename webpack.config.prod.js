var path         = require( 'path' ),
	SOURCE_DIR   = 'src/',
	mediaConfig  = {},
	mediaBuilds  = [ 'audiovideo', 'grid', 'models', 'views' ],
	webpack      = require( 'webpack' );


mediaBuilds.forEach( function ( build ) {
	var path = SOURCE_DIR + 'wp-includes/js/media';
	mediaConfig[ build ] = './' + path + '/' + build + '.manifest.js';
} );

module.exports = {
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
