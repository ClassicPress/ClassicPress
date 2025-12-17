/**
 * @output wp-admin/js/customize-controls.js
 */

/* global _wpCustomizeControlsL10n, _wpCustomizeHeader, _wpCustomizeBackground, MediaElementPlayer, console, confirm */
document.addEventListener( 'DOMContentLoaded', function() {
	var intersectionObserver, orgThemes, localThemes, previousAccordionPane,
		i = 1,
		installedThemesHTML = document.querySelector( '.themes')?.innerHTML,
		reducedMotionMediaQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' ),
		isReducedMotion = reducedMotionMediaQuery.matches,
		isCollapsed = document.querySelector( '.wp-full-overlay' )?.classList.contains( 'collapsed' ),
		form = document.querySelector( 'form' ),
		devicesWrapper = form.querySelector('.devices'),
		buttons = devicesWrapper?.querySelectorAll( 'button[data-device]' ),
		previewFrame = document.getElementById( 'customize-preview' );

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

	// Click management
	document.addEventListener( 'click', function( e ) {
		var id;
		if ( ( e.target.tagName === 'H3' || e.target.classList && e.target.classList.contains( 'change-theme' ) ) && e.target.closest( 'ul' ) ) {
			e.preventDefault();
			previousAccordionPane = e.target.closest( 'ul' );
			id = e.target.closest( 'li' ).id;

			// Go down to the second level
			if ( e.target.closest( 'ul' ).classList.contains( 'customize-pane-parent' ) ) {
				e.target.closest( 'ul' ).style.display = 'none';
				document.getElementById( 'customize-info' ).style.display = 'none';
				document.getElementById( 'sub-' + id ).style.display = 'block';
				document.getElementById( 'sub-' + id ).querySelector( 'button' ).focus();

			// Go down to the third level
			} else if ( e.target.closest( 'ul' ).classList.contains( 'customize-pane-child' ) ) {
				e.target.closest( 'ul' ).style.display = 'none';
				document.getElementById( 'sub-' + id ).style.display = 'block';
				document.getElementById( 'sub-' + id ).querySelector( 'button' ).focus();				
			}
		} else if ( e.target.classList && ( e.target.classList.contains( 'customize-section-back' ) || e.target.classList.contains( 'customize-panel-back' ) ) ) {
			e.preventDefault();
			e.target.closest( 'ul' ).style.display = 'none';

			// Go up to the top level
			if ( e.target.parentNode.classList.contains( 'panel-meta' ) ) {
				document.getElementById( 'customize-info' ).style.display = 'block';
				document.querySelector( '.customize-pane-parent' ).style.display = 'block';
				document.querySelector( '.customize-pane-parent h3' ).focus();

			// Go up to the second level	
			} else {			
				previousAccordionPane.style.display = 'block';
				if ( previousAccordionPane.querySelector( '.customize-panel-back' ) ) {
					previousAccordionPane.querySelector( '.customize-panel-back' ).focus();
				} else {
					previousAccordionPane.querySelector( '.customize-section-back' ).focus();
				}
			}

		// Open Create New Menu panel
		} else if ( e.target.classList && e.target.classList.contains( 'customize-add-menu-button' ) ) {
			e.preventDefault();			
			previousAccordionPane = e.target.closest( 'ul' );
			e.target.closest( 'ul' ).style.display = 'none';
			document.getElementById( 'sub-accordion-section-add_menu' ).style.display = 'block';

		// Open Next panel to create new menu
		} else if ( e.target.id === 'customize-new-menu-submit' ) {
			e.preventDefault();			
			previousAccordionPane = e.target.closest( 'ul' );
			e.target.closest( 'ul' ).style.display = 'none';
			document.getElementById( 'menu-to-edit' ).style.display = 'block';

		// Go to widgets panel
		} else if ( e.target.tagName === 'A' && ( e.target.closest( 'li' ).id === 'accordion-section-menu_locations' || e.target.closest( 'ul' ).id === 'sub-accordion-section-menu_locations' ) ) {
			e.preventDefault();			
			e.target.closest( 'ul' ).style.display = 'none';
			document.getElementById( 'sub-accordion-panel-widgets' ).style.display = 'block';
			
		// Open and close description
		} else if ( e.target.classList && e.target.classList.contains( 'customize-help-toggle' ) ) {
			if ( e.target.parentNode.classList.contains( 'open' ) ) {
				e.target.parentNode.classList.remove( 'open' )
				e.target.parentNode.nextElementSibling.style.display = 'none';
				e.target.setAttribute( 'aria-expanded', false );
			} else {
				e.target.parentNode.classList.add( 'open' );
				e.target.parentNode.nextElementSibling.style.display = 'block';
				e.target.setAttribute( 'aria-expanded', true );
			}

		// Browse installed themes
		} else if ( e.target.classList.contains( 'themes-section-installed_themes' ) ) {
			form.querySelector( '.themes-section-wporg_themes' ).classList.remove( 'selected' );
			e.target.classList.add( 'selected' );
			if ( document.querySelector( '.wp-org' ) ) {
				document.querySelector( '.themes').innerHTML = installedThemesHTML;
				document.querySelector( '.theme-browser' ).classList.remove( 'wp-org' );
				document.querySelector( '.theme-browser' ).classList.add( 'local' );
				intersectionObserver.unobserve( orgThemes[orgThemes.length - 1] ); // deactivate Intersection Observer
				document.querySelector( '.filter-themes-count .theme-count' ).textContent = document.querySelectorAll( '.local .themes li' ).length;
			}

		// Browse themes at wp.org
		} else if ( e.target.classList.contains( 'themes-section-wporg_themes' ) ) {
			form.querySelector( '.themes-section-installed_themes' ).classList.remove( 'selected' );
			e.target.classList.add( 'selected' );
			document.querySelector( '.theme-browser' ).classList.remove( 'local' );
			document.querySelector( '.theme-browser' ).classList.add( 'wp-org' );
			updateThemes( 'browse', 'new' );
		}
	} );

	// Keyboard navigation management
	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) {
			if ( isVisible( document.querySelector( '.iris-picker' ) ) ) {
				document.querySelectorAll( '.iris-picker' ).forEach( function( iris ) {
					iris.style.display = 'none';
				} );
			} else {
				e.preventDefault();
				e.target.closest( 'ul' ).style.display = 'none';
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
	 * Enable different device views.
	 */
    buttons.forEach( function( button ) {
        button.addEventListener( 'click', function () {
            var device = button.getAttribute( 'data-device' );

            // Update button active state + aria-pressed
            buttons.forEach(function ( btn ) {
                var isActive = ( btn === button );
                btn.classList.toggle( 'active', isActive );
                btn.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
            } );

            if ( ! previewFrame ) {
                return;
            }

            // Use a data attribute and drive CSS from it
            previewFrame.setAttribute('data-device', device);
        } );
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
		if ( isIntersecting ) {
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

			// Populate grid with new items
			themesGrid.insertAdjacentHTML( 'beforeend', result.data.html );
			orgThemes = document.querySelectorAll( '.wp-org .themes li' );
			orgThemes.forEach( function( theme ) {
				theme.style.marginRight = '2%';
				theme.stylemarginBottom = '2%';
			} );

			// Update count
			document.querySelector( '.filter-themes-count .theme-count' ).textContent = orgThemes.length;

			intersectionObserver.observe( orgThemes[orgThemes.length - 1] );
		} )
		.catch( function( error ) {
			console.error( error );
		} );
	}
} );
