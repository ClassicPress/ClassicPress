/**
 * @output wp-admin/js/application-passwords.js
 *
 * @since CP-2.8.0
 */

( function () {
	const appPassSection   = document.getElementById( 'application-passwords-section' );
	const newAppPassForm   = appPassSection.querySelector( '.create-application-password' );
	const newAppPassField  = newAppPassForm.querySelector( '.input' );
	const newAppPassButton = newAppPassForm.querySelector( '.button' );
	const appPassTwrapper  = appPassSection.querySelector( '.application-passwords-list-table-wrapper' );
	const appPassTbody     = appPassSection.querySelector( 'tbody' );
	const appPassTrNoItems = appPassTbody.querySelector( '.no-items' );
	const removeAllBtn     = document.getElementById( 'revoke-all-application-passwords' );
	const tmplNewAppPass   = document.getElementById( 'tmpl-new-application-password' );
	const tmplAppPassRow   = document.getElementById( 'tmpl-application-password-row' );
	const userId           = document.getElementById( 'user_id' ).value;

	// ---------------------------------------------------------------------------
	// Template renderers
	// ---------------------------------------------------------------------------

	/**
	 * Clones the new-password notice template and populates it with data.
	 *
	 * @param {Object} data
	 * @param {string} data.name     The application password name.
	 * @param {string} data.password The plaintext password (shown once).
	 * @returns {HTMLElement} The populated notice element.
	 */
	function renderNewPasswordNotice( data ) {
		const fragment = tmplNewAppPass.content.cloneNode( true );

		fragment.querySelectorAll( '[data-name]' ).forEach( el => {
			el.textContent = data.name;
		} );

		const input = fragment.querySelector( 'input[data-password]' );
		if ( input ) {
			input.value = data.password;
		}

		return fragment.querySelector( '.new-application-password-notice' );
	}

	/**
	 * Clones the password row template and populates it with data.
	 *
	 * @param {Object} data A REST API application password response object.
	 * @returns {DocumentFragment} The populated row fragment.
	 */
	function renderPasswordRow( data ) {
		const fragment = tmplAppPassRow.content.cloneNode( true );

		// Set uuid on the <tr>.
		const tr = fragment.querySelector( 'tr' );
		if ( tr ) {
			tr.dataset.uuid = data.uuid;
		}

		// Name cell.
		const nameSpan = fragment.querySelector( 'span[data-name]' );
		if ( nameSpan ) {
			nameSpan.textContent = data.name;
		}

		// Last IP cell — fall back to an em-dash when absent.
		const lastIpSpan = fragment.querySelector( 'span[data-last_ip]' );
		if ( lastIpSpan ) {
			lastIpSpan.textContent = data.last_ip || '\u2014';
		}

		// Revoke button: set id, name, and complete the aria-label.
		const revokeBtn = fragment.querySelector( '.delete[data-aria-label]' );
		if ( revokeBtn ) {
			const identifier = 'revoke-application-password-' + data.uuid;
			revokeBtn.setAttribute( 'name', identifier );
			revokeBtn.setAttribute( 'id', identifier );

			const prefix = revokeBtn.getAttribute( 'data-aria-label' );
			revokeBtn.setAttribute( 'aria-label', prefix + data.name + '\u201d' );
			revokeBtn.removeAttribute( 'data-aria-label' );
		}

		return fragment;
	}

	// ---------------------------------------------------------------------------
	// Notices
	// ---------------------------------------------------------------------------

	/**
	 * Displays a notice message in the Application Passwords section.
	 *
	 * @param {string} message
	 * @param {string} type  'success' or 'error'
	 * @returns {HTMLElement} The notice element.
	 */
	function addNotice( message, type ) {
		const notice = document.createElement( 'div' );
		notice.setAttribute( 'role', 'alert' );
		notice.setAttribute( 'tabindex', '-1' );
		notice.className = 'is-dismissible notice notice-' + type;

		const p = document.createElement( 'p' );
		p.textContent = message;

		const dismissBtn = document.createElement( 'button' );
		dismissBtn.type = 'button';
		dismissBtn.className = 'notice-dismiss';

		const srText = document.createElement( 'span' );
		srText.className = 'screen-reader-text';
		srText.textContent = wp.i18n.__( 'Dismiss this notice.' );

		dismissBtn.appendChild( srText );
		notice.appendChild( p );
		notice.appendChild( dismissBtn );

		newAppPassForm.insertAdjacentElement( 'afterend', notice );

		return notice;
	}

	/**
	 * Removes all notice elements from the Application Passwords section.
	 */
	function clearNotices() {
		appPassSection.querySelectorAll( '.notice' ).forEach( el => el.remove() );
	}

	// ---------------------------------------------------------------------------
	// Helpers: notice dismiss animation
	// ---------------------------------------------------------------------------

	/**
	 * Animates a notice out, then removes it from the DOM.
	 *
	 * Replicates jQuery's fadeTo(100) + slideUp(100) sequence using the
	 * Web Animations API and a CSS height collapse.
	 *
	 * @param {HTMLElement} el
	 */
	function dismissNotice( el ) {
		el.removeAttribute( 'role' );

		el.animate(
			[ { opacity: 1 }, { opacity: 0 } ],
			{ duration: 100, fill: 'forwards' }
		).finished.then( () => {
			const fullHeight = el.offsetHeight;

			el.style.overflow = 'hidden';

			el.animate(
				[
					{ height: fullHeight + 'px', marginTop: '', marginBottom: '', paddingTop: '', paddingBottom: '' },
					{ height: '0',               marginTop: '0', marginBottom: '0', paddingTop: '0', paddingBottom: '0' }
				],
				{ duration: 100, fill: 'forwards' }
			).finished.then( () => {
				el.remove();
				newAppPassField.focus();
			} );
		} );
	}

	// ---------------------------------------------------------------------------
	// Helpers: REST API fetch wrapper
	// ---------------------------------------------------------------------------

	/**
	 * Sends a request to the WP REST API using fetch().
	 *
	 * @param {string} path    REST API path.
	 * @param {string} method  HTTP method.
	 * @param {Object} [data]  Optional request body.
	 * @returns {Promise<{response: Response, json: Object|null}>}
	 */
	async function apiRequest( path, method, data = null ) {
		const options = {
			method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   wpApiSettings.nonce,
			},
		};

		if ( data ) {
			options.body = JSON.stringify( data );
		}

		const response = await fetch( wpApiSettings.root + path, options );
		let json = null;

		try {
			json = await response.json();
		} catch ( _ ) {
			// Non-JSON response body — json stays null.
		}

		return { response, json };
	}

	// ---------------------------------------------------------------------------
	// Error handler
	// ---------------------------------------------------------------------------

	/**
	 * Handles an error response from the REST API.
	 *
	 * @param {Response}    response The fetch Response object.
	 * @param {Object|null} json     The parsed JSON body, or null if unparseable.
	 */
	function handleErrorResponse( response, json ) {
		const errorMessage = ( json && json.message ) ? json.message : response.statusText;
		addNotice( errorMessage, 'error' );
	}

	// ---------------------------------------------------------------------------
	// Event: Create new application password
	// ---------------------------------------------------------------------------

	newAppPassButton.addEventListener( 'click', async function ( e ) {
		e.preventDefault();

		if ( newAppPassButton.getAttribute( 'aria-disabled' ) === 'true' ) {
			return;
		}

		const name = newAppPassField.value;

		if ( name.length === 0 ) {
			newAppPassField.focus();
			return;
		}

		clearNotices();
		newAppPassButton.setAttribute( 'aria-disabled', 'true' );
		newAppPassButton.classList.add( 'disabled' );

		let request = { name };

		/**
		 * Filters the request data used to create a new Application Password.
		 *
		 * @since 5.6.0
		 *
		 * @param {Object} request The request data.
		 * @param {number} userId  The id of the user the password is added for.
		 */
		request = wp.hooks.applyFilters( 'wp_application_passwords_new_password_request', request, userId );

		try {
			const { response, json } = await apiRequest(
				'wp/v2/users/' + userId + '/application-passwords?_locale=user',
				'POST',
				request
			);

			if ( ! response.ok ) {
				handleErrorResponse( response, json );
				return;
			}

			newAppPassField.value = '';

			// Render and insert the new-password notice.
			const noticeEl = renderNewPasswordNotice( {
				name:     json.name,
				password: json.password,
			} );
			newAppPassForm.insertAdjacentElement( 'afterend', noticeEl );
			noticeEl.focus();

			// Render and prepend the new table row.
			appPassTbody.prepend( renderPasswordRow( json ) );

			appPassTwrapper.style.display = '';
			if ( appPassTrNoItems ) {
				appPassTrNoItems.remove();
			}

			/**
			 * Fires after an application password has been successfully created.
			 *
			 * @since 5.6.0
			 *
			 * @param {Object} response The response data from the REST API.
			 * @param {Object} request  The request data used to create the password.
			 */
			wp.hooks.doAction( 'wp_application_passwords_created_password', json, request );

		} finally {
			newAppPassButton.removeAttribute( 'aria-disabled' );
			newAppPassButton.classList.remove( 'disabled' );
		}
	} );

	// ---------------------------------------------------------------------------
	// Event: Revoke single application password (delegated)
	// ---------------------------------------------------------------------------

	appPassTbody.addEventListener( 'click', async function ( e ) {
		const deleteBtn = e.target.closest( '.delete' );
		if ( ! deleteBtn ) {
			return;
		}

		e.preventDefault();

		if ( ! window.confirm( wp.i18n.__( 'Are you sure you want to revoke this password? This action cannot be undone.' ) ) ) {
			return;
		}

		const tr   = deleteBtn.closest( 'tr' );
		const uuid = tr.dataset.uuid;

		clearNotices();
		deleteBtn.disabled = true;

		try {
			const { response, json } = await apiRequest(
				'wp/v2/users/' + userId + '/application-passwords/' + uuid + '?_locale=user',
				'DELETE'
			);

			if ( ! response.ok ) {
				handleErrorResponse( response, json );
				return;
			}

			if ( json.deleted ) {
				if ( tr.parentElement.querySelectorAll( 'tr' ).length === 1 ) {
					appPassTwrapper.style.display = 'none';
				}
				tr.remove();

				addNotice( wp.i18n.__( 'Application password revoked.' ), 'success' ).focus();
			}

		} finally {
			deleteBtn.disabled = false;
		}
	} );

	// ---------------------------------------------------------------------------
	// Event: Revoke all application passwords
	// ---------------------------------------------------------------------------

	removeAllBtn.addEventListener( 'click', async function ( e ) {
		e.preventDefault();

		if ( ! window.confirm( wp.i18n.__( 'Are you sure you want to revoke all passwords? This action cannot be undone.' ) ) ) {
			return;
		}

		clearNotices();
		removeAllBtn.disabled = true;

		try {
			const { response, json } = await apiRequest(
				'wp/v2/users/' + userId + '/application-passwords?_locale=user',
				'DELETE'
			);

			if ( ! response.ok ) {
				handleErrorResponse( response, json );
				return;
			}

			if ( json.deleted ) {
				appPassTbody.replaceChildren();
				appPassSection.querySelectorAll( '.new-application-password' ).forEach( el => el.remove() );
				appPassTwrapper.style.display = 'none';

				addNotice( wp.i18n.__( 'All application passwords revoked.' ), 'success' ).focus();
			}

		} finally {
			removeAllBtn.disabled = false;
		}
	} );

	// ---------------------------------------------------------------------------
	// Event: Dismiss notice (delegated)
	// ---------------------------------------------------------------------------

	appPassSection.addEventListener( 'click', function ( e ) {
		const dismissBtn = e.target.closest( '.notice-dismiss' );
		if ( ! dismissBtn ) return;

		e.preventDefault();
		dismissNotice( dismissBtn.closest( '.notice' ) );
	} );

	// ---------------------------------------------------------------------------
	// Event: Enter key in new-password field
	// ---------------------------------------------------------------------------

	newAppPassField.addEventListener( 'keypress', function ( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			newAppPassButton.click();
		}
	} );

	// ---------------------------------------------------------------------------
	// Init: hide table wrapper if no rows exist yet
	// ---------------------------------------------------------------------------

	const realRows = Array.from( appPassTbody.querySelectorAll( 'tr' ) )
		.filter( tr => ! tr.classList.contains( 'no-items' ) );

	if ( realRows.length === 0 ) {
		appPassTwrapper.style.display = 'none';
	}

} )();
