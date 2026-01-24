// Custom Strip Source Map plugin

class StripSourceMapURLPlugin {
	constructor( mode ) {
		this.mode = mode;
	};
	apply( compiler ) {
		if ( 'production' === this.mode ) {
			return;
		}
		compiler.hooks.compilation.tap( 'StripSourceMapURLPlugin', compilation => {
			compilation.hooks.processAssets.tap(
				{
					name: 'StripSourceMapURLPlugin',
					stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_OPTIMIZE,
				},
				assets => {
					const re = /^\/[\/*][#@]\s*sourceMappingURL=.*?\n$/gm;
					for ( const name of Object.keys( assets ) ) {
						if ( ! name.endsWith( '.js' ) || name.endsWith( 'min.js' ) ) {
							continue;
						}
						const source = assets[ name ].source().toString();
						const cleaned = source.replace( re, '' );
						if ( cleaned !== source ) {
							compilation.updateAsset(
								name,
								new compiler.webpack.sources.RawSource( cleaned )
							);
						}
					}
				}
			);
		});
	}
}

module.exports = StripSourceMapURLPlugin;
