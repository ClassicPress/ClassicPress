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

				// The tag of the added node itself is audio
				if ( node.matches( 'audio' ) ) {
					initMediaElement( node );
				}

				// The audio tag is nested inside the added node
				const mediaNodes = node.querySelectorAll?.( 'audio' );
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
		new MediaElementPlayer( el, settings );
	}
} );
