/**
 * @output wp-admin/js/theme.js
 */
/* global _wpThemeSettings, _wpUpdatesSettings, ajaxurl, console, KeyboardEvent */

/**
 * @since CP 2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var prevElement, nextElement, observer, themeID,
		i = 1,
		updateType = 'browse',
		updateValue = 'popular',
		queryParams = new URLSearchParams( window.location.search ),
		dialog = document.getElementById( 'theme-modal' ),
		previewDialog = document.querySelector( '.theme-install-overlay' ),
		footer = document.querySelector( '.theme-install-php #wpfooter' ),
		tagSearch = document.querySelector( '.theme-install-php #wp-filter-search-input' ),
		config = {
			rootMargin: '50%',
			threshold: 0
		};

	// Open modal automatically if URL contains appropriate query param
	if ( queryParams.has( 'theme' ) ) {
		themeID = queryParams.get( 'theme' );
		if ( themeID != null && document.getElementById( themeID ) != null ) {
			setTimeout( function() {
				document.getElementById( themeID ).querySelector( '.more-details' ).click();
			} );
		}
	} else if ( document.body.className.includes( 'themes-php' ) && queryParams.has( 'search' ) ) {
		document.getElementById( 'wp-filter-search-input' ).value = queryParams.get( 'search' );
		setTimeout( function() {
			document.getElementById( 'wp-filter-search-input' ).dispatchEvent( new KeyboardEvent( 'keyup', { 'key':'Enter' } ) );
		} );
	} else {
		history.replaceState( null, null, location.href.split( '?' )[0] );
	}

	// Reload the list of themes from wordpress.org using Intersection Observer
	if ( footer ) {
		observer = new IntersectionObserver( function( entries ) {
			entries.forEach( entry => {
				if ( entry.isIntersecting ) {
					i++;
					updateThemes();
				}
			} );
		}, config );
		observer.observe( footer );
	}

	// Close modal and set focus on theme
	function closeModal() {
		dialog.close();
		document.getElementById( dialog.dataset.highlightedTheme ).focus();
		dialog.querySelector( '.left.dashicons.dashicons-no' ).disabled = false;
		dialog.querySelector( '.right.dashicons.dashicons-no' ).disabled = false;
		cleanup();
	}

	// Reset the dialog element to its default state
	function cleanup() {
		dialog.querySelector( '#theme-modal-insert-container' ).remove();
		dialog.dataset.highlightedTheme = '';

		// Reset URL params
		history.replaceState( null, null, location.href.split( '?' )[0] );
	}

	// Close the theme preview dialog and set focus on theme
	function closePreviewDialog() {
		document.body.style.overflow = '';
		previewDialog.close();
		document.getElementById( previewDialog.querySelector( '.theme-install-container' ).dataset.id ).focus();
		previewDialog.querySelector( '.previous-theme' ).disabled = false;
		previewDialog.querySelector( '.next-theme' ).disabled = false;
		restoreDefaultsPreviewDialog();
	}

	// Reset the theme preview dialog element to its default state
	function restoreDefaultsPreviewDialog() {
		previewDialog.classList.add( 'expanded' );
		previewDialog.classList.remove( 'collapsed' );
		previewDialog.querySelector( '.wp-full-overlay-main' ).style.width = 'calc(100% - 300px)';
		previewDialog.querySelector( '.theme-install-container' ).dataset.id = '';
		if ( previewDialog.querySelector( '.activate' ) ) {
			previewDialog.querySelector( '.activate' ).className = 'button button-primary theme-install';
		}
		previewDialog.querySelector( '.theme-install' ).href = '';
		previewDialog.querySelector( '.theme-name' ).textContent = '';
		previewDialog.querySelector( '.theme-by' ).textContent = '';
		previewDialog.querySelector( '.theme-screenshot img' ).src = '';
		previewDialog.querySelector( '.theme-rating' ).innerHTML = ' <a class="num-ratings" href=""></a>';
		previewDialog.querySelector( '.theme-version' ).textContent = '';
		previewDialog.querySelector( '.theme-description' ).textContent = '';
		previewDialog.querySelector( 'iframe' ).src = '';

		// Reset URL params
		history.replaceState( null, null, location.href.split( '?' )[0] );
	}

	function installTheme( link ) {
		var formData = new FormData(),
			queryParams = new URLSearchParams( link ),
			slug = queryParams.get( 'theme' );

		// Create URLSearchParams object
		formData.append( 'action', 'install-theme' );
		formData.append( 'slug', slug );
		formData.append( '_ajax_nonce', _wpUpdatesSettings.ajax_nonce );

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
			var themeID = result.data.slug,
				theme = document.getElementById( themeID ),
				div = document.createElement( 'div' );

			div.className = 'notice inline notice-success notice-alt';
			div.innerHTML = '<p>' + _wpThemeSettings.l10n.installed + '</p>';
			theme.querySelector( '.theme-screenshot' ).after( div );
			theme.querySelector( '.theme-install' ).textContent = _wpThemeSettings.l10n.activate;
			theme.querySelector( '.theme-install' ).href = theme.dataset.activateNonce;
			theme.querySelector( '.theme-install' ).className = 'button button-primary activate';

			if ( previewDialog ) {
				closePreviewDialog();
				theme.focus();
			}
		} )
		.catch( function( error ) {
			console.error( _wpThemeSettings.error, error );
		} );
	}

	function updateIndividualTheme( slug ) {
		var formData = new FormData();

		// Create URLSearchParams object
		formData.append( 'action', 'update-theme' );
		formData.append( 'slug', slug );
		formData.append( '_ajax_nonce', _wpUpdatesSettings.ajax_nonce );

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
		.then( function() {
			var theme = document.getElementById( slug ),
				notice = theme.querySelector( '.update-message' );

			notice.innerHTML = '<p>' + _wpThemeSettings.l10n.updated + '</p>';
			notice.className = 'notice inline notice-success notice-alt';
		} )
		.catch( function( error ) {
			console.error( _wpThemeSettings.error, error );
		} );
	}

	function updateThemes() {
		var themesGrid = document.querySelector( '.themes' ),

			// Create URLSearchParams object
			params = new URLSearchParams( {
				'action': 'query-themes',
				'request[per_page]': 100,
				'request[page]': i
			} );

		if ( updateType === 'browse' ) {
			params.append( 'request[browse]', updateValue ); // popular or new
		} else if ( updateType === 'search' ) {
			params.append( 'request[search]', updateValue );
		} else if ( updateType === 'tag' ) { // array
			params.append( 'request[tag]', updateValue );
		}

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
			if ( i === 1 ) {
				themesGrid.innerHTML = ''; // clear the current grid
			}
			// Update count
			document.querySelector( '.filter-count .theme-count' ).textContent = result.data.count;

			// Populate grid with new items
			themesGrid.insertAdjacentHTML( 'beforeend', result.data.html );

			showAndHide( document.querySelectorAll( '.themes li:not( .add-new-theme )' ) );
		} )
		.catch( function( error ) {
			console.error( _wpThemeSettings.error, error );
		} );
	}

	// Show and hide each theme's details button when hovering over and out of a theme
	function showAndHide( themes ) {
		themes.forEach( function( theme ) {
			theme.addEventListener( 'mouseover', function() {
				themes.forEach( function( other ) {
					other.querySelector( '.more-details' ).style.opacity = '0';
					other.querySelector( '.theme-actions' ).style.opacity = '0';
				} );
				theme.querySelector( '.more-details' ).style.opacity = '1';
				theme.querySelector( '.theme-actions' ).style.opacity = '1';
				theme.querySelector( '.theme-actions' ).style.display = 'block';
			} );
			theme.addEventListener( 'touchenter', function() {
				themes.forEach( function( other ) {
					other.querySelector( '.more-details' ).style.opacity = '0';
					other.querySelector( '.theme-actions' ).style.opacity = '0';
				} );
				theme.querySelector( '.more-details' ).style.opacity = '1';
				theme.querySelector( '.theme-actions' ).style.opacity = '1';
				theme.querySelector( '.theme-actions' ).style.display = 'block';
			} );
			theme.addEventListener( 'mouseout', function() {
				theme.querySelector( '.more-details' ).style.opacity = '0';
				theme.querySelector( '.theme-actions' ).style.opacity = '0';
				theme.querySelector( '.theme-actions' ).style.display = 'none';
			} );
			theme.addEventListener( 'touchleave', function() {
				theme.querySelector( '.more-details' ).style.opacity = '0';
				theme.querySelector( '.theme-actions' ).style.opacity = '0';
				theme.querySelector( '.theme-actions' ).style.display = 'none';
			} );
		} );
	}
	showAndHide( document.querySelectorAll( '.themes li:not( .add-new-theme )' ) );

	// Navigate the modals by using the keyboard
	document.addEventListener( 'keydown', function( e ) {
		if ( dialog && dialog.open ) {
			if ( e.key === 'Escape' ) {
				closeModal();
			} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				dialog.querySelector( '.left.dashicons.dashicons-no' ).click();
			} else if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				dialog.querySelector( '.right.dashicons.dashicons-no' ).click();
			}
		} else if ( previewDialog && previewDialog.open ) {
			if ( e.key === 'Escape' ) {
				closePreviewDialog();
			} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				previewDialog.querySelector( '.previous-theme' ).click();
			} else if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				previewDialog.querySelector( '.next-theme' ).click();
			}
		}
	} );

	// Open the modal
	document.addEventListener( 'click', function( e ) {
		var filterDrawer, img, template, clone, response,
			theme = e.target.closest( '.theme' ),
			allThemes = document.querySelectorAll( '.themes li:not( .add-new-theme )' ),
			firstElement = document.querySelector( '.themes li' ),
			lastElement = allThemes[ parseInt( allThemes.length - 1 ) ];

		if ( dialog && e.target.className === 'more-details' ) {
			e.preventDefault();

			if ( theme ) {

				template = document.getElementById( 'theme-modal-insert' );
				clone = template.content.cloneNode( true );
				dialog.querySelector( '.theme-wrap' ).append( clone );

				// Set URL
				queryParams.set( 'theme', theme.id );
				history.replaceState( null, null, '?' + queryParams.toString() );

				// Set theme ID and previous and next themes
				dialog.dataset.highlightedTheme = theme.id;
				prevElement = document.getElementById( dialog.dataset.highlightedTheme ).previousElementSibling;
				if ( prevElement == null ) { // first theme
					dialog.querySelector( '.left.dashicons.dashicons-no' ).disabled = true;
				}

				nextElement = document.getElementById( dialog.dataset.highlightedTheme ).nextElementSibling;
				if ( nextElement == null ) { // last theme
					dialog.querySelector( '.right.dashicons.dashicons-no' ).disabled = true;
				}

				// Fill fields in modal
				if ( theme.querySelector( 'img' ) ) {
					img = document.createElement( 'img' );
					img.src = theme.querySelector( 'img' ).src;
					img.alt = theme.querySelector( 'img' ).alt;
					dialog.querySelector( '.screenshot' ).append( img );
				} else {
					dialog.querySelector( '.screenshot' ).classList.add( 'blank' );
				}

				if ( theme.className.includes( 'active' ) ) {
					dialog.querySelector( '.current-label' ).removeAttribute( 'hidden' );
				}

				dialog.querySelector( '.theme-name' ).textContent = theme.querySelector( '.theme-name' ).textContent;
				dialog.querySelector( '.theme-version' ).textContent = _wpThemeSettings.l10n.version + ' ' + theme.dataset.version;
				dialog.querySelector( '.theme-author' ).innerHTML = theme.querySelector( '.theme-author' ).innerHTML;

				if ( theme.dataset.compatibleWp !== '1' && theme.dataset.compatiblePhp !== '1' ) {
					dialog.querySelector( '.no-wp-php' ).removeAttribute( 'hidden' );
				} else if ( theme.dataset.compatibleWp !== '1' ) {
					dialog.querySelector( '.no-wp' ).removeAttribute( 'hidden' );
				} else if ( theme.dataset.compatiblePhp !== '1' ) {
					dialog.querySelector( '.no-php' ).removeAttribute( 'hidden' );
				}

				if ( theme.dataset.hasUpdate ) {
					if ( theme.dataset.updateResponse === '1-1' ) {
						dialog.querySelector( '.has-update span' ).innerHTML = theme.dataset.update;
						dialog.querySelector( '.has-update' ).removeAttribute( 'hidden' );
					} else {
						dialog.querySelector( '.incompat-update span' ).innerHTML = theme.dataset.update;
						dialog.querySelector( '.incompat-update' ).removeAttribute( 'hidden' );
						response = theme.dataset.updateResponse.split( '-' );
						if ( response[0] !== '1' && response[1] !== '1' ) {
							dialog.querySelector( '.incompat-update .no-wp-php' ).removeAttribute( 'hidden' );
						} else if ( response[0] !== '1' ) {
							dialog.querySelector( '.incompat-update .no-wp' ).removeAttribute( 'hidden' );
						} else if ( response[1] !== '1' ) {
							dialog.querySelector( '.incompat-update .no-php' ).removeAttribute( 'hidden' );
						}
					}
				}

				if ( theme.dataset.autoupdate ) {
					dialog.querySelector( '.theme-autoupdate' ).removeAttribute( 'hidden' );
					if ( theme.dataset.autoupdateSupported === '1' ) {
						if ( theme.dataset.autoupdateForced === '1' && dialog.querySelector( '.theme-autoupdate .forced' ) ) {
							dialog.querySelector( '.theme-autoupdate .forced' ).removeAttribute( 'hidden' );
						} else if ( theme.dataset.autoupdateEnabled === '1' && dialog.querySelector( '.theme-autoupdate .enabled' ) ) {
							dialog.querySelector( '.theme-autoupdate .enabled' ).dataset.slug = theme.id;
							dialog.querySelector( '.theme-autoupdate .enabled' ).removeAttribute( 'hidden' );
						} else if ( dialog.querySelector( '.theme-autoupdate .not-forced' ) ) {
							dialog.querySelector( '.theme-autoupdate .not-forced' ).removeAttribute( 'hidden' );
						}
					} else if ( dialog.querySelector( '.theme-autoupdate .no-auto' ) ) {
						dialog.querySelector( '.theme-autoupdate .no-auto' ).dataset.slug = theme.id;
						dialog.querySelector( '.theme-autoupdate .no-auto' ).removeAttribute( 'hidden' );
					}
				}

				if ( theme.dataset.hasUpdate ) {
					if ( theme.dataset.autoupdateSupported === '1' && theme.dataset.autoupdateEnabled === '1' ) {
						dialog.querySelector( '.theme-autoupdate .auto-update-time' ).removeAttribute( 'hidden' );
					}
				}

				dialog.querySelector( '.theme-description' ).textContent = theme.dataset.description;

				if ( theme.dataset.parent ) {
					dialog.querySelector( '.parent-theme strong' ).textContent = theme.dataset.parent;
					dialog.querySelector( '.parent-theme' ).removeAttribute( 'hidden' );
				}

				if ( theme.dataset.tags ) {
					dialog.querySelector( '.theme-tags-span' ).textContent = theme.dataset.tags;
					dialog.querySelector( '.theme-tags' ).removeAttribute( 'hidden' );
				}

				if ( theme.dataset.customize && dialog.querySelector( '.active-theme .customize' ) ) {
					dialog.querySelector( '.active-theme .customize' ).href = theme.dataset.customize;
				}

				// Identify current active theme
				if ( theme.className.includes( 'active' ) ) {
					if ( dialog.querySelector( '.active-theme a' ) ) {
						dialog.querySelector( '.active-theme a' ).href = theme.dataset.customize;
					}
					dialog.querySelector( '.active-theme' ).removeAttribute( 'hidden' );
				} else {
					dialog.querySelector( '.inactive-theme' ).removeAttribute( 'hidden' );

					if ( theme.dataset.compatibleWp === '1' && theme.dataset.compatiblePhp === '1' ) {
						if ( theme.dataset.activateNonce && dialog.querySelector( '.inactive-theme a' ) ) {
							dialog.querySelector( '.inactive-theme a' ).href = theme.dataset.activateNonce;
							dialog.querySelector( '.inactive-theme a' ).classList.add( 'activate' );
							dialog.querySelector( '.inactive-theme a' ).setAttribute( 'aria-label', _wpThemeSettings.l10n.activate + ' ' + theme.querySelector( '.theme-name' ).textContent );
							dialog.querySelector( '.inactive-theme a' ).disabled = false;
						}
						if ( dialog.querySelector( '.inactive-theme .load-customize' ) ) {
							dialog.querySelector( '.inactive-theme .load-customize' ).href = theme.dataset.customize;
						}
					} else {
						if ( theme.dataset.activateNonce && dialog.querySelector( '.inactive-theme a' ) ) {
							dialog.querySelector( '.inactive-theme a' ).removeAttribute( 'href' );
							dialog.querySelector( '.inactive-theme a' ).classList.add( 'disabled' );
							dialog.querySelector( '.inactive-theme a' ).setAttribute( 'aria-label', _wpThemeSettings.l10n.cannot_activate + ' ' + theme.querySelector( '.theme-name' ).textContent );
						}
						if ( dialog.querySelector( '.inactive-theme .load-customize' ) ) {
							dialog.querySelector( '.inactive-theme .load-customize' ).removeAttribute( 'href' );
							dialog.querySelector( '.inactive-theme .load-customize' ).classList.add( 'disabled' );
							dialog.querySelector( '.inactive-theme .load-customize' ).classList.remove( 'load-customize' );
						}
					}

					if ( theme.dataset.deleteNonce && dialog.querySelector( '.delete-theme' ) ) {
						dialog.querySelector( '.delete-theme' ).href = theme.dataset.deleteNonce;
						dialog.querySelector( '.delete-theme' ).setAttribute( 'aria-label', _wpThemeSettings.l10n.delete + ' ' + theme.querySelector( '.theme-name' ).textContent );
						dialog.querySelector( '.delete-theme' ).removeAttribute( 'hidden' );
					}
				}

				// Show or hide fields
				if ( ! theme.className.includes( 'active' ) && theme.dataset.deleteNonce && dialog.querySelector( '.delete-theme' ) ) {
					dialog.querySelector( '.delete-theme' ).style.display = 'inline-block';
				} else if ( dialog.querySelector( '.delete-theme' ) ) {
					dialog.querySelector( '.delete-theme' ).style.display = 'none';
				}

				dialog.showModal();
			}

		// Get the previous theme
		} else if ( e.target.className === 'left dashicons dashicons-no' ) {
			if ( prevElement ) {
				if ( prevElement === firstElement ) { // first theme
					e.target.disabled = true;
				}
				cleanup();
				prevElement.querySelector( '.more-details' ).click();
				dialog.querySelector( '.right.dashicons.dashicons-no' ).disabled = false;
			} else {
				e.target.disabled = true;
			}

		// Get the next theme
		} else if ( e.target.className === 'right dashicons dashicons-no' ) {
			if ( nextElement ) {
				if ( nextElement === lastElement ) { // last theme
					e.target.disabled = true;
				}
				cleanup();
				nextElement.querySelector( '.more-details' ).click();
				dialog.querySelector( '.left.dashicons.dashicons-no' ).disabled = false;
			} else {
				e.target.disabled = true;
			}

		// Close modal
		} else if ( e.target.className === 'close dashicons dashicons-no' ) {
			closeModal();

		// Update a theme
		} else if ( e.target.className.includes( 'update-button-link' ) ) {
			e.target.closest( '.update-message' ).classList.add( 'updating-message' );
			updateIndividualTheme( e.target.closest( 'li' ).id );

		// Search for popular or latest themes at wordpress.org
		} else if ( document.body.className.includes( 'theme-install-php' ) ) {

			if ( e.target.parentNode.parentNode.className === 'filter-links' && e.target.tagName === 'A' ) {
				i = 1;
				updateType = 'browse';
				if ( e.target.href.split( '?' ).pop() === 'browse=popular' ) {
					updateValue = 'popular';
				} else if ( e.target.href.split( '?' ).pop() === 'browse=new' ) {
					updateValue = 'new';
				}
				updateThemes();

				// Modify current URL
				queryParams.delete( 'search' );
				queryParams.delete( 'theme' );
				queryParams.set( 'browse', updateValue );
				history.replaceState( null, null, '?' + queryParams.toString() );

			// Search for themes at wordpress.org by tags
			} else if ( e.target.className === 'apply-filters button' ) {
				i = 1;
				updateType = 'tag';
				updateValue = [];
				document.querySelectorAll( '.filter-group-feature input' ).forEach( function( input ) {
					if ( input.checked ) {
						updateValue.push( input.value );
					}
				} );
				document.querySelector( '.filter-drawer' ).style.display = 'none';
				updateThemes();

				// Reset URL params
				history.replaceState( null, null, location.href.split( '?' )[0] );

			// Show and hide feature filter tags
			} else if ( e.target.className === 'button drawer-toggle' ) {
				filterDrawer = document.querySelector( '.filter-drawer' );
				if ( ! filterDrawer.checkVisibility() ) {
					e.target.setAttribute( 'aria-expanded', true );
					filterDrawer.style.display = 'block';
				} else {
					e.target.setAttribute( 'aria-expanded', false );
					filterDrawer.style.display = 'none';
				}

			// Show theme preview
			} else if ( e.target.className === 'more-details' || e.target.className === 'button preview install-theme-preview' ) {
				previewDialog.querySelector( '.theme-install-container' ).dataset.id = theme.id;
				queryParams.delete( 'browse' );
				queryParams.delete( 'search' );
				queryParams.set( 'theme', theme.id );
				history.replaceState( null, null, '?' + queryParams.toString() );
				previewDialog.querySelector( '.theme-install' ).href = theme.dataset.installNonce;
				if ( theme.querySelector( '.notice-success' ) ) {
					previewDialog.querySelector( '.theme-install' ).href = theme.dataset.activateNonce;
					previewDialog.querySelector( '.theme-install' ).textContent = _wpThemeSettings.l10n.activate;
					previewDialog.querySelector( '.theme-install' ).className = 'button button-primary activate';
					if ( theme.className.includes( 'active' ) ) {
						previewDialog.querySelector( '.activate' ).classList.add( 'disabled' );
					}
				}

				previewDialog.querySelector( '.theme-name' ).textContent = theme.querySelector( '.theme-name' ).textContent;
				previewDialog.querySelector( '.theme-by' ).textContent = theme.querySelector( '.theme-author' ).textContent;
				previewDialog.querySelector( '.theme-screenshot img' ).src = theme.querySelector( '.theme-screenshot img' ).src;
				previewDialog.querySelector( '.theme-rating' ).insertAdjacentHTML( 'afterbegin', theme.dataset.ratings );
				previewDialog.querySelector( '.num-ratings' ).textContent = '(' + theme.dataset.numRatings + ' ' + _wpThemeSettings.l10n.ratings + ')';
				previewDialog.querySelector( '.num-ratings' ).href = 'https://wordpress.org/support/theme/' + theme.id + '/reviews/';
				previewDialog.querySelector( '.theme-version' ).textContent = _wpThemeSettings.l10n.version + ' ' + theme.dataset.version;
				previewDialog.querySelector( '.theme-description' ).textContent = theme.dataset.description;
				previewDialog.querySelector( 'iframe' ).src = '//wp-themes.com/' + theme.id + '/';
				document.body.style.overflow = 'hidden';
				previewDialog.showModal();

				prevElement = document.getElementById( previewDialog.querySelector( '.theme-install-container' ).dataset.id ).previousElementSibling;
				if ( prevElement == null ) { // first theme
					previewDialog.querySelector( '.previous-theme' ).disabled = true;
				}

				nextElement = document.getElementById( previewDialog.querySelector( '.theme-install-container' ).dataset.id ).nextElementSibling;
				if ( nextElement == null ) { // last theme
					previewDialog.querySelector( '.next-theme' ).disabled = true;
				}

			// Close install dialog
			} else if ( e.target.className === 'close-full-overlay' ) {
				closePreviewDialog();

			// Go to previous theme
			} else if ( e.target.className === 'previous-theme' ) {
				if ( prevElement ) {
					if ( prevElement === firstElement ) { // first theme
						e.target.disabled = true;
					}
					restoreDefaultsPreviewDialog();
					previewDialog.querySelector( '.next-theme' ).disabled = false;
					setTimeout( function() {
						prevElement.querySelector( '.more-details' ).click();
					}, 500);
				} else {
					e.target.disabled = true;
				}

			// Go to next theme
			} else if ( e.target.className === 'next-theme' ) {
				if ( nextElement ) {
					if ( nextElement === lastElement ) { // last theme
						e.target.disabled = true;
					}
					restoreDefaultsPreviewDialog();
					previewDialog.querySelector( '.previous-theme' ).disabled = false;
					setTimeout( function() {
						nextElement.querySelector( '.more-details' ).click();
					}, 500);
				} else {
					e.target.disabled = true;
				}

			// Collapse modal sidebar
			} else if ( e.target.className.includes( 'collapse' ) ) {
				if ( previewDialog.className.includes( 'expanded' ) ) {
					previewDialog.classList.add( 'collapsed' );
					previewDialog.classList.remove( 'expanded' );
					previewDialog.querySelector( '.wp-full-overlay-main' ).style.width = '100%';
				} else {
					previewDialog.classList.add( 'expanded' );
					previewDialog.classList.remove( 'collapsed' );
					previewDialog.querySelector( '.wp-full-overlay-main' ).style.width = 'calc(100% - 300px)';
				}

			// Toggle display of Upload Theme area
			} else if ( e.target.className.includes( 'upload-view-toggle' ) ) {
				if ( document.querySelector( '.upload-theme' ).style.display === 'block' ) {
					document.querySelector( '.upload-theme' ).style.display = 'none';
					e.target.setAttribute( 'aria-expanded', false );
				} else {
					document.querySelector( '.upload-theme' ).style.display = 'block';
					e.target.setAttribute( 'aria-expanded', true );
				}

			// Install new theme
 			} else if ( e.target.className.includes( 'theme-install' ) ) {
				e.preventDefault();
				e.target.textContent = _wpThemeSettings.l10n.installing;
				e.target.classList.add( 'updating-message' );
				if ( e.target.closest( '.theme-id-container' ) ) {
					e.target.setAttribute( 'aria-label', 'Installing ' + e.target.closest( '.theme-id-container' ).querySelector( '.theme-name' ).textContent );
				} else {
					e.target.setAttribute( 'aria-label', 'Installing ' + previewDialog.querySelector( '.theme-name' ).textContent );
				}
				e.target.focus();
				wp.a11y.speak( _wpThemeSettings.l10n.installing_wait );
				installTheme( e.target.href );
			}
		}
	} );

	// Search for themes at wordpress.org by search term
	if ( tagSearch ) {
		tagSearch.addEventListener( 'keyup', _.debounce( function( e ) {
			if ( e.target.value.length > 2 ) { // requires at least 3 characters
				i = 1;
				updateType = 'search';
				updateValue = e.target.value;
				updateThemes();

				// Modify current URL
				queryParams.delete( 'browse' );
				queryParams.delete( 'theme' );
				queryParams.set( 'search', updateValue );
				history.replaceState( null, null, '?' + queryParams.toString() );
			}
		}, 500 ) );
	} else {
		document.querySelector( '.themes-php #wp-filter-search-input' ).addEventListener( 'keyup', _.debounce( function( e ) {
			var themes = document.querySelectorAll( '.themes li' ),
				count = themes.length;

			if ( document.querySelector( '.theme-browser.search-loading' ) ) {
				document.querySelector( '.theme-browser.search-loading' ).classList.remove( 'search-loading' );
			}

			if ( e.key === 'Enter' || e.target.value.length > 2 ) { // requires at least 3 characters
				themes.forEach( function( theme ) {
					if ( ! theme.id.includes( e.target.value ) ) {
						theme.style.display = 'none';
						count--;
					}
				} );

				// Modify current URL
				queryParams.set( 'search', e.target.value );
				history.replaceState( null, null, '?' + queryParams.toString() );
			} else {
				themes.forEach( function( theme ) {
					theme.style.display = '';
				} );
				count = themes.length - 1;
				history.replaceState( null, null, location.href.split( '?' )[0] );
			}
			document.querySelector( '.wp-heading-inline .title-count' ).textContent = count;
		}, 500 ) );
	}
} );
