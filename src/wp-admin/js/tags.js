/**
 * Contains logic for deleting and adding tags.
 *
 * For deleting tags it makes a request to the server to delete the tag.
 * For adding tags it makes a request to the server to add the tag.
 *
 * @output wp-admin/js/tags.js
 *
 * @since CP-2.8.0
 * Rewritten in vanilla JavaScript
 */

 /* global ajaxurl, showNotice, wpAjax */

document.addEventListener( 'DOMContentLoaded', function() {

	let addingTerm = false;

	/**
	 * Adds an event handler to the delete term link on the term overview page.
	 *
	 * Cancels default event handling and event bubbling.
	 *
	 * @since 2.8.0
	 *
	 * @return {boolean} Always returns false to cancel the default event handling.
	 */
	document.addEventListener( 'click', function( e ) {
		if ( ! e.target.classList?.contains( 'delete-tag' ) ) {
			return;
		}

		let t = e.target, tr = t.closest( 'tr' ), r = true, data;

		if ( 'undefined' != showNotice ) {
			r = showNotice.warn();
		}

		e.preventDefault();

		if ( ! r ) {
			return false;
		}

		data = t.getAttribute( 'href' ).replace( /[^?]*\?/, '' ).replace( /action=delete/, 'action=delete-tag' );

		/**
		 * Makes a request to the server to delete the term that corresponds to the
		 * delete term button.
		 *
		 * @param {string} r The response from the server.
		 *
		 * @return {void}
		 */
		fetch( ajaxurl, {
			method: 'POST',
			body: new URLSearchParams( data ),
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			return response.text();
		} )
		.then( function( result ) {
			const ajaxResponse = document.getElementById( 'ajax-response' ),
				tagId = data.match( /tag_ID=(\d+)/ )[1],
				div = document.createElement( 'div' ),
				p = document.createElement( 'p' ),
				button = document.createElement( 'button' ),
				span = document.createElement( 'span' );

			button.type = 'button';
			button.className = 'notice-dismiss';
			span.className = 'screen-reader-text';
			span.textContent = adminTagsStrings.dismiss;

			if ( '1' == result ) {
				ajaxResponse?.replaceChildren();
				tr.remove();

				button.append( span );
				div.className = 'notice notice-success is-dismissible';
				p.textContent = adminTagsStrings.deleted;
				div.append( p, button );
				ajaxResponse.append( div );

				/**
				 * Removes the term from the parent box and the tag cloud.
				 */
				document.querySelector( 'select#parent option[value="' + tagId + '"]' )?.remove();
				document.querySelector( 'a.tag-link-' + tagId )?.remove();

			} else {
				button.append( span );
				div.className = 'notice notice-error is-dismissible';

				if ( '-1' == r ) {
					p.textContent = wpAjax.noPerm;
				} else {
					p.textContent = wpAjax.broken;
				}

				div.append( p, button );
				ajaxResponse.append( div );
			}
		} );

		return false;
	} );

	/**
	 * Adds a deletion confirmation when removing a tag.
	 *
	 * @since 4.8.0
	 *
	 * @return {void}
	 */
	document.getElementById( 'edittag' )?.addEventListener( 'click', function( e ) {
		if ( 'undefined' === typeof showNotice ) {
			return true;
		}

		if ( ! e.target.closest( '.delete' ) ) {
			return true;
		}

		// Confirms the deletion, a negative response means the deletion must not be executed.
		let response = showNotice.warn();

		if ( ! response ) {
			e.preventDefault();
		}
	} );

	/**
	 * Adds an event handler to the form submit on the term overview page.
	 *
	 * Cancels default event handling and event bubbling.
	 *
	 * @since 2.8.0
	 *
	 * @return {boolean} Always returns false to cancel the default event handling.
	 */
	document.getElementById( 'submit' )?.addEventListener( 'click', function( e ) {
		const form = e.target.closest( 'form' ),
			data = new FormData( form ),
			spinner = form.querySelector( '.submit .spinner' ),
			ajaxResponse = document.getElementById( 'ajax-response' ),
			div = document.createElement( 'div' ),
			p = document.createElement( 'p' ),
			button = document.createElement( 'button' ),
			span = document.createElement( 'span' );

		e.preventDefault();

		// Avoid duplicate requests.
		if ( addingTerm ) {
			return;
		}

		data.append( 'action', 'add-tag' );
		data.append( '_wpnonce_add-tag', document.getElementById( '_wpnonce_add-tag' ).value );

		addingTerm = true;
		spinner.classList.add( 'is-active' );

		button.type = 'button';
		button.className = 'notice-dismiss';
		span.className = 'screen-reader-text';
		span.textContent = 'Dismiss this notice.';
		button.append( span );

		/**
		 * Sends a request to the server to add a new term to the database
		 *
		 * @param {string} result The response from the server.
		 *
		 * @return {void}
		 */
		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.text(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			const xml = new DOMParser().parseFromString( result, 'text/xml' ),
				rows = xml.querySelector( 'response taxonomy supplemental parents' ).textContent,
				noparents = xml.querySelector( 'response taxonomy supplemental noparents' )?.textContent ?? '',
				noticeText = xml.querySelector( 'response taxonomy supplemental notice' )?.textContent ?? '',
				term = xml.querySelector( 'response term supplemental' ),
				termId = term?.querySelector( 'term_id' )?.textContent ?? '',
				termName = term?.querySelector( 'name' )?.textContent ?? '',
				parent = form.querySelector( 'select#parent' )?.value ?? '0',
				rowHtml = ( parent > 0 ) ? noparents : rows,
				parentSelect = form.querySelector( 'select#parent' ),
				parentOption = parentSelect?.querySelector( 'option[value="' + parent + '"]' );
				theList = document.getElementById( 'the-list' );

			let newOption, parentText, match, indent = '';

			// Display success message from server.
			if ( noticeText && ajaxResponse ) {
				p.textContent = noticeText;
				div.className = 'notice notice-success is-dismissible';
				div.append( p, button );
				ajaxResponse.replaceChildren( div );
			}
			
			// Insert the new row into the table.
			if ( theList ) {
				// For hierarchical taxonomies with a parent, use 'noparents' (includes indentation).
				// For flat or no parent, use 'parent'.
				if ( rowHtml ) {
					if ( parent > 0 ) {
						// Insert after the parent row.
						const parentRow = document.getElementById( 'tag-' + parent );
						if ( parentRow ) {
							parentRow.insertAdjacentHTML( 'afterend', rowHtml );
						} else {
							// Parent not found, insert at top as fallback.
							theList.insertAdjacentHTML( 'afterbegin', rowHtml );
						}
					} else {
						// No parent, insert at top.
						theList.insertAdjacentHTML( 'afterbegin', rowHtml );
					}
				}

				// Remove "No items" message if present.
				theList.querySelectorAll( '.no-items' ).forEach( function( element ) {
					element.remove();
				} );
			}

			// Add to select dropdown list, if there is one.
			if ( parentSelect && termName && termId ) {
				if ( parent > 0 ) {
					if ( parentOption ) {
						// Count groups of 3 non-breaking spaces at the start.
						parentText = parentOption.textContent;
						match = parentText.match( /^(\u00A0{3})+/ );
						if ( match ) {
							// Add one more group of 3 spaces to the parent's indentation.
							indent = match[0] + '\u00A0\u00A0\u00A0';
						} else {
							// Parent has no indent, so this is level 2.
							indent = '\u00A0\u00A0\u00A0';
						}
						parentOption.after( new Option( indent + termName, termId ) );
					} else {
						// Fallback in case parent not found.
						form.querySelector( 'select#parent option:checked' ).after( new Option( termName, termId ) );
					}
				} else {
					form.querySelector( 'select#parent option:checked' ).after( new Option( termName, termId ) );
				}
			}

			// Clear the form fields.
			form.reset();
		} )
		.catch( function() {
			if ( ajaxResponse ) {
				p.textContent = wpAjax.broken;
				div.className = 'notice notice-error is-dismissible';
				div.append( p, button );
				ajaxResponse.replaceChildren( div );
			}
		} )
		.finally( function() {
			addingTerm = false;
			spinner.classList.remove( 'is-active' );
		} );

		return false;
	} );

	// Handle dismissible notice buttons.
	document.addEventListener( 'click', function( e ) {
		if ( e.target.closest( '.notice-dismiss' ) ) {
			const notice = e.target.closest( '.notice' );
			if ( notice ) {
				notice.remove();
			}
		}
	} );
} );
