/**
 * @output wp-admin/js/widgets/media-audio-widgets.js
 */

/* eslint consistent-this: [ "error", "control" ] */

/**
 * @namespace wp.mediaWidgets
 * @memberOf  wp
 */

/*
 * @since CP-2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {

	/**
	 * Open the media select frame to chose an item.
	 *
	 * @return {void}
	 */
	function selectMedia( widget ) {
		var mediaUploader,
			embedded = false;

		if ( mediaUploader ) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media( {
			title: AUDIO_WIDGET.add_audio,
			button: {
				text: AUDIO_WIDGET.add_to_widget
			},
			multiple: false,
			library: {
				type: 'audio'
			},
			states: [
				new wp.media.controller.Library( {
					title: AUDIO_WIDGET.add_audio,
					library: wp.media.query({ type: 'audio' }),
					multiple: false,
					priority: 20
				} ),
				new wp.media.controller.Embed( {
					id: 'embed',
					title: AUDIO_WIDGET.insert_from_url,
					priority: 30
				} )
			]
		} );

		// This runs once the modal is open
		mediaUploader.on( 'ready', function() {
			var separator = document.createElement( 'div' ),
				addButton = mediaUploader.el.querySelector( '.media-button-select' ),
				libraryButton = mediaUploader.el.querySelector( '#menu-item-library' );

			separator.className = 'separator';
			separator.setAttribute( 'role', 'presentation' );
			libraryButton.after( separator );

			// Prevent duplicate separator lines
			if ( mediaUploader.el.querySelectorAll( '.separator' ).length > 1 ) {
				mediaUploader.el.querySelectorAll( '.separator' )[1].remove();
			}

			// Insert audio from URL
			mediaUploader.el.querySelector( '#menu-item-embed' ).addEventListener( 'click', function() {
				var embed = document.createElement( 'div' );

				embed.className = 'media-embed';
				embed.innerHTML = '<span class="embed-url">' +
					'<input id="embed-url-field" type="url" aria-label="' + AUDIO_WIDGET.insert_from_url + '" placeholder="https://">' +
					'<span class="spinner"></span>' +
					'</span>';

				if ( embedded === false ) {
					mediaUploader.el.querySelector( '.media-frame-content' ).append( embed );
					embedded = true;
				}
				if ( mediaUploader.el.querySelector( '.uploader-inline' ) ) {
					mediaUploader.el.querySelector( '.uploader-inline' ).remove();
				}
				if ( mediaUploader.el.querySelector( '.attachments-browser' ) ) {
					mediaUploader.el.querySelector( '.attachments-browser' ).remove();
				}

				mediaUploader.el.querySelector( '#embed-url-field' ).addEventListener( 'change', function( e ) {
					var fileType  = e.target.value.split( '.' ).pop(),
						error     = document.createElement( 'div' ),
						audio     = document.createElement( 'audio' ),
						source    = document.createElement( 'source' ),
						buttons   = document.createElement( 'div' ),
						message   = mediaUploader.el.querySelector( '#message' );

					// Update values in hidden fields
					widget.querySelector( '[data-property="url"]' ).value = e.target.value;
					if ( widget.querySelector( '[data-property="' + fileType + '"]' ) ) {
						if ( message ) {
							message.remove();
						}
						widget.querySelector( '[data-property="' + fileType + '"]' ).value = e.target.value;

						audio.className = 'wp_audio_shortcode';
						audio.style.width = '100%';
						audio.controls = true;

						// Create source element
						source.src = e.target.value;

						// Append source to audio
						audio.append( source );

						// Add Edit and Replace buttons
						buttons.className = 'media-widget-buttons';
						buttons.innerHTML = '<button type="button" class="button edit-media">' + AUDIO_WIDGET.edit_audio + '</button>' +
							'<button type="button" class="button change-media select-media">' + AUDIO_WIDGET.replace_audio + '</button>';

						// Insert audio according to whether this is a new insertion or replacement
						if ( widget.querySelector( '.attachment-media-view' ) !== null ) {
							widget.querySelector( '.media_audio' ).append( audio );
							widget.querySelector( '.attachment-media-view' ).replaceWith( buttons );
						} else { // replacement
							widget.querySelector( '.wp-audio' ).replaceWith( audio );
						}

						// Activate Add to widget button
						if ( addButton.disabled ) {
							addButton.removeAttribute( 'disabled' );
						}

						addButton.addEventListener( 'click', function() {
							document.querySelector( '.media-modal-close' ).click();

							// Activate Save/Publish button
							if ( document.body.className.includes( 'widgets-php' ) ) {
								widget.classList.add( 'widget-dirty' );
							}
							widget.dispatchEvent( new Event( 'change' ) );
						} );
					} else {
						if ( message == null ) {
							error.id = 'message';
							error.className = 'notice-error is-dismissible';
							error.innerHTML = '<p style="color:#fff;background:red;padding:0.5em 1em;"></p>';
							error.querySelector( 'p' ).textContent = AUDIO_WIDGET.unsupported_file_type;
							e.target.before( error );
						}
					}
				} );

			} );

			libraryButton.addEventListener( 'click', function() {
				embedded = false;
			} );
		} );

		// Insert audio from media library
		mediaUploader.on( 'select', function() {
			var attachment = mediaUploader.state().get( 'selection' ).first().toJSON(),
				fileType   = attachment.url.split( '.' ).pop(),
				audio      = document.createElement( 'audio' ),
				source     = document.createElement( 'source' ),
				buttons    = document.createElement( 'div' );

			audio.className = 'wp_audio_shortcode';
			audio.style.width = '100%';
			audio.controls = true;

			// Create source element
			source.src = attachment.url;
			source.type = attachment.mime;

			// Append source to audio
			audio.append( source );

			// Add Edit and Replace buttons
			buttons.className = 'media-widget-buttons';
			buttons.innerHTML = '<button type="button" class="button edit-media">' + AUDIO_WIDGET.edit_audio + '</button>' +
				'<button type="button" class="button change-media select-media">' + AUDIO_WIDGET.replace_audio + '</button>';

			// Insert audio according to whether this is a new insertion or replacement
			if ( widget.querySelector( '.attachment-media-view' ) !== null ) {
				widget.querySelector( '.media_audio' ).append( audio );
				widget.querySelector( '.attachment-media-view' ).replaceWith( buttons );
			} else { // replacement
				widget.querySelector( '.media_audio' ).replaceWith( audio );
			}

			// Update values in hidden fields
			widget.querySelector( '[data-property="attachment_id"]' ).value = attachment.id;
			widget.querySelector( '[data-property="url"]' ).value = attachment.url;

			if ( widget.querySelector( '[data-property="' + fileType + '"]' ) ) {		
				widget.querySelector( '[data-property="' + fileType + '"]' ).value = attachment.url;
			} else {
				console.error( AUDIO_WIDGET.unsupported_file_type );
			}

			// Activate Save/Publish button
			if ( document.body.className.includes( 'widgets-php' ) ) {
				widget.classList.add( 'widget-dirty' );
			}
			widget.dispatchEvent( new Event( 'change' ) );
		} );

		mediaUploader.open();
	}

	/**
	 * Open the media frame to modify the selected item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editMedia( widget ) {
		var	frame,
			audioURL = widget.querySelector( '[data-property="url"]' ).value,
			audioID = widget.querySelector( '[data-property="attachment_id"]' ).value;

        if ( audioURL === null ) {
			audioURL = widget.querySelector( '[data-property="mp3"]' ).value;
		}
		if ( audioURL === null ) {
			audioURL = widget.querySelector( '[data-property="ogg"]' ).value;
		}
		if ( audioURL === null ) {
			audioURL = widget.querySelector( '[data-property="flac"]' ).value;
		}
		if ( audioURL === null ) {
			audioURL = widget.querySelector( '[data-property="m4a"]' ).value;
		}
		if ( audioURL === null ) {
			audioURL = widget.querySelector( '[data-property="wav"]' ).value;
		}
		if ( audioURL === null ) {
            console.error( AUDIO_WIDGET.no_audio_selected );
            return;
        }

        // Open the media modal in edit mode
        frame = wp.media( {
            frame: 'audio',
            state: 'audio-details',
            metadata: wp.media.attachment( audioID )
        } );

		// This runs once the modal is open
		frame.on( 'ready', function() {
			frame.el.querySelector( '#audio-details-loop' ).checked = widget.querySelector( '[data-property="loop"]' ).value === '1' ? true : false;

			updateFrame( frame, widget, audioURL );

			frame.el.querySelector( '#menu-item-audio-details' ).addEventListener( 'click', function() {
				updateFrame( frame, widget, audioURL );
			} );
		} );

        frame.open();
    }
    
    function updateFrame( frame, widget, audioURL ) {
		var audio = document.createElement( 'audio' ),
			source = document.createElement( 'source' )
			span = document.createElement( 'span' ),
			fileType = audioURL.split( '.' ).pop(),
			autoplay = frame.el.querySelector( '#audio-details-autoplay' ),
			loop = frame.el.querySelector( '#audio-details-loop' );

		// Organize menu items in left-hand column
		if ( frame.el.querySelector( '#menu-item-replace-audio' ) ) {
			frame.el.querySelector( '#menu-item-replace-audio' ).remove();
		}
		if ( frame.el.querySelector( '#menu-item-select-poster-image' ) ) {
			frame.el.querySelector( '#menu-item-select-poster-image' ).remove();
		}

		audio.className = 'wp_audio_shortcode';
		audio.style.width = '100%';
		audio.controls = true;

		// Create source element
		source.src = audioURL;
		source.type = 'audio/' + fileType;

		// Append source to audio
		audio.append( source );
		frame.el.querySelector( 'audio' ).replaceWith( audio );
			
		span.className = 'setting';
		span.innerHTML = '<label for="audio-details-' + fileType + '-source" class="name">' + fileType.toUpperCase() + '</label>' +
			'<input type="text" id="audio-details-' + fileType + '-source" readonly="" data-setting="' + fileType + '" value="' + audioURL + '">' +
			'<button type="button" class="button-link remove-setting">' + AUDIO_WIDGET.remove_audio_source + '</button>' +
			'</span>';
		if ( frame.el.querySelector( '[data-setting="' + fileType + '"]' ) == null ) {
			frame.el.querySelector( '.wp_audio_shortcode' ).after( span );
		}

		// Remove current filetype button from alternate sources list
		frame.el.querySelectorAll( '.add-media-source' ).forEach( function( mime ) {
			if ( mime.textContent === fileType ) {
				mime.remove();
			}
		} );

		// Remove autoplay option
		if ( autoplay ) {
			autoplay.parentNode.remove();
		}

		// Set whether the audio should loop or not
		loop.addEventListener( 'change', function() {
			if ( loop.checked ) {
				widget.querySelector( '[data-property="loop"]' ).value = '1';
			} else {
				widget.querySelector( '[data-property="loop"]' ).value = '';				
			}
		} );

		// Set preload option
		frame.el.querySelectorAll( '.setting.preload .button' ).forEach( function( preload ) {
			if ( preload.value === widget.querySelector( '[data-property="preload"]' ).value ) {
				setTimeout( preload.click() );
			}
			preload.addEventListener( 'click', function() {
				if ( preload.value === 'none' ) {
					preload.value = '';
				}
				widget.querySelector( '[data-property="preload"]' ).value = preload.value;
			} );
		} );

		// Activate Save button on widget after update
		frame.el.querySelector( '.media-button-button' ).addEventListener( 'click', function() {
			widget.dispatchEvent( new Event( 'change' ) );
		} );
	}

	// Handle clicks on Add, Edit, and Replace Video buttons
	document.addEventListener( 'click', function( e ) {
		var base,
			widget = e.target.closest( '.widget' );

		if ( widget ) {
			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'media_audio' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					editMedia( widget );
				}
			}
		}
	} );
} );
