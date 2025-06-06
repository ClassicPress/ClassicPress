/**
 * @output wp-admin/js/widgets/text-widgets.js
 */

/* global wp, tinymce */
/* eslint consistent-this: [ "error", "control" ] */

document.addEventListener( 'DOMContentLoaded', function() {

	function initTextWidget( textarea ) {

		function initTinyMCE() {

			// Remove existing editor instance if present
			setTimeout( function() {
				var ed = tinymce.get( textarea.id );
				if ( ed ) {
					try {
						ed.remove();
					} catch( e ) {
						// Ignore errors
					}
				}

				wp.editor.initialize( textarea.id, {
					tinymce: {
						wpautop: true,
						setup: function( editor ) {
							editor.on( 'change', function() {
								editor.save(); // Sync content to textarea on change
								textarea.dispatchEvent( new Event( 'change', { bubbles: true } ) );
							} );
						}
					},
					quicktags: true,
					mediaButtons: true
				} );
			}, 1 );
		}

        // Initialize TinyMCE
        initTinyMCE();
	}

	function handleWidgetUpdate( event ) {
		var widget = event.detail.widget;
		if ( widget.querySelector( '.id_base' ).value === 'text' ) {
			initTextWidget( widget.querySelector( 'textarea' ) );
		}
	}

	// Listen for when widgets are added, synced, or updated
	document.addEventListener( 'widget-added', handleWidgetUpdate );
	document.addEventListener( 'widget-synced', handleWidgetUpdate );
	document.addEventListener( 'widget-updated', handleWidgetUpdate );

	// Ensure TinyMCE loads on page load
	document.querySelectorAll( '#widgets-right .id_base' ).forEach( function( base ) {
		if ( base.value === 'text' ) {
			initTextWidget( base.closest ( '.widget' ).querySelector( 'textarea' ) );
		}
	} );
} );
