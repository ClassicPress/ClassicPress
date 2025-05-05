/* global console, _wpMediaLibSettings, showNotice, findPosts, FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginFileRename, FilePondPluginImagePreview */

document.addEventListener( 'DOMContentLoaded', function() {
	/**
	 * Initializes the file once the DOM is fully loaded and attaches events to the
	 * various form elements.
	 *
	 * @since CP-2.2.0
	 *
	 * @return {void}
	 */
	var pond, itemID, focusID, copyAttachmentURLs, copyAttachmentURLSuccessTimeout,
		{ FilePond } = window, // import FilePond
		queryParams = new URLSearchParams( window.location.search ),
		addNew = document.querySelector( '.page-title-action' ),
		uploader = document.querySelector( '.uploader-inline' ),
		close = document.querySelector( '.close' ),
		uploadCatSelect = document.getElementById( 'upload-category' ),
		inputElement = document.getElementById( 'filepond' ),
		ajaxurl = document.getElementById( 'ajax-url' ).value,
		dialog = document.getElementById( 'media-modal' ),
		leftIcon = document.getElementById( 'left-dashicon' ),
		rightIcon = document.getElementById( 'right-dashicon' ),
		closeButton = document.getElementById( 'dialog-close-button' ),
		leftIconMobile = document.getElementById( 'left-dashicon-mobile' ),
		rightIconMobile = document.getElementById( 'right-dashicon-mobile' ),
		mediaNavigation = document.querySelector( '.edit-media-header .media-navigation' ),
		mediaNavigationMobile = document.querySelector( '.attachment-media-view .media-navigation' ),
		paged = '1',
		dateFilter = document.getElementById( 'filter-by-date' ) ? document.getElementById( 'filter-by-date' ) : '',
		typeFilter = document.getElementById( 'filter-by-type' ) ? document.getElementById( 'filter-by-type' ) : '',
		search = document.getElementById( 'media-search-input' ),
		mediaCatSelect = document.getElementById( 'taxonomy=media_category&term' ) ? document.getElementById( 'taxonomy=media_category&term' ) : '',
		mediaGrid = document.querySelector( '#media-grid ul' ),
		startTouchPosition = 0,
		endTouchPosition = 0,
		quickEditUpdateButton = document.getElementById( 'quick-edit-update' ),
		quickEditEventHandler = null;

	/**
	 * Autosuggest tags when bulk or quick editing
	 *
	 * Based on https://phuoc.ng/collection/mirror-a-text-area/add-autocomplete-to-your-text-area/
	 */
	function autoCompleteTextarea( textarea ) {
		var currentSuggestionIndex = -1,
			mirror = textarea.previousElementSibling,
			suggestionsContainer = document.querySelector( '.container__suggestions' ),
			suggestions = document.getElementById( 'tags-list' ).value.split( ', ' );

		textarea.addEventListener( 'scroll', function() {
			mirror.scrollTop = textarea.scrollTop;
		} );

		// Listen for characters input to the textarea
		textarea.addEventListener( 'input', function() {
			var currentWord, matches, pre, post, caret, rect, textBeforeCursor, textAfterCursor,
				currentValue = textarea.value,
				cursorPos = textarea.selectionStart,
				startIndex = findIndexOfCurrentWord();

			// Extract just the current word
			currentWord = currentValue.substring( startIndex + 1, cursorPos );
			if ( currentWord === '' ) {
				suggestionsContainer.style.display = 'none';
				return;
			}

			// Make matching case insensitive
			matches = suggestions.filter( function( suggestion ) {
				return suggestion.toLowerCase().indexOf( currentWord.toLowerCase() ) > -1;
			} );
			if ( matches.length === 0 ) {
				suggestionsContainer.style.display = 'none';
				return;
			}

			textBeforeCursor = currentValue.substring( 0, cursorPos ),
			textAfterCursor = currentValue.substring( cursorPos ),
			pre = document.createTextNode( textBeforeCursor );
			post = document.createTextNode( textAfterCursor );
			caret = document.createElement( 'span' );
			caret.innerHTML = '&nbsp;';

			mirror.innerHTML = '';
			mirror.append( pre, caret, post );

			rect = caret.getBoundingClientRect();
			suggestionsContainer.style.top = `${rect.top + rect.height}px`;
			suggestionsContainer.style.left = `${rect.left}px`;
			suggestionsContainer.innerHTML = '';

			matches.forEach( function( match ) {
				var option = document.createElement( 'div' );
				option.innerText = match;
				option.classList.add( 'container__suggestion' );
				option.addEventListener( 'click', function() {
					replaceCurrentWord( this.innerText );
					suggestionsContainer.style.display = 'none';
				} );
				suggestionsContainer.appendChild( option );
			} );
			suggestionsContainer.style.display = 'block';
		} );

		// Enable keys to navigate through list of suggestions and select one
		textarea.addEventListener( 'keydown', function( e ) {
			var suggestions, numSuggestions;

			if ( ! [ 'ArrowDown', 'ArrowUp', 'Enter', 'Escape' ].includes( e.key ) ) {
				return;
			}

			suggestions = suggestionsContainer.querySelectorAll( '.container__suggestion' );
			numSuggestions = suggestions.length;
			if ( numSuggestions === 0 || suggestionsContainer.style.display === 'none' ) {
				return;
			}

			e.preventDefault();

			switch( e.key ) {
				case 'ArrowDown':
					suggestions[
						clamp( 0, currentSuggestionIndex, numSuggestions - 1 )
					].classList.remove( 'container__suggestion--focused' );
					currentSuggestionIndex = clamp( 0, currentSuggestionIndex + 1, numSuggestions - 1 );
					suggestions[ currentSuggestionIndex ].classList.add( 'container__suggestion--focused' );
					break;
				case 'ArrowUp':
					suggestions[
						clamp( 0, currentSuggestionIndex, numSuggestions - 1 )
					].classList.remove( 'container__suggestion--focused' );
					currentSuggestionIndex = clamp( 0, currentSuggestionIndex - 1, numSuggestions - 1 );
					suggestions[ currentSuggestionIndex ].classList.add( 'container__suggestion--focused' );
					break;
				case 'Enter':
					e.stopPropagation(); // prevent closing of Bulk or Quick Edit when Enter key pressed
					replaceCurrentWord( suggestions[ currentSuggestionIndex ].innerText );
					suggestionsContainer.style.display = 'none';
					break;
				case 'Escape':
					suggestionsContainer.style.display = 'none';
					break;
				default:
					break;
			}
		} );

		function findIndexOfCurrentWord() {
			// Get current value and cursor position
			var startIndex,
				currentValue = textarea.value,
				cursorPos = textarea.selectionStart;

			// Iterate backwards through characters until we find a space or newline character
			startIndex = cursorPos - 1;
			while ( startIndex >= 0 && !/\s/.test( currentValue[ startIndex ] ) ) {
				startIndex--;
			}
			return startIndex;
		}

		// Replace current word with selected suggestion
		function replaceCurrentWord( newWord ) {
			var currentValue = textarea.value,
				cursorPos = textarea.selectionStart,
				startIndex = findIndexOfCurrentWord(),
				newValue = currentValue.substring( 0, startIndex + 1 ) + newWord + currentValue.substring( cursorPos );

			textarea.value = newValue;
			textarea.focus();
			textarea.selectionStart = textarea.selectionEnd = startIndex + 1 + newWord.length;
		}

		function clamp( min, value, max ) {
			return Math.min( Math.max( min, value ), max );
		}
	}

	/**
	* Creates a dialog containing posts that can have a particular media attached
	* to it.
	*
	* @since CP-2.2.0
	*
	* @namespace findPosts
	*/
	window.findPosts = {
		/**
		 * Opens a dialog to attach media to a post.
		 *
		 * Adds an overlay prior to retrieving a list of posts to attach the media to.
		 *
		 * @since 2.7.0
		 *
		 * @memberOf findPosts
		 *
		 * @param {string} af_name The name of the affected element.
		 * @param {string} af_val The value of the affected post element.
		 *
		 * @return {boolean} Always returns false.
		 */
		open: function( af_name, af_val ) {
			var overlay = document.querySelector( '.ui-find-overlay' );

			if ( overlay == null ) {
				overlay = document.createElement( 'div' );
				overlay.className = 'ui-find-overlay';
				document.body.append( overlay );
				findPosts.overlay();
			}

			overlay.style.display = '';

			if ( af_name && af_val ) {
				// #affected is a hidden input field in the dialog that keeps track of which media should be attached.
				document.getElementById( 'affected' ).setAttribute( 'name', af_name );
				document.getElementById( 'affected' ).value = af_val;
			}

			document.getElementById( 'find-posts' ).style.display = '';

			// Close the dialog when the escape key is pressed.
			document.getElementById( 'find-posts-input' ).addEventListener( 'keyup', function( event ) {
				this.focus();
				if ( event.key === 'Escape' ) {
					findPosts.close();
				}
			} );

			// Retrieves a list of applicable posts for media attachment and shows them.
			findPosts.send();

			return false;
		},

		/**
		 * Clears the found posts lists before hiding the attach media dialog.
		 *
		 * @since 2.7.0
		 *
		 * @memberOf findPosts
		 *
		 * @return {void}
		 */
		close: function() {
			document.getElementById( 'find-posts-response' ).innerHTML = '';
			document.getElementById( 'find-posts' ).style.display = 'none';
			document.querySelector( '.ui-find-overlay' ).style.display = 'none';
		},

		/**
		 * Binds a click event listener to the overlay which closes the attach media
		 * dialog.
		 *
		 * @since 3.5.0
		 *
		 * @memberOf findPosts
		 *
		 * @return {void}
		 */
		overlay: function() {
			document.querySelector( '.ui-find-overlay' ).addEventListener( 'click', function () {
				findPosts.close();
			} );
		},

		/**
		 * Retrieves and displays posts based on the search term.
		 *
		 * Sends a post request to the admin_ajax.php, requesting posts based on the
		 * search term provided by the user. Defaults to all posts if no search term is
		 * provided.
		 *
		 * @since 2.7.0
		 *
		 * @memberOf findPosts
		 *
		 * @return {void}
		 */
		send: function() {
			var post = {
					ps: document.getElementById( 'find-posts-input' ).value,
					action: 'find_posts',
					_ajax_nonce: document.getElementById( '_ajax_nonce' ).value
				},
				spinner = document.querySelector( '.find-box-search .spinner' );

			spinner.classList.add( 'is-active' );

			/**
			 * Send a POST request to admin_ajax.php, hide the spinner and replace the list
			 * of posts with the response data. If an error occurs, display it.
			 */
			fetch( ajaxurl, {
				method: 'POST',
				body: new URLSearchParams( post ),
				credentials: 'same-origin'
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.json(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function( x ) {
				spinner.classList.remove( 'is-active' );
				document.getElementById( 'find-posts-response' ).innerHTML = x.data;
			} )
			.catch( function() {
				spinner.classList.remove( 'is-active' );
				document.getElementById( 'find-posts-response' ).textContent = wp.i18n.__( 'An error has occurred. Please reload the page and try again.' );
			} );
		}
	};

	/**
	 * Saves the changes made in the quick edit window to the post.
	 * Ajax saving is only for Quick Edit and not for bulk edit.
	 *
	 * @since CP-2.2.0
	 *
	 * @param {number} id The ID for the attachment that has been changed.
	 * @return {boolean} False, so the form does not submit when pressing
	 *                   Enter on a focused field.
	 */
	function saveAttachments( quickEdit, id ) {
		var params, inputs;

		if ( typeof( id ) === 'object' ) {
			id = this.getId( id );
		}

		params = new URLSearchParams( {
			action: 'quick-edit-attachment',
			post_type: 'attachment',
			id: id,
			edit_date: 'true'
		} );

		inputs = quickEdit.querySelectorAll( 'input:not([readonly=""]), select, textarea' );
		inputs.forEach( function( input ) {
			if ( input.name === 'media_category[]' ) {
				if ( input.checked ) {
					params.append( input.name, input.value );
				}
			} else {
				params.append( input.name, input.value );
			}
		} );

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
		.then( function( success ) {
			document.getElementById( 'post-' + id ).innerHTML = success.data;
			hideColumns( 'post-' + id );
			wp.a11y.speak( wp.i18n.__( 'Changes saved.' ) );
		} )
		.catch( function() {
			var errorNotice = quickEdit.querySelector( '.inline-edit-save .notice-error' ),
				error = errorNotice.querySelector( '.error' );

			errorNotice.classList.remove( 'hidden' );
			error.textContent = wp.i18n.__( 'Error while saving the changes.' );
			wp.a11y.speak( wp.i18n.__( 'Error while saving the changes.' ) );
		} );
	}

	/**
	 * Ensures that columns in a Quick Edit response are hidden
	 * if the relevant column is checked in Screen Options.
	 *
	 * @since CP-2.4.0
	 */
	function hideColumns( postID ) {
		document.querySelectorAll( '.hide-column-tog' ).forEach( function( hide ) {
			if ( hide.checked === false ) {
				document.getElementById( postID ).querySelector( '.' + hide.value ).classList.add( 'hidden' );
			}
		} );
	}

	/**
	 * @since CP-2.5.0
	 */
	function quickEditUpdate( quickEdit, tr ) {
		var inputs = document.querySelector( '.inline-edit-wrapper' ).querySelectorAll( 'input[pattern]' ),
			allValid = true;

		for ( var i = 0, n = inputs.length; i < n; i++ ) {
			if ( ! inputs[i].checkValidity() ) {
				// Show the first invalid field message
				inputs[i].reportValidity();

				// Mark as invalid
				allValid = false;

				// Stop after first invalid field
				break;
			}
		}

		if ( allValid ) {
			var id = tr.id.replace( 'post-', '' );
			saveAttachments( quickEdit, id );
			document.getElementById( 'bulk-action-selector-top' ).value = '-1';
			document.getElementById( 'bulk-action-selector-bottom' ).value = '-1';

			// Allow time for element to be updated.
			setTimeout( function() {
				tr.style.display = '';
				quickEdit.style.display = 'none';
				document.body.append( quickEdit );
			}, 100 );

			// Remove click event listener to prevent multiple AJAX submissions
			if ( quickEditEventHandler !== null ) {
				quickEditUpdateButton.removeEventListener( 'click', quickEditEventHandler );
				quickEditEventHandler = null;
			}
		}
	}

	// Open modal
	function openModalDialog( item ) {
		var id = item.dataset.id,
			title = item.getAttribute( 'aria-label' ),
			date = item.dataset.date,
			filename = item.dataset.filename,
			filetype = item.dataset.filetype,
			mime = item.dataset.mime,
			size = item.dataset.size,
			width = item.dataset.width,
			height = item.dataset.height,
			caption = item.dataset.caption,
			description = item.dataset.description,
			taxes = item.dataset.taxes,
			tags = item.dataset.tags,
			url = item.dataset.url,
			alt = item.querySelector( 'img' ).getAttribute( 'alt' ),
			link = item.dataset.link,
			author = item.dataset.author,
			authorLink = item.dataset.authorLink,
			orientation = item.dataset.orientation ? ' ' + item.dataset.orientation : '',
			menuOrder = item.dataset.menuOrder,
			updateNonce = item.dataset.updateNonce,
			deleteNonce = item.dataset.deleteNonce,
			prev = item.previousElementSibling ? item.previousElementSibling.id : '',
			next = item.nextElementSibling ? item.nextElementSibling.id : '',
			order = item.dataset.order,
			total = parseInt( document.querySelector( '.displaying-num' ).textContent );

			if ( mediaGrid == null ) {
				prev = item.closest( 'tr' ).previousElementSibling ? item.closest( 'tr' ).previousElementSibling.querySelector( '.media-item' ).id : '';
				next = item.closest( 'tr' ).nextElementSibling ? item.closest( 'tr' ).nextElementSibling.querySelector( '.media-item' ).id : '';
			}

		// Modify current URL
		queryParams.delete( 'deleted' );
		queryParams.set( 'item', id );
		history.replaceState( null, null, '?' + queryParams.toString() );

		// Set menu_order, media_category, and media_post_tag field IDs correctly
		setAddedMediaFields( id );

		// Populate modal with attachment details
		dialog.querySelector( '.attachment-date' ).textContent = date;
		dialog.querySelector( '.uploaded-by' ).children[1].textContent = author;
		dialog.querySelector( '.uploaded-by' ).children[1].href = authorLink;
		dialog.querySelector( '.attachment-filename' ).textContent = filename;
		dialog.querySelector( '.attachment-filetype' ).textContent = mime;
		dialog.querySelector( '.attachment-filesize' ).textContent = size;
		dialog.querySelector( '.attachment-dimensions' ).textContent = width + ' ' + _wpMediaLibSettings.by + ' ' + height + ' ' + _wpMediaLibSettings.pixels;
		dialog.querySelector( '.attachment-media-view' ).className = 'attachment-media-view' + orientation;

		dialog.querySelector( '#attachment-details-two-column-alt-text' ).value = alt;
		dialog.querySelector( '#attachment-details-two-column-title' ).value = title;
		dialog.querySelector( '#attachment-details-two-column-caption' ).value = caption;
		dialog.querySelector( '#attachment-details-two-column-description' ).value = description;
		dialog.querySelector( '#attachment-details-two-column-copy-link' ).value = url;

		dialog.querySelector( '#menu-order' ).value = menuOrder;
		dialog.querySelector( '#attachments-' + id + '-media_category' ).value = taxes;
		dialog.querySelector( '#attachments-' + id + '-media_post_tag' ).value = tags;

		if ( filetype === 'audio' ) {
			dialog.querySelector( '#media-image' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-video' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-audio' ).removeAttribute( 'hidden' );
			dialog.querySelector( 'audio.wp-audio-shortcode' ).src = url;
			dialog.querySelector( 'audio' ).setAttribute( 'type', mime );
		} else if ( filetype === 'video' ) {
			dialog.querySelector( '.wp-video' ).removeAttribute( 'style' );
			dialog.querySelector( '#media-image' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-audio' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-video' ).removeAttribute( 'hidden' );
			dialog.querySelector( 'video.wp-video-shortcode' ).src = url;
			dialog.querySelector( 'video' ).setAttribute( 'type', mime );
		} else {
			dialog.querySelector( '#media-audio' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-video' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-image' ).removeAttribute( 'hidden' );

			if ( filetype === 'image' ) {
				dialog.querySelector( '.thumbnail-image img' ).src = url;
				dialog.querySelector( '.edit-attachment' ).style.display = '';

				if (
					( mime === 'image/webp' && ! _wpMediaLibSettings.webp_editable ) ||
					( mime === 'image/avif' && ! _wpMediaLibSettings.avif_editable ) ||
					( mime === 'image/heic' && ! _wpMediaLibSettings.heic_editable )
				) {
					dialog.querySelector( '.edit-attachment' ).style.display = 'none';
				}
			} else {
				dialog.querySelector( '.thumbnail-image img' ).src = item.querySelector( 'img' ).src;
				dialog.querySelector( '.edit-attachment' ).style.display = 'none';
			}
		}
		dialog.querySelector( '.thumbnail-image img' ).setAttribute( 'alt', alt );

		dialog.querySelector( '#view-attachment').href = link;
		dialog.querySelector( '#edit-more' ).href = ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + id + '&action=edit' );
		dialog.querySelector( '#download-file' ).href = url;

		/*
		 * Existence of nonce means that user capability has already been checked and verified.
		 *
		 * @see wp_prepare_attachment_for_js()
		 */
		if ( updateNonce ) {
			dialog.querySelector( '#attachment-details-two-column-alt-text' ).removeAttribute( 'readonly' );
			dialog.querySelector( '#attachment-details-two-column-title' ).removeAttribute( 'readonly' );
			dialog.querySelector( '#attachment-details-two-column-caption' ).removeAttribute( 'readonly' );
			dialog.querySelector( '#attachment-details-two-column-description' ).removeAttribute( 'readonly' );
			dialog.querySelector( '#attachments-' + id + '-media_category' ).removeAttribute( 'readonly' );
			dialog.querySelector( '#attachments-' + id + '-media_post_tag' ).removeAttribute( 'readonly' );
			dialog.querySelector( '.edit-attachment' ).style.display = '';
		} else {
			dialog.querySelector( '#attachment-details-two-column-alt-text' ).setAttribute( 'readonly', true );
			dialog.querySelector( '#attachment-details-two-column-title' ).setAttribute( 'readonly', true );
			dialog.querySelector( '#attachment-details-two-column-caption' ).setAttribute( 'readonly', true );
			dialog.querySelector( '#attachment-details-two-column-description' ).setAttribute( 'readonly', true );
			dialog.querySelector( '#attachments-' + id + '-media_category' ).setAttribute( 'readonly', true );
			dialog.querySelector( '#attachments-' + id + '-media_post_tag' ).setAttribute( 'readonly', true );
			dialog.querySelector( '.edit-attachment' ).style.display = 'none';
		}

		/*
		 * Existence of nonce means that user capability has already been checked and verified.
		 *
		 * @see wp_prepare_attachment_for_js()
		 */
		if ( deleteNonce ) {
			dialog.querySelector( '.delete-attachment' ).style.display = '';
			dialog.querySelectorAll( '.links-separator' )[2].style.display = '';
		} else {
			dialog.querySelector( '.delete-attachment' ).style.display = 'none';
			dialog.querySelectorAll( '.links-separator' )[2].style.display = 'none';
		}

		leftIcon.setAttribute( 'data-prev', prev );
		rightIcon.setAttribute( 'data-next', next );

		if ( prev === '' ) {
			leftIcon.setAttribute( 'aria-disabled', true );
			leftIconMobile.setAttribute( 'aria-disabled', true );
		} else {
			leftIcon.setAttribute( 'aria-disabled', false );
			leftIconMobile.setAttribute( 'aria-disabled', false );
		}

		if ( next === '' ) {
			rightIcon.setAttribute( 'aria-disabled', true );
			rightIconMobile.setAttribute( 'aria-disabled', true );
		} else {
			rightIcon.setAttribute( 'aria-disabled', false );
			rightIconMobile.setAttribute( 'aria-disabled', false );
		}

		// Set order of media item and total
		dialog.querySelector( '#current-media-item' ).textContent = order;
		dialog.querySelector( '#total-media-items' ).textContent = total;
		dialog.querySelector( '#current-media-item-mobile' ).textContent = order;
		dialog.querySelector( '#total-media-items-mobile' ).textContent = total;

		toggleMediaNavigation();

		// Show modal
		dialog.classList.add( 'modal-loading' );

		document.body.style.overflow = 'hidden';

		// Fix wrong image flash
		setTimeout( function() {
			dialog.showModal();
		}, 1 );

		// Update media categories and tags
		dialog.querySelectorAll( '.compat-item input' ).forEach( function( input ) {
			input.addEventListener( 'blur', function() {
				if ( updateNonce ) {
					updateMediaTaxOrTag( input, id );
					updateMediaRow( item );
				}
			} );
		} );
	}

	/* Select and unselect media items for deletion */
	function selectItemForDeletion( item ) {
		var deleteButton = document.querySelector( '.delete-selected-button' );
		if ( item.className.includes( 'selected' ) ) {
			item.classList.remove( 'selected' );
			item.setAttribute( 'aria-checked', false );

			// Disable delete button if no media items are selected
			if ( document.querySelector( '.media-item.selected' ) == null ) {
				deleteButton.setAttribute( 'disabled', true );
			}
		} else {
			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );

			// Enable delete button
			if ( deleteButton.disabled ) {
				deleteButton.removeAttribute( 'disabled' );
			}
		}
	}

	/* Populate media items within grid */
	function populateGridItem( attachment ) {
		var gridItem = document.createElement( 'li' ),
			image = '<img src="' + attachment.url + '" alt="' + attachment.alt + '">';

		if ( attachment.type === 'application' ) {
			if ( attachment.subtype === 'vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) {
				image = '<div class="icon"><div class="centered"><img src="' + _wpMediaLibSettings.includes_url + 'images/media/spreadsheet.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			} else if ( attachment.subtype === 'zip' ) {
				image = '<div class="icon"><div class="centered"><img src="' + _wpMediaLibSettings.includes_url + 'images/media/archive.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			} else {
				image = '<div class="icon"><div class="centered"><img src="' + _wpMediaLibSettings.includes_url + 'images/media/document.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			}
		} else if ( attachment.type === 'audio' ) {
			image = '<div class="icon"><div class="centered"><img src="' + _wpMediaLibSettings.includes_url + 'images/media/audio.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
		} else if ( attachment.type === 'video' ) {
			image = '<div class="icon"><div class="centered"><img src="' + _wpMediaLibSettings.includes_url + 'images/media/video.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
		}

		gridItem.className = 'media-item';
		gridItem.id = 'media-' + attachment.id;
		gridItem.setAttribute( 'tabindex', 0 );
		gridItem.setAttribute( 'role', 'checkbox' );
		gridItem.setAttribute( 'aria-checked', 'false' );
		gridItem.setAttribute( 'aria-label', attachment.title );
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
		gridItem.setAttribute( 'data-update-nonce', attachment.nonces.update );
		gridItem.setAttribute( 'data-delete-nonce', attachment.nonces.delete );
		gridItem.setAttribute( 'data-edit-nonce', attachment.nonces.edit );

		gridItem.innerHTML = '<div class="select-attachment-preview type-' + attachment.type + ' subtype-' + attachment.subtype + '">' +
			'<div class="media-thumbnail">' + image + '</div>' +
			'</div>' +
			'<button type="button" class="check" tabindex="-1">' +
			'<span class="media-modal-icon"></span>' +
			'<span class="screen-reader-text">' + _wpMediaLibSettings.deselect + '></span>' +
			'</button>';

		return gridItem;
	}

	/* Update items displayed according to dropdown selections */
	function updateGrid() {
		var date  = dateFilter.value,
			type  = typeFilter.value,
			count = document.querySelector( '.load-more-count' ).textContent;

		// Create URLSearchParams object
		const params = new URLSearchParams( {
			'action': 'query-attachments',
			'query[posts_per_page]': document.getElementById( 'media_grid_per_page' ).value,
			'query[monthnum]': date ? parseInt( date.substr( 4, 2 ), 10 ) : 0,
			'query[year]': date ? parseInt( date.substr( 0, 4 ), 10 ) : 0,
			'query[post_mime_type]': type || '',
			'query[s]': search ? search.value : '',
			'query[paged]': paged ? paged : '1',
			'query[media_category_name]': mediaCatSelect ? mediaCatSelect.value : '',
			'_ajax_nonce': document.getElementById( 'media_grid_nonce' ).value
		} );

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

				// Clear existing grid
				mediaGrid.innerHTML = '';

				if ( result.data.length === 0 ) {

					// Reset pagination
					document.querySelectorAll( '.pagination-links a' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'href', pageLink.href.replace( pageLink.href.split( '?paged=' )[1], 1 ) );
						pageLink.setAttribute( 'disabled', true );
						pageLink.setAttribute( 'inert', true );
					} );

					document.getElementById( 'current-page-selector' ).setAttribute( 'value', 1 );
					document.querySelector( '.total-pages' ).textContent = 1;
					document.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					document.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					document.querySelector( '.no-media' ).removeAttribute( 'hidden' );

					queryParams.set( 'paged', 1 );
					history.replaceState( null, null, '?' + queryParams.toString() );
				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment );
						mediaGrid.appendChild( gridItem );
					} );

					// Reset pagination
					document.querySelectorAll( '.pagination-links a' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'first-page' ) || pageLink.className.includes( 'prev-page' ) ) {
							if ( paged === '1' ) {
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
								if ( pageLink.className.includes( 'prev-page' ) ) {
									pageLink.setAttribute( 'href', pageLink.href.replace( pageLink.href.split( '?paged=' )[1], parseInt( paged ) - 1 ) );
								}
							}
						} else if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'href', pageLink.href.replace( pageLink.href.split( '?paged=' )[1], paged ) );
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.setAttribute( 'href', pageLink.href.replace( pageLink.href.split( '?paged=' )[1], parseInt( paged ) + 1 ) );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'href', pageLink.href.replace( pageLink.href.split( '?paged=' )[1], result.headers.max_pages ) );
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}

						// Update both HTML and DOM
						document.getElementById( 'current-page-selector' ).setAttribute( 'value', paged ? paged : 1 );
						document.getElementById( 'current-page-selector' ).value = paged ? paged : 1;
						document.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
						document.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

						queryParams.set( 'paged', paged );
						history.replaceState( null, null, '?' + queryParams.toString() );
					} );

					// Open modal to show details about file, or select files for deletion
					document.querySelectorAll( '.media-item' ).forEach( function( item ) {
						item.onclick = function() {
							if ( document.querySelector( '.media-toolbar-mode-select' ) == null ) {
								openModalDialog( item );
							} else {
								selectItemForDeletion( item );
							}
						};
					} );

					// Update the count at the bottom of the page
					document.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					document.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					document.querySelector( '.load-more-count' ).textContent = count.replace( /[0-9]+/g, result.headers.total_posts ).replace( /[0-9]+/, result.data.length );
				}

				// Reset paged variable
				paged = '1';
			} else {
				console.error( _wpMediaLibSettings.failed_update, result.data.message );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaLibSettings.error, error );
		} );
	}

	function removeImageEditWrap() {
		if ( document.querySelector( '.imgedit-wrap' ) != null ) {
			document.querySelector( '.imgedit-wrap' ).remove();
			document.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );
			document.querySelector( '.attachment-details' ).removeAttribute( 'inert' );
		}
	}

	// Update details within modal
	function setAddedMediaFields( id ) {
		var form = document.createElement( 'form' );
		form.className = 'compat-item';
		form.innerHTML = '<input type="hidden" id="menu-order" name="attachments[' + id + '][menu_order]" value="0">' +
			'<p class="media-types media-types-required-info"><span class="required-field-message">Required fields are marked <span class="required">*</span></span></p>' +
			'<span class="setting" data-setting="media_category">' +
				'<label for="attachments-' + id + '-media_category">' +
					'<span class="alignleft">Media Categories</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_category" name="attachments[' + id + '][media_category]" value="">' +
			'</span>' +
			'<span class="setting" data-setting="media_post_tag">' +
				'<label for="attachments-' + id + '-media_post_tag">' +
					'<span class="alignleft">Media Tags</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_post_tag" name="attachments[' + id + '][media_post_tag]" value="">' +
			'</span>';

		if ( document.querySelector( '.compat-item' ) != null ) {
			document.querySelector( '.compat-item' ).remove();
		}
		document.querySelector( '.attachment-compat' ).append( form );
	}

	// Update attachment details
	function updateDetails( input, id ) {
		var mediaItem = document.getElementById( 'media-' + id );
		if ( ! mediaItem ) {
			return;
		}
		var successTimeout,
			data = new FormData();

		data.append( 'action', 'save-attachment' );
		data.append( 'id', id );
		data.append( 'nonce', mediaItem.dataset.updateNonce );

		// Append metadata fields
		if ( input.parentNode.dataset.setting === 'alt' ) {
			data.append( 'changes[alt]', input.value );
		} else if ( input.parentNode.dataset.setting === 'title' ) {
			data.append( 'changes[title]', input.value );
		} else if ( input.parentNode.dataset.setting === 'caption' ) {
			data.append( 'changes[caption]', input.value );
		} else if ( input.parentNode.dataset.setting === 'description' ) {
			data.append( 'changes[description]', input.value );
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
			if ( result.success ) {

				// Update data attributes
				if ( input.parentNode.dataset.setting === 'alt' ) {
					mediaItem.querySelector( 'img' ).setAttribute( 'alt', input.value );
				} else if ( input.parentNode.dataset.setting === 'title' ) {
					mediaItem.setAttribute( 'aria-label', input.value );
				} else if ( input.parentNode.dataset.setting === 'caption' ) {
					mediaItem.setAttribute( 'data-caption', input.value );
				} else if ( input.parentNode.dataset.setting === 'description' ) {
					mediaItem.setAttribute( 'data-description', input.value );
				}

				// Show success visual feedback.
				clearTimeout( successTimeout );
				document.getElementById( 'details-saved' ).classList.remove( 'hidden' );
				document.getElementById( 'details-saved' ).setAttribute( 'aria-hidden', 'false' );

				// Hide success visual feedback after 3 seconds.
				successTimeout = setTimeout( function() {
					document.getElementById( 'details-saved' ).classList.add( 'hidden' );
					document.getElementById( 'details-saved' ).setAttribute( 'aria-hidden', 'true' );
				}, 3000 );
			} else {
				console.error( _wpMediaLibSettings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaLibSettings.error, error );
		} );
	}

	// Update media categories and tags
	function updateMediaTaxOrTag( input, id ) {
		var mediaItem = document.getElementById( 'media-' + id );
		if ( ! mediaItem ) {
			return;
		}
		var successTimeout, newTaxes,
			data = new FormData(),
			taxonomy = input.getAttribute( 'name' ).replace( 'attachments[' + id + '][' , '' ).replace( ']', '' );

		data.append( 'action', 'save-attachment-compat' );
		data.append( 'nonce', mediaItem.dataset.updateNonce );
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
					mediaItem.setAttribute( 'data-taxes', newTaxes );
				} else if ( taxonomy === 'media_post_tag' ) {
					newTaxes = result.data.media_tags.join( ', ' );
					input.value = newTaxes;
					mediaItem.setAttribute( 'data-tags', newTaxes );
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
				console.error( _wpMediaLibSettings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaLibSettings.error, error );
		} );
	}

	// Delete attachment from within modal
	function deleteItem( id ) {
		var mediaItem = document.getElementById( 'media-' + id );
		if ( ! mediaItem ) {
			return;
		}
		var data = new URLSearchParams( {
			action: 'delete-post',
			_ajax_nonce: mediaItem.dataset.deleteNonce,
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
			if ( result === 1 ) { // success
				if ( mediaItem.previousElementSibling != null ) {
					focusID = mediaItem.previousElementSibling.id;
				} else if ( mediaItem.nextElementSibling != null ) {
					focusID = mediaItem.nextElementSibling.id;
				} else {
					focusID = addNew.id;
				}
				mediaItem.remove();
				closeButton.click();
				resetDataOrdering();
				if ( mediaGrid == null ) {
					// Modify current URL
					queryParams.set( 'deleted', '1' );
					history.replaceState( null, null, '?' + queryParams.toString() );
					location.href = location.href;
				}
			} else {
				console.log( _wpMediaLibSettings.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaLibSettings.error, error );
		} );
	}

	// Update media row
	function updateMediaRow( item ) {
		var id = item.id.replace( 'media-', '' ),
			row = item.closest('tr');

		if ( ! row ) {
			return;
		}

		var data = new FormData();
		data.append( 'action', 'get-attachment-html' );
		data.append( 'id', id );
		data.append( 'nonce', item.dataset.updateNonce );

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
				row.innerHTML = result.data;
				hideColumns( 'post-' + id );
			} else {
				console.error( _wpMediaLibSettings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaLibSettings.error, error );
		} );
	}

	// Set or reset ordering of media items
	function resetDataOrdering() {
		var items = document.querySelectorAll( '.media-item' );
		items.forEach( function( item, index ) {
			item.setAttribute( 'data-order', parseInt( index + 1 ) );
		} );

		if ( mediaGrid != null ) {
			var num = document.querySelector( '.displaying-num' ).textContent.split( ' ' ),
				count = document.querySelector( '.load-more-count' ).textContent.split( ' ' ),
				count5;

			// Reset totals
			if ( 5 in count ) { // allow for different languages
				count5 = ' ' + count[5];
			} else {
				count5 = '';
			}
			document.querySelector( '.load-more-count' ).textContent = count[0] + ' ' + items.length + ' ' + count[2] + ' ' + items.length + ' ' + count[4] + count5;

			document.querySelector( '.displaying-num' ).textContent = items.length + ' ' + num[1];
			dialog.querySelector( '#total-media-items' ).textContent = items.length;
			dialog.querySelector( '#total-media-items-mobile' ).textContent = items.length;
		}
	}

	// Close modal by clicking button
	function closeModalDialog() {
		queryParams.delete( 'item' );
		queryParams.delete( 'mode' );
		queryParams.delete( 'deleted' );
		history.replaceState( null, null, location.href.split('?')[0] ); // reset URL params
		dialog.classList.remove( 'modal-loading' );
		document.body.style.overflow = '';
		dialog.close();
		if ( focusID != null && document.getElementById( focusID ) ) { // set focus correctly
			document.getElementById( focusID ).focus();
			focusID = null; // reset focusID
		}
		removeImageEditWrap();
	}

	function prevModalDialog() {
		var id = leftIcon.dataset.prev;
		if ( id ) {
			focusID = id; // set focusID for when modal is closed
			document.getElementById( id ).click();
			mediaNavigation.style.display === '' ? leftIcon.focus() : leftIconMobile.focus();
		}
		removeImageEditWrap();
	}

	function nextModalDialog() {
		var id = rightIcon.dataset.next;
		if ( id ) {
			focusID = id; // set focusID for when modal is closed
			document.getElementById( id ).click();
			mediaNavigation.style.display === '' ? rightIcon.focus() : rightIconMobile.focus();
		}
		removeImageEditWrap();
	}

	// Handle keyboard navigation
	function keydownHandler( e ) {
		if ( dialog.open ) {
			if ( e.key === 'ArrowLeft' ) {
				e.preventDefault();
				prevModalDialog();
			} else if ( e.key === 'ArrowRight' ) {
				e.preventDefault();
				nextModalDialog();
			}
		}
	}

	// Handle touch navigation (touchstart event)
	function touchStartHandler( e ) {
		startTouchPosition = e.touches[0].clientX;
	}
	// Handle touch navigation (touchend event)
	// The swipe is considered valid if the horizontal distance moved (difference between startTouchPosition and endTouchPosition) is more than 50 pixels. This threshold prevents accidental small touches from triggering a swipe.
	function touchEndHandler( e ) {
		endTouchPosition = e.changedTouches[0].clientX;

		// Determine swipe direction
		if ( endTouchPosition - startTouchPosition > 50 ) {
			// Swipe left (next media)
			prevModalDialog();
		} else if ( startTouchPosition - endTouchPosition > 50 ) {
			// Swipe right (previous media)
			nextModalDialog();
		}
	}

	// Toggle media button navigation wrappers according to viewport width
	function toggleMediaNavigation() {
		if ( window.innerWidth > 480 ) {
			mediaNavigation.style.display = '';
			mediaNavigationMobile.style.display = 'none';
		} else {
			mediaNavigation.style.display = 'none';
			mediaNavigationMobile.style.display = '';
		}
	}

	// List view
	if ( mediaGrid == null ) {
		// Prevents the attach form submission if no post has been selected.
		document.getElementById( 'find-posts-submit' ).addEventListener( 'click', function( event ) {
			var flag = false,
				radios = document.querySelectorAll( '#find-posts-response input[type="radio"]' );

			for ( var i = 0, n = radios.length; i < n; i++ ) {
				if ( radios[i].checked ) {
					flag = true;
					break;
				}
			}

			if ( flag === false ) {
				event.preventDefault();
			}
		}, false );

		// Submits the attach form search query when hitting the enter key in the search input.
		document.getElementById( 'find-posts-input' ).addEventListener( 'keydown', function( event ) {
			if ( 'Enter' == event.key ) {
				event.preventDefault();
				findPosts.send();
			}
		}, false );

		// Binds the click event to the attach form search button.
		document.getElementById( 'find-posts-search' ).addEventListener( 'click', findPosts.send );

		// Binds the attach form close dialog click event.
		document.getElementById( 'find-posts-close' ).addEventListener( 'click', findPosts.close );

		// Binds the bulk action events to the submit buttons.
		var action = document.getElementById( 'doaction' );
		if ( action ) {
			action.addEventListener( 'click', function( event ) {
				var tr, checkboxes, delButtons, dateSplit, author, authorsList, cats,
					catsArray, categoriesList, mediaTags, hiddenTr, cancel, inputs,
					te = '',
					quickEdit = document.getElementById( 'quick-edit' ),
					bulkEdit = document.getElementById( 'bulk-edit' ),
					optionValue = document.querySelector( 'select[name="action"]' ).value,
					number = 0,
					count = 0,
					columns = [ ...document.querySelector( '.widefat thead tr' ).children ];

				// Set default state in case a Bulk or Quick Edit has already been opened.
				bulkEdit.style.display = 'none';
				document.body.append( bulkEdit );
				quickEdit.style.display = 'none';
				document.body.append( quickEdit );
				quickEdit.querySelector( '.inline-edit-save .notice-error' ).classList.add( 'hidden' );

				/**
				 * Handle the bulk action based on its value.
				 */
				// If Bulk Actions is selected, reload the page without any query args.
				if ( '-1' === optionValue ) {
					event.preventDefault();
					location.href = location.pathname;

				// Otherwise apply the appropriate values.
				} else if ( 'attach' === optionValue ) {
					event.preventDefault();
					findPosts.open();
				} else if ( 'delete' === optionValue ) {
					if ( ! showNotice.warn() ) {
						event.preventDefault();
					}
				} else if ( 'edit' === optionValue ) {
					event.preventDefault();
					checkboxes = document.querySelectorAll( 'tbody th.check-column input[type="checkbox"]' );

					// Create hidden element for inserting above Bulk or Quick Edit to maintain striping.
					hiddenTr = document.createElement( 'tr' );
					hiddenTr.className = 'hidden';

					/**
					 * Create a HTML div with the title and a
					 * link(delete-icon) for each selected media item.
					 *
					 * Get the selected posts based on the checked
					 * checkboxes in the post table.
					 */
					checkboxes.forEach( function( checkbox ) {

						// If the checkbox for a post is selected, add the post to the edit list.
						if ( checkbox.checked ) {
							var id = checkbox.value,
								theTitle = document.getElementById( 'post-' + id ).querySelector( ' .filename' ).innerHTML || wp.i18n.__( '(no title)' ),
								buttonVisuallyHiddenText = wp.i18n.sprintf(
									/* translators: %s: Post title. */
									wp.i18n.__( 'Remove &#8220;%s&#8221; from Bulk Edit' ),
									theTitle
								);

							number++;
							if ( number === 1 ) {
								tr = document.getElementById( 'post-' + id );
							}

							te += '<li class="ntdelitem" name="attachment[]" value="' + id + '"><button type="button" id="_' + id + '" class="button-link ntdelbutton"><span class="screen-reader-text">' + buttonVisuallyHiddenText + '</span></button><span class="ntdeltitle" aria-hidden="true">' + theTitle + '</span></li>';
						}
					} );

					// Set the number of columns.
					columns.forEach( function( column ) {
						if ( ! column.className.includes( 'hidden' ) ) {
							count++;
						}
					} );
					bulkEdit.querySelector( 'td' ).setAttribute( 'colspan', count );
					quickEdit.querySelector( 'td' ).setAttribute( 'colspan', count );

					if ( number < 1 ) {

						// No checkboxes were checked, so hide the bulk and quick edit rows.
						bulkEdit.style.display = 'none';
						document.body.append( bulkEdit );
						quickEdit.style.display = 'none';
						document.body.append( quickEdit );

					} else if ( number === 1 ) {

						// Quick Edit: reset all fields except nonce and media_category[]
						inputs = quickEdit.querySelectorAll( 'input:not(#_inline_edit_attachment):not([name="media_category[]"]), select, textarea' );
						inputs.forEach( function( input ) {
							input.value = '';
						} );

						// Replace the row with the Quick Edit row.
						document.getElementById( 'the-list' ).prepend( hiddenTr );
						quickEdit.dataset.id = tr.id;
						tr.before( quickEdit );
						quickEdit.classList.add( 'inline-editor' );
						quickEdit.style.display = '';
						tr.style.display = 'none';

						// Scroll to Quick Edit.
						quickEdit.scrollIntoView( {
							behavior: 'smooth'
						} );

						// Check the box for the current author.
						author = tr.querySelector( '.column-author a' ).textContent;
						authorsList = quickEdit.querySelectorAll( '#quick-author option' );
						authorsList.forEach( function( item ) {
							if ( item.textContent === author ) {
								document.getElementById( 'quick-author' ).value = item.value;
								item.setAttribute( 'selected', 'selected' );
							}
						} );

						// Check the boxes for the appropriate media categories.
						cats = tr.querySelectorAll( '.column-taxonomy-media_category a' );
						catsArray = [];
						cats.forEach( function( cat ) {
							catsArray.push( cat.textContent );
						} );
						categoriesList = quickEdit.querySelectorAll( '.category-checklist li' );
						categoriesList.forEach( function( item ) {
							if ( catsArray.includes( item.querySelector( 'label' ).textContent ) ) {
								item.querySelector( 'input' ).checked = true;
							} else {
								item.querySelector( 'input' ).checked = false;
							}
						} );

						// Enable autocomplete for tags.
						mediaTags = tr.querySelector( '.column-taxonomy-media_post_tag a' ) ? tr.querySelector( '.column-taxonomy-media_post_tag' ).textContent : '';
						autoCompleteTextarea( quickEdit.querySelector( 'textarea' ) );

						// Split date into year, month, and day.
						dateSplit = tr.querySelector( '.column-date time' ).getAttribute( 'datetime' ).split( '/' );
						quickEdit.querySelector( '[name="mm"]' ).value = dateSplit[1];
						quickEdit.querySelector( '[name="jj"]' ).value = dateSplit[2];
						quickEdit.querySelector( '[name="aa"]' ).value = dateSplit[0];

						// Fill the other relevant boxes.
						quickEdit.querySelector( '[name="post_title"]' ).value = tr.querySelector( '.column-title strong' ).textContent.trim();
						quickEdit.querySelector( '[name="post_name"]' ).value = tr.querySelector( '.column-title .row-actions .copy-attachment-url' ).dataset.clipboardText;
						quickEdit.querySelector( '#quick-media-tags' ).value = mediaTags;
						quickEdit.querySelector( '[name="alt"]' ).value = tr.querySelector( '.column-alt' ).textContent;
						quickEdit.querySelector( '[name="post_excerpt"]' ).value = tr.querySelector( '.column-caption' ).textContent;
						quickEdit.querySelector( '[name="post_content"]' ).value = tr.querySelector( '.column-desc' ).textContent;

						hiddenTr.remove();

						// Update.
						if ( quickEditEventHandler === null ) {
							quickEditEventHandler = function() {
								quickEditUpdate( quickEdit, tr );
							};
							quickEditUpdateButton.addEventListener( 'click', quickEditEventHandler );
						}
					} else {
						document.querySelectorAll( 'tr' ).forEach( function( item ) {
							if ( item.style.display === 'none' ) {
								item.style.display = '';
							}
						} );

						// Bulk Edit: insert the editor at the top of the table.
						document.getElementById( 'the-list' ).prepend( bulkEdit );
						document.getElementById( 'the-list' ).prepend( hiddenTr );
						bulkEdit.classList.add( 'inline-editor' );
						bulkEdit.style.display = '';

						// Make sure any element hidden for Quick Edit is visible.
						tr.style.display = '';

						// Enable autocomplete for tags.
						autoCompleteTextarea( bulkEdit.querySelector( 'textarea' ) );

						// Populate the list of items to bulk edit.
						document.getElementById( 'bulk-titles' ).innerHTML = '<ul id="bulk-titles-list" role="list">' + te + '</ul>';

						// Scroll to Bulk Edit.
						bulkEdit.scrollIntoView( {
							behavior: 'smooth'
						} );

						/**
						 * Binds on click events to handle the list of items to bulk edit.
						 *
						 * @listens click
						 */
						delButtons = document.querySelectorAll( '#bulk-titles .ntdelbutton' );
						delButtons.forEach( function( delButton ) {
							delButton.addEventListener( 'click', function() {
								var id     = delButton.id,
									parent = delButton.parentNode,
									prev   = parent.previousElementSibling ? parent.previousElementSibling.querySelector( '.ntdelbutton' ) : null,
									next   = parent.nextElementSibling ? parent.nextElementSibling.querySelector( '.ntdelbutton' ) : null;

								document.querySelector( 'table.widefat input[value="' + id.replace( '_', '' ) + '"]' ).checked = false;
								document.getElementById( id ).parentNode.remove();
								wp.a11y.speak( wp.i18n.__( 'Item removed.' ), 'assertive' );

								// Move focus to a proper place when items are removed.
								if ( next !== null ) {
									next.focus();
								} else if ( prev !== null ) {
									prev.focus();
								} else {
									hiddenTr.remove();
									document.getElementById( 'bulk-titles-list' ).remove();
									bulkEdit.style.display = 'none';
									document.body.append( bulkEdit );
									wp.a11y.speak( wp.i18n.__( 'All selected items have been removed. Select new items to use Bulk Actions.' ) );
								}
							} );
						} );

						// Update
						document.getElementById( 'bulk-edit-update' ).addEventListener( 'click', function() {
							hiddenTr.remove();
							//bulkEdit.style.display = 'none';
							//document.body.append( bulkEdit );
						} );
					}

					// Set initial focus on the Quick/Bulk Edit region.
					document.querySelector( '.inline-edit-wrapper' ).setAttribute( 'tabindex', '-1' );
					document.querySelector( '.inline-edit-wrapper' ).focus();

					// Cancel button and Escape key.
					cancel = document.querySelector( '.inline-edit-save .cancel' );
					cancel.addEventListener( 'click', function() {
						if ( hiddenTr != null ) {
							hiddenTr.remove();
						}
						tr.style.display = '';
						document.getElementById( 'bulk-action-selector-top' ).value = '-1';
						document.getElementById( 'bulk-action-selector-bottom' ).value = '-1';
						bulkEdit.style.display = 'none';
						document.body.append( bulkEdit );
						quickEdit.style.display = 'none';
						document.body.append( quickEdit );

						// Remove click event listener to prevent multiple AJAX submissions
						if ( quickEditEventHandler !== null ) {
							quickEditUpdateButton.removeEventListener( 'click', quickEditEventHandler );
							quickEditEventHandler = null;
						}
					} );

					quickEdit.addEventListener( 'keydown', function( e ) {
						if ( e.key === 'Escape' ) {
							cancel.click();
						}
					} );

					bulkEdit.addEventListener( 'keydown', function( e ) {
						if ( e.key === 'Escape' ) {
							cancel.click();
						}
					} );
				}
			} );
		}

		/**
		 * Enables clicking on the entire table row.
		 *
		 * @return {void}
		 */
		document.querySelectorAll( '.find-box-inside tr' ).forEach( function( row ) {
			row.addEventListener( 'click', function() {
				row.querySelector( '.found-radio input' ).checked = true;
			} );
		} );

		/**
		 * Handles media list copy media URL button.
		 *
		 * Uses Clipboard API (with execCommand fallback for sites
		 * on neither https nor localhost).
		 *
		 * @since CP-2.2.0
		 *
		 * @param {MouseEvent} event A click event.
		 * @return {void}
		 */
		copyAttachmentURLs = document.querySelectorAll( '.copy-attachment-url.media-library' );
		copyAttachmentURLs.forEach( function( copyURL ) {
			copyURL.addEventListener( 'click', function() {
				var copyText = copyURL.getAttribute( 'data-clipboard-text' ),
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
				copyURL.nextElementSibling.classList.remove( 'hidden' );
				input.remove();

				// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
				copyAttachmentURLSuccessTimeout = setTimeout( function() {
					copyURL.nextElementSibling.classList.add( 'hidden' );
				}, 3000 );

				// Handle success audible feedback.
				wp.a11y.speak( wp.i18n.__( 'The file URL has been copied to your clipboard' ) );
			} );
		} );

		// Observe media list for changes
		var mediaList = document.querySelector( '.upload-php tbody#the-list' );
		var mediaListObserver = new MutationObserver( function( mutationsList ) {
			for ( let i = 0, n = mutationsList.length; i < n; i++ ) {
				const mutation = mutationsList[i];
				if ( mutation.removedNodes ) {
					for ( let i = 0, n = mutation.removedNodes.length; i < n; i++ ) {
						const node = mutation.removedNodes[i];
						if ( node.nodeName !== '#text' && node.id.startsWith( 'post' ) ) {
							// Reset media items ordering
							resetDataOrdering();
						}
					}
				}
				if ( mutation.addedNodes ) {
					for ( let i = 0, n = mutation.addedNodes.length; i < n; i++ ) {
						const node = mutation.addedNodes[i];
						if ( node.nodeName !== '#text' ) {
							// Reset media items ordering
							const postRow = node.id.startsWith( 'post' );
							const lastColumn = node.classList.contains( 'date' );
							if ( postRow || lastColumn ) {
								resetDataOrdering();
							}
							// Reset media item click event handler
							const mediaItem = node.querySelector( '.media-item' );
							if ( mediaItem != null && mediaItem.onclick === null ) {
								// Ignore "Functions declared within loops referencing an outer scoped variable may lead to confusing semantics" error
								/* jshint -W083 */
								( function ( mediaItem ) {
									mediaItem.onclick = function() {
										openModalDialog( mediaItem );
									};
								} ( mediaItem ) );
							}
						}
					}
				}
			}
		} );
		if ( mediaList ) {
			mediaListObserver.observe( mediaList, { childList: true, subtree: true } );
		}

		// Set ordering of media items in list view on page load
		resetDataOrdering();
	}

	// Grid view
	if ( mediaGrid != null ) {
		// Add event listeners for changing the selection of items displayed
		if ( typeFilter ) {
			typeFilter.addEventListener( 'change', updateGrid );
		}
		if ( dateFilter ) {
			dateFilter.addEventListener( 'change', updateGrid );
		}
		if ( mediaCatSelect ) {
			mediaCatSelect.addEventListener( 'change', updateGrid );
		}
		search.addEventListener( 'input', function() {
			var searchtimer;
			clearTimeout( searchtimer );
			searchtimer = setTimeout( updateGrid, 200 );
		} );
		document.getElementById( 'current-page-selector' ).addEventListener( 'change', function( e ) {
			var searchtimer;
			paged = e.target.value;
			clearTimeout( searchtimer );
			searchtimer = setTimeout( updateGrid, 200 );
		} );

		// Make pagination work in conjunction with the select dropdowns
		document.querySelectorAll( '.pagination-links a' ).forEach( function( pageLink ) {
			pageLink.addEventListener( 'click', function( e ) {
				if ( typeFilter.value !== '' || dateFilter.value !== '0' || mediaCatSelect.value !== '0' ) {
					e.preventDefault();
					paged = pageLink.href.split( '?paged=' )[1];
					updateGrid();
				}
			} );
		} );

		// Bulk select media items
		document.querySelector( '.select-mode-toggle-button' ).addEventListener( 'click', function( e ) {
			var toolbar = document.querySelector( '.cp-media-toolbar' ),
				deleteButton = document.querySelector( '.delete-selected-button' );

			if ( toolbar.className.includes( 'media-toolbar-mode-select' ) ) {
				document.querySelectorAll( '.media-item' ).forEach( function( item ) {
					if ( item.className.includes( 'selected' ) ) {
						item.classList.remove( 'selected' );
						item.setAttribute( 'aria-checked', false );
					}
				} );

				e.target.textContent = 'Bulk select';
				dateFilter.style.display = '';
				typeFilter.style.display = '';
				mediaCatSelect.style.display = '';
				deleteButton.classList.add( 'hidden' );
				toolbar.classList.remove( 'media-toolbar-mode-select' );
			} else {
				e.target.textContent = 'Cancel';
				dateFilter.style.display = 'none';
				typeFilter.style.display = 'none';
				mediaCatSelect.style.display = 'none';
				deleteButton.classList.remove( 'hidden' );
				toolbar.classList.add( 'media-toolbar-mode-select' );

				deleteButton.addEventListener( 'click', function() {
					var selectedItems = document.querySelectorAll( '.media-item.selected' );
					if ( selectedItems.length !== 0 ) {
						if ( window.confirm( _wpMediaLibSettings.confirm_multiple ) ) {
							selectedItems.forEach( function( deleteSelect ) {
								deleteItem( deleteSelect.id.replace( 'media-', '' ) );
							} );
						}
						document.querySelector( '.select-mode-toggle-button' ).click();
					}
				} );
			}
		} );
	}

	// Both list and grid view

	closeButton.addEventListener( 'click', closeModalDialog );
	leftIcon.addEventListener( 'click', prevModalDialog );
	leftIconMobile.addEventListener( 'click', prevModalDialog );
	rightIcon.addEventListener( 'click', nextModalDialog );
	rightIconMobile.addEventListener( 'click', nextModalDialog );
	document.querySelector( '.edit-media-header' ).addEventListener( 'keydown', keydownHandler );
	mediaNavigationMobile.addEventListener( 'keydown', keydownHandler );
	dialog.addEventListener( 'touchstart', touchStartHandler );
	dialog.addEventListener( 'touchend', touchEndHandler );
	window.addEventListener( 'resize', toggleMediaNavigation );

	// Delete media item
	dialog.querySelector( '.delete-attachment' ).addEventListener( 'click', function() {
		var id = location.search.match( /\d+/g )[0];
		if ( window.confirm( _wpMediaLibSettings.confirm_delete ) ) {
			deleteItem( id );
		}
	} );

	// Open modal automatically if URL contains media item ID as query param
	if ( queryParams.has( 'item' ) ) {
		itemID = parseInt( queryParams.get( 'item' ) );
		if ( itemID != null && document.getElementById( 'media-' + itemID ) != null ) {
			setTimeout( function() {
				document.getElementById( 'media-' + itemID ).click();
				if ( queryParams.has( 'mode' ) && queryParams.get( 'mode' ) === 'edit' ) {
					document.querySelector( '.edit-attachment' ).click();
				}
			} );
		}
	}

	// Open and close file upload area by clicking Add New button
	addNew.addEventListener( 'click', function() {
		if ( ! uploader.hasAttribute( 'hidden' ) ) {
			uploader.setAttribute( 'inert', true );
			uploader.setAttribute( 'hidden', true );
			addNew.setAttribute( 'aria-expanded', false );
		} else {
			uploader.removeAttribute( 'inert' );
			uploader.removeAttribute( 'hidden' );
			addNew.setAttribute( 'aria-expanded', true );
		}
	} );

	// Close file upload area by clicking X button
	close.addEventListener( 'click', function() {
		uploader.setAttribute( 'inert', true );
		uploader.setAttribute( 'hidden', true );
		addNew.setAttribute( 'aria-expanded', false );
	} );

	// Set functions for Escape and Enter keys
	document.addEventListener( 'keyup', function( e ) {
		if ( e.key === 'Escape' ) {
			queryParams.delete( 'item' );
			queryParams.delete( 'mode' );
			queryParams.delete( 'deleted' );
			history.replaceState( null, null, location.href.split('?')[0] ); // reset URL params
			close.click(); // close file upload area
			if ( focusID != null ) { // set focus correctly
				document.getElementById( focusID ).focus();
				focusID = null; // reset focusID
			}
			removeImageEditWrap();
		} else if ( e.key === 'Enter' && e.target.className === 'media-item' ) {
			e.target.click(); // open modal
		}
	} );

	// Open modal to show details about file, or select files for deletion
	document.querySelectorAll( '.media-item' ).forEach( function( item ) {
		item.addEventListener( 'click', function() {
			if ( document.querySelector( '.media-toolbar-mode-select' ) == null ) {
				openModalDialog( item );
			} else {
				selectItemForDeletion( item );
			}
		} );
	} );

	// Update media attachment details
	dialog.querySelectorAll( '.settings input, .settings textarea' ).forEach( function( input ) {
		input.addEventListener( 'blur', function() {
			var id = queryParams.get( 'item' );
			var item = document.getElementById( 'media-' + id );
			if ( item.dataset.updateNonce ) {
				updateDetails( input, id );
				updateMediaRow( item );
			}
		} );
	} );



	// Edit image
	document.querySelector( '.edit-attachment' ).addEventListener( 'click', function() {
		var itemID = parseInt( queryParams.get( 'item' ) ),
			item = document.getElementById( 'media-' + itemID ),
			nonce = item.dataset.editNonce,
			action = 'rotate-cw', // or any other valid action e.g. save, scale, restore
			target = 'full'; // or 'thumbnail', etc.

		// Construct the FormData object
		var formData = new FormData();
		formData.append( 'action', 'image-editor' );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'postid', itemID );
		formData.append( 'do', action );
		formData.append( 'target', target );
		formData.append( 'context', 'edit-attachment' );

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
			// Add navigation buttons
			if ( dialog.querySelector( '#edit-image-navigation') == null ) {
				var div = document.createElement( 'div' );
				div.id = 'edit-image-navigation';
				div.className = 'attachment-media-view landscape';
				div.append( mediaNavigationMobile );
				document.querySelector( '.media-frame-content' ).before( div );
			}

			document.querySelector( '.attachment-details' ).setAttribute( 'hidden', true );
			document.querySelector( '.attachment-details' ).setAttribute( 'inert', true );
			document.querySelector( '.media-frame-content' ).insertAdjacentHTML( 'beforeend', result.data.html );

			// Modify current URL
			queryParams.delete( 'deleted' );
			queryParams.set( 'mode', 'edit' );
			history.replaceState( null, null, '?' + queryParams.toString() );
		} )
		.catch( function( error ) {
			console.error( 'Error:', error );
		} );
	} );

	/**
	 * Copies the attachment URL to the clipboard.
	 *
	 * @since CP-2.2.0
	 *
	 * @param {MouseEvent} event A click event.
	 *
	 * @return {void}
	 */
	document.querySelector( '.copy-attachment-url' ).addEventListener( 'click', function( e ) {
		var successTimeout,
			copyText = e.target.parentNode.previousElementSibling.value,
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
		clearTimeout( successTimeout );
		e.target.nextElementSibling.classList.remove( 'hidden' );
		e.target.nextElementSibling.setAttribute( 'aria-hidden', 'false' );
		input.remove();

		// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
		successTimeout = setTimeout( function() {
			e.target.nextElementSibling.classList.add( 'hidden' );
			e.target.nextElementSibling.setAttribute( 'aria-hidden', 'true' );
		}, 3000 );

	} );

	/* Upload files using FilePond */
	// Register FilePond plugins
	FilePond.registerPlugin(
		FilePondPluginFileValidateSize,
		FilePondPluginFileValidateType,
		FilePondPluginFileRename,
		FilePondPluginImagePreview
	);

	// Create a FilePond instance
	pond = FilePond.create( inputElement, {
		allowMultiple: true,
		server: {
			process: function( fieldName, file, metadata, load, error, progress, abort ) {

				// Create FormData
				var formData = new FormData();
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
					var gridItem;
					if ( result.success ) {
						load( result.data );
						if ( mediaGrid != null ) {
							gridItem = populateGridItem( result.data );
							mediaGrid.prepend( gridItem );

							// Open modal to show details about file, or select file for deletion
							gridItem.addEventListener( 'click', function() {
								if ( document.querySelector( '.media-toolbar-mode-select' ) == null ) {
									openModalDialog( gridItem );
								} else {
									selectItemForDeletion( gridItem );
								}
							} );
						}
					} else {
						error( _wpMediaLibSettings.upload_failed );
					}
				} )
				.catch( function( err ) {
					error( _wpMediaLibSettings.upload_failed );
					console.error( _wpMediaLibSettings.error, err );
				} );

				// Return an abort function
				return {
					abort: function() {
						// This function is called when the user aborts the upload
						abort();
					}
				};
			},
			maxFileSize: document.getElementById( 'ajax-url' ).dataset.maxFileSize
		},
		labelTapToUndo: _wpMediaLibSettings.tap_close,
		fileRenameFunction: ( file ) =>
			new Promise( function( resolve ) {
				resolve( window.prompt( _wpMediaLibSettings.new_filename, file.name ) );
			} ),
		acceptedFileTypes: document.querySelector( '.uploader-inline' ).dataset.allowedMimes.split( ',' ),
		labelFileTypeNotAllowed: _wpMediaLibSettings.invalid_type,
		fileValidateTypeLabelExpectedTypes: _wpMediaLibSettings.check_types
	} );

	pond.on( 'addfile', function( error ) {
		document.getElementById( 'post-upload-info' ).classList.add( 'no-top-margin' );
		if ( error ) {
			if ( mediaGrid == null ) {
				document.getElementById( 'refresh' ).classList.remove( 'hidden' );
			}
		}
	} );

	pond.on( 'processfile', function( error, file ) {
		document.getElementById( 'post-upload-info' ).classList.add( 'no-top-margin' );
		if ( error ) {
			if ( mediaGrid == null ) {
				document.getElementById( 'refresh' ).classList.remove( 'hidden' );
			}
		} else {
			setTimeout( function() {
				pond.removeFile( file.id );
			}, 100 );
			resetDataOrdering();
			document.getElementById( 'post-upload-info' ).classList.remove( 'no-top-margin' );
		}
	} );

	pond.on( 'processfiles', function() {
		document.getElementById( 'post-upload-info' ).classList.remove( 'no-top-margin' );
		if ( mediaGrid == null ) {
			location.href = location.pathname;
		}
	} );

	pond.on( 'removefile', function() {
		document.getElementById( 'post-upload-info' ).classList.remove( 'no-top-margin' );
	} );

	// Enable the setting of the media upload category
	if ( uploadCatSelect != null ) {

		if ( uploadCatSelect.value == '' ) {
			addNew.setAttribute( 'inert', true );
			addNew.setAttribute( 'disabled', true );

			// Prevent uploading file into browser window
			window.addEventListener( 'dragover', function( e ) {
				e.preventDefault();
			}, false );
			window.addEventListener( 'drop', function( e ) {
				e.preventDefault();
			}, false );
		}

		// Set up variables when a change of upload category is made.
		uploadCatSelect.addEventListener( 'change', function( e ) {
			var div,
				dismissible = document.querySelector( '.is-dismissible' ),
				uploadCatFolder = new URLSearchParams( {
					action: 'media-cat-upload',
					option: 'media_cat_upload_folder',
					media_cat_upload_value: e.target.value,
					media_cat_upload_nonce: document.getElementById( 'media_cat_upload_nonce' ).value
				} );

			// Prevent accumulation of notices.
			if ( dismissible != null ) {
				dismissible.remove();
			}

			if ( uploadCatSelect.value == '' ) {
				addNew.setAttribute( 'inert', true );
				addNew.setAttribute( 'disabled', true );

				// Prevent uploading file into browser window
				window.addEventListener( 'dragover', function( e ) {
					e.preventDefault();
				}, false );
				window.addEventListener( 'drop', function( e ) {
					e.preventDefault();
				}, false );
			} else {
				addNew.removeAttribute( 'inert' );
				addNew.removeAttribute( 'disabled' );
			}

			// Update upload category.
			fetch( ajaxurl, {
				method: 'POST',
				body: uploadCatFolder,
				credentials: 'same-origin'
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.json(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function( response ) {
				if ( response.success ) {
					if ( response.data.value == '' ) {
						div = document.createElement( 'div' );
						div.id = 'message';
						div.className = 'notice notice-error is-dismissible';
						div.innerHTML = '<p>' + response.data.message + '</p><button class="notice-dismiss" type="button"></button>';
						document.querySelector( '.page-title-action' ).after( div );
					} else {
						div = document.createElement( 'div' );
						div.id = 'message';
						div.className = 'updated notice notice-success is-dismissible';
						div.innerHTML = '<p>' + response.data.message + '</p><button class="notice-dismiss" type="button"></button>';
						document.querySelector( '.page-title-action' ).after( div );

						// Update selected attribute in DOM.
						uploadCatSelect.childNodes.forEach( function( option ) {
							if ( option.value === e.target.value ) {
								option.setAttribute( 'selected', true );
							} else {
								option.removeAttribute( 'selected' );
							}
						} );
					}
				}
			} )
			.catch( function( error ) {
				div = document.createElement( 'div' );
				div.id = 'message';
				div.className = 'notice notice-error is-dismissible';
				div.innerHTML = '<p>' + error + '</p><button class="notice-dismiss" type="button"></button>';
				document.querySelector( '.page-title-action' ).after( div );
			} );
		} );

		// Make notices dismissible.
		document.addEventListener( 'click', function( e ) {
			if ( e.target.className === 'notice-dismiss' ) {
				document.querySelector( '.is-dismissible' ).remove();
			}
		} );
	}

} );
