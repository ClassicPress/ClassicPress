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
	var newMultiValue, timeNow, timeout,
		originalID = '',
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
	 * 
	 */
	function widgetInputChange( widget ) {
		var name, match, key, value,
			widgetData = {},
			formDiv = widget.querySelector( '.form' );

		formDiv.querySelectorAll( 'input[name], textarea[name], select[name]' ).forEach( function( field ) {
			name = field.name;
			if ( ! name ) {
				return;
			}

			match = name.match( /^widget-[^\[]+\[\d+\]\[(.+)\]$/ );
			if ( ! match ) {
				return;
			}

			key = match[1];

			if ( field.type === 'checkbox' || field.type === 'radio' ) {
				if ( ! field.checked ) {
					return;
				}
				value = field.value || 'on';
			} else {
				value = field.value || '';
			}

			widgetData[ key ] = value;
		} );

		activatePublishButton();
		_.debounce( function() {
			updatedControls[ widget.dataset.settingId ] = widgetData;
		}, 250 )();
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
			onChange: sortableChange
		} );
	} );

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
		var addNew, rem, sidebar, list, children, child, item, saveButton,
			widget = e.item;

		function unsavedWidget() {
			widget.classList.add( 'widget-dirty' );
			saveButton.disabled = false;
			saveButton.value = wp.i18n.__( 'Save' );
		}

		if ( widget.className.includes( 'deleting' ) ) {
			saveWidget( widget, 1, 0, 1 ); // delete widget
			widget.remove();
		} else {
			widget.removeAttribute( 'style' );
			widget.querySelector( '.widget-inside' ).removeAttribute( 'inert' );
			addNew = widget.querySelector( 'input.add_new' ).value;

			if ( addNew ) {
				if ( 'multi' === addNew ) {
					widget.innerHTML = widget.innerHTML.replace( /<[^<>]+>/g, function( tag ) {
						return tag.replace( /__i__|%i%/g, newMultiValue );
					} );

					widget.id = widget.id.replace( '__i__', newMultiValue );
					widget.querySelector( 'input.multi_number' ).value = newMultiValue;
				} else if ( 'single' === addNew ) {
					widget.id = 'new-' + widget.id;
					rem = 'li#' + widget.id;
				}

				saveWidget( widget, 0, 0, 1 );
				widget.querySelector( 'input.add_new' ).value = '';

				// Trigger event so that media, text, and HTML widgets get their fields
				document.dispatchEvent( new CustomEvent( 'widget-added', {
					detail: { widget: widget }
				} ) );

				// Save button is initially disabled, but is enabled when a field is changed.
				saveButton = widget.querySelector( '.widget-control-save' );
				saveButton.disabled = true;
				saveButton.value = wp.i18n.__( 'Saved' );

				if ( ! widget.className.includes( 'widget-dirty' ) ) {
					widget.addEventListener( 'input', unsavedWidget );
					widget.addEventListener( 'change', unsavedWidget );
				}
			} else {
				widget.innerHTML = widget.innerHTML.replace( /<[^<>]+>/g, function( tag ) {
					return tag.replace( /__i__|%i%/g, newMultiValue );
				} );

				widget.id = widget.id.replace( '__i__', newMultiValue );
				widget.querySelector( 'input.multi_number' ).value = newMultiValue;
				document.dispatchEvent( new CustomEvent( 'widget-updated', {
					detail: { widget: widget }
				} ) );

			}

			if ( addNew ) {
				widget.querySelector( 'details' ).setAttribute( 'open', 'open' );
			} else {
				sidebar = widget.closest( 'ul' );
				saveOrder( sidebar.id );
			}
		}
	}


	function saveWidget( widget, del, animate, order ) {
		var data,
			sidebarId = widget.closest( 'ul.widgets-sortables' ).id,
			form = widget.querySelector( 'form' ),
			isAdd = widget.querySelector( 'input.add_new' ).value;

		// Prevent duplicates
		if ( Number.isInteger( timeNow ) && timeNow + 50 > Date.now() ) {
			return false;
		}
		timeNow = Date.now();

		if ( ! isAdd || form.checkValidity ) {

			data = new URLSearchParams( new FormData( form ) );

			widget.querySelector( '.spinner' ).classList.add( 'is-active' );

			data.append( 'action', 'save-widget' );
			data.append( 'savewidgets', document.getElementById( '_wpnonce_widgets' ).value );
			data.append( 'sidebar', sidebarId );

			if ( del ) {
				data.append( 'delete_widget', 1 );
			}

			/*
			 * Prepare data for posting to database
			 */
			fetch( ajaxurl, {
				method: 'POST',
				headers: {
					'Accept': 'text/html',
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: data
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.text(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function( responseText ) {
				var id = widget.querySelector( 'input.widget-id' ).value;

				if ( del ) {
					if ( ! widget.querySelector( 'input.widget_number' ).value ) {
						document.getElementById( '#available-widgets' ).querySelectorAll( 'input.widget-id' ).forEach( function( widgetId ) {
							if ( widgetId.value === id ) {
								widgetId.closest( 'li.widget' ).style.display = 'block';
							}
						} );
					}

					if ( animate ) {
						order = 0;
						widget.removeAttribute( 'open' );
						widget.remove();
						saveOrder( sidebarId );
					} else {
						widget.remove();
					}
				} else {
					if ( responseText && responseText.length > 2 ) {
						widget.querySelector( '.widget-content' ).innerHTML = responseText;

						let title = widget.querySelector( 'input[id*="-title"]' ) ? widget.querySelector( 'input[id*="-title"]' ).value : '';
						if ( title ) {
							title = ': ' + title.replace( /<[^<>]+>/g, '' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
						}
						widget.querySelector( '.in-widget-title' ).innerHTML = title;

						widget.querySelector( '.widget-control-save' ).disabled = true;
						widget.querySelector( '.widget-control-save' ).value = wp.i18n.__( 'Saved' );

						widget.classList.remove( 'widget-dirty' );

						document.dispatchEvent( new CustomEvent('widget-updated', {
							detail: { widget: widget }
						}));
					}
					document.querySelectorAll( '.spinner' ).forEach( function( spinner ) {
						spinner.classList.remove( 'is-active' );
					} );
				}

				if ( order ) {
					saveOrder( sidebarId );
				}
			} )
			.catch( function( error ) {
				console.error( 'Error:', error );
			} );
		}
	}


	function saveOrder( sidebarId ) {
		var data = new URLSearchParams( {
			action: 'widgets-order',
			savewidgets: document.getElementById( '_wpnonce_widgets' ).value,
			sidebars: []
		} );

		if ( sidebarId ) {
			document.querySelectorAll( '.spinner' ).forEach( function( spinner ) {
				spinner.classList.remove( 'is-active' );
			} );
		}

		sortables.forEach( function( sortable ) {
			var widgetIds = [];
			sortable.querySelectorAll( '.widget' ).forEach( function( widget ) {
				widgetIds.push( widget.id );
			} );
			data.append( 'sidebars[' + sortable.id + ']', widgetIds.join( ',' ) );
		} );

		// Post the updated widget locations to the database
		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function() {
			document.querySelectorAll( '.spinner' ).forEach( function( spinner ) {
				spinner.classList.remove( 'is-active' );
			} );
		} )
		.catch( function( error ) {
			console.log( error );
		} );
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
} );
