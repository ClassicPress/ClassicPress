/* global ajaxurl */

/**
 * @summary Registers all events for customizing the background.
 *
 * @since WP-3.0.0
 *
 * @requires jQuery
 */
(function($) {
	$(document).ready(function() {
		var frame,
			bgImage = $( '#custom-background-image' );

		/**
		 * @summary Instantiates the ClassicPress color picker and binds the change and clear events.
		 *
		 * @since WP-3.5.0
		 *
		 * @returns {void}
		 */
		$('#background-color').wpColorPicker({
			change: function( event, ui ) {
				bgImage.css('background-color', ui.color.toString());
			},
			clear: function() {
				bgImage.css('background-color', '');
			}
		});

		/**
		 * @summary Alters the background size CSS property whenever the background size input has changed.
		 *
		 * @since WP-4.7.0
		 *
		 * @returns {void}
		 */
		$( 'select[name="background-size"]' ).change( function() {
			bgImage.css( 'background-size', $( this ).val() );
		});

		/**
		 * @summary Alters the background position CSS property whenever the background position input has changed.
		 *
		 * @since WP-4.7.0
		 *
		 * @returns {void}
		 */
		$( 'input[name="background-position"]' ).change( function() {
			bgImage.css( 'background-position', $( this ).val() );
		});

		/**
		 * @summary Alters the background repeat CSS property whenever the background repeat input has changed.
		 *
		 * @since WP-3.0.0
		 *
		 * @returns {void}
		 */
		$( 'input[name="background-repeat"]' ).change( function() {
			bgImage.css( 'background-repeat', $( this ).is( ':checked' ) ? 'repeat' : 'no-repeat' );
		});

		/**
		 * @summary Alters the background attachment CSS property whenever the background attachment input has changed.
		 *
		 * @since WP-4.7.0
		 *
		 * @returns {void}
		 */
		$( 'input[name="background-attachment"]' ).change( function() {
			bgImage.css( 'background-attachment', $( this ).is( ':checked' ) ? 'scroll' : 'fixed' );
		});

		/**
		 * @summary Binds the event for opening the WP Media dialog.
		 *
		 * @since WP-3.5.0
		 *
		 * @returns {void}
		 */
		$('#choose-from-library-link').click( function( event ) {
			var $el = $(this);

			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.open();
				return;
			}

			// Create the media frame.
			frame = wp.media.frames.customBackground = wp.media({
				// Set the title of the modal.
				title: $el.data('choose'),

				// Tell the modal to show only images.
				library: {
					type: 'image'
				},

				// Customize the submit button.
				button: {
					// Set the text of the button.
					text: $el.data('update'),
					/*
					 * Tell the button not to close the modal, since we're
					 * going to refresh the page when the image is selected.
					 */
					close: false
				}
			});

			/**
			 * @summary When an image is selected, run a callback.
			 *
			 * @since WP-3.5.0
			 *
			 * @returns {void}
 			 */
			frame.on( 'select', function() {
				// Grab the selected attachment.
				var attachment = frame.state().get('selection').first();
				var nonceValue = $( '#_wpnonce' ).val() || '';

				// Run an AJAX request to set the background image.
				$.post( ajaxurl, {
					action: 'set-background-image',
					attachment_id: attachment.id,
					_ajax_nonce: nonceValue,
					size: 'full'
				}).done( function() {
					// When the request completes, reload the window.
					window.location.reload();
				});
			});

			// Finally, open the modal.
			frame.open();
		});
	});
})(jQuery);
