/**
 * @since CP-2.1.0
 * @requires SortableJS
 *
 * @output wp-admin/js/widgets.js
 */

/* global Sortable, ajaxurl, console */
document.addEventListener( 'DOMContentLoaded', function() {

	// Set variables for the whole file
	var newMultiValue,
		widgetList = document.getElementById( 'widget-list' ),
		sortables = document.querySelectorAll( '.widgets-sortables' ),
		sidebarWrappers = document.querySelectorAll( '.widgets-holder-wrap' ),
		widgets = document.querySelectorAll( '.widget' ),
		chooser = document.querySelector( '.widgets-chooser' ); // Refresh the widgets containers in the right column.;

	// Set first active Sidebar and Inactive Sidebar to open.
	document.querySelector( '#widgets-right details' ).setAttribute( 'open', 'open' );
	document.querySelector( '.inactive-sidebar .sidebar-name' ).parentElement.setAttribute( 'open', 'open' );

	// Hide elements on the Inactive Sidebar.
	document.querySelector( '.inactive-sidebar .sidebar-name' ).addEventListener( 'click', function( e ) {
		e.target.parentElement.nextElementSibling.classList.toggle( 'hidden' );
		e.target.closest( '.widget-holder' ).nextElementSibling.classList.toggle( 'hidden' );
	} );

	// Update the admin menu "sticky" state
	document.querySelector( '#widgets-left .sidebar-name' ).addEventListener( 'click', function() {
		document.dispatchEvent( new CustomEvent( 'wp-pin-menu' ) );
	} );

	document.querySelectorAll( '#widgets-right summary' ).forEach( function( summary ) {
		summary.addEventListener( 'click', function() {
			document.dispatchEvent( new CustomEvent( 'wp-pin-menu' ) );
		} );
	} );


	/*
	 * Show AYS dialog when there are unsaved widget changes.
	 *
	 * Note that previous code inherited from WordPress was obsolete.
	 *
	 * Browsers no longer permit the display of a custom message.
	 */
	window.addEventListener( 'beforeunload', function( e ) {
		var firstUnsaved,
			unsavedAll = document.querySelectorAll( '.widget-dirty' );

		if ( unsavedAll.length > 0 ) {
			e.preventDefault();

			unsavedAll.forEach( function( unsaved, index ) {
				var details = unsaved.querySelector( 'details' );

				if ( ! details.hasAttribute( 'open' ) ) {
					details.setAttribute( 'open', 'open' );
				}

				// Bring the first unsaved widget into view and focus on the first tabbable field
				if ( index === 0 ) {
					firstUnsaved = unsaved.querySelector( 'input' ) ? unsaved.querySelector( 'input' ) : unsaved.querySelector( 'textarea' );
					if ( firstUnsaved == null ) { // catches undefined too
						firstUnsaved = unsaved.querySelector( 'summary' );
					}

					if ( firstUnsaved.scrollIntoViewIfNeeded ) {
						firstUnsaved.scrollIntoViewIfNeeded();
					} else {
						firstUnsaved.scrollIntoView();
					}

					setTimeout( function() {
						firstUnsaved.focus();
					}, 0 );
				}
			} );
		}
	} );


	/*
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


	/*
	 * Add event handlers for buttons
	 */
	document.addEventListener( 'click', function( e ) {
		var removeButton = document.getElementById( 'inactive-widgets-control-remove' );

		// Remove all inactive widgets
		if ( e.target === removeButton ) {
			removeInactiveWidgets();
			removeButton.disabled = true;
		}

		// If last inactive widget deleted
		else if ( e.target.closest( 'ul' ) == null ) { // catches undefined too
			removeButton.disabled = true;
		}

		else {

			// Add chooser
			if ( e.target.closest( 'ul' ).id === 'widget-list' ) {
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
	 * Keep widgets updated
	 */
	widgets.forEach( function( widget ) {
		var saveButton;

		// Save button is initially disabled, but is enabled when a field is changed.
		if ( widget.closest( 'ul' ).id !== 'widget-list' ) {
			saveButton = widget.querySelector( '.widget-control-save' );
			saveButton.disabled = true;
			saveButton.value = wp.i18n.__( 'Saved' );

			if ( ! widget.className.includes( 'widget-dirty' ) ) {
				widget.addEventListener( 'input', unsavedWidget );
				widget.addEventListener( 'change', unsavedWidget );
			}
		}

		// Enable closing of widget using Escape key
		widget.addEventListener( 'keyup', function( e ) {
			if ( e.target.closest( '.widget-top' ).hasAttribute( 'open' ) && e.key === 'Escape' ) {
				e.target.closest( '.widget-top' ).removeAttribute( 'open' );
				document.querySelector( '.chooser' ).classList.remove( 'chooser' );
			}
		} );

		function unsavedWidget() {
			widget.classList.add( 'widget-dirty' );
			saveButton.disabled = false;
			saveButton.value = wp.i18n.__( 'Save' );
		}
	} );


	// Add sidebar chooser on the widgets screen but not the Customizer.
	if ( document.querySelector( 'body' ).className.includes( 'widgets-php' ) ) {
		document.addEventListener( 'click', function( e ) {
			var widget = e.target.closest( 'details' );
			if ( widgetList.contains( e.target ) && widget.className.includes( 'widget-top' ) ) {
				widget.addEventListener( 'toggle', function() {
					widgetToggled( widget );
				} );
			}
		} );
	}

	/*
	 * Attach SortableJS to Available Widgets sidebar
	 */
	Sortable.create( widgetList, {
		group: {
			name: 'widget-list',
			pull: 'clone',
			put: false
		},
		sort: false,
		setData: ghostImage,
		forceFallback: navigator.vendor.match(/apple/i) ? true : false, // forces fallback for webkit browsers
		//forceFallback: 'GestureEvent' in window ? true : false, // forces fallback for Safari
		onChoose: function( e ) {
			var multi;
			if ( e.item.className.includes( 'widget' ) ) {
				multi = e.item.querySelector( 'input.multi_number' );
				multi.value = newMultiValue = parseInt( multi.value, 10 ) + 1;
			}
		},
		onClone: function( e ) {
			var widget = e.clone.querySelector( 'details' );
			if ( widget.hasAttribute( 'open' ) ) {
				widget.removeAttribute( 'open' );
			}
			widgetToggled( widget );
		}
	} );


	/*
	 * Attach listeners and SortableJS to active sidebars
	 */
	sortables.forEach( function( sortable ) {

		sortable.addEventListener( 'dragover', function( e ) {
			e.preventDefault();
			sortable.setAttribute( 'data-dragover', true );
		} );

		sortable.addEventListener( 'dragleave', function() {
			sortable.removeAttribute( 'data-dragover' );
		} );

		sortable.querySelectorAll( '.widget' ).forEach( function( widget ) {
			var title = widget.querySelector( 'input[id*="-title"]' ).value || '';
			if ( title ) {
				title = ': ' + title.replace( /<[^<>]+>/g, '' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}
			widget.querySelector( '.in-widget-title' ).innerHTML = title;

			if ( widget.querySelector( 'p.widget-error' ) != null ) {
				widget.querySelector( 'details' ).setAttribute( 'open', 'open' );
			}
		} );

		Sortable.create( document.getElementById( sortable.id ), {
			group: {
				name: 'active-widgets',
				put: ['widget-list', 'active-widgets', 'inactive']
			},
			handle: '.widget',
			filter: 'input, select, textarea, label, button, fieldset, legend, datalist, output, option, optgroup',
			preventOnFilter: false, // ensures correct position of cursor in input fields
			setData: ghostImage,
			forceFallback: navigator.vendor.match(/apple/i) ? true : false, // forces fallback for webkit browsers
			//forceFallback: 'GestureEvent' in window ? true : false, // forces fallback for Safari
			onStart: sortableStart,
			onChange: sortableChange
		} );
	} );


	/*
	 * Attach SortableJS to Inactive Widgets sidebar
	 */
	Sortable.create( document.getElementById( 'wp_inactive_widgets' ), {
		group: {
			name: 'inactive',
			put: ['widget-list', 'active-widgets']
		},
		handle: '.widget',
		filter: 'input, select, textarea, label, button, fieldset, legend, datalist, output, option, optgroup',
		preventOnFilter: false, // ensures correct position of cursor in input fields
		setData: ghostImage,
		forceFallback: navigator.vendor.match(/apple/i) ? true : false, // forces fallback for webkit browsers
		//forceFallback: 'GestureEvent' in window ? true : false, // forces fallback for Safari
		onStart: sortableStart,
		onChange: sortableChange,
		onAdd: function() {
			document.getElementById( 'inactive-widgets-control-remove' ).disabled = false;
		}
	} );


	/**
	 * Opens and closes previously closed Sidebars when Widgets are dragged over or out of them.
	 */
	sidebarWrappers.forEach( function( wrapper ) {
		var details = wrapper.querySelector( 'details' ),
			original = 'open'; // original state of child sidebar sortable area

		wrapper.addEventListener( 'dragover', function( e ) {
			e.preventDefault();
			if ( ! details.hasAttribute( 'open' ) ) {
				original = 'closed';
				details.setAttribute( 'open', 'open' );
			}
		} );

		// Treat dragging as having left only if it has also left child sidebar sortable area
		wrapper.addEventListener( 'dragleave', function() {
			setTimeout( function() {
				if ( details.hasAttribute( 'open' ) && original === 'closed' && ! wrapper.querySelector( '.widgets-sortables' ).hasAttribute( 'data-dragover' ) ) {
					details.removeAttribute( 'open' );
				}
			}, 1000 ); // allow time for drag to move between wrapper and child sidebar sortable area
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
			}

			list = widget.closest( 'details' );
			sidebar = widget.parentNode;

			if ( ! list.hasAttribute('open') ) {
				list.setAttribute( 'open', 'open' );

				children = list.querySelectorAll( '.widget' );

				// Make sure the dropped widget is at the top.
				if ( children.length > 1 ) {
					child = children[0];
					item = widget[0];

					if ( child.id && item.id && child.id !== item.id ) {
						child.before( widget );
					}
				}
			}

			if ( addNew ) {
				widget.querySelector( 'details' ).setAttribute( 'open', 'open' );
			} else {
				saveOrder( sidebar.id );
			}
		}

		// If the last widget was moved out of an orphaned sidebar, close and remove it.
		if ( e.from.id.indexOf( 'orphaned_widgets' ) > -1 && ! e.from.querySelector( '.widget' ).length ) {
			e.from.closest( 'details' ).removeAttribute( 'open' );
			e.from.remove();
		}
	}


	function saveWidget( widget, del, animate, order ) {
		var data, xhr, id,
			sidebarId = widget.closest( 'ul.widgets-sortables' ).id,
			form = widget.querySelector( 'form' ),
			isAdd = widget.querySelector( 'input.add_new' ).value;

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
			 *
			 * Note that using fetch API causes a 400 error
			 */
			xhr = new XMLHttpRequest();
			xhr.open( 'POST', ajaxurl, true );

			// Send the proper header information along with the request
			xhr.setRequestHeader( 'Accept', 'text/html' );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

			xhr.onreadystatechange = function() {

				// Call a function when the state changes.
				if ( xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200 ) {

					// Request finished. Do processing here.
					id = widget.querySelector( 'input.widget-id' ).value;

					if ( del ) {
						if ( ! widget.querySelector( 'input.widget_number' ).value ) {
							document.getElementById('#available-widgets').querySelectorAll( 'input.widget-id' ).forEach( function( widgetId ) {
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
						if ( xhr.response && xhr.response.length > 2 ) {
							widget.querySelector( '.widget-content' ).innerHTML = xhr.response;

							var title = widget.querySelector( 'input[id*="-title"]' ).value || '';
							if ( title ) {
								title = ': ' + title.replace( /<[^<>]+>/g, '' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
							}
							widget.querySelector( '.in-widget-title' ).innerHTML = title;

							// Re-disable the save button.
							widget.querySelector( '.widget-control-save' ).disabled = true;
							widget.querySelector( '.widget-control-save' ).value = wp.i18n.__( 'Saved' );

							widget.classList.remove( 'widget-dirty' );

							// Trigger event so that media, text, and HTML widgets get their fields
							document.dispatchEvent( new CustomEvent( 'widget-updated', {
								detail: { widget: widget }
							} ) );
						}
						document.querySelectorAll( '.spinner' ).forEach( function( spinner ) {
							spinner.classList.remove( 'is-active' );
						} );
					}

					if ( order ) {
						saveOrder( sidebarId );
					}
				}
			};

			// Post data to database.
			xhr.send( data );
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


	function removeInactiveWidgets() {
		var data, xhr;

		document.querySelector( '.remove-inactive-widgets' ).querySelector( '.spinner' ).classList.add( 'is-active' );

		data = new URLSearchParams( {
			action : 'delete-inactive-widgets',
			removeinactivewidgets : document.getElementById( '_wpnonce_remove_inactive_widgets' ).value
		} );

		/*
		 * Prepare to make call to database.
		 *
		 * Note that using fetch API triggers a bug in Firefox.
		 * https://bugzilla.mozilla.org/show_bug.cgi?id=1280189
		 */
		xhr = new XMLHttpRequest();
		xhr.open( 'POST', ajaxurl, true );

		// Send the appropriate header information along with the request
		xhr.setRequestHeader( 'Accept', 'text/html' );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );

		xhr.onreadystatechange = function() {

			// Call a function when the state changes
			if ( xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200 ) {

				// Remove widget from screen
				document.getElementById( 'wp_inactive_widgets' ).querySelectorAll( 'li.widget' ).forEach( function( widget ) {
					widget.remove();
				} );

				// Hide spinner
				document.querySelectorAll( '.spinner' ).forEach( function( spinner ) {
					spinner.classList.remove( 'is-active' );
				} );
			}
		};

		xhr.send( data );
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
			document.getElementById( widgetId ).querySelector( 'input.multi_number' ).value = newMultiValue;
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


	function clearWidgetSelection() {
		document.getElementById( 'widgets-left' ).classList.remove( 'chooser' );

		if ( document.querySelector( '.widget-in-question' ) != null ) { // catches undefined too
			document.querySelectorAll( '.widget-in-question' ).forEach( function( inQuestion ) {
				inQuestion.classList.remove( 'widget-in-question' );
			} );
		}
	}

} );
