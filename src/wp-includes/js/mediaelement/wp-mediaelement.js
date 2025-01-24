/* global _wpmejsSettings, mejsL10n, MediaElementPlayer */
(function(window) {
	window.wp = window.wp || {};

	function wpMediaElement() {
		var settings = {};

		/**
		 * Initialize media elements.
		 *
		 * Ensures media elements that have already been initialized won't be
		 * processed again.
		 *
		 * @memberOf wp.mediaelement
		 *
		 * @since 4.4.0
		 *
		 * Rewritten in vanilla JavaScript
		 * @since CP-2.4.0
		 *
		 * @return {void}
		 */
		function initialize() {
			if ( typeof _wpmejsSettings !== 'undefined' ) {
				settings = Object.assign( {}, _wpmejsSettings );
			}
			settings.classPrefix = 'mejs-';
			settings.success = settings.success || function( mejs ) {
				var autoplay, loop;

				if ( mejs.rendererName && mejs.rendererName.indexOf( 'flash' ) !== -1 ) {
					autoplay = mejs.attributes.autoplay && mejs.attributes.autoplay !== 'false';
					loop = mejs.attributes.loop && mejs.attributes.loop !== 'false';

					if ( autoplay ) {
						mejs.addEventListener( 'canplay', function() {
							mejs.play();
						}, false );
					}

					if ( loop ) {
						mejs.addEventListener( 'ended', function() {
							mejs.play();
						}, false );
					}
				}
			};

			/**
			 * Custom error handler.
			 *
			 * Sets up a custom error handler in case a video render fails, and provides a download
			 * link as the fallback.
			 *
			 * @since 4.9.3
			 *
			 * Rewritten in vanilla JavaScript
			 * @since CP-2.4.0
			 *
			 * @param {object} media The wrapper that mimics all the native events/properties/methods for all renderers.
			 * @param {object} node  The original HTML video, audio, or iframe tag where the media was loaded.
			 * @return {string}
			 */
			settings.customError = function( media, node ) {
				// Make sure we only fall back to a download link for flash files.
				if ( media.rendererName.indexOf( 'flash' ) !== -1 || media.rendererName.indexOf( 'flv' ) !== -1 ) {
					return '<a href="' + node.src + '">' + mejsL10n.strings['mejs.download-file'] + '</a>';
				}
			};

			// Only initialize new media elements.
			var mediaElements = document.querySelectorAll( '.wp-audio-shortcode, .wp-video-shortcode' );
			mediaElements.forEach( function( element ) {
				if ( !element.classList.contains( 'mejs-container' ) && 
					( !element.parentNode || !element.parentNode.classList.contains( 'mejs-mediaelement' ) ) ) {
					new MediaElementPlayer( element, settings );
				}
			});
		}

		return {
			initialize: initialize
		};
	}

	/**
	 * @namespace wp.mediaelement
	 * @memberOf wp
	 */
	window.wp.mediaelement = new wpMediaElement();

	window.addEventListener( 'load', window.wp.mediaelement.initialize );

} )( window );
