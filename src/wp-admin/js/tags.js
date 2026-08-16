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
				error = document.createElement( 'div' ),
				paragraph = document.createElement( 'p' );

			if ( '1' == result ) {
				ajaxResponse?.replaceChildren();
				tr.remove();

				/**
				 * Removes the term from the parent box and the tag cloud.
				 */
				document.querySelector( 'select#parent option[value="' + tagId + '"]' )?.remove();
				document.querySelector( 'a.tag-link-' + tagId )?.remove();

			} else if ( '-1' == r ) {
				error.className = 'notice notice-error';
				paragraph.textContent = wpAjax.noPerm;

				error.append( paragraph );
				ajaxResponse.append( error );
				tr.querySelectorAll( ':scope > *' ).forEach( function( element ) {
					element.style.backgroundColor = '';
				} );

			} else {
				error.className = 'notice notice-error';
				paragraph.textContent = wpAjax.broken;

				error.append( paragraph );
				ajaxResponse.append( error );
				tr.querySelectorAll( ':scope > *' ).forEach( function( element ) {
					element.style.backgroundColor = '';
				} );
			}
		} );

		tr.querySelectorAll( ':scope > *' ).forEach( function( element ) {
			element.style.backgroundColor = '#f33';
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
			p = document.createElement( 'p' );

		e.preventDefault();

		// Avoid duplicate requests.
		if ( addingTerm ) {
			return;
		}

		data.append( 'action', 'add-tag' );
		data.append( '_wpnonce_add-tag', document.getElementById( '_wpnonce_add-tag' ).value );

		addingTerm = true;
		spinner.classList.add( 'is-active' );

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
				theList = document.getElementById( 'the-list' );

			// Display success message from server.
			if ( noticeText && ajaxResponse ) {
				p.textContent = noticeText;
				div.className = 'notice notice-success';
				div.append( p );
				ajaxResponse.replaceChildren( div );
			}
			
			// Insert the new row into the table.
			if ( theList ) {
				if ( rows ) {
					theList.insertAdjacentHTML( 'afterbegin', rows );
				} else if ( noparents ) {
					theList.insertAdjacentHTML( 'afterbegin', noparents );
				}

				// Remove "No items" message if present.
				theList.querySelectorAll( '.no-items' ).forEach( function( element ) {
					element.remove();
				} );
			}

			// Clear the form fields.
			form.reset();
		} )
		.catch( function() {
			if ( ajaxResponse ) {
				p.textContent = wpAjax.broken;
				div.className = 'notice notice-error';
				div.append( p );
				ajaxResponse.replaceChildren( div );
			}
		} )
		.finally( function() {
			addingTerm = false;
			spinner.classList.remove( 'is-active' );
		} );

		return false;
	} );

} );
