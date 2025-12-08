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


// Listen for when audio file is added to page
function audioWidgetPlayer( event ) {
	var settings,
		element = event.detail.element,
		el = element.querySelector( 'audio' );

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
	new MediaElementPlayer( el, settings );
}
document.addEventListener( 'widget-media-audio', audioWidgetPlayer );


// Listen for when video file is added to page
function videoWidgetPlayer( event ) {
	var settings,
		element = event.detail.element,
		el = element.querySelector( 'video' );

	// Wait for video metadata to load before MediaElement init
	function tryInit() {
		if ( el.readyState >= 1 ) { // HAVE_METADATA
			new MediaElementPlayer( el, settings );
		} else {
			setTimeout( tryInit, 50 );
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

	// Trigger video load and wait
	el.preload = 'metadata';
	el.load();
	tryInit();
}
document.addEventListener( 'widget-media-video', videoWidgetPlayer );
