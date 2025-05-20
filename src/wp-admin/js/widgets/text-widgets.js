/**
 * @output wp-admin/js/widgets/text-widgets.js
 */

/* global wp, tinymce */
/* eslint consistent-this: [ "error", "control" ] */

document.addEventListener( 'DOMContentLoaded', function() {

	function initTextWidget( textarea ) {

		function initTinyMCE() {

			// Check if TinyMCE is already initialized for this textarea
			if ( typeof tinymce !== 'undefined' && tinymce.get( textarea.id ) ) {
				if ( document.body.className.includes( 'wp-customizer' ) ) {
					return;
				} else { // On widgets.php
					tinymce.remove( '#' + textarea.id );
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

						// Add custom button
                        editor.addButton( 'wp_add_media', {
                            title: 'Add Media',
                            icon: 'image',
                            onclick: function() {
                                var frame = wp.media( {
                                    title: 'Select or Upload Media',
                                    button: {
                                        text: 'Use this media'
                                    },
                                    multiple: false
                                } );

                                frame.on( 'select', function() {
                                    var attachment = frame.state().get( 'selection' ).first().toJSON();
                                    editor.insertContent( '<img src="' + attachment.url + '" alt="' + attachment.alt + '">' );
                                } );

                                frame.open();
                            }
                        } );
					}
				},
				quicktags: true,
                mediaButtons: true
			} );
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
