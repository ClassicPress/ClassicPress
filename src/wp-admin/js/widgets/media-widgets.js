/**
 * @output wp-admin/js/widgets/media-widgets.js
 */

/* eslint consistent-this: [ "error", "control" ] */

document.addEventListener( 'change', function( e ) {
	if ( e.target.className === 'widefat' && e.target.closest( '.media-widget-control' ) ) {
		e.target.closest( '.widget-inside' ).querySelector( '.widget-control-save' ).disabled = false;
	}
} );
