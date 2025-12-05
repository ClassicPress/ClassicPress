/**
 * @output wp-admin/js/widgets/media-widgets.js
 */

/* eslint consistent-this: [ "error", "control" ] */
/* global MediaElementPlayer, _wpmejsSettings */

document.addEventListener( 'change', function( e ) {
	if ( e.target.className === 'widefat' && e.target.closest( '.media-widget-control' ) ) {
		e.target.closest( '.widget-inside' ).querySelector( '.widget-control-save' ).disabled = false;
	}
} );

// Watch for new MediaelementPlayer added to page and initialize
document.addEventListener( 'DOMContentLoaded', function() {
	var observer = new MutationObserver( function( mutations ) {
		for ( var mutation of mutations ) {
			for ( var node of mutation.addedNodes ) {
				if ( node.nodeType !== 1 ) {
					continue; // ELEMENT_NODE
				}

				// The tag of the added node itself is audio or video
				if ( node.matches( 'audio, video' ) ) {
					initMediaElement( node );
				}

				// The audio or video tag is nested inside the added node
				const mediaNodes = node.querySelectorAll?.( 'audio, video' );
				if ( mediaNodes && mediaNodes.length ) {
					mediaNodes.forEach( initMediaElement );
				}
			}
		}
	} );

	observer.observe( document.documentElement, {
		childList: true,
		subtree: true
	} );

	function initMediaElement( el ) {
		var settings;

		// Wait for video metadata to load before MediaElement init
		function tryInit() {
			if ( el.readyState >= 1 ) { // HAVE_METADATA
				new MediaElementPlayer( el, settings );
			} else {
				setTimeout( tryInit, 0 );
			}
		}

		// Abort if there is no relevant element
		if ( ! el || el.classList.contains( 'mejs__player' ) ) {
			return;
		}

		// Avoid double-init: MediaElement wraps the media in a container
		if ( el.closest( '.mejs__container' ) ) {
			return;
		}

		// Merge global Medialelement settings and initialize player
		if ( typeof _wpmejsSettings !== 'undefined' ) {
			settings = Object.assign( {}, _wpmejsSettings );
		}

		if ( el.tagName === 'AUDIO' ) {
			new MediaElementPlayer( el, settings );
		} else {
			// Trigger video load and wait
			el.preload = 'metadata';
			el.load();
			tryInit();
		}
	}
} );
