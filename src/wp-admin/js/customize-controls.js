/**
 * @output wp-admin/js/customize-controls.js
 */

/* global _wpCustomizeControlsL10n, _wpCustomizeHeader, _wpCustomizeBackground, MediaElementPlayer, console, confirm */
document.addEventListener( 'DOMContentLoaded', function() {
	var intersectionObserver, orgThemes, localThemes,
		i = 1,
		installedThemesHTML = document.querySelector( '.themes')?.innerHTML,
		reducedMotionMediaQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' ),
		isReducedMotion = reducedMotionMediaQuery.matches,
		childPanes = document.querySelectorAll( 'customize-pane-child' ),
		isCollapsed = document.querySelector( '.wp-full-overlay' )?.classList.contains( 'collapsed' ),
		form = document.querySelector( 'form' );

	reducedMotionMediaQuery.addEventListener( 'change' , function handleReducedMotionChange( event ) {
		isReducedMotion = event.matches;
	} );

	/*
	 * Helper function copied from jQuery
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

	/**
	 * Prevent tabbing out of dialog form.
	 *
	 * @since CP-2.7.0
	 *
	 * @param event - Event.
	 * @return {void}
	 */
	function constrainTab( event ) {
		var first = form.querySelector( '#customize-save-button-wrapper' ).disabled === false ? form.querySelector( '#customize-save-button-wrapper' ) : form.querySelector( '.customize-controls-close' ),
			last = form.querySelector( '.preview-mobile' );

		event.stopPropagation();
		if ( event.target === last && ! event.shiftKey ) {
			event.preventDefault();
			first.focus();
		} else if ( event.target === first && event.shiftKey ) {
			event.preventDefault();
			last.focus();
		}
	}

	// Go down to the next level and back
	document.addEventListener( 'click', function( e ) {
		var id;
		if ( ( e.target.tagName === 'H3' || e.target.tagName === 'BUTTON' ) && e.target.closest( 'ul' ).className === 'customize-pane-parent' ) {
			e.preventDefault();
			id = e.target.closest( 'li' ).id;
			e.target.closest( 'ul' ).style.display = 'none';
			childPanes.forEach( function( childPane ) {
				e.preventDefault();
				childPane.style.display = 'none';
			} );
			document.getElementById( 'customize-info' ).style.display = 'none';
			document.getElementById( 'sub-' + id ).style.display = 'block';
			document.querySelector( '#sub-' + id + ' button' ).focus();
		} else if ( e.target.className === 'customize-section-back' || e.target.className === 'customize-panel-back' ) {
			e.preventDefault();
			id = e.target.closest( 'ul' ).id;
			document.getElementById( id ).style.display = 'none';
			document.getElementById( 'customize-info' ).style.display = 'block';
			document.querySelector( '.customize-pane-parent' ).style.display = 'block';
			document.querySelector( '.customize-pane-parent h3' ).focus();
		} else if ( e.target.classList.contains( 'themes-section-wporg_themes' ) ) {
			form.querySelector( '.themes-section-installed_themes' ).classList.remove( 'selected' );
			e.target.classList.add( 'selected' );
			document.querySelector( '.theme-browser' ).classList.remove( 'local' );
			document.querySelector( '.theme-browser' ).classList.add( 'wp-org' );
			updateThemes( 'browse', 'new' );
		} else if ( e.target.classList.contains( 'themes-section-installed_themes' ) ) {
			form.querySelector( '.themes-section-wporg_themes' ).classList.remove( 'selected' );
			e.target.classList.add( 'selected' );
			if ( document.querySelector( '.wp-org' ) ) {
				document.querySelector( '.themes').innerHTML = installedThemesHTML;
				document.querySelector( '.theme-browser' ).classList.remove( 'wp-org' );
				document.querySelector( '.theme-browser' ).classList.add( 'local' );
				intersectionObserver.unobserve( orgThemes[orgThemes.length - 1] ); // deactivate Intersection Observer
			}
		}

	} );

	// Go back to the top-level Customizer panels
	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) {
			if ( isVisible( document.querySelector( '.iris-picker' ) ) ) {
				document.querySelectorAll( '.iris-picker' ).forEach( function( iris ) {
					iris.style.display = 'none';
				} );
			} else {
				childPanes.forEach( function( childPane ) {
					e.preventDefault();
					childPane.style.display = 'none';
				} );
				document.getElementById( 'customize-info' ).style.display = 'block';
				document.querySelector( '.customize-pane-parent' ).style.display = 'block';
			}
		} else if ( e.key === 'Tab' ) {
			if ( document.querySelector( '.devices-wrapper' ) ) {
				constrainTab( e );
			}
		}
	} );

	/**
	 * Expand and collapse the sidebar.
	 */
	function setCollapsed( collapsed ) {
		var overlay       = document.querySelector( '.wp-full-overlay' ),
			sidebar       = document.getElementById( 'customize-controls' ),
			preview       = document.getElementById( 'customize-preview' ),
			footer        = document.getElementById( 'customize-footer-actions' ),
			button        = footer ? footer.querySelector( '.collapse-sidebar' ) : null,
			closeButton   = document.querySelector( '.customize-controls-close' ),
			footerDevices = footer ? footer.querySelector( '.devices-wrapper' ) : null,
			labelEl       = button.querySelector( '.collapse-sidebar-label' );

		if ( ! overlay || ! sidebar || ! preview || ! button ) {
			return;
		}
    
		// Overlay classes.
		overlay.classList.toggle( 'collapsed', collapsed );
		overlay.classList.toggle( 'expanded', ! collapsed );

		// Sidebar / preview.
		sidebar.classList.toggle( 'collapsed', collapsed );
		preview.classList.toggle( 'expanded-preview', collapsed );

		// Button ARIA + label text.
		button.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
		if ( labelEl ) {
			labelEl.textContent = collapsed ? 'Show Controls' : 'Hide Controls';
		}

		// Hide close button in header when collapsed.
		if ( closeButton ) {
			closeButton.style.display = collapsed ? 'none' : '';
		}

		// Hide footer actions except the arrow when collapsed.
		if ( footerDevices ) {
			footerDevices.style.display = collapsed ? 'none' : '';
		}
	}
	setCollapsed( isCollapsed );

	document.querySelector( '.collapse-sidebar' ).addEventListener( 'click', function ( e ) {
		e.preventDefault();
		isCollapsed = ! isCollapsed;
		setCollapsed( isCollapsed );
	} );

	/**
	 * Code for the Iris color picker.
	 */
	jQuery( '.cp-color-picker' ).wpColorPicker(); // Iris requires jQuery

	// Focus/click: ensure picker shows.
	document.querySelectorAll( '.cp-color-picker' ).forEach( function( input ) {
		var container = input.closest( '.wp-picker-container' );
		if ( ! container ) {
			return;
		}

		function showPicker() {
			var holder = container.querySelector( '.wp-picker-holder' );
			if ( holder ) {
				holder.style.display = '';
			}
		}

		input.addEventListener( 'focus', showPicker );
		input.addEventListener( 'click', showPicker );

		// Color change: update the preview swatch.
		// wpColorPicker triggers a native 'change' event on the input with its current value.
		input.addEventListener( 'change', function() {
			var swatch = container.querySelector( '.wp-color-result' );
			if ( swatch ) {
				swatch.style.backgroundColor = input.value;
			}
		} );
	} );

	// Themes
	document.querySelector( '#installed_themes-themes-filter' )?.addEventListener( 'keyup', _.debounce( function( e ) {
		var localThemes = document.querySelectorAll( '.local .themes li' ),
			count = localThemes.length;

		e.preventDefault();

		if ( e.key === 'Enter' || e.target.value.length > 2 ) { // requires at least 3 characters
			localThemes.forEach( function( theme ) {
				if ( ! theme.id.includes( e.target.value ) ) {
					theme.style.display = 'none';
					count--;
				}
			} );
		} else {
			localThemes.forEach( function( theme ) {
				theme.style.display = '';
			} );
			count = localThemes.length - 1;
		}
		document.querySelector( '.filter-themes-count .theme-count' ).textContent = count;
	}, 500 ) );
	
	// Reload the list of themes from wordpress.org using Intersection Observer
	intersectionObserver = new IntersectionObserver( function( entries ) {
		const isIntersecting = entries[0]?.isIntersecting ?? false;
		if ( isIntersecting ) { console.log('update');
			i++;
			updateThemes( 'browse', 'new' );
		}
	} );

	function updateThemes( updateType, updateValue ) {
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
			params.append( 'request[tag]', updateValueArray );
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
			document.querySelector( '.filter-themes-count .theme-count' ).textContent = result.data.count;

			// Populate grid with new items
			themesGrid.insertAdjacentHTML( 'beforeend', result.data.html );
			orgThemes = document.querySelectorAll( '.wp-org .themes li' );
			orgThemes.forEach( function( theme ) {
				theme.style.marginRight = '2%';
				theme.stylemarginBottom = '2%';
			} );
			intersectionObserver.observe( orgThemes[orgThemes.length - 1] );
		} )
		.catch( function( error ) {
			console.error( error );
		} );
	}
