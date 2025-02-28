/**
 * @output wp-admin/js/widgets/media-video-widgets.js
 */

/* eslint consistent-this: [ "error", "control" ] */

/* global VIDEO_WIDGET, console */

/**
 * @namespace wp.mediaWidgets
 * @memberOf  wp
 */

/*
 * @since CP 2.3.0
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
			title: VIDEO_WIDGET.add_video,
			button: {
				text: VIDEO_WIDGET.add_to_widget
			},
			multiple: false,
			library: {
				type: 'video'
			},
			states: [
				new wp.media.controller.Library( {
					title: VIDEO_WIDGET.add_video,
					library: wp.media.query({ type: 'video' }),
					multiple: false,
					priority: 20
				} ),
				new wp.media.controller.Embed( {
					id: 'embed',
					title: VIDEO_WIDGET.insert_from_url,
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

			// Insert video from URL
			mediaUploader.el.querySelector( '#menu-item-embed' ).addEventListener( 'click', function() {
				var embed = document.createElement( 'div' );

				embed.className = 'media-embed';
				embed.innerHTML = '<span class="embed-url">' +
					'<input id="embed-url-field" type="url" aria-label="' + VIDEO_WIDGET.insert_from_url + '" placeholder="https://">' +
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
						video     = document.createElement( 'video' ),
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

						video.className = 'wp_video_shortcode';
						video.style.width = '100%';
						video.controls = true;

						// Create source element
						source.src = e.target.value;

						// Append source to video
						video.append( source );

						// Add Edit and Replace buttons
						buttons.className = 'media-widget-buttons';
						buttons.innerHTML = '<button type="button" class="button edit-media">' + VIDEO_WIDGET.edit_video + '</button>' +
							'<button type="button" class="button change-media select-media">' + VIDEO_WIDGET.replace_video + '</button>';

						// Insert video according to whether this is a new insertion or replacement
						if ( widget.querySelector( '.attachment-media-view' ) !== null ) {
							widget.querySelector( '.media_video' ).append( video );
							widget.querySelector( '.attachment-media-view' ).replaceWith( buttons );
						} else { // replacement
							widget.querySelector( '.wp-video' ).replaceWith( video );
						}

						// Activate Add to widget button
						if ( addButton.disabled ) {
							addButton.removeAttribute( 'disabled' );
							addButton.textContent = VIDEO_WIDGET.add_to_widget;
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
							error.querySelector( 'p' ).textContent = VIDEO_WIDGET.unsupported_file_type;
							e.target.before( error );
						}
					}
				} );

			} );

			libraryButton.addEventListener( 'click', function() {
				embedded = false;
				addButton.textContent = VIDEO_WIDGET.add_to_widget;
			} );
		} );

		// Insert video from media library
		mediaUploader.on( 'select', function() {
			var attachment = mediaUploader.state().get( 'selection' ).first().toJSON(),
				fileType   = attachment.mime.split( '/' )[1],
				video      = document.createElement( 'video' ),
				source     = document.createElement( 'source' ),
				buttons    = document.createElement( 'div' );

			video.className = 'wp_video_shortcode';
			video.style.width = '100%';
			video.controls = true;

			// Create source element
			source.src = attachment.url;
			source.type = attachment.mime;

			// Append source to video
			video.append( source );

			// Add Edit and Replace buttons
			buttons.className = 'media-widget-buttons';
			buttons.innerHTML = '<button type="button" class="button edit-media">' + VIDEO_WIDGET.edit_video + '</button>' +
				'<button type="button" class="button change-media select-media">' + VIDEO_WIDGET.replace_video + '</button>';

			// Insert video according to whether this is a new insertion or replacement
			if ( widget.querySelector( '.attachment-media-view' ) !== null ) {
				widget.querySelector( '.media_video' ).append( video );
				widget.querySelector( '.attachment-media-view' ).replaceWith( buttons );
			} else { // replacement
				widget.querySelector( '.wp-video' ).replaceWith( video );
			}

			// Update values in hidden fields
			widget.querySelector( '[data-property="attachment_id"]' ).value = attachment.id;
			widget.querySelector( '[data-property="url"]' ).value = attachment.url;
			if ( widget.querySelector( '[data-property="' + fileType + '"]' ) ) {		
				widget.querySelector( '[data-property="' + fileType + '"]' ).value = attachment.url;
			} else {
				console.error( VIDEO_WIDGET.unsupported_file_type );
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
			videoURL = widget.querySelector( '[data-property="url"]' ).value,
			videoID = widget.querySelector( '[data-property="attachment_id"]' ).value;

        if ( videoURL === null ) {
			videoURL = widget.querySelector( '[data-property="mp4"]' ).value;
		}
		if ( videoURL === null ) {
			videoURL = widget.querySelector( '[data-property="m4v"]' ).value;
		}
		if ( videoURL === null ) {
			videoURL = widget.querySelector( '[data-property="webm"]' ).value;
		}
		if ( videoURL === null ) {
			videoURL = widget.querySelector( '[data-property="ogv"]' ).value;
		}
		if ( videoURL === null ) {
			videoURL = widget.querySelector( '[data-property="flv"]' ).value;
		}
        if ( videoURL == null ) {
            console.error( VIDEO_WIDGET.no_video_selected );
            return;
        }

        // Open the media modal in edit mode
        frame = wp.media( {
            frame: 'video',
            state: 'video-details',
            metadata: wp.media.attachment( videoID )
        } );

		// This runs once the modal is open
		frame.on( 'ready', function() {
			frame.el.querySelector( '#video-details-loop' ).checked = widget.querySelector( '[data-property="loop"]' ).value === '1' ? true : false;

			updateFrame( frame, widget, videoURL );

			frame.el.querySelector( '#menu-item-video-details' ).addEventListener( 'click', function() {
				updateFrame( frame, widget, videoURL );
			} );
		} );

        frame.open();
    }
    
    function updateFrame( frame, widget, videoURL ) {
		var video = document.createElement( 'video' ),
			source = document.createElement( 'source' ),
			span = document.createElement( 'span' ),
			fileType = videoURL.split( '.' ).pop(),
			fileMime = frame.el.querySelector( '.add-media-source[data-mime="video/' + fileType + '"]' ),
			autoplay = frame.el.querySelector( '#video-details-autoplay' ),
			loop = frame.el.querySelector( '#video-details-loop' );

		// Organize menu items in left-hand column
		if ( frame.el.querySelector( '#menu-item-replace-video' ) ) {
			frame.el.querySelector( '#menu-item-replace-video' ).remove();
		}
		if ( frame.el.querySelector( '#menu-item-select-poster-image' ) ) {
			frame.el.querySelector( '#menu-item-select-poster-image' ).remove();
		}
		frame.el.querySelector( '#menu-item-add-track' ).textContent = VIDEO_WIDGET.add_subtitles;

		video.className = 'wp_video_shortcode';
		video.style.width = '100%';
		video.controls = true;

		// Create source element
		source.src = videoURL;
		source.type = 'video/' + fileType;

		// Append source to video
		video.append( source );
		frame.el.querySelector( 'video' ).replaceWith( video );
			
		span.className = 'setting';
		span.innerHTML = '<label for="video-details-' + fileType + '-source" class="name">' + fileType.toUpperCase() + '</label>' +
			'<input type="text" id="video-details-' + fileType + '-source" readonly="" data-setting="' + fileType + '" value="' + videoURL + '">' +
			'<button type="button" class="button-link remove-setting">' + VIDEO_WIDGET.remove_video_source + '</button>' +
			'</span>';
		if ( frame.el.querySelector( '[data-setting="' + fileType + '"]' ) == null ) {
			frame.el.querySelector( '.wp-video' ).after( span );
		}

		// Remove current filetype button from alternate sources list
		if ( fileMime ) {
			fileMime.remove();
		}

		// Remove autoplay option
		if ( autoplay ) {
			autoplay.parentNode.remove();
		}

		// Set whether the video should loop or not
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
			if ( base && base.value === 'media_video' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className && e.target.className.includes( 'edit-media' ) ) {
					editMedia( widget );
				}
			}
		}
	} );
} );
