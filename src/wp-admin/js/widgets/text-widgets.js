/**
 * @output wp-admin/js/widgets/text-widgets.js
 */

/* global wp, tinymce, ajaxurl, TEXT_WIDGET, Sortable, console, prompt, FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginFileRename, FilePondPluginImagePreview */
/* eslint consistent-this: [ "error", "control" ] */

/**
 * @since CP 2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var addButton, pond, content,
		{ FilePond } = window, // import FilePond
		selectedIds = [],
		parser = new DOMParser(),
		dialog = document.getElementById( 'widget-modal' );

	// Initialize the editor in the widget
	function initTextWidget( widget ) {
		var widgetNumber = widget.id.split( '-' ).pop(),
			textareaId = 'widget-text-' + widgetNumber + '-text',
			textarea = widget.querySelector( '#' + textareaId );

		if ( ! textarea ) {
			textarea = widget.querySelector( '.widget-content .wp-editor-area' );
		}
		if ( ! textarea ) {
			return;
		}

		function initTinyMCE() {
			if ( typeof tinymce !== 'undefined' && tinymce ) {

				// Remove existing editor instance if present
				setTimeout( function() {
					if ( textarea.id && wp && wp.editor && typeof wp.editor.remove === 'function') {
						try {
							wp.editor.remove( textarea.id );
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
									widget.classList.add( 'widget-dirty' );
									textarea.dispatchEvent( new Event( 'change', { bubbles: true } ) );
									widget.querySelector( '.widget-control-save' ).disabled = false;
								} );

								// Edit an image or gallery
								editor.on( 'click', function( e ) {
									var selectedIdsMatch, attachmentId, fullShortcode, paramRegex, paramMatch, key, value,
										nan = false,
										params = { columns: '3', size: 'thumbnail', link: 'post', orderby: false };

									if ( e.target.nodeName === 'IMG' ) {
										content = editor.getContent();

										if ( e.target.className.includes( 'wp-gallery' ) ) { // gallery
											selectedIdsMatch = content.match( /\[(gallery)\s+ids=["']([^"']+)["'][^[\]]*]/i );
											if ( selectedIdsMatch ) {
												selectedIds = selectedIdsMatch[2].split( ',' );

												// Check that IDs are positive integers
												for( var i, n = selectedIds.length; i < n; i++ ) {
													if ( ! Number.isInteger( Number( selectedIds[i] ) ) || Number( selectedIds[i] ) <= 0 ) {
														nan = true;
														break;
													}
												}
												if ( nan === true ) {
													return;
												}

												// Now extract other parameters from within the matched shortcode only
												fullShortcode = selectedIdsMatch[0];
												paramRegex = /(\bcolumns|size|link|orderby)=["']([^"']+)["']/gi;
												while ( ( paramMatch = paramRegex.exec( fullShortcode ) ) !== null ) {
													key = paramMatch[1];
													value = paramMatch[2];
													if ( params.hasOwnProperty( key ) ) {
														params[key] = value;
													}
												}

												editList( widget, JSON.parse( '[' + selectedIds + ']' ), 'image', params );
												dialog.querySelector( '#menu-item-gallery' ).classList.add( 'update' );
											}
										} else if ( e.target.height ) { // single image

											// Get attachment ID and set it as a data attribute on the widget
											attachmentId = e.target.className.split( '-' ).pop();
											dialog.dataset.attachmentId = attachmentId;
											if ( e.target.parentNode && e.target.parentNode.tagName === 'A' ) {
												dialog.dataset.linkUrl = e.target.parentNode.href;
											}
										}
									}
								} );
							}
						},
						quicktags: true,
						mediaButtons: true
					} );
				}, 1 );
			}
		}

        // Initialize TinyMCE and Quicktags toolbar fallback
		initTinyMCE();
		initQuickTags( widget );
	}

	function handleWidgetUpdate( event ) {
		var widget = event.detail.widget;
		if ( widget.querySelector( '.id_base' ).value === 'text' ) {
			if ( document.body.className.includes( 'wp-customizer' ) ) {
				if ( event.type !== 'widget-synced' ) {
					setTimeout( function() {
						initTextWidget( widget );
					}, 100);
				}
			} else {
				initTextWidget( widget );
			}
		}
	}

	// Listen for when widgets are added, synced, or updated
	document.addEventListener( 'widget-added', handleWidgetUpdate );
	document.addEventListener( 'widget-synced', handleWidgetUpdate );
	document.addEventListener( 'widget-updated', handleWidgetUpdate );

	// Ensure TinyMCE loads on page load
	document.querySelectorAll( '#widgets-right .id_base, #wp_inactive_widgets .id_base' ).forEach( function( base ) {
		if ( base.value === 'text' ) {
			initTextWidget( base.closest ( '.widget' ) );
		}
	} );

	/**
	 * Update taxonomy details and data IDs within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function setAddedTaxonomyFields( id ) {
		var form = document.createElement( 'form' ),
			inputs = dialog.querySelectorAll( '.widget-modal-right-sidebar-info input, .widget-modal-right-sidebar-info textarea, .widget-modal-right-sidebar-info button.delete-attachment' );

		inputs.forEach( function( input ) {
			input.dataset.id = id;
		} );

		form.className = 'compat-item';
		form.innerHTML = '<input type="hidden" id="menu-order" name="attachments[' + id + '][menu_order]" value="0">' +
			'<p class="media-types media-types-required-info"><span class="required-field-message">Required fields are marked <span class="required">*</span></span></p>' +
			'<div class="setting" data-setting="media_category">' +
				'<label for="attachments-' + id + '-media_category" style="width:30%;">' +
					'<span class="alignleft">Media Categories</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_category" name="attachments[' + id + '][media_category]" data-id="' + id + '" value="">' +
			'</div>' +
			'<div class="setting" data-setting="media_post_tag">' +
				'<label for="attachments-' + id + '-media_post_tag">' +
					'<span class="alignleft">Media Tags</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_post_tag" name="attachments[' + id + '][media_post_tag]" data-id="' + id + '" value="">' +
			'</div>';

		if ( document.querySelector( '.compat-item' ) != null ) {
			document.querySelector( '.compat-item' ).remove();
		}
		document.querySelector( '.attachment-compat' ).append( form );
	}

	/**
	 * Update meta details for audio files within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function setAddedMetaFields( artist, album, id ) {
		var fields = document.createElement( 'div' );
		fields.className = 'artist-album';
		fields.innerHTML = '<div class="setting" data-setting="artist">' +
			'<label for="attachments-details-artist" class="name">' + TEXT_WIDGET.artist + '</label>' +
			'<input type="text" id="attachments-details-artist" data-id="' + id + '" value="' + artist + '">' +
			'</div>' +
			'<div class="setting" data-setting="album">' +
			'<label for="attachments-details-album" class="name">' + TEXT_WIDGET.album + '</label>' +
			'<input type="text" id="attachments-details-album" data-id="' + id + '" value="' + album + '">' +
			'</div>';
		document.querySelector( '.widget-modal-descriptions .settings-save-status' ).after( fields );
	}

	/**
	 * Update audio and video playlist settings.
	 *
	 * @abstract
	 * @return {void}
	 */
	function playlistSettings( fileType ) {
		var fields = document.createElement( 'div' ),
			showlist = fileType === 'audio' ?  TEXT_WIDGET.show_tracklist : TEXT_WIDGET.show_video_list,
			block = fileType === 'audio' ?  'block' : 'none';

		fields.className = 'collection-settings playlist-settings';
		fields.innerHTML = '<h2>Playlist Settings</h2>' +
			'<span class="setting" style="display: block;margin-bottom: 0.5em;">' +
			'<input type="checkbox" id="playlist-settings-show-list" data-setting="tracklist" checked="" style="margin-right: 8px;">' +
			'<label for="playlist-settings-show-list" class="checkbox-label-inline">' + showlist + '</label>' +
			'</span>' +
			'<span class="setting" style="display:' + block + ';margin-bottom: 0.5em;"">' +
			'<input type="checkbox" id="playlist-settings-show-artist" data-setting="artists" checked="" style="margin-right: 8px;">' +
			'<label for="playlist-settings-show-artist" class="checkbox-label-inline">Show Artist Name in Tracklist</label>' +
			'</span>' +
			'<span class="setting" style="display: block;margin-bottom: 0.5em;"">' +
			'<input type="checkbox" id="playlist-settings-show-images" data-setting="images" checked="" style="margin-right: 8px;">' +
			'<label for="playlist-settings-show-images" class="checkbox-label-inline">Show Images</label>' +
			'</span>';
		document.querySelector( '.widget-modal-right-sidebar' ).prepend( fields );
	}

	/**
	 * Update attachment details.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateDetails( input, id ) {
		var successTimeout,
			nonce = document.getElementById( 'media-' + id ).dataset.updateNonce,
			data = new FormData();

		if ( ! nonce ) {
			return;
		}

		data.append( 'action', 'save-attachment' );
		data.append( 'id', id );
		data.append( 'nonce', nonce );

		// Append metadata fields
		if ( input.parentNode.dataset.setting === 'alt' || input.id === 'embed-image-settings-alt-text' || input.id === 'image-details-alt-text' ) {
			data.append( 'changes[alt]', input.value );
		} else if ( input.parentNode.dataset.setting === 'title' ) {
			data.append( 'changes[title]', input.value );
		} else if ( input.parentNode.dataset.setting === 'caption' || input.id === 'embed-image-settings-caption' || input.id === 'image-details-caption' ) {
			data.append( 'changes[caption]', input.value );
		} else if ( input.parentNode.dataset.setting === 'description' ) {
			data.append( 'changes[description]', input.value );
		} else if ( input.parentNode.dataset.setting === 'artist' ) {
			data.append( 'changes[artist]', input.value );
		} else if ( input.parentNode.dataset.setting === 'album' ) {
			data.append( 'changes[album]', input.value );
		}

		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			var saved = dialog.querySelector( '#details-saved' );

			if ( result.success ) {

				// Update data attributes
				if ( input.parentNode.dataset.setting === 'alt' ) {
					document.getElementById( 'media-' + id ).querySelector( 'img' ).setAttribute( 'alt', input.value );
				} else if ( input.parentNode.dataset.setting === 'title' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'aria-label', input.value );
				} else if ( input.parentNode.dataset.setting === 'caption' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'data-caption', input.value );
				} else if ( input.parentNode.dataset.setting === 'description' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'data-description', input.value );
				} else if ( input.parentNode.dataset.setting === 'artist' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'data-artist', input.value );
				} else if ( input.parentNode.dataset.setting === 'album' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'data-album', input.value );
				}

				// Show success visual feedback.
				clearTimeout( successTimeout );
				saved.classList.remove( 'hidden' );
				saved.setAttribute( 'aria-hidden', 'false' );

				// Hide success visual feedback after 3 seconds.
				successTimeout = setTimeout( function() {
					saved.classList.add( 'hidden' );
					saved.setAttribute( 'aria-hidden', 'true' );
				}, 3000 );
			} else {
				console.error( TEXT_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
	}

	/**
	 * Update media categories and tags.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateMediaTaxOrTag( input, id ) {
		var successTimeout, newTaxes,
			data = new FormData(),
			nonce = document.getElementById( 'media-' + id ).dataset.updateNonce,
			taxonomy = input.getAttribute( 'name' ).replace( 'attachments[' + id + '][' , '' ).replace( ']', '' );

		if ( ! nonce ) {
			return;
		}

		data.append( 'action', 'save-attachment-compat' );
		data.append( 'nonce', nonce);
		data.append( 'id', id );
		data.append( 'taxonomy', taxonomy );
		data.append( 'attachments[' + id + '][' + taxonomy + ']', input.value );

		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {
				if ( taxonomy === 'media_category' ) {
					newTaxes = result.data.media_cats.join( ', ' );
					input.value = newTaxes;
					document.getElementById( 'media-' + id ).setAttribute( 'data-taxes', newTaxes );
				} else if ( taxonomy === 'media_post_tag' ) {
					newTaxes = result.data.media_tags.join( ', ' );
					input.value = newTaxes;
					document.getElementById( 'media-' + id ).setAttribute( 'data-tags', newTaxes );
				}

				// Show success visual feedback.
				clearTimeout( successTimeout );
				document.getElementById( 'tax-saved' ).classList.remove( 'hidden' );
				document.getElementById( 'tax-saved' ).setAttribute( 'aria-hidden', 'false' );

				// Hide success visual feedback after 3 seconds.
				successTimeout = setTimeout( function() {
					document.getElementById( 'tax-saved' ).classList.add( 'hidden' );
					document.getElementById( 'tax-saved' ).setAttribute( 'aria-hidden', 'true' );
				}, 3000 );
			} else {
				console.error( TEXT_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
	}

	/**
	 * Handles media list copy media URL button.
	 *
	 * Uses Clipboard API (with execCommand fallback for sites
	 * on neither https nor localhost).
	 *
	 * @since CP-2.5.0
	 *
	 * @param {MouseEvent} event A click event.
	 * @return {void}
	 */
	function copyToClipboard( button ) {
		var copyAttachmentURLSuccessTimeout,
			copyText = dialog.querySelector( '#attachment-details-copy-link' ).value,
			input = document.createElement( 'input' );

		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( copyText );
		} else {
			document.body.append( input );
			input.value = copyText;
			input.select();
			document.execCommand( 'copy' );
		}

		// Show success visual feedback.
		clearTimeout( copyAttachmentURLSuccessTimeout );
		button.nextElementSibling.classList.remove( 'hidden' );
		input.remove();

		// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
		copyAttachmentURLSuccessTimeout = setTimeout( function() {
			button.nextElementSibling.classList.add( 'hidden' );
		}, 3000 );

		// Handle success audible feedback.
		wp.a11y.speak( wp.i18n.__( 'The file URL has been copied to your clipboard' ) );
	}

	/**
	 * Delete attachment from within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function deleteItem( id ) {
		var data = new URLSearchParams( {
			action: 'delete-post',
			_ajax_nonce: document.getElementById( 'media-' + id ).dataset.deleteNonce,
			id: id
		} );

		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result === 1 ) {
				if ( dialog ) {
					dialog.querySelector( '#media-' + id ).remove();
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}
			} else {
				console.log( TEXT_WIDGET.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
	}

	/**
	 * Select and deselect media items for adding to widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectItemToAdd( item, widget, fileType, clicked ) {
		var selectedItems = dialog.querySelectorAll( '.widget-modal-grid .selected' ),
			id = item.dataset.id,
			title = item.getAttribute( 'aria-label' ),
			date = item.dataset.date,
			filename = item.dataset.filename,
			size = item.dataset.size,
			width = item.dataset.width,
			height = item.dataset.height,
			caption = item.dataset.caption,
			description = item.dataset.description,
			taxes = item.dataset.taxes,
			tags = item.dataset.tags,
			artist = item.dataset.artist,
			album = item.dataset.album,
			url = item.dataset.url,
			alt = item.querySelector( 'img' ) ? item.querySelector( 'img' ).getAttribute( 'alt' ) : '',
			updateNonce = item.dataset.updateNonce,
			deleteNonce = item.dataset.deleteNonce,
			info = dialog.querySelector( '.widget-modal-attachment-info' ),
			thumbnail = info.querySelector( '.thumbnail' ),
			img = info.querySelector( 'img' ),
			details = info.querySelector( '.details' ),
			altInput = dialog.querySelector( '#attachment-details-alt-text' ),
			inputs = dialog.querySelectorAll( '.widget-modal-right-sidebar input, .widget-modal-right-sidebar textarea, .widget-modal-media-embed input, .widget-modal-media-embed textarea' ),
			selects = dialog.querySelectorAll( '.widget-modal-right-sidebar select, .widget-modal-media-embed select' ),
			audioTemplate = document.getElementById( 'tmpl-edit-audio-modal' ),
			cloneAudio = audioTemplate.content.cloneNode( true ),
			audioClone = cloneAudio.querySelector( 'audio' ),
			videoTemplate = document.getElementById( 'tmpl-edit-video-modal' ),
			cloneVideo = videoTemplate.content.cloneNode( true ),
			videoClone = cloneVideo.querySelector( 'video' ),
			ul = dialog.querySelector( '.widget-modal-footer-selection-view ul' ),
			li = document.createElement( 'li' ),
			count = ul.childNodes.length;

		// Clean up from previous use of this function
		if ( dialog.querySelector( '.alt-text' ) ) {
			dialog.querySelector( '.alt-text' ).style.display = 'none';
			altInput.value = '';
			altInput.setAttribute( 'inert', true );
		}
		if ( dialog.querySelector( '#alt-text-description' ) ) {
			dialog.querySelector( '#alt-text-description' ).style.display = 'none';
		}
		if ( dialog.querySelector( '.widget-modal-display-settings' ) ) {
			dialog.querySelector( '.widget-modal-display-settings' ).previousElementSibling.remove();
			dialog.querySelector( '.widget-modal-display-settings' ).remove();
		}
		if ( dialog.querySelector( '.wp_audio_shortcode' ) ) {
			dialog.querySelector( '.wp_audio_shortcode' ).remove();
		}
		if ( dialog.querySelector( '.artist-album' ) ) {
			dialog.querySelector( '.artist-album' ).remove();
		}
		if ( dialog.querySelector( '.wp_video_shortcode' ) ) {
			dialog.querySelector( '.wp_video_shortcode' ).remove();
		}
		details.removeAttribute( 'style' );

		// Set taxonomy fields
		setAddedTaxonomyFields( id );

		// Set metadata
		if ( item.dataset.filetype === 'image' ) {
			dialog.querySelector( '.attachment-dimensions' ).textContent = width + ' ' + TEXT_WIDGET.by + ' ' + height + ' ' + TEXT_WIDGET.pixels;
			thumbnail.style.display = '';
			info.querySelector( '.dimensions' ).style.display = '';
			img.style.display = '';
			thumbnail.className = 'thumbnail thumbnail-image';
			dialog.querySelector( '.alt-text' ).style.display = '';
			dialog.querySelector( '#alt-text-description' ).style.display = '';

			if ( clicked === true ) {
				img.src = url;
				img.alt = alt;
				altInput.removeAttribute( 'inert' );
				altInput.value = alt;
				dialog.querySelector( '#attachment-details-title' ).value = title;
				dialog.querySelector( '#attachment-details-caption' ).textContent = caption;
				dialog.querySelector( '#attachment-details-description' ).textContent = description;
			}
		} else {
			info.querySelector( '.dimensions' ).style.display = 'none';

			if ( item.dataset.filetype === 'audio' ) {
				img.style.display = 'none';
				setAddedMetaFields( artist, album, id );
				if ( audioClone ) {
					audioClone.querySelector( 'source' ).src = url;
					if ( dialog.querySelector( '.wp_audio_shortcode' ) ) {
						dialog.querySelector( '.wp_audio_shortcode' ).replaceWith( audioClone );
					} else {
						info.prepend( audioClone );
					}
					details.style.width = '100%';
				}
			} else {
				if ( item.dataset.filetype === 'video' && videoClone ) {
					thumbnail.style.display = 'none';
					videoClone.querySelector( 'source' ).src = url;
					info.prepend( videoClone );
					details.style.width = '100%';
				} else if ( item.dataset.filetype === 'application' ) {
					thumbnail.style.display = '';
					thumbnail.className = 'thumbnail thumbnail-application';
					img.src = item.querySelector( 'img' ).src;
					img.removeAttribute( 'style' );
				}
			}
		}

		// Populate modal with attachment details
		dialog.querySelector( '.attachment-date' ).textContent = date;
		dialog.querySelector( '.attachment-filename' ).textContent = filename;
		dialog.querySelector( '.attachment-filesize' ).textContent = size;
		dialog.querySelector( '#edit-more' ).href = ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + id + '&action=edit' );
		dialog.querySelector( '#attachment-details-description').textContent = description;
		dialog.querySelector( '#attachment-details-copy-link').value = url;

		// Set status of items according to user's capabilities
		if ( updateNonce ) {
			inputs.forEach( function( input ) {
				input.removeAttribute( 'readonly' );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.removeAttribute( 'hidden' );
		} else {
			inputs.forEach( function( input ) {
				input.setAttribute( 'readonly', true );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.setAttribute( 'hidden', true );
		}

		if ( deleteNonce ) {
			selects.forEach( function( select ) {
				select.removeAttribute( 'disabled' );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.removeAttribute( 'hidden' );
		} else {
			selects.forEach( function( select ) {
				select.setAttribute( 'disabled', true );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.setAttribute( 'hidden', true );
		}

		dialog.querySelector( '#attachments-' + id + '-media_category').value = taxes;
		dialog.querySelector( '#attachments-' + id + '-media_post_tag').value = tags;

		dialog.querySelector( '.widget-modal-right-sidebar-info' ).removeAttribute( 'hidden' );

		// Update media attachment details
		dialog.querySelectorAll( '.widget-modal-right-sidebar-info input, .widget-modal-right-sidebar-info textarea' ).forEach( function( input ) {
			input.addEventListener( 'change', function() {
				if ( input.parentNode.parentNode.className === 'compat-item' ) {
					updateMediaTaxOrTag( input, input.dataset.id ); // Update media categories and tags
				} else {
					updateDetails( input, input.dataset.id );
				}
			} );
		} );

		// Uncheck item if clicked on
		if ( item.className.includes( 'selected' ) ) {
			count--;
			if ( clicked === false ) {
				item.querySelector( '.check' ).style.display = 'block';
				if ( addButton ) {
					addButton.setAttribute( 'disabled', true );
				}
			} else {
				item.classList.remove( 'selected' );
				item.setAttribute( 'aria-checked', false );
				item.querySelector( '.check' ).style.display = 'none';
				dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );

				// If no media items are selected
				if ( document.querySelector( '.media-item.selected' ) == null ) {

					// Empty and hide footer selection
					ul.innerHTML = '';
					ul.parentNode.parentNode.style.visibility = 'hidden';

					// Disable add to widget button
					if ( addButton ) {
						addButton.setAttribute( 'disabled', true );
					}
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}
				if ( ul.querySelector( '[data-id="' + id + '"]' ) ) {
					ul.querySelector( '[data-id="' + id + '"]' ).remove();
				}
			}
		} else {
			count++;

			// Prevent selection of multiple items unless creating a gallery or playlist
			if ( selectedItems && fileType === 'all' ) {
				selectedItems.forEach( function( selectedItem ) {
					selectedItem.classList.remove( 'selected' );
					selectedItem.setAttribute( 'aria-checked', false );
					selectedItem.querySelector( '.check' ).style.display = 'none';
				} );
				ul.childNodes.forEach( function( node ) {
					node.remove();
				} );
			}

			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );
			if ( item.querySelector( '.check' ) ) {
				item.querySelector( '.check' ).style.display = 'block';
			}

			li.role = 'checkbox';
			li.setAttribute( 'tabindex', '0' );
			li.setAttribute( 'aria-label', filename );
			li.setAttribute( 'aria-checked', true );
			li.dataset.id = id;
			li.className = 'attachment selection details selected save-ready';
			li.innerHTML = item.querySelector( '.select-attachment-preview' ).outerHTML;
			ul.append( li );
			ul.parentNode.parentNode.style.visibility = 'visible';

			// Enable add to widget button
			if ( addButton ) {
				addButton.removeAttribute( 'disabled' );
			}
		}

		// Update text in modal footer
		if ( count === 1 || fileType === 'all' ) {
			dialog.querySelector( '.widget-modal-footer .count' ).textContent = '1 ' + TEXT_WIDGET.item_selected;
		} else {
			dialog.querySelector( '.widget-modal-footer .count' ).textContent = count + ' ' + TEXT_WIDGET.items_selected;
		}
	}

	/**
	 * Populate media items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	function populateGridItem( attachment ) {
		var selected = '',
			gridItem = document.createElement( 'li' ),
			image = '<img src="' + attachment.url + '" alt="' + attachment.alt + '">';

		if ( attachment.type === 'application' ) {
			if ( attachment.subtype === 'vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) {
				image = '<div class="icon"><div class="centered"><img src="' + TEXT_WIDGET.includes_url + 'images/media/spreadsheet.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			} else if ( attachment.subtype === 'zip' ) {
				image = '<div class="icon"><div class="centered"><img src="' + TEXT_WIDGET.includes_url + 'images/media/archive.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			} else {
				image = '<div class="icon"><div class="centered"><img src="' + TEXT_WIDGET.includes_url + 'images/media/document.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			}
		} else if ( attachment.type === 'audio' ) {
			image = '<div class="icon"><div class="centered"><img src="' + TEXT_WIDGET.includes_url + 'images/media/audio.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
		} else if ( attachment.type === 'video' ) {
			image = '<div class="icon"><div class="centered"><img src="' + TEXT_WIDGET.includes_url + 'images/media/video.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
		}

		gridItem.className = 'media-item' + selected;
		gridItem.id = 'media-' + attachment.id;
		gridItem.setAttribute( 'tabindex', 0 );
		gridItem.setAttribute( 'role', 'checkbox' );
		gridItem.setAttribute( 'aria-checked', selected ? true : false );
		gridItem.setAttribute( 'aria-label', attachment.title );
		gridItem.setAttribute( 'data-id', attachment.id );
		gridItem.setAttribute( 'data-date', attachment.dateFormatted );
		gridItem.setAttribute( 'data-url', attachment.url );
		gridItem.setAttribute( 'data-filename', attachment.filename );
		gridItem.setAttribute( 'data-filetype', attachment.type );
		gridItem.setAttribute( 'data-mime', attachment.mime );
		gridItem.setAttribute( 'data-width', attachment.width );
		gridItem.setAttribute( 'data-height', attachment.height );
		gridItem.setAttribute( 'data-size', attachment.filesizeHumanReadable );
		gridItem.setAttribute( 'data-caption', attachment.caption );
		gridItem.setAttribute( 'data-description', attachment.description );
		gridItem.setAttribute( 'data-link', attachment.link );
		gridItem.setAttribute( 'data-orientation', attachment.orientation );
		gridItem.setAttribute( 'data-menu-order', attachment.menuOrder );
		gridItem.setAttribute( 'data-taxes', attachment.media_cats );
		gridItem.setAttribute( 'data-tags', attachment.media_tags );
		gridItem.setAttribute( 'data-artist', attachment.meta.artist );
		gridItem.setAttribute( 'data-album', attachment.meta.album );
		gridItem.setAttribute( 'data-update-nonce', attachment.nonces.update );
		gridItem.setAttribute( 'data-delete-nonce', attachment.nonces.delete );
		gridItem.setAttribute( 'data-edit-nonce', attachment.nonces.edit );

		gridItem.innerHTML = '<div class="select-attachment-preview type-' + attachment.type + ' subtype-' + attachment.subtype + '">' +
			'<div class="media-thumbnail">' + image + '</div>' +
			'</div>' +
			'<button type="button" class="check" tabindex="-1">' +
			'<span class="media-modal-icon"></span>' +
			'<span class="screen-reader-text">' + TEXT_WIDGET.deselect + '></span>' +
			'</button>';

		return gridItem;
	}

	/**
	 * Populate the grid with images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectMedia( widget, fileType, gallery ) {
		var template = document.getElementById( 'tmpl-media-grid-modal' ),
			clone = template.content.cloneNode( true ),
			dialogButtons = clone.querySelector( '.widget-modal-header-buttons' ),
			dialogContent = clone.querySelector( '#widget-modal-media-content' ),
			header = dialog.querySelector( 'header' ),
			galleryTemplate = document.getElementById( 'tmpl-edit-gallery-modal' ),
			galleryClone = galleryTemplate.content.cloneNode( true ),
			mediaButton = dialog.querySelector( '#menu-item-add' ),
			galleryButton = dialog.querySelector( '#menu-item-gallery' ),
			playlistButton = dialog.querySelector( '#menu-item-playlist' ),
			videoListButton = dialog.querySelector( '#menu-item-video-playlist' ),
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': TEXT_WIDGET.per_page,
				'query[paged]': 1
			} );

		if ( fileType !== 'all' ) {
			params.append( 'query[post_mime_type]', fileType );
		}

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			body: params,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {

				// Append cloned template and show relevant elements
				header.append( dialogButtons );
				if ( dialog.querySelector( '#widget-modal-media-content' ) == null ) {
					header.after( dialogContent );
				}
				dialog.querySelector( '#menu-item-embed' ).removeAttribute( 'hidden' );
				dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId = widget.id;

				galleryButton.removeAttribute( 'hidden' );
				playlistButton.removeAttribute( 'hidden' );
				videoListButton.removeAttribute( 'hidden' );

				if ( fileType === 'all' || gallery === false ) {
					mediaButton.textContent = TEXT_WIDGET.add_media;
					mediaButton.classList.add( 'active' );
					mediaButton.removeAttribute( 'hidden' );
					mediaButton.setAttribute( 'aria-selected', true );
					addButton = dialog.querySelector( '#media-button-insert' );
					if ( gallery === false ) {
						addButton.textContent = TEXT_WIDGET.replace;
					}
				} else {
					mediaButton.setAttribute( 'aria-selected', false );
					mediaButton.classList.remove( 'active' );

					dialog.querySelector( '.separator' ).insertAdjacentHTML( 'afterend', galleryClone.querySelector( '#gallery-buttons' ).innerHTML ),
					dialog.querySelector( '.media-library-grid-section' ).after( galleryClone.querySelector( '.media-gallery-grid-section' ) ),
					dialog.querySelector( '.widget-modal-right-sidebar' ).prepend( galleryClone.querySelector( '.widget-modal-gallery-settings' ) );
					dialog.querySelector( 'footer' ).replaceWith( galleryClone.querySelector( 'footer' ) ),

					addButton = dialog.querySelector( '#gallery-button-new' );
					addButton.classList.remove( 'hidden' );

					if ( fileType === 'image' && gallery === true ) {
						dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.create_gallery;
						galleryButton.classList.add( 'active' );
						galleryButton.removeAttribute( 'hidden' );
						galleryButton.setAttribute( 'aria-selected', true );
						addButton.textContent = TEXT_WIDGET.create_new_gallery;
						addButton.classList.add( 'image' );
					} else if ( fileType === 'audio' ) {
						dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.create_playlist;
						playlistButton.classList.add( 'active' );
						playlistButton.removeAttribute( 'hidden' );
						playlistButton.setAttribute( 'aria-selected', true );
						addButton.textContent = TEXT_WIDGET.create_new_playlist;
						addButton.classList.add( 'audio' );
					} else if ( fileType === 'video' ) {
						dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.create_video_playlist;
						videoListButton.classList.add( 'active' );
						videoListButton.removeAttribute( 'hidden' );
						videoListButton.setAttribute( 'aria-selected', true );
						addButton.textContent = TEXT_WIDGET.create_new_video_playlist;
						addButton.classList.add( 'video' );
					}
				}

				checkWindowWidth();

				if ( result.data.length === 0 ) {

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'data-page', 1 );
						pageLink.setAttribute( 'disabled', true );
						pageLink.setAttribute( 'inert', true );
					} );

					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', 1 );
					dialog.querySelector( '.total-pages' ).textContent = 1;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					dialog.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.no-media' ).removeAttribute( 'hidden' );

				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment, widget );
						dialog.querySelector( '.widget-modal-grid' ).append( gridItem );
					} );

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages !== 1 ) {
								pageLink.setAttribute( 'data-page', 2 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages !== 1 ) {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					dialog.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Open modal to show details about file, or select files for deletion
					dialog.querySelectorAll( '.media-item' ).forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							selectItemToAdd( item, widget, fileType, false );
						}
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, widget, fileType, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + TEXT_WIDGET.of + ' ' + result.headers.total_posts + ' ' + TEXT_WIDGET.media_items;
				}
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
		dialog.showModal();
	}

	/**
	 * Update the grid with new images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateGrid( widget, paged, fileType ) {
		var dateFilter = dialog.querySelector( '#filter-by-date' ),
			mediaCatSelect = dateFilter.nextElementSibling,
			search = dialog.querySelector( '#widget-modal-search-input' ),
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': TEXT_WIDGET.per_page,
				'query[monthnum]': dateFilter.value ? parseInt( dateFilter.value.substr( 4, 2 ), 10 ) : 0,
				'query[year]': dateFilter.value ? parseInt( dateFilter.value.substr( 0, 4 ), 10 ) : 0,
				'query[s]': search.value ? search.value : '',
				'query[paged]': paged ? paged : 1,
				'query[media_category_name]': mediaCatSelect.value ? mediaCatSelect.value : ''
			} );

		if ( fileType === 'image' ) {
			params.append( 'query[post_mime_type]', 'image' );
		} else if ( fileType === 'audio' ) {
			params.append( 'query[post_mime_type]', 'audio' );
		} else if ( fileType === 'video' ) {
			params.append( 'query[post_mime_type]', 'video' );
		}

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			body: params,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {

				// Clear grid
				dialog.querySelector( '.widget-modal-grid' ).innerHTML = '';

				if ( result.data.length === 0 ) {

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'data-page', 1 );
						pageLink.setAttribute( 'disabled', true );
						pageLink.setAttribute( 'inert', true );
					} );

					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', 1 );
					dialog.querySelector( '.total-pages' ).textContent = 1;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					dialog.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.no-media' ).removeAttribute( 'hidden' );
				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment, widget );
						dialog.querySelector( '.widget-modal-grid' ).append( gridItem );
					} );

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'first-page' ) || pageLink.className.includes( 'prev-page' ) ) {
							if ( paged === '1' ) {
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
								if ( pageLink.className.includes( 'prev-page' ) ) {
									if ( ( parseInt( paged ) - 1 ) < 1 ) {
										pageLink.setAttribute( 'data-page', 1 );
									} else {
										pageLink.setAttribute( 'data-page', parseInt( paged ) - 1 );
									}
								}
							}
						} else if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'data-page', paged );
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.setAttribute( 'data-page', parseInt( paged ) + 1 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', paged ? paged : 1 );
					dialog.querySelector( '#current-page-selector' ).value = paged ? paged : 1;
					dialog.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Open modal to show details about file, or select files for deletion
					if ( dialog.querySelector( '.media-item.selected' ) == null ) {
						dialog.querySelector( '.widget-modal-footer-buttons button' ).setAttribute( 'disabled', true );
						dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
					}
					dialog.querySelectorAll( '.media-item' ).forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							selectItemToAdd( item, widget, fileType, false );
							item.focus();
						}
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, widget, fileType, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + TEXT_WIDGET.of + ' ' + result.headers.total_posts + ' ' + TEXT_WIDGET.media_items;
				}
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );

		dialog.showModal();
	}

	/**
	 * Check if image URL is valid and display if possible.
	 *
	 * @abstract
	 * @return {void}
	 */
	function validateUrl( url ) {
		var file     = url.split( '.' ).pop(),
			fileType = file.split( '?' )[0],
			error    = document.createElement( 'div' ),
			message  = dialog.querySelector( '#message' ),
			img      = new Image(),
			fields   = dialog.querySelectorAll( '#widget-modal-url-settings input, #widget-modal-url-settings textarea' );

		if ( TEXT_WIDGET.image_file_types.includes( fileType.toLowerCase() ) ) {
			img.src = url;
			img.onerror = function() {
				if ( message ) {
					message.remove();
				}
				error.id = 'message';
				error.className = 'notice-error is-dismissible';
				error.innerHTML = '<p style="color:#fff;background:red;font-size:1em;padding:0.5em 1em;margin-top:-1px;">' + TEXT_WIDGET.wrong_url + '</p>';
				dialog.querySelector( '#widget-modal-embed-url-field' ).before( error );

				// Disable other fields until the issue is corrected
				addButton.setAttribute( 'disabled', true );
				fields.forEach( function( input ) {
					input.setAttribute( 'disabled', true );
				} );
			};

			// Load image and set width and height properties
			dialog.querySelector( '.link-text' ).setAttribute( 'hidden', true );
			if ( dialog.querySelector( '.widget-modal-media-embed .thumbnail img' ) ) {
				dialog.querySelector( '.widget-modal-media-embed .thumbnail img' ).remove(); // avoid duplicates
			}
			dialog.querySelector( '.widget-modal-media-embed .thumbnail' ).append( img );
			dialog.querySelector( '.widget-modal-media-embed img' ).src = url;
			img.onload = function() {
				img.width = this.naturalWidth;
				img.height = this.naturalHeight;
			};

			if ( message ) {
				message.remove();
				fields.forEach( function( input ) {
					input.removeAttribute( 'disabled' );
				} );
			}
		} else {
			if ( message ) {
				message.remove();
			}
		}
	}

	/**
	 * Insert image or hyperlinked text from URL.
	 *
	 * @abstract
	 * @return {void}
	 */
	function insertEmbed() {
		var originalUrl = '',
			originalText = '',
			originalAlt = '',
			originalCaption = '',
			embed = dialog.querySelector( '#widget-modal-embed-url-field' ),
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId,
			htmlString = tinymce.get( 'widget-text-' + widgetId.split( '-' ).pop() + '-text' ).getContent(),
			doc = parser.parseFromString( htmlString, 'text/html' ),
			embedLinkText = dialog.querySelector( '#embed-link-settings-link-text' ),
			embedAlt = dialog.querySelector( '#embed-image-settings-alt-text' ),
			embedCaption = dialog.querySelector( '#embed-image-settings-caption' ),
			embedLinkTo = dialog.querySelector( '#link-to' ),
			embedLinkUrl = dialog.querySelector( '#embed-image-settings-link-to-custom' );

		// Get the first anchor tag if it exists
		if ( doc.querySelector( 'a' ) ) {
			originalUrl = doc.querySelector( 'a' ).href;
			originalText = doc.querySelector( 'a' ).getAttribute( 'value' );
		} else {
			// Otherwise get the first image, audio, or video source not within a shortcode
			doc.querySelectorAll( 'img, audio, video' ).forEach( function( item, index ) {
				if ( index === 0 ) {
					originalUrl = item.src;
					if ( item.alt ) {
						originalAlt = item.alt;
					}
					if ( item.closest( 'figure' ) && item.closest( 'figure' ).querySelector( 'figcaption' ) ) {
						originalCaption = item.closest( 'figure' ).querySelector( 'figcaption' ).innerHTML;
					}
				}
			} );
		}

		if ( originalUrl !== '' ) {
			validateUrl( originalUrl );
			embed.value = originalUrl;
			embedLinkText.value = originalText;
			embedAlt.value = originalAlt;
			embedCaption.value = originalCaption;
			embedLinkTo.value = '';
			embedLinkUrl.value = '';
		}

		embed.addEventListener( 'change', function( e ) {
			var url = e.target.value,
				message = dialog.querySelector( '#message' );

			// Activate Add to Widget button if appropriate
			if ( url !== originalUrl ) {
				addButton.removeAttribute( 'disabled' );
				validateUrl( url );
			} else {
				if ( message ) {
					message.remove();
				}
				addButton.setAttribute( 'disabled', true );
			}
		} );

		// Update value and trigger Add to Widget button
		embedLinkText.addEventListener( 'change', function() {
			addButton.removeAttribute( 'disabled' );
		} );

		embedAlt.addEventListener( 'change', function() {
			addButton.removeAttribute( 'disabled' );
		} );

		embedCaption.addEventListener( 'change', function() {
			addButton.removeAttribute( 'disabled' );
		} );

		embedLinkTo.addEventListener( 'change', function() {
			addButton.removeAttribute( 'disabled' );
		} );

		embedLinkUrl.addEventListener( 'change', function() {
			addButton.removeAttribute( 'disabled' );
		} );
	}

	/**
	 * Open the modal to edit a gallery or playlist.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editList( widget, selectedIds, fileType, params ) {
		var itemAdd, itemEdit, galleryAdd, galleryInsert, galleryUpdate, formData,
			template        = document.getElementById( 'tmpl-media-grid-modal' ),
			clone           = template.content.cloneNode( true ),
			dialogButtons   = clone.querySelector( '.widget-modal-header-buttons' ),
			dialogContent   = clone.querySelector( '#widget-modal-media-content' ),
			header          = dialog.querySelector( 'header' ),
			galleryTemplate = document.getElementById( 'tmpl-edit-gallery-modal' ),
			galleryClone    = galleryTemplate ? galleryTemplate.content.cloneNode( true ) : '',
			selectedItems   = [];

		// Append cloned template and show relevant elements
		header.append( dialogButtons );
		header.after( dialogContent );
		dialog.querySelector( '.separator' ).insertAdjacentHTML( 'afterend', galleryClone.querySelector( '#gallery-buttons' ).innerHTML );
		dialog.querySelector( '.media-library-grid-section' ).after( galleryClone.querySelector( '.media-gallery-grid-section' ) );
		dialog.querySelector( '.widget-modal-right-sidebar' ).prepend( galleryClone.querySelector( '.widget-modal-gallery-settings' ) );
		dialog.querySelector( 'footer' ).replaceWith( galleryClone.querySelector( 'footer' ) );

		dialog.querySelector( '#menu-item-embed' ).removeAttribute( 'hidden' );
		dialog.querySelector( '.media-library-select-section').classList.add( 'hidden' );
		dialog.querySelector( '#widget-modal-title h2' ).textContent = TEXT_WIDGET.edit_gallery;

		dialogButtons.style.display = 'none';
		dialog.querySelector( '#menu-item-embed' ).setAttribute( 'hidden', true );
		dialog.querySelector( '.media-library-grid-section' ).classList.add( 'hidden' );
		dialog.querySelector( '.media-gallery-grid-section' ).classList.remove( 'hidden' );

		itemAdd = dialog.querySelector( '#menu-item-add' );
		itemAdd.classList.remove( 'active' );
		itemAdd.setAttribute( 'hidden', true );
		itemAdd.setAttribute( 'aria-selected', false );

		itemEdit = dialog.querySelector( '#menu-item-gallery-edit' );
		itemEdit.removeAttribute( 'hidden' );
		itemEdit.classList.add ( 'active' );
		itemEdit.setAttribute( 'aria-selected', true );

		dialog.querySelector( '#gallery-button-new' ).classList.add( 'hidden' );

		galleryAdd = dialog.querySelector( '#menu-item-gallery' );
		galleryAdd.classList.add( 'cancel' );
		galleryAdd.removeAttribute( 'hidden' );
		galleryInsert = dialog.querySelector( '#gallery-button-insert' );
		galleryUpdate = dialog.querySelector( '#gallery-button-update' );

		if ( fileType === 'image' ) {
			dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.edit_gallery;
			galleryAdd.textContent = TEXT_WIDGET.cancel_gallery;
			itemEdit.textContent = TEXT_WIDGET.edit_gallery;
			dialog.querySelector( '#menu-item-gallery-library' ).textContent = TEXT_WIDGET.add_to_gallery;
			galleryInsert.textContent = TEXT_WIDGET.insert_gallery;
			dialog.querySelector( '.widget-modal-gallery-settings' ).removeAttribute( 'hidden' );

			// Get values from shortcode parameters
			dialog.querySelector( '#gallery-settings-columns' ).value = params.columns;
			dialog.querySelector( '#gallery-settings-size' ).value = params.size;
			dialog.querySelector( '#gallery-settings-link-to' ).value = params.link;
			dialog.querySelector( '#gallery-settings-random-order' ).checked = params.orderby;
		} else if ( fileType === 'audio' ) {
			dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.edit_playlist;
			galleryAdd.textContent = TEXT_WIDGET.cancel_playlist;
			itemEdit.textContent = TEXT_WIDGET.edit_playlist;
			dialog.querySelector( '#menu-item-gallery-library' ).textContent = TEXT_WIDGET.add_to_playlist;
			galleryInsert.textContent = TEXT_WIDGET.insert_playlist;
			dialog.querySelector( '.widget-modal-gallery-settings' ).setAttribute( 'hidden', true );
			playlistSettings( 'audio' );
		} else if ( fileType === 'video' ) {
			dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.edit_video_playlist;
			galleryAdd.textContent = TEXT_WIDGET.cancel_video_playlist;
			itemEdit.textContent = TEXT_WIDGET.edit_video_playlist;
			dialog.querySelector( '#menu-item-gallery-library' ).textContent = TEXT_WIDGET.add_to_video_playlist;
			galleryInsert.textContent = TEXT_WIDGET.insert_video_playlist;
			dialog.querySelector( '.widget-modal-gallery-settings' ).setAttribute( 'hidden', true );
			playlistSettings( 'video' );
		}

		setTimeout( function() { // necessary to allow for cleanup first
			if ( dialog.querySelector( '#menu-item-gallery' ).className.includes( 'update' ) ) {
				galleryInsert.classList.add( 'hidden' );
				galleryInsert.setAttribute( 'disabled', true );
				galleryUpdate.classList.remove( 'hidden' );
				galleryUpdate.removeAttribute( 'disabled' );
			} else {
				galleryUpdate.classList.add( 'hidden' );
				galleryUpdate.setAttribute( 'disabled', true );
				galleryInsert.classList.remove( 'hidden' );
				galleryInsert.removeAttribute( 'disabled' );
			}
			dialog.querySelector( '#gallery-button-new' ).classList.add( fileType );
			dialog.querySelector( '#menu-item-gallery-library' ).removeAttribute( 'hidden' );
		}, 0);

		formData = new FormData();
		formData.append( 'action', 'query-attachments' );
		formData.append( 'query[post__in]', selectedIds );
		formData.append( 'query[orderby]', 'post__in' );
		formData.append( 'query[post_mime_type]', fileType );
		formData.append( 'query[paged]', 1 );

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {

				// Set data attributes
				dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId = widget.id;

				// Populate grid with new items
				result.data.forEach( function( attachment ) {
					var gridItem = populateGridItem( attachment, widget );
					selectedItems.push( gridItem );
				} );

				enableGallerySorting( selectedItems, widget, 'edit' );
				dialog.showModal();
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
	}

	/**
	 * Enable sorting of chosen gallery items.
	 *
	 * @abstract
	 * @return {void}
	 */
	function enableGallerySorting( selectedItems, widget, mode ) {
		var gallerySortable, focused,
			wasDragged = false,
			grid = dialog.querySelector( '#gallery-grid' );

		// Give each item the correct class and attributes
		selectedItems.forEach( function( item ) {
			var input;

			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );
			item.querySelector( '.check' ).style.display = 'block';

			// Add a caption input box
			if ( item.querySelector( '.describe' ) == null ) {
				input = document.createElement( 'input' );
				input.className = 'describe';
				input.type = 'text';
				input.value = item.dataset.caption;
				input.dataset.setting = 'caption';
				input.setAttribute( 'aria-label', 'Caption' );
				input.placeholder = TEXT_WIDGET.caption + '...';
				item.append( input );
			}

			grid.append( item );
		} );

		// Update captions
		dialog.querySelectorAll( '.describe' ).forEach( function( input ) {
			input.addEventListener( 'change', function() {
				var item = input.closest( 'li' );
				updateCaption( item.dataset.id, item.dataset.updateNonce, input.value );
			} );
		} );

		// Make gallery grid sortable
		gallerySortable = Sortable.create( grid, {
			group: 'items',
			sort: true,
			handle: 'li',
			fallbackTolerance: 2,

			// Remove an item when clicking on the cross
			onChoose: function( e ) {
				if ( e.originalEvent.target.className === 'check' ) {
					e.item.remove();
				}
				focused = document.activeElement; // find element that currently has focus
			},

			onStart: function() {
				wasDragged = true;
			},

			onEnd: function() {
				setTimeout( function() {
					wasDragged = false;
				}, 0 );
			},

			// Re-identify selectedIds after deselection, reselection, or sorting
			onUnchoose: function( e ) {
				if ( mode === 'edit' && wasDragged === false ) {
					selectItemToAdd( e.item, widget, 'image', true );
				}
				if ( grid.querySelector( '.details' ) ) {
					grid.querySelector( '.details' ).classList.remove( 'details' );
				}
				if ( e.item.className.includes( 'selected' ) ) {
					e.item.classList.add( 'details' );
				}

				// Set focus on previously focused element if it would otherwise revert to body
				if ( document.activeElement.tagName === 'BODY' ) {
					focused.focus();
				}
				setTimeout( function() {
					grid.dispatchEvent( new Event( 'change' ) );
				}, 0 );
			}
		} );

		// Collect selectedIds and enable or disable button to insert items into widget
		grid.addEventListener( 'change', function() {
			var items = grid.querySelectorAll( '.selected' );
			selectedIds = [];
			items.forEach( function( item ) {
				selectedIds.push( item.dataset.id );
			} );
			if ( selectedIds.length === 0 ) {
				dialog.querySelector( '#gallery-button-insert' ).disabled = true;
			} else {
				dialog.querySelector( '#gallery-button-insert' ).disabled = false;
			}
		} );
	}

	/**
	 * Show an updated list of unselected images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateLibrary( widget, fileType ) {
		var gridSection,
			updatedItems = [],
			formData = new FormData();

		formData.append( 'action', 'query-attachments' );
		formData.append( 'query[post__not_in]', selectedIds );
		formData.append( 'query[post_mime_type]', fileType );
		formData.append( 'query[paged]', 1 );

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {
				gridSection = dialog.querySelector( '.media-library-grid-section' );
				gridSection.querySelector( 'ul' ).innerHTML = '';
				if ( fileType === 'image' ) {
					dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.add_to_gallery;
				} else if ( fileType === 'audio' ) {
					dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.add_to_playlist;
				} else if ( fileType === 'video' ) {
					dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.add_to_video_playlist;
				}

				if ( result.data.length === 0 ) {

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'data-page', 1 );
						pageLink.setAttribute( 'disabled', true );
						pageLink.setAttribute( 'inert', true );
					} );

					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', 1 );
					dialog.querySelector( '.total-pages' ).textContent = 1;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					dialog.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.no-media' ).removeAttribute( 'hidden' );

				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment, widget );
						gridSection.querySelector( 'ul' ).append( gridItem );

						updatedItems.push( gridItem );
					} );

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages !== 1 ) {
								pageLink.setAttribute( 'data-page', 2 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages !== 1 ) {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					dialog.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Show details about file, or select files for deletion
					dialog.querySelectorAll( '.media-item' ).forEach( function( item ) {
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, widget, fileType, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + TEXT_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
				}

				// Set widget ID and values of variables
				dialog.querySelector( '.widget-modal-header-buttons' ).style.display = 'flex';
				dialog.querySelector( '.media-library-select-section' ).classList.remove( 'hidden' );
				gridSection.classList.remove( 'hidden' );
				addButton = dialog.querySelector( '#gallery-button-new' );
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
	}

	/**
	 * Update captions for gallery images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateCaption( id, nonce, caption ) {
		var formData = new FormData();

		formData.append( 'action', 'save-attachment' );
		formData.append( 'id', id );
		formData.append( 'nonce', nonce );
		formData.append( 'post_id', 0 );
		formData.append( 'changes[caption]', caption );

		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( !result.success ) {
				console.error( TEXT_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( TEXT_WIDGET.error, error );
		} );
	}

	/**
	 * Add image to widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function addItemToWidget( widget ) {
		var url, file, fileType, selectedItems, selectedItem, columns,
			size, link, orderby, tracklist, images, artists, width, height,
			items     = '',
			text      = '',
			alt       = '',
			caption   = '',
			anchor    = '',
			endAnchor = '',
			linkTo    = '',
			linkUrl   = '',
			tab       = 'mce';

		// Check which tab is currently open
		if ( widget.querySelector( '.wp-editor-wrap' ).className.includes( 'html-active' ) ) {
			tab = 'html';
			widget.querySelector( '.switch-tmce' ).click();
		}

		// Add from URL
		if ( ! dialog.querySelector( '#insert-from-url-panel' ).hasAttribute( 'hidden' ) ) {
			url      = dialog.querySelector( '#widget-modal-embed-url-field' ).value;
			file     = url.split( '.' ).pop();
			fileType = file.split( '?' )[0];
			if ( dialog.querySelector( '.link-text' ).hasAttribute( 'hidden' ) ) {
				if ( TEXT_WIDGET.image_file_types.includes( fileType.toLowerCase() ) ) {
					width   = dialog.querySelector( '.widget-modal-media-embed .thumbnail img').width,
					height  = dialog.querySelector( '.widget-modal-media-embed .thumbnail img').height,
					text    = dialog.querySelector( '#embed-link-settings-link-text' ).value;
					alt     = dialog.querySelector( '#embed-image-settings-alt-text' ).value;
					caption = dialog.querySelector( '#embed-image-settings-caption' ).value;
					linkTo  = dialog.querySelector( '#link-to' ).value;
					if ( linkTo !== 'none' ) {
						if ( linkTo === 'custom' ) {
							linkUrl = dialog.querySelector( '#embed-image-settings-link-to-custom' ).value;
						} else if ( linkTo === 'file' ) {
							linkUrl = url;
						}
						anchor = '<a href="' + linkUrl + '">';
						endAnchor = '</a>';
					}
					tinymce.activeEditor.insertContent( '[caption id="" align="alignnone" width="'+ width + '"]' + anchor + '<img class="size-medium" src="' + url + '" alt="' + alt + '" width="'+ width + '" height="'+ height + '">' + endAnchor + ' ' + caption + '[/caption]' );
				} else if ( TEXT_WIDGET.audio_file_types.includes( fileType.toLowerCase() ) ) {
					tinymce.activeEditor.insertContent( '[embed]' + url + '[/embed]' );
				} else if ( TEXT_WIDGET.video_file_types.includes( fileType.toLowerCase() ) ) {
					tinymce.activeEditor.insertContent( '[embed]' + url + '[/embed]' );
				}
			} else {
				tinymce.activeEditor.insertContent( '<a href="' + url + '" data-mce-href="' + url + '" data-mce-selected="inline-boundary">' + text + '</a>' );
			}

		// Add from Media Library
		} else if ( ! dialog.querySelector( '#media-library-grid' ).hasAttribute( 'hidden' ) ) {
			selectedItems = dialog.querySelectorAll( '.widget-modal-grid .selected' );
			selectedItem = selectedItems[0];

			// Insert items into editor
			if ( dialog.querySelector( '.widget-modal-left-tablist .active' ).id === 'menu-item-add' ) {
				if ( selectedItem.dataset.filetype === 'image' ) {
					alt = dialog.querySelector( '#attachment-details-alt-text' ).value;
					caption = dialog.querySelector( '#attachment-details-caption' ).value;
					tinymce.activeEditor.insertContent( '[caption id="attachment_' + selectedItem.dataset.id + '" align="alignnone" width="' + selectedItem.dataset.width + '"]<a href="' + selectedItem.dataset.url + '" rel="attachment wp-att-' + selectedItem.dataset.id + '"><img class="size-full wp-image-' + selectedItem.dataset.id + '" src="' + selectedItem.dataset.url + '" alt="' + alt + '" width="' + selectedItem.dataset.width + '" height="' + selectedItem.dataset.height + '" /></a> ' + caption + '[/caption]' );
				} else if ( selectedItem.dataset.filetype === 'application' ) {
					tinymce.activeEditor.insertContent( '<a href="' + selectedItem.dataset.url + '" rel="attachment wp-att-' + selectedItem.dataset.id + '">' + selectedItem.getAttribute( 'aria-label' ) + '</a>' );
				} else if ( selectedItem.dataset.filetype === 'audio' ) {
					tinymce.activeEditor.insertContent( '[audio ' + selectedItem.dataset.filename.split( '.' )[1] + '="' + selectedItem.dataset.url + '"][/audio]' );
				} else if ( selectedItem.dataset.filetype === 'video' ) {
					tinymce.activeEditor.insertContent( '[video ' + selectedItem.dataset.mime.split( '/' )[1] + '="' + selectedItem.dataset.url + '"][/video]' );
				}
			} else {
				selectedItems.forEach( function( item, index ) {
					if ( index === selectedItems.length - 1 ) {
						items += item.dataset.id;
					} else {
						items += item.dataset.id + ',';
					}
				} );

				if ( selectedItem.dataset.filetype === 'image' ) {
					columns = dialog.querySelector( '#gallery-settings-columns' ).value;
					size = dialog.querySelector( '#gallery-settings-size' ).value;
					link = dialog.querySelector( '#gallery-settings-link-to' ).value;
					orderby = dialog.querySelector( '#gallery-settings-random-order' ).checked ? ' orderby="rand"' : '';
					tinymce.activeEditor.insertContent( '[gallery ids="' + items + '" columns="' + columns + '" size="' + size + '" link="' + link + '"' + orderby + ']' );
				} else if ( selectedItem.dataset.filetype === 'audio' ) {
					tracklist = dialog.querySelector( '#playlist-settings-show-list' ).checked ? true : false;
					images = dialog.querySelector( '#playlist-settings-show-images' ).checked ? true : false;
					artists = dialog.querySelector( '#playlist-settings-show-artist' ).checked ? true : false;
					tinymce.activeEditor.insertContent( '[playlist ids="' + items + '" tracklist="' + tracklist + '" images="' + images + '" artists="' + artists + '"]' );
				} else if ( selectedItem.dataset.filetype === 'video' ) {
					tracklist = dialog.querySelector( '#playlist-settings-show-list' ).checked ? true : false;
					images = dialog.querySelector( '#playlist-settings-show-images' ).checked ? true : false;
					tinymce.activeEditor.insertContent( '[playlist type="video" ids="' + items + '" tracklist="' + tracklist + '" images="' + images + '"]' );
				}
			}
		}

		// Switch back to Text tab if appropriate
		if ( tab === 'html' ) {
			widget.querySelector( '.switch-html' ).click();
		}

		// Activate Save/Publish button
		if ( document.body.className.includes( 'widgets-php' ) ) {
			widget.classList.add( 'widget-dirty' );
		}
		widget.querySelector( '.widget-control-save' ).textContent = TEXT_WIDGET.save;
		widget.dispatchEvent( new Event( 'change' ) );

		// Explicitly enable Save button (required by some browsers)
		widget.querySelector( '.widget-control-save' ).disabled = false;

		closeModal();
	}

	/**
	 * Open the media frame to modify the selected item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editImage( editor, widget ) {
		var header, imageSize, linkTo, linkToCustom, editOriginal, image,
			widthField, heightField, customSizeField, updateButton,
			substring, replaceButton, formData,
			attachmentId    = dialog.dataset.attachmentId,
			doc             = parser.parseFromString( content, 'text/html' ),
			imgEl           = doc.querySelector( 'img.wp-image-' + attachmentId ),
			alt             = imgEl.getAttribute( 'alt' ),
			title           = imgEl.getAttribute ( 'title' ),
			caption         = '',
			url             = imgEl.src,
			width           = imgEl.width,
			height          = imgEl.height,
			size            = imgEl.className.split( 'size-' )[1].split( ' ' )[0],
			imageClass      = imgEl.className.replace( 'alignnone size-' + size + ' wp-image-' + attachmentId, '' ).trim(),
			anchor          = imgEl.parentNode && imgEl.parentNode.tagName === 'A' ? imgEl.parentNode : '',
			linkUrl         = anchor ? anchor.href : '',
			linkClasses     = anchor ? anchor.className : '',
			linkRel         = anchor ? anchor.getAttribute( 'rel' ) : '',
			linkTargetBlank = anchor ? anchor.getAttribute( 'target' ) : '',
			linkType        = 'none',
			attachmentPages = false,
			template        = document.getElementById( 'tmpl-edit-image-modal' ),
			clone           = template.content.cloneNode( true ),
			openingTag      = normalizeString( '[caption id="attachment_' + attachmentId );

		// Detect caption
		if ( doc.body.textContent.includes( openingTag ) ) {
			substring = doc.body.textContent.substring(
				doc.body.textContent.indexOf( openingTag ),
				doc.body.textContent.indexOf( '[/caption]', doc.body.textContent.indexOf( openingTag ) )
			);
			caption = substring.split( ']' )[1].trim();
		}

		// Get available sizes
		formData = new FormData();
		formData.append( 'action', 'get-attachment' );
		formData.append( 'id', attachmentId );

		// Make the fetch request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			var sizeOptions = '',
				sizes  = result.data.sizes,
				nonces = result.data.nonces;

			// Get available size options
			for ( var dimension in sizes ) {
				sizeOptions += '<option value="' + dimension + '">' + dimension[0].toUpperCase() + dimension.slice(1) + ' – ' + sizes[dimension].width + ' x ' + sizes[dimension].height + '</option>';
			}

			// Append cloned template and establish new variables
			header = dialog.querySelector( 'header' );
			header.after( clone.querySelector( '#image-modal-content' ) );
			dialog.querySelector( '.widget-modal-main' ).append( clone.querySelector( 'footer' ) );
			dialog.style.width = '85%';
			dialog.style.height = '85%';
			dialog.dataset.nonce = nonces.edit;
			header.querySelector( '.widget-modal-headings' ).style.padding = '0 0.5em 0 0.75em';
			header.querySelector( '#widget-modal-title h2' ).textContent = TEXT_WIDGET.image_details;
			dialog.querySelector( '#image-modal-content' ).dataset.widgetId = widget.id;
			dialog.querySelector( '.widget-modal-left-sidebar' ).classList.add( 'hidden' );
			image = dialog.querySelector ( '.image img' );
			image.src = url;
			image.alt = alt;
			image.width = width;
			image.height = height;
			checkWindowWidth();

			imageSize       = dialog.querySelector( '#image-details-size' );
			linkTo          = dialog.querySelector( '#image-details-link-to' );
			linkToCustom    = dialog.querySelector( '#image-details-link-to-custom' );
			editOriginal    = dialog.querySelector( '#edit-original' );
			widthField      = dialog.querySelector( '#image-details-size-width' );
			heightField     = dialog.querySelector( '#image-details-size-height' );
			updateButton    = dialog.querySelector( '#media-button-update' );
			customSizeField = imageSize.closest( 'fieldset' ).querySelector( '.custom-size' );

			// Change editOriginal ID to avoid conflict with image widget
			dialog.querySelector( '.widget-modal-footer' ).style.display = 'block';
			editOriginal.id = 'edit-original-text';
			document.getElementById( 'media-button-update' ).id = 'media-button-update-text';

			// Set available sizes select dropdown
			imageSize.insertAdjacentHTML( 'afterbegin', sizeOptions );
			if ( size === undefined || size === 'custom' ) {
				size = 'custom';
				widthField.value = width;
				heightField.value = height;
				customSizeField.classList.remove( 'hidden' );
			} else {
				customSizeField.classList.add( 'hidden' );
				widthField.value = '';
				heightField.value = '';
			}

			// Show the edit image button only if the image is stored in the media library
			if ( attachmentId == 0 ) {
				editOriginal.parentNode.setAttribute( 'hidden', true );
				editOriginal.parentNode.setAttribute( 'inert', true );
			} else {
				imageSize.querySelector( 'option[value="' + size + '"]' ).setAttribute( 'selected', true );
			}

			imageSize.addEventListener( 'change', function() {
				var selectedOption = imageSize.options[imageSize.selectedIndex];
				imageSize.querySelectorAll( 'option' ).forEach( function( option ) {
					option.removeAttribute( 'selected' );
				} );
				selectedOption.setAttribute( 'selected', true );
				if ( selectedOption.value === 'custom' ) {
					customSizeField.classList.remove( 'hidden' );
					widthField.value = width;
					heightField.value = height;
				} else {
					customSizeField.classList.add( 'hidden' );
					widthField.value = '';
					heightField.value = '';
				}
				updateButton.removeAttribute( 'disabled' ); // trigger Update button
			} );

			// Determine link type, if any
			if ( ! anchor ) {
				linkType = 'none';
			} else if ( dialog.dataset.linkUrl ) {
				if ( dialog.dataset.linkUrl === url ) {
					linkType = 'file';
				} else if ( dialog.dataset.linkUrl === linkUrl ) {
					linkType = 'post';
				} else {
					linkType = 'custom';
				}
			}

			linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
				if ( option.value === linkType ) {
					option.setAttribute( 'selected', true );
				}
				if ( option.value === 'post' ) {
					attachmentPages = true; // the site uses attachment pages
				}
			} );

			if ( linkType === 'none' ) {
				linkTo.value = 'none';
				linkToCustom.parentNode.setAttribute( 'hidden', true );
				linkToCustom.value = '';
			} else {
				linkToCustom.parentNode.removeAttribute( 'hidden' );
				if ( linkType === 'file' ) {
					linkToCustom.value = url;
				} else if ( linkType === 'post' ) {
					linkToCustom.value = url;
					if ( attachmentPages === false ) {
						linkType = 'file';
					}
				} else if ( linkType === 'custom' ) {
					linkToCustom.value = linkUrl;
				}
			}

			// Widget-specific details
			dialog.querySelector( '#image-details-alt-text' ).value = alt;
			dialog.querySelector( '#image-details-caption' ).value = caption;
			dialog.querySelector( '#image-details-title-attribute' ).value = title;
			dialog.querySelector( '#image-details-css-class' ).value = imageClass;
			dialog.querySelector( '#image-details-link-target' ).checked = false;
			if ( linkTargetBlank === '_blank' ) {
				dialog.querySelector( '#image-details-link-target' ).checked = true;
			}
			dialog.querySelector( '#image-details-link-rel' ).value = linkRel;
			dialog.querySelector( '#image-details-link-css-class' ).value = linkClasses;

			// Add correct links
			dialog.querySelector( '.column-image img' ).src = url;
			if ( editOriginal != null ) {
				editOriginal.setAttribute( 'data-href', editOriginal.dataset.href.replace( 'item=xxx', 'item=' + attachmentId ) );
				editOriginal.setAttribute( 'data-widget-id', widget.id );

			replaceButton   = document.createElement( 'input' ),
			replaceButton.id = 'replace-image-button';
			replaceButton.type = 'button';
			replaceButton.className = 'replace-attachment button';
			replaceButton.value = 'Replace';
			replaceButton.style.marginLeft = '6px';
			editOriginal.after( replaceButton );
			}
			dialog.showModal();

			// Show and hide URL field as appropriate
			linkTo.addEventListener( 'change', function() {
				var selectedOption = this[this.selectedIndex];
				linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
					option.removeAttribute( 'selected' );
				} );
				selectedOption.setAttribute( 'selected', true );
				if ( selectedOption.value === 'none' ) {
					linkToCustom.parentNode.setAttribute( 'hidden', true );
					linkToCustom.value = '';
				} else {
					if ( selectedOption.value === 'file' ) {
						linkToCustom.value = url;
					} else if ( selectedOption.value === 'post' ) {
						linkToCustom.value = url;
					} else if ( selectedOption.value === 'custom' ) {
						linkToCustom.value = linkUrl;
					}
					linkToCustom.parentNode.removeAttribute( 'hidden' );
				}
				updateButton.removeAttribute( 'disabled' ); // trigger update button
			} );

			// Trigger update button when other changes made to inputs or textareas
			dialog.querySelectorAll( '#image-modal-content textarea, #image-modal-content input' ).forEach( function( input ) {
				input.addEventListener( 'change', function() {
					updateButton.removeAttribute( 'disabled' );
				} );
			} );
		} )
		.catch( function( error ) {
			console.error( 'Error:', error );
		} );
	}

	/**
	 * Enable update of image details.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateEditedImage( widgetId ) {
		var width, height, imgEl, fullContent, shortcodeRegex, shortcodeMatch,
			fullShortcode, innerContent, cleanedInner, newInnerContent,
			newCaptionShortcodeStart, newCaptionShortcodeEnd, parentContainer,
			hyperlink, updatedContent,
			doc = parser.parseFromString( content, 'text/html' ),
			attachmentId = dialog.dataset.attachmentId,
			updatedShortcode = '',
			originalClosingTag = '',
			captionOpeningTag = '',
			fragment = document.createDocumentFragment(),
			imageClass = dialog.querySelector( '#image-details-css-class' ).value !== '' ? ' ' + dialog.querySelector( '#image-details-css-class' ).value : '',
			alt = dialog.querySelector( '#image-details-alt-text' ).value,
			caption = dialog.querySelector( '#image-details-caption' ).value,
			linkUrl = dialog.querySelector( '#image-details-link-to-custom' ).value,
			linkClass = dialog.querySelector( '#image-details-link-css-class' ).value,
			title = dialog.querySelector( '#image-details-title-attribute' ).value,
			rel = dialog.querySelector( '#image-details-link-rel' ).value,
			linkTarget = dialog.querySelector( '#image-details-link-target' ).checked ? '_blank' : '',
			size = dialog.querySelector( '#image-details-size' ),
			widget = document.getElementById( widgetId );

		if ( size.value === 'custom' ) {
			width = dialog.querySelector( '#image-details-size-width' ).value;
			height = dialog.querySelector( '#image-details-size-height' ).value;
		} else {
			width = parseInt( size.options[size.selectedIndex].text.split( ' – ' )[1] );
			height = parseInt( size.options[size.selectedIndex].text.split( ' x ' )[1] );
		}

		dialog.querySelector( '#image-modal-content' ).remove();
		dialog.querySelector( '.widget-modal-footer' ).remove();
		dialog.close();

		// Find the img element with class containing wp-image-{ID}
		imgEl = doc.querySelector( 'img.wp-image-' + attachmentId );
		if ( imgEl ) {
			fullContent = doc.body.innerHTML;
			shortcodeRegex = new RegExp(
				'\\[caption[^\\]]*id=["\']attachment_' + attachmentId + '["\'][^\\]]*\\]([\\s\\S]*?)\\[\\/caption\\]',
				'i'
			);
			shortcodeMatch = fullContent.match( shortcodeRegex );
			if ( shortcodeMatch ) {

				// Caption shortcode exists
				fullShortcode = shortcodeMatch[0];  // Entire [caption]...[/caption]
				innerContent = shortcodeMatch[1];   // Content inside shortcode

				// Remove old caption text immediately (after </a> or />)
				cleanedInner = innerContent.replace( /(<\/a>|\/>)([\s\S]*)$/i, '$1' );

				// Build new inner content with new caption
				newInnerContent = cleanedInner + ( caption ? ' ' + caption : '' );

				// Create updated shortcode
				updatedShortcode = '[caption id="attachment_' + attachmentId + '" align="alignnone" width="' + width + '"]' + newInnerContent + '[/caption]';

				// Replace the entire shortcode block immediately
				doc.body.innerHTML = fullContent.replace( fullShortcode, updatedShortcode );
			} else if ( caption ) {

				// No caption shortcode, but user wants to ADD one
				newCaptionShortcodeStart = '[caption id="attachment_' + attachmentId + '" align="alignnone" width="' + width + '"]';
				newCaptionShortcodeEnd = caption + '[/caption]';

				// Insert caption shortcode wrapper around imgEl (and its hyperlink if exists)
				parentContainer = imgEl.parentNode && imgEl.parentNode.tagName === 'A' ? imgEl.parentNode : imgEl;
				parentContainer.outerHTML = newCaptionShortcodeStart + parentContainer.outerHTML + newCaptionShortcodeEnd;
			}

			// Re-find imgEl after potential DOM changes
			imgEl = doc.querySelector( 'img.wp-image-' + attachmentId );
			if ( imgEl ) {

				// Update image attributes
				imgEl.setAttribute( 'alt', alt );
				imgEl.setAttribute( 'width', width );
				imgEl.setAttribute( 'height', height );
				if ( title ) {
					imgEl.setAttribute( 'title', title );
				}
				imgEl.className = 'alignnone size-' + size.value + ' wp-image-' + attachmentId + imageClass;

				// Get hyperlink (if one exists) and modify href attribute
				if ( imgEl.parentNode && imgEl.parentNode.tagName === 'A' ) {
					hyperlink = imgEl.parentNode;
					if ( hyperlink ) {
						if ( linkUrl === '' ) {
							fragment.innerHTML = '';
							fragment.append( imgEl );
							hyperlink.replaceWith( fragment );
						} else {
							hyperlink.href = linkUrl;
							if ( linkClass ) {
								hyperlink.className = linkClass;
							}
							if ( linkTarget ) {
								hyperlink.setAttribute( 'target', linkTarget );
							}
							if ( rel ) {
								hyperlink.setAttribute( 'rel', rel );
							}
							if ( captionOpeningTag !== '' ) {
								fragment.innerHTML = '';
								fragment.append( captionOpeningTag );
								hyperlink.before( fragment );
							}
						}
					}
				} else if ( linkUrl ) { // Hyperlink has been added
					hyperlink = document.createElement( 'a' );
					hyperlink.href = linkUrl;
					if ( linkClass ) {
						hyperlink.className = linkClass;
					}
					if ( linkTarget ) {
						hyperlink.setAttribute( 'target', linkTarget );
					}
					if ( rel ) {
						hyperlink.setAttribute( 'rel', rel );
					}
					imgEl.before( hyperlink );
					hyperlink.append( imgEl );
					if ( captionOpeningTag !== '' ) {
						fragment.innerHTML = '';
						fragment.append( captionOpeningTag );
						hyperlink.before( fragment );
					}
				}
			}

			// Serialize the updated document back to HTML string
			updatedContent = doc.body.innerHTML.replace( originalClosingTag, '' );

			// Re-insert updated content back into TinyMCE editor
			tinymce.activeEditor.setContent( updatedContent );
		}

		if ( document.body.className.includes( 'widgets-php' ) ) {
			widget.classList.add( 'widget-dirty' );
		}
		widget.querySelector( '.widget-control-save' ).textContent = TEXT_WIDGET.save;
		widget.dispatchEvent( new Event( 'change' ) );

		// Explicitly enable Save button (required by some browsers)
		widget.querySelector( '.widget-control-save' ).disabled = false;
	}

	/**
	 * Insert a new media frame within the modal to enable editing of image.
	 *
	 * @abstract
	 * @return {void}
	 */
	function imageEdit( widgetId ) {
		var formData,
			attachmentId = dialog.dataset.attachmentId,
			nonce = dialog.dataset.nonce;

		formData = new FormData();
		formData.append( 'action', 'image-editor' );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'postid', attachmentId );
		formData.append( 'do', 'rotate-cw' );

		// Make the fetch request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			dialog.querySelector( '.media-embed' ).style.display = 'none';
			dialog.querySelector( '.modal-image-details' ).insertAdjacentHTML( 'beforeend', result.data.html );

			// Cancel
			dialog.querySelector( '.imgedit-cancel-btn' ).addEventListener( 'click', function() {
				dialog.querySelector( '.media-embed' ).style.display = '';
			} );

			// Submit changes
			dialog.querySelector( '.imgedit-submit-btn' ).addEventListener( 'click', function() {
				document.getElementById( widgetId ).dispatchEvent( new Event( 'change' ) );
				closeModal();
			} );
		} )
		.catch( function( error ) {
			console.error( 'Error:', error );
		} );
	}

	/* Enable choosing of panel on narrow screen */
	function checkWindowWidth() {
		var itemAdd, embed, details;
		if ( dialog.querySelector( '#widget-modal-media-content' ) && window.innerWidth < 901 ) {
			details = dialog.querySelector( 'details' );
			details.removeAttribute( 'hidden' );
			details.addEventListener( 'toggle', function( e ) {
				if ( e.target.open ) {
					itemAdd = dialog.querySelector( '#menu-item-add' );
					itemAdd.removeAttribute( 'hidden' );
					itemAdd.setAttribute( 'aria-selected', true );
					embed = dialog.querySelector( '#menu-item-embed' );
					embed.removeAttribute( 'hidden' );
					details.append( itemAdd );
					details.append( embed );
				}
			} );
		}
	}

	/**
	 * Close modal by clicking button.
	 *
	 * @abstract
	 * @return {void}
	 */
	function closeModal() {
		dialog.close();
		cleanup();
	}

	/**
	 * Restore modal to its original state when it was opened.
	 *
	 * @abstract
	 * @return {void}
	 */
	function cleanup() {
		var mediaButton = dialog.querySelector( '#menu-item-add' ),
			galleryButton = dialog.querySelector( '#menu-item-gallery' ),
			playlistButton = dialog.querySelector( '#menu-item-playlist' ),
			videoListButton = dialog.querySelector( '#menu-item-video-playlist' ),
			urlButton = dialog.querySelector( '#menu-item-embed' );

		if ( dialog.querySelector( '.widget-modal-header-buttons' ) ) {
			dialog.querySelector( '.widget-modal-header-buttons' ).remove();
		}
		if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			dialog.querySelector( '#widget-modal-media-content' ).remove();
		}
		if ( dialog.querySelector( '#image-modal-content' ) ) {
			dialog.querySelector( '#image-modal-content' ).remove();
		}
		if ( dialog.querySelector( '.widget-modal-footer' ) ) {
			dialog.querySelector( '.widget-modal-footer' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-gallery-edit' ) ) {
			dialog.querySelector( '#menu-item-gallery-edit' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-gallery-library' ) ) {
			dialog.querySelector( '#menu-item-gallery-library' ).remove();
		}
		if ( dialog.querySelector( '.media-gallery-grid-section' ) ) {
			dialog.querySelector( '.media-gallery-grid-section' ).remove();
		}
		if ( dialog.querySelector( '.widget-modal-gallery-settings' ) ) {
			dialog.querySelector( '.widget-modal-gallery-settings' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-audio-details' ) ) {
			dialog.querySelector( '#menu-item-audio-details' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-video-details' ) ) {
			dialog.querySelector( '#menu-item-video-details' ).remove();
		}

		dialog.removeAttribute( 'style' );
		mediaButton.removeAttribute( 'hidden' );
		mediaButton.setAttribute( 'aria-selected', true );

		galleryButton.setAttribute( 'hidden', true );
		galleryButton.setAttribute( 'aria-selected', false );
		galleryButton.className = 'media-menu-item';
		galleryButton.textContent = TEXT_WIDGET.create_gallery;

		playlistButton.setAttribute( 'hidden', true );
		playlistButton.setAttribute( 'aria-selected', false );
		playlistButton.classList.remove( 'active' );
		playlistButton.textContent = TEXT_WIDGET.create_playlist;

		videoListButton.setAttribute( 'hidden', true );
		videoListButton.setAttribute( 'aria-selected', false );
		videoListButton.classList.remove( 'active' );
		videoListButton.textContent = TEXT_WIDGET.create_video_playlist;

		urlButton.setAttribute( 'aria-selected', false );
		urlButton.classList.remove( 'active' );

		dialog.querySelector( '.widget-modal-headings' ).removeAttribute( 'style' );
		dialog.querySelector( '.widget-modal-left-sidebar' ).classList.remove( 'hidden' );
		dialog.querySelector( '.widget-modal-title h2' ).textContent = TEXT_WIDGET.media_library;
	}

	/**
	 * Handle clicks on buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var base, page, widgetId, widgetEl, mediaButton, galleryButton,
			playlistButton, videoListButton, activeButton, fileType, itemEmbed,
			itemBrowse, itemUpload, gridPanel, uploadPanel, urlPanel,
			modalButtons, rightSidebar, modalPages, tinymceWidgetId, itemEdit,
			itemLibrary, div, urlSettings, itemCancel, galleryInsert, galleryUpdate,
			librarySelect, headerButtons, galleryGrid, libraryGrid, libraryItems,
			content, sidebarSettings, sidebarInfo, gridSubPanel, dom, shortcodes,
			editor, selectedNode, editorContainer, tinyWidget, oldModal,
			params = '',
			selectedItems = [],
			widget = e.target.closest( '.widget' );

		// Add a media file
		if ( widget ) {
			// If in Customizer, remove old-style dialog
			// Preventing it from opening would be a breaking change, so just remove it asap for now
			if ( document.body.className.includes( 'wp-customizer' ) ) {
				oldModal = document.querySelector( '.media-modal.wp-core-ui' );
				if ( oldModal ) {
					oldModal.parentNode.remove();
				}
			}

			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'text' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'add_media' ) ) {
					selectMedia( widget, 'all', true );
				}
			}

		// Modifying an image
		} else if ( dialog.querySelector( '#image-modal-content' ) ) {

			// Edit the image in a widget
			if ( e.target === dialog.querySelector( '#edit-original-text' ) ) {
				imageEdit( dialog.querySelector( '#image-modal-content' ).dataset.widgetId );

			// Replace current image
			} else if ( e.target === dialog.querySelector( '#replace-image-button' ) ) {
				widget = document.getElementById( dialog.querySelector( '#image-modal-content' ).dataset.widgetId );
				cleanup();
				selectMedia( widget, 'image', false );
			}

		// Close the modal
		} else if ( e.target.id === 'widget-modal-close' ) {
			closeModal();

		// Edit an image file
		} else if ( e.target.closest( '.mce-inline-toolbar-grp' ) && e.target.className.includes( 'dashicons-edit' ) ) {
			editor = tinymce.activeEditor;
			if ( editor ) {
				selectedNode = editor.selection.getNode();

				// Identify widget
				if ( selectedNode.nodeName === 'IMG' ) {
					editorContainer = editor.getContainer();
					tinyWidget = editorContainer.closest( '.widget' );
					editImage( editor, tinyWidget );
				}
			}

		// Update an edited image
		} if ( e.target.id === 'media-button-update-text' ) {
			updateEditedImage( document.getElementById( 'image-modal-content' ).dataset.widgetId );

		// Set variables for the rest of the options below
		} else if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			widgetEl = document.getElementById( widgetId );
			base     = widgetEl ? widgetEl.querySelector( '.id_base' ) : '';

			// Only run on a media text widget
			if ( base && base.value === 'text' ) {
				mediaButton     = dialog.querySelector( '#menu-item-add' );
				galleryButton   = dialog.querySelector( '#menu-item-gallery' );
				playlistButton  = dialog.querySelector( '#menu-item-playlist' );
				videoListButton = dialog.querySelector( '#menu-item-video-playlist' );
				activeButton    = dialog.querySelector( '.widget-modal-left-tablist .active' );
				itemEmbed       = dialog.querySelector( '#menu-item-embed' );
				itemBrowse      = dialog.querySelector( '#menu-item-browse' );
				itemUpload      = dialog.querySelector( '#menu-item-upload' );
				gridPanel       = dialog.querySelector( '#media-library-grid' );
				rightSidebar    = dialog.querySelector( '.widget-modal-right-sidebar' );
				modalPages      = dialog.querySelector( '.widget-modal-pages' );
				uploadPanel     = dialog.querySelector( '#uploader-inline' );
				urlPanel        = dialog.querySelector( '#insert-from-url-panel' );
				modalButtons    = dialog.querySelector( '.widget-modal-header-buttons' );

				itemCancel      = dialog.querySelector( '.active' );
				itemEdit        = dialog.querySelector( '#menu-item-gallery-edit' );
				itemLibrary     = dialog.querySelector( '#menu-item-gallery-library' );

				galleryInsert   = dialog.querySelector( '#gallery-button-insert' );
				galleryUpdate   = dialog.querySelector( '#gallery-button-update' );
				sidebarInfo     = dialog.querySelector( '.widget-modal-right-sidebar-info' );
				sidebarSettings = dialog.querySelector( '.widget-modal-gallery-settings' );

				uploadPanel     = dialog.querySelector( '#uploader-inline' );
				headerButtons   = dialog.querySelector( '.widget-modal-header-buttons');
				modalPages      = dialog.querySelector( '.widget-modal-pages' );
				librarySelect   = dialog.querySelector( '.media-library-select-section' );
				libraryGrid     = dialog.querySelector( '.media-library-grid-section' );
				libraryItems    = dialog.querySelectorAll( '.media-library-grid-section li' );
				galleryGrid     = dialog.querySelector( '.media-gallery-grid-section' );
				content         = dialog.querySelector( '.media-frame-content' );

				gridSubPanel    = dialog.querySelectorAll( '#attachments-browser, .media-views-heading, .attachments-wrapper, .media-sidebar, .widgets-modal-pages, .media-frame-toolbar' );

				if ( activeButton === mediaButton ) {
					fileType = 'all';
				} else if ( activeButton === galleryButton || dialog.querySelector( '#gallery-button-new' ).className.includes( 'image' ) ) {
					fileType = 'image';
					galleryInsert.textContent = TEXT_WIDGET.insert_gallery;
					galleryUpdate.textContent = TEXT_WIDGET.update_gallery;
				} else if ( activeButton === playlistButton || dialog.querySelector( '#gallery-button-new' ).className.includes( 'audio' ) ) {
					fileType = 'audio';
					galleryInsert.textContent = TEXT_WIDGET.insert_playlist;
					galleryUpdate.textContent = TEXT_WIDGET.update_playlist;
				} else if ( activeButton === videoListButton || dialog.querySelector( '#gallery-button-new' ).className.includes( 'video' ) ) {
					fileType = 'video';
					galleryInsert.textContent = TEXT_WIDGET.insert_video_playlist;
					galleryUpdate.textContent = TEXT_WIDGET.update_video_playlist;
				}

				// Add a media file
				if ( e.target === mediaButton ) {
					cleanup();
					selectMedia( widgetEl, 'all', true );

				// Create or cancel a gallery or playlist
				} else if ( e.target === galleryButton ) {
					if ( e.target.className.includes( 'update' ) ) {
						closeModal();
					} else {
						cleanup();
						if ( e.target.className.includes( 'cancel' ) ) {
							e.target.textContent = TEXT_WIDGET.create_gallery;
							e.target.classList.remove( 'cancel' );
							selectMedia( widgetEl, 'all', true );
						} else {
							selectMedia( widgetEl, 'image', true );
						}
					}

				// Create an audio playlist
				} else if ( e.target === playlistButton ) {
					cleanup();
					if ( e.target.className.includes( 'cancel' ) ) {
						e.target.textContent = TEXT_WIDGET.create_playlist;
						e.target.classList.remove( 'cancel' );
						selectMedia( widgetEl, 'all', true );
					} else {
						selectMedia( widgetEl, 'audio', true );
					}

				// Create a video playlist
				} else if ( e.target === videoListButton ) {
					cleanup();
					if ( e.target.className.includes( 'cancel' ) ) {
						e.target.textContent = TEXT_WIDGET.create_video_playlist;
						e.target.classList.remove( 'cancel' );
						selectMedia( widgetEl, 'all', true );
					} else {
						selectMedia( widgetEl, 'video', true );
					}

				} else if ( e.target.className === 'media-menu-item cancel' ) {
					closeModal();

				} else if ( e.target.className === 'button-link remove-setting'	) {
					e.target.previousElementSibling.remove();
					e.target.parentNode.querySelector( 'audio' ).remove();
					e.target.remove();
					dialog.querySelector( '#audio-button-update' ).disabled = false;

				// Empty the modal footer
				} else if ( e.target.className === 'button-link clear-selection' ) {
					dialog.querySelectorAll( '.widget-modal-grid .selected' ).forEach( function( item ) {
						item.querySelector( '.check' ).style.display = 'none';
						item.classList.remove( 'selected' );
					} );
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
					e.target.parentNode.parentNode.style.visibility = 'hidden';
					dialog.querySelector( '.widget-modal-footer-selection-view ul' ).innerHTML = '';
					dialog.querySelector( '.widget-modal-footer-buttons button' ).disabled = true;

				// Search or go to a specific page in the media library grid
				} else if ( e.target.parentNode.className === 'pagination-links' && e.target.tagName === 'BUTTON' ) {
					page = e.target.dataset.page;
					updateGrid( widgetEl, page, fileType );
				} else if ( e.target.parentNode.parentNode && e.target.parentNode.parentNode.className === 'pagination-links' && e.target.parentNode.tagName === 'BUTTON' ) {
					page = e.target.parentNode.dataset.page;
					updateGrid( widgetEl, page, fileType );

				// Open the library to add items to current gallery or playlist
				} else if ( e.target === itemLibrary ) {
					sidebarInfo.setAttribute( 'hidden', true );
					sidebarSettings.setAttribute( 'hidden', true );
					itemEdit.classList.remove( 'active' );
					itemEdit.setAttribute( 'aria-selected', false );
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					mediaButton.classList.remove( 'active' );
					mediaButton.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					galleryInsert.classList.add( 'hidden' );
					galleryInsert.setAttribute( 'disabled', true );
					galleryGrid.classList.add( 'hidden' );
					sidebarInfo.setAttribute( 'hidden', true );
					libraryGrid.classList.remove( 'hidden' );

					updateLibrary( widgetEl, fileType );
					galleryUpdate.classList.remove( 'hidden' );
					galleryUpdate.removeAttribute( 'disabled' );

				// Create a new gallery or playlist
				} else if ( e.target.id === 'gallery-button-new' ) {
					headerButtons.style.display = 'none';
					sidebarInfo.setAttribute( 'hidden', true );

					e.target.classList.add( 'hidden' );
					mediaButton.classList.remove( 'active' );
					mediaButton.setAttribute( 'aria-selected', false );
					mediaButton.setAttribute( 'hidden', true );
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					libraryGrid.classList.add( 'hidden' );
					librarySelect.classList.add( 'hidden' );
					itemEmbed.setAttribute( 'hidden', true );

					if ( e.target.className.includes ( 'image' ) ) {
						dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.edit_gallery;
						playlistButton.setAttribute( 'hidden', true );
						videoListButton.setAttribute( 'hidden', true );
						sidebarSettings.removeAttribute( 'hidden' );
						itemCancel.textContent = TEXT_WIDGET.cancel_gallery;
						itemEdit.textContent = TEXT_WIDGET.edit_gallery;
						itemLibrary.textContent = TEXT_WIDGET.add_to_gallery;
						galleryInsert.textContent = TEXT_WIDGET.insert_gallery;
					} else if ( e.target.className.includes ( 'audio' ) ) {
						dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.edit_playlist;
						galleryButton.setAttribute( 'hidden', true );
						videoListButton.setAttribute( 'hidden', true );
						sidebarSettings.setAttribute( 'hidden', true );
						itemCancel.textContent = TEXT_WIDGET.cancel_playlist;
						itemEdit.textContent = TEXT_WIDGET.edit_playlist;
						itemLibrary.textContent = TEXT_WIDGET.add_to_playlist;
						galleryInsert.textContent = TEXT_WIDGET.insert_playlist;
						playlistSettings( 'audio' );
					} else if ( e.target.className.includes ( 'video' ) ) {
						dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.edit_video_playlist;
						galleryButton.setAttribute( 'hidden', true );
						playlistButton.setAttribute( 'hidden', true );
						sidebarSettings.setAttribute( 'hidden', true );
						itemCancel.textContent = TEXT_WIDGET.cancel_video_playlist;
						itemEdit.textContent = TEXT_WIDGET.edit_video_playlist;
						itemLibrary.textContent = TEXT_WIDGET.add_to_video_playlist;
						galleryInsert.textContent = TEXT_WIDGET.insert_video_playlist;
						playlistSettings( 'video' );
					}
					itemCancel.removeAttribute( 'hidden' );
					itemCancel.classList.remove( 'active' );
					itemCancel.classList.add( 'cancel' );
					itemCancel.setAttribute( 'aria-selected', false );
					itemEdit.removeAttribute( 'hidden' );
					itemEdit.classList.add ( 'active' );
					itemEdit.setAttribute( 'aria-selected', true );
					itemLibrary.removeAttribute( 'hidden' );

					galleryGrid.classList.remove( 'hidden' );
					galleryInsert.classList.remove( 'hidden' );

					libraryItems.forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							selectedIds.push( item.dataset.id );
							selectedItems.push( item );
						} else {
							item.setAttribute( 'hidden', true );
							item.setAttribute( 'inert', true );
						}
					} );

					enableGallerySorting( selectedItems, widgetEl, 'select' );

				// Add a new image to a widget via the image's URL
				} else if ( e.target === itemEmbed ) {
					dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.insert_from_url;
					dialog.querySelectorAll( '.widget-modal-left-tablist .media-menu-item' ).forEach( function( menuItem ) {
						menuItem.classList.remove( 'active' );
						menuItem.setAttribute( 'aria-selected', false );
					} );
					itemBrowse.classList.remove( 'active' );
					itemBrowse.setAttribute( 'aria-selected', false );
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					modalButtons.style.display = 'none';
					uploadPanel.setAttribute( 'hidden', true );
					uploadPanel.setAttribute( 'inert', true );
					gridPanel.setAttribute( 'hidden', true );
					gridPanel.setAttribute( 'inert', true );
					rightSidebar.setAttribute( 'hidden', true );
					urlPanel.removeAttribute( 'hidden' );
					urlPanel.removeAttribute( 'inert' );

					dialog.querySelector( '.widget-modal-url-container' ).style.height = '100vh';

					div = document.createElement( 'div' );
					div.className = 'setting link-text';
					div.innerHTML = '<label for="embed-link-settings-link-text" class="name">' + TEXT_WIDGET.link_text + '</label>' +
						'<input type="text" id="embed-link-settings-link-text" data-setting="linkText">';

					urlSettings = dialog.querySelector( '#widget-modal-url-settings' );
					for ( var i = 0, n = urlSettings.children.length; i < n; i++ ) {
						//urlSettings.children[i].classList.add( 'hidden' );
					}
					urlSettings.prepend( div );
					insertEmbed();

				// Insert a gallery or playlist into a widget
				} else if ( e.target === galleryInsert ) {

					// Set the correct activeEditor
					tinymceWidgetId = widgetEl.id.split( '-' ).pop();
					tinymce.get( 'widget-text-' + tinymceWidgetId + '-text' ).focus();

					addItemToWidget( widgetEl );

				// Search for a new image to add to a widget
				} else if ( e.target === mediaButton ) {
					dialog.querySelector( 'h2' ).textContent = TEXT_WIDGET.media_library;
					itemEmbed.classList.remove( 'active' );
					itemEmbed.setAttribute( 'aria-selected', false );
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					itemBrowse.classList.remove( 'active' );
					itemBrowse.setAttribute( 'aria-selected', false );
					urlPanel.setAttribute( 'hidden', true );
					urlPanel.setAttribute( 'inert', true );
					if ( dialog.querySelector( '.widget-modal-media-embed .thumbnail img') ) {
						dialog.querySelector( '.widget-modal-media-embed .thumbnail img').remove();
					}
					modalButtons.style.display = 'flex';
					gridPanel.removeAttribute( 'hidden' );
					gridPanel.removeAttribute( 'inert' );
					rightSidebar.removeAttribute( 'hidden' );
					dialog.querySelector( '#menu-item-browse' ).click();

				// Browse the library of uploaded images
				} else if ( e.target === itemBrowse ) {
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					mediaButton.classList.add( 'active' );
					mediaButton.setAttribute( 'aria-selected', true );
					itemEmbed.classList.remove( 'active' );
					itemEmbed.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					uploadPanel.setAttribute( 'hidden', true );
					uploadPanel.setAttribute( 'inert', true );
					gridPanel.removeAttribute( 'hidden' );
					gridPanel.removeAttribute( 'inert' );
					rightSidebar.removeAttribute( 'hidden' );
					rightSidebar.removeAttribute( 'inert' );
					modalPages.removeAttribute( 'hidden' );
					modalPages.removeAttribute( 'inert' );

				// Upload a new attachment
				} else if ( e.target === itemUpload ) {
					itemBrowse.classList.remove( 'active' );
					itemBrowse.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					uploadPanel.removeAttribute( 'hidden' );
					uploadPanel.removeAttribute( 'inert' );
					gridPanel.setAttribute( 'hidden', true );
					gridPanel.setAttribute( 'inert', true );
					rightSidebar.setAttribute( 'hidden', true );
					rightSidebar.setAttribute( 'inert', true );
					modalPages.setAttribute( 'hidden', true );
					modalPages.setAttribute( 'inert', true );
					goFilepond( widgetId );

				// Add item to widget
				} else if ( e.target.id === 'media-button-insert' ) {

					// Set the correct activeEditor
					tinymceWidgetId = widgetEl.id.split( '-' ).pop();
					tinymce.get( 'widget-text-' + tinymceWidgetId + '-text' ).focus();

					// Add media item
					addItemToWidget( widgetEl );

				// Update a gallery or playlist
				} else if ( e.target === galleryUpdate ) {
					libraryItems.forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							selectedIds.push( item.dataset.id );
						}
					} );

					// Update gallery settings
					if ( fileType === 'image' ) {
						params = { columns: '3', size: 'thumbnail', link: 'post', orderby: false };
					}
					params.columns = dialog.querySelector( '#gallery-settings-columns' ).value;
					params.size = dialog.querySelector( '#gallery-settings-size' ).value;
					params.link = dialog.querySelector( '#gallery-settings-link-to' ).value;
					if ( dialog.querySelector( '#gallery-settings-random-order' ).checked ) {
						params.orderby = true;
					}

					cleanup();
					editList( widgetEl, selectedIds, fileType, params );

				// Reverse the order of items in the gallery or playlist
				} else if ( e.target.className.includes( 'gallery-button-reverse' ) ) {
					dialog.querySelectorAll( '#gallery-grid li:not( [hidden] )' ).forEach( function( item ) {
						item.parentNode.prepend( item );
					} );
					selectedIds.reverse();

				// Delete an item from the media library
				} else if ( e.target.className.includes( 'delete-attachment' ) ) {
					if ( dialog ) {
						if ( window.confirm( TEXT_WIDGET.confirm_delete ) ) {
							deleteItem( e.target.dataset.id );
							resetDataOrdering();
						}
					} else {
						// Protect against XSS vulnerability
						selectedIds = '';
						dom = parser.parseFromString( widgetEl.querySelector( 'textarea' ).textContent, 'text/html' );
						shortcodes = dom.body.textContent.split( 'ids="' );
						shortcodes.forEach( function( selected, index ) {
							if ( index !== 0 ) {
								selected.replace( '[gallery', '' ).replace( '[playlist', '' ).replace( '[playlist type="video"', '' );
								selectedIds += selected.split( '"]' );
							}
						} );
						if ( selectedIds ) {
							libraryItems.forEach( function( item ) {
								//if ( item.className.includes( 'selected' ) ) {
									if ( ! selectedIds.split( ',' ).includes( item.dataset.id ) ) {
										if ( window.confirm( TEXT_WIDGET.confirm_delete ) ) {
											deleteItem( item.dataset.id );
										}
									}
								//}
							} );
						}
					}

				// Copy URL
				} else if ( e.target.className.includes( 'copy-attachment-url' ) ) {
					copyToClipboard( e.target );
				}
			}
		}
	} );

	/**
	 * Enable searching for items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	dialog.addEventListener( 'change', function( e ) {
		var widgetId, widgetEl, base, fileType,
			activeButton = dialog.querySelector( '.widget-modal-left-tablist .active' );

		if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			widgetEl = document.getElementById( widgetId );
			base     = widgetEl.querySelector( '.id_base' );

			// Only run on a media image widget
			if ( base && base.value === 'text' ) {

				if ( activeButton.id === 'menu-item-add' ) {
					fileType = 'all';
				} else if ( activeButton.id === 'menu-item-gallery' ) {
					fileType = 'image';
				} else if ( activeButton.id === 'menu-item-playlist' ) {
					fileType = 'audio';
				} else if ( activeButton.id === 'menu-item-video-playlist' ) {
					fileType = 'video';
				}

				if ( e.target.id === 'filter-by-date' ) {
					updateGrid( widgetEl, 1, fileType );
				} else if ( e.target.className === 'postform' ) {
					updateGrid( widgetEl, 1, fileType );
				} else if ( e.target.id === 'current-page-selector' ) {
					updateGrid( widgetEl, e.target.value, fileType );
				} else if ( e.target.id === 'widget-modal-search-input' ) {
					updateGrid( widgetEl, 1, fileType );
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}
			} else if ( dialog.querySelector( '#image-modal-content' ) ) {
				widgetId = dialog.querySelector( '#image-modal-content' ).dataset.widgetId;
			}
		}
	} );

	// Set focus after closing modal using Escape key
	dialog.addEventListener( 'keydown', function( e ) {
		var widgetId, widget, details, base,
			modal = dialog.querySelector( '#media-widget-modal' );

		if ( modal ) {
			if ( dialog.querySelector( '#image-modal-content' ) ) {
				widgetId = dialog.querySelector( '#image-modal-content' ).dataset.widgetId;
			} else {
				widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			}

			if ( widgetId ) {
				widget  = document.getElementById( widgetId );
				details = widget.querySelector( 'details' );
				base    = widget.querySelector( '.id_base' );

				if ( base && base.value === 'text' ) {
					if ( dialog.open && e.key === 'Escape' ) {
						closeModal();
						setTimeout( function() {
							details.open = true;
						}, 100 );
						details.addEventListener( 'toggle', function( e ) {
							if ( e.target.open ) {
								widget.querySelector( '.add_media' ).focus();
							}
						} );
					}
				}
			}
		}
	} );

	/**
	 * Upload files using FilePond
	 */
	function goFilepond( widgetId ) {

		// Register FilePond plugins
		FilePond.registerPlugin(
			FilePondPluginFileValidateSize,
			FilePondPluginFileValidateType,
			FilePondPluginFileRename,
			FilePondPluginImagePreview
		);

		// Create a FilePond instance
		pond = FilePond.create( dialog.querySelector( '#filepond' ), {
			allowMultiple: true,
			server: {
				process: function( fieldName, file, metadata, load, error, progress, abort ) {

					// Create FormData
					var uploadID = null,
						formData = new FormData();

					formData.append( 'async-upload', file, file.name );
					formData.append( 'action', 'upload-attachment' );
					formData.append( '_wpnonce', document.getElementById( '_wpnonce' ).value );

					// Use Fetch to upload the file
					fetch( ajaxurl, {
						method: 'POST',
						body: formData,
						credentials: 'same-origin'
					} )
					.then( function( response ) {
						if ( response.ok ) {
							return response.json(); // no errors
						}
						throw new Error( response.status );
					} )
					.then( function( result ) {
						if ( result.success ) {
							load( result.data );
							uploadID = result.data.id;
						} else {
							error( TEXT_WIDGET.upload_failed );
						}
					} )
					.catch( function( err ) {
						error( TEXT_WIDGET.upload_failed );
						console.error( TEXT_WIDGET.error, err );
					} );

					// Return an abort function
					return {
						abort: function() {
							// Cancel the fetch request
							pond.removeFile( file.id, { revert: true } );

							// If the file has already been uploaded to the server, delete it
							if ( uploadID !== null ) {
								setTimeout( function() {
									deleteItem( uploadID );
								}, 0 );
								uploadID = null;
							}

							// Tell filePond to stop tracking the file
							abort();
						}
					};
				},
				maxFileSize: dialog.querySelector( '#ajax-url' ).dataset.maxFileSize
			},
			onprocessfile: ( error, file ) => { // Called when an individual file upload completes
				if ( ! error ) {
					setTimeout( function() {
						pond.removeFile( file.id );
					}, 100 );
					resetDataOrdering();
				}
			},
			onprocessfiles: () => { // Called when all files in the queue have finished uploading
				updateGrid( document.getElementById( widgetId ), 1, 'all' );
				dialog.querySelector( '#menu-item-browse' ).click();
				setTimeout( function() {
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}, 500 );
			},
			labelTapToUndo: TEXT_WIDGET.tap_close,
			fileRenameFunction: ( file ) =>
				new Promise( function( resolve ) {
					resolve( window.prompt( TEXT_WIDGET.new_filename, file.name ) );
				} ),
			acceptedFileTypes: document.querySelector( '.uploader-inline' ).dataset.allowedMimes.split( ',' ),
			labelFileTypeNotAllowed: TEXT_WIDGET.invalid_type,
			fileValidateTypeLabelExpectedTypes: TEXT_WIDGET.check_types
		} );
	}

	// Reset ordering of remaining media items after deletion
	function resetDataOrdering() {
		var items = document.querySelectorAll( '.media-item' ),
			num = document.querySelector( '.displaying-num' ).textContent.split( ' ' ),
			count = document.querySelector( '.load-more-count' ).textContent.split( ' ' ),
			count5;

		items.forEach( function( item, index ) {
			item.setAttribute( 'data-order', parseInt( index + 1 ) );
		} );

		// Reset totals
		if ( 5 in count ) { // allow for different languages
			count5 = ' ' + count[5];
		} else {
			count5 = '';
		}
		document.querySelector( '.load-more-count' ).textContent = count[0] + ' ' + items.length + ' ' + count[2] + ' ' + items.length + ' ' + count[4] + count5;

		document.querySelector( '.displaying-num' ).textContent = items.length + ' ' + num[1];
	}

	// Enable QuickTags toolbar when TinyMCE is not present
	function initQuickTags( widget ) {
		if ( typeof tinymce === 'undefined' ) {
			setTimeout( function() {
				widget.querySelector( '.quicktags-toolbar' ).addEventListener( 'click', function( e ) {
					var textarea, start, end, selection;
					if ( e.target.tagName !== 'BUTTON' ) {
						return;
					}
					textarea  = widget.querySelector( 'textarea' );
					start     = textarea.selectionStart;
					end       = textarea.selectionEnd;
					selection = textarea.value.slice( start, end );

					switch ( e.target.dataset.tag ) {
						case 'b':
							wrapSelection( textarea, '<strong>', '</strong>' );
							break;
						case 'i':
							wrapSelection( textarea, '<em>', '</em>' );
							break;
						case 'link':
							insertLink( textarea, selection );
							break;
						case 'ul':
							insertUnorderedList( textarea, selection );
							break;
						case 'ol':
							insertOrderedList( textarea, selection );
							break;
						case 'li':
							wrapSelection( textarea, '<li>', '</li>' );
							break;
						case 'code':
							wrapSelection( textarea, '<code>', '</code>' );
							break;
					}
					widget.classList.add( 'widget-dirty' );
					widget.dispatchEvent( new Event( 'change' ) );
				} );
			}, 0);
		}
	}

	function wrapSelection( textarea, before, after ) {
		var start = textarea.selectionStart,
			end = textarea.selectionEnd,
			value = textarea.value,
			selected = value.slice( start, end ),
			wrapped = before + selected + after;

		textarea.value = value.slice( 0, start ) + wrapped + value.slice( end );
		textarea.setSelectionRange( start + before.length, start + before.length + selected.length );
		textarea.focus();
	}

	function insertUnorderedList( textarea, selected ) {
		var lines = selected ? selected.split( '\n' ) : [ 'List item' ],
			newText = '<ul>\n' + lines.map( l => '<li>' + l + '</li>' ).join( '\n ') + '\n</ul>';

		replaceSelection( textarea, newText );
	}

	function insertOrderedList( textarea, selected ) {
		var lines = selected ? selected.split( '\n' ) : [ 'List item' ],
			newText = '<ol>\n' + lines.map( l => '<li>' + l + '</li>' ).join( '\n ') + '\n</ol>';

		replaceSelection( textarea, newText );
	}

	function insertLink( textarea, selected ) {
		var newText,
			url = prompt( 'Enter URL:', 'https://' );

		if ( ! url ) {
			return;
		}

		newText = '<a href="' + url + '">' + ( selected || 'link text' ) + '</a>';
		replaceSelection( textarea, newText );
	}

	function replaceSelection( textarea, newText ) {
		var start = textarea.selectionStart,
			end = textarea.selectionEnd;

		textarea.value = textarea.value.slice( 0, start ) + newText + textarea.value.slice( end );
		textarea.setSelectionRange( start, start + newText.length );
		textarea.focus();
	}

	// Normalize string
	function normalizeString( str ) {
		return str.replace( /[\s\r\n]+/g, ' ' ).replace( /&quot;/g, '"' ).trim();
	}
} );
