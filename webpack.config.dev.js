const path         = require( 'path' );
const SOURCE_DIR   = 'src/';
const mediaConfig  = {};
const mediaBuilds  = [ 'audiovideo', 'grid', 'models', 'views' ];
const webpack      = require( 'webpack' );


mediaBuilds.forEach( build => {
	const path = `${SOURCE_DIR}wp-includes/js/media`;
	mediaConfig[ build ] = `./${path}/${build}.manifest.js`;
});

module.exports = {
	mode: 'development',
	cache: true,
	watch: true,
	entry: mediaConfig,
	output: {
		path: path.join( __dirname, 'src/wp-includes/js' ),
		filename: 'media-[name].js'
	}
};
