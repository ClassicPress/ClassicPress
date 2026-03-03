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
	 * Keep widgets updated
	 *
	 * @since CP-2.8.0
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
		var response, text,
			json = null,
			formData = new FormData(),
			formDiv = widget.querySelector( '.form' ),
			idBase = widget.querySelector( '.id_base' ).value,
			widgetId = widget.querySelector( '.widget_id' ).value,
			url = ajaxurl + ( ajaxurl.indexOf( '?' ) === -1 ? '?' : '&' ) + 'wp_customize=on';

		// Debounced server call to get the WRAPPED instance.
		_.debounce( async function() {
			try {
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

				// Get data sanitized by PHP handler
				response = await fetch( url, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				} );
				text = await response.text();
				try {
					json = JSON.parse( text );
				}
				catch ( e ) {}

				if ( ! response.ok || ! json ) {
					console.error( 'update-widget raw response:', text );
					throw new Error( response.status );
				}

				if ( json && json.success && json.data && json.data.instance ) {

					// Add sanitized data to the updatedControls object
					updatedControls[ widget.dataset.settingId ] = json.data.instance;

					// Enable the Publish/Save button
					activatePublishButton();
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
			newWidgetOrder = Array.from( e.target.querySelectorAll( '.widget-id' ) ).map( input => input.value );

		currentSidebars = updatedControls[ 'sidebars_widgets' ];
		newSidebars = Object.assign( {}, currentSidebars );
		updatedControls[ settingId ] = newWidgetOrder.slice();
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
			updatedControls[ sidebarKey ] = updatedControls[ sidebarKey ].filter( id => id !== widgetId );
		} else {
			// Sidebar hasn't been touched yet this session — build the array from the DOM first, then filter
			updatedControls[ sidebarKey ] = Array.from( sidebar.querySelectorAll( '.widget-id' ) ).map( input => input.value ).filter( id => id !== widgetId );
		}

		// Remove widget key from updatedControls entirely if it was pending
		delete updatedControls[ widgetKey ];

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
			clone.querySelector( 'summary' ).insertAdjacentHTML( 'beforeend',  _wpCustomizeWidgetsSettings.tpl.widgetReorderNav );
			buttons.previousElementSibling.classList.remove( 'last-widget' );
			buttons.before( clone );

			// Set variables for this sidebar and the widget instance
			sidebarId  = ul.dataset.id;
			sidebarKey = 'sidebars_widgets[' + sidebarId + ']';
			widgetKey  = 'widget_' + idBase + '[' + multiNumber + ']';

			// Collect all widget instance IDs in this section in order and push them into updatedControls object
			allIds = Array.from( ul.querySelectorAll( '.widget-id' ) ).map( input => input.value );
			updatedControls[sidebarKey] = allIds;

			// Create the instance’s empty setting object (to be filled by PHP back-end on save)
			if ( ! updatedControls[widgetKey] ) {
				updatedControls[widgetKey] = {};
			}

			// Enable Save/Publish button
			activatePublishButton();
		} );
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

			// Remove widget
			else if ( e.target.className.includes( 'widget-control-remove' ) ) {
				e.preventDefault();
				deleteWidget( e.target.closest( '.customize-control-widget_form' ) );
			}

			// Close widget
			else if ( e.target.className.includes( 'widget-control-close' ) ) {
				e.target.closest( 'details' ).removeAttribute( 'open' );
				e.target.closest( 'details' ).querySelector( 'summary' ).focus();
			}
		}
	} );
} );
