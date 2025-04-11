/**
 * Creates a dialog containing posts that can have a particular media attached
 * to it.
 *
 * @since CP-2.2.0
 * @output wp-admin/js/media.js
 *
 * @namespace findPosts
 */

/* global ajaxurl, _wpMediaGridSettings, showNotice, findPosts */

document.addEventListener( 'DOMContentLoaded', function() {

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
				document.getElementById( postID ).querySelector( '.' + hide.id.replace( '-hide', '' ) ).classList.add( 'hidden' );
			}
		} );
	}

	/**
	 * Initializes the file once the DOM is fully loaded and attaches events to the
	 * various form elements.
	 *
	 * @since CP-2.2.0
	 *
	 * @return {void}
	 */
	var settings, copyAttachmentURLs, copyAttachmentURLSuccessTimeout,
		mediaGridWrap = document.getElementById( 'wp-media-grid' ),
		uploadCatSelect = document.getElementById( 'upload-category' );

	// Grid View: Opens a manage media frame into the grid.
	if ( mediaGridWrap != null && window.wp && window.wp.media ) {
		settings = _wpMediaGridSettings;

		var frame = window.wp.media( {
			frame: 'manage',
			container: mediaGridWrap,
			library: settings.queryVars
		} ).open();

		// Fire a global ready event.
		mediaGridWrap.dispatchEvent( new CustomEvent( 'wp-media-grid-ready', {
			detail: frame
		} ) );
	} else {

		// List View
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
					columns = [ ...document.querySelector( '.widefat thead tr' ).children ],
					selectAll1 = document.getElementById( 'cb-select-all-1' ),
					selectAll2 = document.getElementById( 'cb-select-all-2' );

				// Set default state in case a Bulk or Quick Edit has already been opened.
				bulkEdit.style.display = 'none';
				document.body.append( bulkEdit );
				quickEdit.style.display = 'none';
				document.body.append( quickEdit );

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

					} else if ( number === 1 && ! selectAll1.checked && ! selectAll2.checked ) {

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
						quickEdit.querySelector( '[name="post_title"]' ).value = tr.querySelector( '.column-title strong a' ).textContent.trim();
						quickEdit.querySelector( '[name="post_name"]' ).value = tr.querySelector( '.column-title .row-actions .copy-attachment-url' ).dataset.clipboardText;
						quickEdit.querySelector( '#quick-media-tags' ).value = mediaTags;
						quickEdit.querySelector( '[name="alt"]' ).value = tr.querySelector( '.column-alt' ).textContent;
						quickEdit.querySelector( '[name="post_excerpt"]' ).value = tr.querySelector( '.column-caption' ).textContent;
						quickEdit.querySelector( '[name="post_content"]' ).value = tr.querySelector( '.column-desc' ).textContent;

						// Update.
						document.getElementById( 'quick-edit-update' ).addEventListener( 'click', function() {
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
						}, { once: true } );
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
	}

	// Enable the setting of the media upload category on the Media Library List View page.
	if ( document.body.className.includes( 'upload-php' ) && mediaGridWrap == null && uploadCatSelect != null ) {

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
