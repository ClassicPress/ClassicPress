/**
 * @output wp-admin/js/customize-widgets.js
 */

/* global Sortable, _wpCustomizeWidgetsSettings */
/**
 * @since CP-2.8.0
 * @requires SortableJS
 *
 * @output wp-admin/js/widgets.js
 */

/* global Sortable, ajaxurl, console */
document.addEventListener( 'DOMContentLoaded', function() {

	// Set variables for the whole file
	var newMultiValue, timeNow,
		form = document.querySelector( 'form' ),
		saveButton = form.querySelector( '#save' ),
		sortables = document.querySelectorAll( '.control-section-sidebar' ),
		widgets = document.querySelectorAll( '.widget-rendered' );

	/**
	 * Trigger activation of Publish button
	 */
	function activatePublishButton() {
		saveButton.disabled = false;
		saveButton.value = _wpCustomizeControlsL10n.publish;
	}


	/**
	 * Keep widgets updated
	 */
	widgets.forEach( function( widget ) {
		widget.addEventListener( 'input', function() {
			widgetInputChange( widget );
		} );
		widget.addEventListener( 'change', function() {
			widgetInputChange( widget );
		} );

		// Enable closing of widget using Escape key
		widget.addEventListener( 'keyup', function( e ) {
			if ( widget.querySelector( '.widget-top' ).hasAttribute( 'open' ) && e.key === 'Escape' ) {
				widget.closest( '.widget-top' ).removeAttribute( 'open' );
				if ( widget.closest( 'ul' ).className.includes( 'reordering' ) ) {
					widget.closest( 'ul' ).querySelector( '.reorder-done' ).click();
				}
			}
		} );
	} );
	
	/**
	 * Send changes to widgets to the updatedControls object.
	 *
	 * @since CP-2.8.0
	 */
	function widgetInputChange( widget ) {
		var formDiv = widget.querySelector( '.form' ),
			idBase = widget.querySelector( '.id_base' ).value,
			widgetId = widget.querySelector( '.widget_id' ).value,
			url = ajaxurl + ( ajaxurl.indexOf( '?' ) === -1 ? '?' : '&' ) + 'wp_customize=on';

		// Enable the Publish/Save button
		activatePublishButton();

		// Debounced server call to get the WRAPPED instance.
		_.debounce( async function() {
			try {
				var formData = new FormData();

				formDiv.querySelectorAll( 'input[name], textarea[name], select[name]' ).forEach( function( field ) {
					if ( field.type === 'radio' && ! field.checked ) {
						return;
					}
					if ( field.type === 'checkbox' ) {
						if ( field.checked ) {
							formData.append( field.name, '1' );
						}
					} else {
						formData.append( field.name, field.value || '' );
					}
				} );

				// Required params
				formData.append( 'action', 'update-widget' );
				formData.append( 'nonce', document.getElementById( 'nonce' ).value );
				formData.append( 'id_base', idBase );
				formData.append( 'widget-id', widgetId );
				formData.append( 'customize_theme', document.getElementById( 'theme_stylesheet' ).value );
				formData.append( 'customize_changeset_uuid', document.getElementById( 'customize_changeset_uuid' ).value );

				const response = await fetch( url, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				} );
				const text = await response.text();

				let json = null;
				try {
					json = JSON.parse( text );
				}
				catch ( e ) {}

				if ( ! response.ok || ! json ) {
					console.error( 'update-widget raw response:', text );
					throw new Error( response.status );
				}

				if ( json && json.success && json.data && json.data.instance ) {
					updatedControls[ widget.dataset.settingId ] = json.data.instance;
				}
			} catch ( err ) {
				console.error( 'update-widget request failed:', err );
			}
		}, 250 )();
	}

	/*
	 * Attach listeners and SortableJS to active sidebars
	 */
	sortables.forEach( function( sortable ) {
		sortable.querySelectorAll( '.widget' ).forEach( function( widget ) {
			var title,
				input = widget.querySelector( 'input[id*="-title"]' );

			if ( input ) {
				title = input.value || '';
			}
			if ( title ) {
				title = ': ' + title.replace( /<[^<>]+>/g, '' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
				widget.querySelector( '.in-widget-title' ).innerHTML = title;
			}

			if ( widget.querySelector( 'p.widget-error' ) != null ) {
				widget.querySelector( 'details' ).setAttribute( 'open', 'open' );
			}
		} );

		Sortable.create( document.getElementById( sortable.id ), {
			group: 'active-widgets',
			handle: '.widget',
			filter: 'input, select, textarea, label, button, fieldset, legend, datalist, output, option, optgroup',
			preventOnFilter: false, // ensures correct position of cursor in input fields
			setData: ghostImage,
			onStart: sortableStart,
			onChange: sortableChange,
			onEnd: sortableEnd
		} );
	} );


	function ghostImage( dataTransfer, dragEl ) {
		var ghostImage = document.createElement( 'details' );
		ghostImage.id = 'sortable-ghost';
		ghostImage.className = 'widget-top';
		ghostImage.innerHTML = '<summary class="widget-title"><h3>' + dragEl.querySelector( 'h3' ).textContent + '</h3></summary>';
		ghostImage.style.position = 'absolute';
		ghostImage.style.top = '-1000px';
		ghostImage.style.width = dragEl.getBoundingClientRect().width + 'px';
		document.body.appendChild( ghostImage );
		dataTransfer.setDragImage( ghostImage, 30, 20 );
	}


	function sortableStart( e ) {
		if ( e.item.querySelector( 'details' ).hasAttribute( 'open' ) ) {
			e.item.querySelector( 'details' ).removeAttribute( 'open' );
		}
	}


	function sortableChange( e ) {
		var widgets = e.target.querySelectorAll( '.widget-rendered' );
		widgets.forEach( function( widget, index ) {
			widget.classList.remove( 'first-widget' );
			widget.classList.remove( 'last-widget' );
			if ( index === 0 ) {
				widget.classList.add( 'first-widget' );
			} else if ( index === widgets.length - 1 ) {
				widget.classList.add( 'last-widget' );
			}
		} );
	}


	function sortableEnd( e ) {
		var currentSidebars, newSidebars,
			sidebarId = e.target.dataset.id,
			settingId = 'sidebars_widgets[' + sidebarId + ']',
			widgets = e.target.querySelectorAll( '.widget-rendered' ),
			newWidgetOrder = Array.from( widgets ).map( el => el.querySelector( '.widget_id' ).value );

		currentSidebars = updatedControls[ 'sidebars_widgets' ];
		newSidebars = Object.assign( {}, currentSidebars );
		updatedControls[ settingId ] = newWidgetOrder.slice();
		activatePublishButton();
	}



	function addWidget( chooser ) {
		var widget, widgetId, add,
			sidebarId = chooser.querySelector( '.widgets-chooser-selected' ).dataset.sidebarId,
			sidebar = document.getElementById( sidebarId ),
			rect = sidebar.getBoundingClientRect(),
			offset = 130,
			scroll = false;

		widget = document.getElementById( 'available-widgets' ).querySelector( '.widget-in-question' ).cloneNode( true );
		widgetId = widget.id;
		add = widget.querySelector( 'input.add_new' ).value;

		// Remove the cloned chooser from the widget.
		widget.querySelector( '.widgets-chooser' ).remove();

		if ( 'multi' === add ) {
			widget.innerHTML = widget.innerHTML.replace( /<[^<>]+>/g, function( m ) {
				return m.replace( /__i__|%i%/g, newMultiValue );
			} );

			widget.id = widgetId.replace( '__i__', newMultiValue );
			widget.querySelector( 'input.multi_number' ).value = newMultiValue;
		} else if ( 'single' === add ) {
			widget.id = 'new-' + widgetId;
			document.getElementById( widgetId ).style.display = 'none';
		}

		// Open the sidebar and insert widget.
		sidebar.parentNode.setAttribute( 'open', 'open' );
		sidebar.append( widget );

		// Save widget
		saveWidget( widget, 0, 0, 1 );

		// No longer "new" widget.
		widget.querySelector( 'input.add_new' ).value = '';

		// Trigger event so that media, text, and HTML widgets get their fields
		document.dispatchEvent( new CustomEvent( 'widget-added', {
			detail: { widget: widget }
		} ) );

		/*
		 * Check if any part of the sidebar is visible in the viewport. If it is, don't scroll.
		 * Otherwise, scroll up to so the sidebar is in view.
		 *
		 * https://stackoverflow.com/questions/6215779/scroll-if-element-is-not-visible#answer-72866839
		 */
		if ( rect.top < offset ) {
			scroll = true;
		}
		if ( rect.top > window.innerHeight ) {
			scroll = true;
		}
		if ( scroll ) {
			window.scrollTo( {
				top: ( window.scrollY + rect.top ) - offset,
				behavior: 'smooth'
			} );
		}
	}

	/*
	 * Enable smooth scrolling up and down page when dragging item
	 *
	 * @since CP-2.0.0
	 */
	document.addEventListener( 'dragover', function( e ) {

		// How close (in pixels) to the edge of the screen before scrolling starts
		var scrollThreshold = 50, step = 15;

		if ( e.clientY < scrollThreshold ) {
			scrollStep( -step );
		}
		else if ( e.clientY > window.innerHeight - scrollThreshold ) {
			scrollStep( step );
		}

		function scrollStep( step ) {
			var scrollY = document.scrollingElement.scrollTop;
			window.scrollTo( {
				top: scrollY + step
			} );
		}
	} );


	// Widget toggled open or closed
	function widgetToggled( widget ) {
		var chooserButtons;

		// Open the chooser.
		if ( widget.hasAttribute( 'open' ) ) {

			// Add CSS class and insert the chooser at the end of the details element.
			clearWidgetSelection();
			chooser.style.display = 'block';
			chooser.removeAttribute( 'inert' ); // ensure that chooser is available
			widget.parentNode.classList.add( 'widget-in-question' );
			widget.append( chooser ); // append chooser to disclosure widget
			document.getElementById( 'widgets-left' ).classList.add( 'chooser' );

			chooserButtons = widget.querySelectorAll( '.widgets-chooser-button' );
			chooserButtons.forEach( function( choice ) {
				choice.addEventListener( 'click', function() {
					widget.querySelector( '.widgets-chooser-selected' ).classList.remove( 'widgets-chooser-selected' );
					chooserButtons.forEach( function( chosen ) {
						chosen.setAttribute( 'aria-pressed', 'false' );
					} );
					choice.setAttribute( 'aria-pressed', 'true' );
					choice.closest( 'li' ).classList.add( 'widgets-chooser-selected' );
				} );
			} );

		} else {
			document.getElementById( 'wpbody-content' ).append( chooser );
			chooser.style.display = 'none';
			document.getElementById( 'widgets-left' ).classList.remove( 'chooser' );
			clearWidgetSelection();
			widget.querySelector( 'summary' ).focus();
		}
	}

	/**
	 * Area Chooser
	 */
	document.querySelectorAll( '#widgets-right .widgets-holder-wrap' ).forEach( function( wrapper, index ) {
		var button = document.createElement( 'button' ),
			ariaLabel = wrapper.querySelector( '.sidebar-name' ).dataset.addTo,
			name = wrapper.querySelector( 'summary.sidebar-name' ).textContent || '',
			li = document.createElement( 'li' ),
			selectSidebar = chooser.querySelector( '.widgets-chooser-sidebars' ),
			id = wrapper.querySelector( '.widgets-sortables' ).id;

		button.type = 'button';
		button.className = 'widgets-chooser-button';
		button.setAttribute( 'aria-pressed', false );
		button.setAttribute( 'aria-label', ariaLabel );
		button.innerText = name.toString().trim();
		li.append( button );

		if ( index === 0 ) {
			li.classList.add( 'widgets-chooser-selected' );
			button.setAttribute( 'aria-pressed', 'true' );
		}
		selectSidebar.append( li );
		li.dataset.sidebarId = id;
	} );


	/**
	 * Add event handlers for buttons
	 */
	document.addEventListener( 'click', function( e ) {
		if ( e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' ) {

			// Add chooser
			if ( e.target.closest( 'ul' ) === document.getElementById( 'widget-list' ) ) {
				if ( e.target.className.includes( 'widgets-chooser-add' ) ) {
					addWidget( chooser );
					e.target.closest( '.widget-top' ).removeAttribute( 'open' );
				}

				// Close widget
				else if ( e.target.className.includes( 'widgets-chooser-cancel' ) ) {
					e.target.closest( '.widget-top' ).removeAttribute( 'open' );
				}
			}

			// Save widget
			else if ( e.target.className.includes( 'widget-control-save' ) ) {
				saveWidget( e.target.closest( 'li.widget' ), 0, 1, 0 );
				e.preventDefault();
			}

			// Remove widget
			else if ( e.target.className.includes( 'widget-control-remove' ) ) {

				// Check how many widgets there are on Inactive Widgets list before deleting one
				if ( e.target.closest( 'ul' ).id === 'wp_inactive_widgets' ) {
					if ( [ ...document.querySelectorAll( '#wp_inactive_widgets .widget' ) ].at(1) === undefined ) {
						removeButton.disabled = true;
					} else {
						removeButton.disabled = false;
					}
				}

				saveWidget( e.target.closest( 'li.widget' ), 1, 1, 0 );
			}

			// Close widget
			else if ( e.target.className.includes( 'widget-control-close' ) ) {
				e.target.closest( 'details' ).removeAttribute( 'open' );
				e.target.closest( 'details' ).querySelector( 'summary' ).focus();
			}
		}
	} );
} );
