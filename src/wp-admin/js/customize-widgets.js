/**
 * @since CP-2.8.0
 * @requires SortableJS
 *
 * @output wp-admin/js/widgets.js
 */
/* global Sortable, ajaxurl, console, _wpCustomizeWidgetsSettings,
 * updatedControls, _updatedControlsWatcher, _wpCustomizeControlsL10n */
document.addEventListener( 'DOMContentLoaded', function() {

	// Set variables for the whole file
	var form = document.querySelector( 'form' ),
		saveButton = form.querySelector( '#save' ),
		sortables = document.querySelectorAll( '.control-section-sidebar' ),
		widgets = document.querySelectorAll( '.widget-rendered' ),
		availables = document.querySelectorAll( '#available-widgets-list > li' );

	/**
	 * Trigger activation of Publish button
	 *
	 * @since CP-2.8.0
	 */
	function activatePublishButton() {
		saveButton.disabled = false;
		saveButton.value = _wpCustomizeControlsL10n.publish;
	}

	/**
	 * Bind listeners to a newly-added widget.
	 *
	 * @since CP-2.8.0
	 */
	function bindWidget( widget ) {
		if ( ! widget || widget.dataset._bound === '1') {
			return;
		}
		widget.dataset._bound = '1';

		widget.addEventListener( 'input', function() {
			widgetInputChange( widget );
		}, true ); // capture

		widget.addEventListener( 'change', function() {
			widgetInputChange( widget );
		}, true );
	}

	/**
	 * Keep widgets updated
	 *
	 * @since CP-2.8.0
	 */
	widgets.forEach( function( widget ) {
		widget.dataset._bound = '1'; // prevents double-binding if bindWidget() is called

		widget.addEventListener( 'input', function() {
			widgetInputChange( widget );
		}, true ); // capture
		widget.addEventListener( 'change', function() {
			widgetInputChange( widget );
		}, true );

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
		var formData = new FormData(),
			formDiv = widget.querySelector( '.form' ),
			fields = formDiv.querySelectorAll( 'input, select, textarea' ),
			idBase = widget.querySelector( '.id_base' ).value,
			widgetId = widget.querySelector( '.widget-id' ).value,
			url = ajaxurl + ( ajaxurl.indexOf( '?' ) === -1 ? '?' : '&' ) + 'wp_customize=on';

		// Required params
		formData.append( 'action', 'update-widget' );
		formData.append( 'nonce', document.getElementById( 'nonce' ).value );
		formData.append( 'id_base', idBase );
		formData.append( 'widget-id', widgetId );
		formData.append( 'customize_theme', document.getElementById( 'theme_stylesheet' ).value );
		formData.append( 'customize_changeset_uuid', document.getElementById( 'customize_changeset_uuid' ).value );

		// Serialize ALL fields under .form like a real form submit.
		fields.forEach( function( field ) {
			if ( ! field.name ) {
				return;
			}

			// Radios: only include the checked one
			if ( field.type === 'radio' ) {
				if ( field.checked ) {
					formData.append( field.name, field.value );
				}
				return;
			}

			// Checkboxes: include only when checked (browser behavior)
			if ( field.type === 'checkbox' ) {
				if ( field.checked ) {
					formData.append( field.name, field.value || '1' );
				}
				return;
			}

			// Multi-selects: include each selected option
			if ( field.tagName === 'SELECT' && field.multiple ) {
				Array.from( field.selectedOptions ).forEach( function( opt ) {
					formData.append( field.name, opt.value );
				} );
				return;
			}

			// Everything else: append value (empty string allowed)
			formData.append( field.name, field.value || '' );
		} );

		// Make the fetch request
		fetch( url, {
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
			if ( result && result.success && result.data && result.data.instance ) {

				// Add sanitized data to the updatedControls object
				_updatedControlsWatcher[ widget.dataset.settingId ] = result.data.instance;

				// sendCustomizedToPreview is called by the proxy set handler above,
				// but call it again explicitly to ensure the full blob is sent
				// only after the instance value is confirmed in _cpDirtySettings.
				window.sendCustomizedToPreview();

				// Enable the Publish/Save button
				activatePublishButton();
			}
		} )
		.catch( function( error ) {
			console.error( _wpCustomizeWidgetsSettings.l10n.error, error );
		} );
	}

	/**
	 * Attach listeners and SortableJS to active sidebars
	 *
	 * @since CP-2.1.0
	 */
	sortables.forEach( function( sortable ) {
		sortable.querySelectorAll( '.widget' ).forEach( function( widget ) {
			var title,
				input = widget.querySelector( 'input[id*="-title"]' );

			if ( input ) {
				title = input.value || '';
			}
			if ( title ) {
				widget.querySelector( '.in-widget-title' ).textContent = title;
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
		var currentSidebars,
			sidebarId = e.target.dataset.id,
			settingId = 'sidebars_widgets[' + sidebarId + ']',
			newWidgetOrder = Array.from( e.target.querySelectorAll( '.widget-id' ) ).map( input => input.value );

		currentSidebars = updatedControls.sidebars_widgets;
		_updatedControlsWatcher[ settingId ] = newWidgetOrder.slice();
		activatePublishButton();
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

	/**
	 * Delete widget
	 *
	 * @since CP_2.8.0
	 */
	function deleteWidget( widget ) {
		const widgetId = widget.querySelector( '.widget-id' ).value,
			idBase = widgetId.split( '-' )[0],
			number = widgetId.split( '-' ).pop(),
			sidebar = widget.closest( 'ul' ),
			sidebarId = sidebar.dataset.id,
			sidebarKey = 'sidebars_widgets[' + sidebarId + ']',
			widgetKey = 'widget_' + idBase + '[' + number + ']';

		// Remove from sidebar array
		if ( updatedControls[ sidebarKey ] ) {
			_updatedControlsWatcher[ sidebarKey ] = updatedControls[ sidebarKey ].filter( id => id !== widgetId );
		} else {
			// Sidebar hasn't been touched yet this session — build the array from the DOM first, then filter
			_updatedControlsWatcher[ sidebarKey ] = Array.from( sidebar.querySelectorAll( '.widget-id' ) ).map( input => input.value ).filter( id => id !== widgetId );
		}

		// Remove widget key from updatedControls entirely if it was pending
		delete updatedControls[ widgetKey ];
		delete window._cpPreviewSettings[ window.toPreviewSettingId( widgetKey ) ];

		// Animate out and remove from DOM
		widget.style.transition = 'opacity 0.3s';
		widget.style.opacity = '0';
		setTimeout( function() {
			widget.remove();

			// Re-manage last-widget class on remaining widgets
			const remaining = sidebar.querySelectorAll( '.customize-control-widget_form' );
			if ( remaining.length ) {
				remaining[ remaining.length - 1 ].classList.add( 'last-widget' );
			}

			activatePublishButton();
		}, 300 );
	}

	/**
	 * Reassess the positioning of widgets in a sidebar after a widget has moved
	 *
	 * @since CP-2.8.0
	 */
	function reassessPositioning( sidebar ) {
		var	currentSidebars,
			sidebarId = sidebar.dataset.id,
			settingId = 'sidebars_widgets[' + sidebarId + ']',
			newWidgetOrder = Array.from( sidebar.querySelectorAll( '.widget-id' ) ).map( input => input.value ),
			widgets = sidebar.querySelectorAll( '.widget-rendered' );

		widgets.forEach( function( widget, index ) {
			widget.classList.remove( 'first-widget' );
			widget.classList.remove( 'last-widget' );
			if ( index === 0 ) {
				widget.classList.add( 'first-widget' );
			} else if ( index === widgets.length - 1 ) {
				widget.classList.add( 'last-widget' );
			}
		} );

		currentSidebars = updatedControls.sidebars_widgets;
		_updatedControlsWatcher[ settingId ] = newWidgetOrder.slice();
		activatePublishButton();
	}

	/**
	 * Add widget to sidebar from available widget list
	 *
	 * @since CP-2.8.0
	 */
	availables.forEach( function( widget ) {
		widget.addEventListener( 'click', function() {
			let idBase, sidebarId, sidebarKey, widgetKey, allIds, multiNumber;

			const clone = widget.cloneNode( true ),
				widgetId = widget.dataset.widgetId,
				ul = form.querySelector( '.control-section-sidebar[style*="display: block"]' ), // visible sidebar ul
				buttons = ul.querySelector( '.customize-control-sidebar_widgets.no-drag' );

			if ( ! widgetId || ! ul || ! buttons ) {
				return;
			}

			multiNumber = widgetId.split( '-' ).pop();
			idBase = widgetId.split( '-' + multiNumber )[0];
			form.querySelectorAll( '.id_base[value="' + idBase + '"]' ).forEach( function( base ) {
				let num = parseInt( base.parentNode.querySelector( '.widget_number' ).value, 10 );
				if ( ! isNaN( num ) && num >= parseInt( multiNumber, 10 ) ) {
					multiNumber = num + 1;
				} else {
					multiNumber = parseInt( multiNumber, 10 ) + 1;
				}
			} );

			// Replace all __i__ placeholders with the actual number
			clone.id = 'customize-control-widget-' + idBase + '-' + multiNumber;
			clone.querySelectorAll( '*' ).forEach( function( el ) {
				if ( el.id ) {
					el.id = el.id.replace( /__i__/g, multiNumber );
				}
				if ( el.name ) {
					el.name = el.name.replace( /__i__/g, multiNumber );
				}
				if ( el.htmlFor ) {
					el.htmlFor = el.htmlFor.replace( /__i__/g, multiNumber );
				}
			} );

			// Assign values to hidden inputs
			clone.querySelector( '.widget-id' ).value = idBase + '-' + multiNumber;
			clone.querySelector( '.widget_number' ).value = multiNumber;
			clone.querySelector( '.multi_number' ).value = multiNumber;
			clone.querySelector( '.add_new' ).value = 'multi';

			// Update clone's properties and attributes
			clone.id = clone.id.replace( 'widget-tpl', 'customize-control-widget' );
			clone.className = 'customize-control customize-control-widget_form last-widget widget-rendered';
			clone.dataset.settingId = 'widget_' + idBase + '[' + multiNumber + ']';
			clone.removeAttribute( 'tabindex' );
			clone.removeAttribute( 'data-widget-id' );
			clone.querySelector( '.widget-top' ).name = ul.dataset.id;
			clone.querySelector( 'summary' ).insertAdjacentHTML( 'beforeend', _wpCustomizeWidgetsSettings.tpl.widgetReorderNav );
			clone.querySelectorAll( '.widget-reorder-nav span' ).forEach( function( span ) {
				span.insertAdjacentHTML( 'afterbegin', clone.querySelector( '.widget-title h3' ).textContent + ':' );
			} );
			buttons.previousElementSibling.classList.remove( 'last-widget' );
			buttons.before( clone );

			// Set variables for this sidebar and the widget instance
			sidebarId  = ul.dataset.id;
			sidebarKey = 'sidebars_widgets[' + sidebarId + ']';
			widgetKey  = 'widget_' + idBase + '[' + multiNumber + ']';

			// Collect all widget instance IDs in this section in order and push them into updatedControls object
			allIds = Array.from( ul.querySelectorAll( '.widget-id' ) ).map( input => input.value );
			_updatedControlsWatcher[sidebarKey] = allIds;

			// Create the instance’s empty setting object (to be filled by PHP back-end on save)
			if ( ! updatedControls[widgetKey] ) {
				window.updatedControls[widgetKey] = {};  // bypasses proxy, no postMessage
			}

			// Enable Save/Publish button
			activatePublishButton();

			// Bind listeners to the newly-added widget
			bindWidget( clone );
		} );
	} );

	/**
	 * Search for widgets
	 *
	 * @since CP-2.8.0
	 */
	document.getElementById( 'widgets-search' ).addEventListener( 'input', _.debounce( function( e ) {
		var message,
			needle = e.target.value.toLowerCase().trim(),
			matches = needle ? [...availables].filter( item => item.querySelector( '.widget-title h3' ).textContent.toLowerCase().includes( needle ) ) : [];

		if ( needle.length ) {
			document.querySelector( '.clear-results' ).classList.add( 'is-visible' );
			availables.forEach( function( li ) {
				li.style.display = 'none';
			} );

			if ( matches.length ) {
				matches.forEach( function( li ) {
					li.style.display = '';
				} );
				message = _wpCustomizeWidgetsSettings.l10n.widgetsFound.replace( '%d', matches.length );
				document.getElementById( 'available-widgets' ).classList.remove( 'no-widgets-found' );
			} else {
				message = _wpCustomizeWidgetsSettings.l10n.noWidgetsFound;
				document.getElementById( 'available-widgets' ).classList.add( 'no-widgets-found' );
			}
			wp.a11y.speak( message );
		} else {
			document.querySelector( '.clear-results' ).classList.remove( 'is-visible' );
			availables.forEach( function( li ) {
				li.style.display = '';
			} );
		}
	}, 150 ) );


	/**
	 * Add event handlers for buttons
	 *
	 * @since CP-2.8.0
	 */
	document.addEventListener( 'click', function( e ) {
		var template, clone, oldSidebar, newSidebarId, newSidebar, widgetTitles,
			widget = e.target.closest( '.customize-control-widget_form' );

		// Clear widget search string
		if ( e.target.classList.contains( 'clear-results' ) ) {
			document.getElementById( 'widgets-search' ).value = '';
			e.target.classList.remove( 'is-visible' );
			document.getElementById( 'available-widgets' ).classList.remove( 'no-widgets-found' );
			availables.forEach( function( li ) {
				li.style.display = '';
			} );

		// Remove widget
		} else if ( e.target.className.includes( 'widget-control-remove' ) ) {
			e.preventDefault();
			deleteWidget( widget );

		// Close widget
		} else if ( e.target.className.includes( 'widget-control-close' ) ) {
			e.target.closest( 'details' ).removeAttribute( 'open' );
			e.target.closest( 'details' ).querySelector( 'summary' ).focus();

		// Enable reordering
		} else if ( e.target.className === 'reorder' ) {
			e.target.parentNode.setAttribute( 'aria-label', _wpCustomizeWidgetsSettings.l10n.reorderLabelOn );
			wp.a11y.speak( _wpCustomizeWidgetsSettings.l10n.reorderModeOn );

			// Hide widget titles while reordering: title is already in the reorder controls.
			widgetTitles = e.target.closest( 'ul' ).querySelectorAll( '.customize-control-widget_form .widget-title' );
			widgetTitles.forEach( function( title ) {
				title.setAttribute( 'aria-hidden', 'true' );
			} );

		// Reorder widget or move to another sidebar
		} else if ( e.target.parentNode.classList.contains( 'widget-reorder-nav' ) ) {
			e.preventDefault();
			e.stopPropagation();
			e.target.closest( 'ul' ).querySelectorAll( 'details' ).forEach( function( details ) {
				if ( details.hasAttribute( 'open' ) ) {
					details.removeAttribute( 'open' );
				}
			} );
			if ( e.target.className === 'move-widget' ) {
				if ( document.getElementById( 'move-widget-area' ) ) { // check if already in use
					widget.append( document.getElementById( 'move-widget-area' ) );
				} else { // clone the template for first use
					template = document.getElementById( 'tmpl-change-sidebar' );
					clone = template.content.cloneNode( true );
					widget.append( clone );
				}

				// Mark current sidebar as selected
				document.getElementById( 'move-widget-area' ).querySelectorAll( 'li' ).forEach( function( li ) {
					if ( li.dataset.id === widget.parentNode.dataset.id ) {
						li.className = 'selected';
					} else {
						li.className = '';
					}
				} );
			} else {
				if ( e.target.className.includes( 'move-widget-down' ) ) {
					widget.nextElementSibling.after( widget );
					wp.a11y.speak( _wpCustomizeWidgetsSettings.l10n.widgetMovedDown );
				} else if ( e.target.className.includes( 'move-widget-up' ) ) {
					widget.previousElementSibling.before( widget );
					wp.a11y.speak( _wpCustomizeWidgetsSettings.l10n.widgetMovedUp );
				}
				reassessPositioning( widget.parentNode );
			}

		// Enable selection of different sidebar
		} else if ( widget?.querySelector( '.widget-area-select' )?.contains( e.target ) ) {
			widget.querySelector( '.widget-area-select .selected' ).classList.remove( 'selected' );
			e.target.closest( 'li' ).className = 'selected';
			if ( e.target.closest( 'li' ).dataset.id === widget.parentNode.dataset.id ) {
				widget.querySelector( '.move-widget-btn' ).disabled = true;
			} else {
				widget.querySelector( '.move-widget-btn' ).disabled = false;
			}

		// Finish reordering
		} else if ( e.target.className === 'reorder-done' ) {
			document.getElementById( 'move-widget-area' )?.remove();
			e.target.parentNode.setAttribute( 'aria-label', _wpCustomizeWidgetsSettings.l10n.reorderLabelOff );
			wp.a11y.speak( _wpCustomizeWidgetsSettings.l10n.reorderModeOff );
			widgetTitles = e.target.closest( 'ul' ).querySelectorAll( '.customize-control-widget_form .widget-title' );
			widgetTitles.forEach( function( title ) {
				title.setAttribute( 'aria-hidden', 'false' );
			} );

		// Finish moving widget to different sidebar
		} else if ( e.target.className === 'move-widget-btn button' ) {
			oldSidebar = widget.parentNode;
			newSidebarId = document.getElementById( 'move-widget-area' ).querySelector( '.selected' ).dataset.id;
			newSidebar = document.getElementById( 'sub-accordion-section-sidebar-widgets-' + newSidebarId );
			newSidebar.querySelector( '.customize-control-sidebar_widgets' ).before( widget );
			reassessPositioning( oldSidebar );
			reassessPositioning( newSidebar );
			document.getElementById( 'move-widget-area' ).remove();
		}
	} );
} );
