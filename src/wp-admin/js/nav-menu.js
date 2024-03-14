/**
 * ClassicPress Administration Navigation Menu
 * Interface JS functions
 *
 * Note that this file does not run in the Customizer
 *
 * @since CP-2.1.0
 * @requires SortableJS
 *
 * @package ClassicPress
 * @subpackage Administration
 * @output wp-admin/js/nav-menu.js
 */

/* global Sortable, menus, isRtl, ajaxurl, console */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	/*
	 * Set variables for the whole file
	 */
	var column, originalDepth, originalClientX, originalLabel, newClientX, baseClientX, lastInput, lastSelect,
		postboxTogs = document.querySelectorAll( '.hide-postbox-tog' ),
		advancedMenuProperties = document.querySelectorAll( '#adv-settings .hide-column-tog' ),
		indent = 30,
		maxDepth = 11,
		childrenInfo = {},
		themeLocations = document.getElementById( 'nav-menu-theme-locations' ),
		updateMenu = document.getElementById( 'update-nav-menu' ),
		editMenu = document.getElementById( 'menu-to-edit' ),
		menuEdge = getOffset( editMenu ).left,
		menusChanged = false,
		switchers = document.querySelectorAll( '.bulk-select-switcher' ),
		checkboxes = editMenu ? editMenu.querySelectorAll( '.menu-item-checkbox' ) : {},
		isRTL = !! ( 'undefined' != typeof isRtl && isRtl ),
		managementArray = [];

	/*
	 * Show AYS dialog when there are unsaved changes.
	 *
	 * Note that previous code inherited from WordPress was obsolete.
	 *
	 * Browsers no longer permit the display of a custom message.
	 */
	if ( editMenu || document.querySelector( '.menu-location-menus select' ) ) {
		window.addEventListener( 'beforeunload', function( e ) {
			if ( menusChanged === true ) {
				e.preventDefault();
			}
		} );
	} else if ( document.getElementById( 'menu-settings-column' ) ) {
		column = document.getElementById( 'menu-settings-column' );

		// Make the post boxes read-only, as they can't be used yet.
		lastInput = column.querySelectorAll( 'input' ).pop();
		lastInput.querySelector( 'a' ).setAttribute( 'href', '' );
		lastInput.addEventListener( 'click', function( e ) {
			e.preventDefault();
		} );

		lastSelect = column.querySelectorAll( 'select' ).pop();
		lastSelect.querySelector( 'a' ).setAttribute( 'href', '' );
		lastSelect.addEventListener( 'click', function( e ) {
			e.preventDefault();
		} );
	}

	if ( document.querySelector( '#menu-locations-wrap form' ) !== null ) {
		document.querySelector( '#menu-locations-wrap form' ).addEventListener( 'submit', function() {
			menusChanged === false;
		} );
	}

	document.querySelectorAll( '.menu-location-menus select' ).forEach( function( select ) {
		select.addEventListener( 'change', function() {
			var editLink = select.closest( 'tr' ).querySelector( '.locations-edit-menu-link' );
			if ( select.querySelector( 'option:selected' ).dataset.orig ) {
				editLink.style.display = '';
			} else {
				editLink.style.display = 'none';
			}
		} );
	} );

	/*
	 * Save menu locations
	 */
	if ( themeLocations ) {
		themeLocations.querySelector( 'input[type="submit"]' ).addEventListener( 'click', function() {
			var params = {};

			params.action = 'menu-locations-save';
			params['menu-settings-column-nonce'] = document.getElementById( 'menu-settings-column-nonce' ).value;

			themeLocations.querySelectorAll( 'select' ).forEach( function( select ) {
				params[select.name] = select.value;
			} );

			themeLocations.querySelector( '.spinner' ).classList.add( 'is-active' );

			fetch( ajaxurl, {
				method: 'POST',
				body: new URLSearchParams( params ),
				credentials: 'same-origin'
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.json(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function() {
				themeLocations.querySelector( '.spinner' ).classList.remove( 'is-active' );
			} )
			.catch( function( error ) {
				console.log( error );
			} );
		}, false );
	}

	if ( editMenu ) {
		/*
		* Links for moving items.
		*/
		editMenu.addEventListener( 'click', function( e ) {
			var dir;

			if ( e.target.className.includes( 'menus-move' ) ) {
				dir = e.target.dataset.dir;
				if ( 'undefined' !== typeof dir ) {
					moveMenuItem( e.target.closest( 'li.menu-item' ), dir );
				}
			}
		} );

		/**
		 * Set status of bulk delete checkbox.
		 */
		checkboxes.forEach( function( check ) {
			if ( check.disabled ) {
				check.disabled = false;
			} else {
				check.disabled = true;
			}

			if ( check.checked ) {
				check.checked = false;
			}
		} );

		/**
		* Listen for state changes on bulk action checkboxes.
		*/
		editMenu.addEventListener( 'change', function( e ) {
			var button;

			if ( isVisible( e.target ) && e.target.className.includes( 'menu-item-checkbox' ) ) {
				button = document.querySelector( '.menu-items-delete' );

				if ( document.querySelector( '.menu-item-checkbox:checked' ) !== null ) {
					button.classList.remove( 'disabled' );
				} else {
					button.classList.add( 'disabled' );
				}
			}
		} );

		/*
		* Update the item handle title when the navigation label is changed.
		*/
		editMenu.addEventListener( 'change', updateHandle );
		editMenu.addEventListener( 'input', updateHandle );

		/*
		* Identify the active menu item
		*/
		editMenu.querySelectorAll( 'details' ).forEach( function( details ) {
			var item = details.closest( 'li' );

			details.addEventListener( 'toggle', function() {
				var input, settings = details.querySelectorAll( '.menu-item-settings p' );

				if ( details.hasAttribute( 'open' ) ) {

					// Store title and other attributes in case editing is cancelled
					settings.forEach( function( setting ) {
						input = setting.querySelector( 'input' ) ? setting.querySelector( 'input' ) : setting.querySelector( 'textarea' );
						if ( input ) {
							setting.dataset.store = ( input.type === 'checkbox' ) ? input.checked : input.value;
						} else {
							setting.dataset.store = '';
						}
					} );

					editMenu.querySelectorAll( '.menu-item-edit-active' ).forEach( function( active ) {
						active.classList.remove( 'menu-item-edit-active' );
						active.classList.add( 'menu-item-edit-inactive' );
					} );
					item.classList.remove( 'menu-item-edit-inactive' );
					item.classList.add( 'menu-item-edit-active' );

					refreshAdvancedAccessibilityOfItem( item );
				} else {

					// Remove data attributes to avoid confusion
					settings.forEach( function( setting ) {
						setting.removeAttribute( 'data-store' );
					} );
					details.querySelector( 'summary' ).focus();
				}
			} );
		} );

		// Use the right edge if RTL
		menuEdge += isRTL ? editMenu.innerWidth : 0;

		/*
		* Attach SortableJS to current menu
		*/
		Sortable.create( editMenu, {
			group: 'menu',
			handle: '.item-move',
			forceFallback: navigator.vendor.match(/apple/i) ? true : false, // forces fallback for webkit browsers
			//forceFallback: 'GestureEvent' in window ? true : false, // forces fallback for Safari

			// Get position of menu item when chosen
			onChoose: function( e ) {
				originalClientX = e.originalEvent.clientX;
				originalDepth = menuItemDepth( e.item );
				baseClientX = e.originalEvent.clientX - ( originalDepth * indent );

				// Update aria-label for accessibility
				refreshAdvancedAccessibilityOfItem( e.item, originalDepth, e.oldIndex );

				// Ensure menu widget is closed before moving
				e.item.querySelector( 'details' ).removeAttribute( 'open' );
			},

			// Style placeholder when element starts to be dragged
			onStart: function( e ) {
				var prevItem, children,
					prevDepth = 0,
					details = document.querySelector( '.sortable-ghost details' );

				// Handle placement for RTL orientation
				if ( isRTL ) {
					e.item.style.right = 'auto';
				}

				// Set original label for accessibility
				originalLabel = e.item.querySelector( '.item-move' ).getAttribute( 'aria-label' ).split( '.' ).join( '' ).replace( 'Menu', 'menu' ).replace( 'Sub', menus.child );

				// Style placeholder
				details.style.backgroundColor = '#fefefe';
				details.style.border = '1px dotted #444';
				details.querySelector( 'summary' ).style.visibility = 'hidden';

				// Continually update horizontal position of current item while dragging
				editMenu.addEventListener( 'dragover', function( evt ) {
					var xPos, diff;

					if ( evt.target.closest( 'li' ) === e.item ) {
						newClientX = evt.clientX;

						// Continually update horizontal position of placeholder
						xPos = evt.clientX - baseClientX;

						// Get depth of previous item in list
						prevItem = evt.target.closest( 'li' ).previousElementSibling;
						if ( prevItem ) {
							prevDepth = menuItemDepth( prevItem );
						}

						// Calculate left margin but prevent being indented more than once compared to previous item in list
						if ( prevItem == null || xPos < 0 ) {
							menuEdge = 0;
						} else {
							diff = Math.floor( xPos / indent );
							if ( diff > maxDepth ) {
								diff = maxDepth;
							}
							if ( diff > prevDepth + 1 ) {
								diff = prevDepth + 1;
							}
							menuEdge = diff * indent;
						}
						document.querySelector( '.sortable-ghost' ).style.marginLeft = menuEdge + 'px';
					}
				} );

				// Does this menu item have children?
				children = childMenuItems( e.item );
				if ( children.length > 0 ) {
					childrenInfo.prevItem = e.item;
					childrenInfo.menuItem = children[0];
				}
			},

			// Element dropped
			onEnd: function( e ) {
				var i, n, diff, prevItem, parent, parentDepth, newLabel,
					newLabels, positionSpeech,
					details = e.item.querySelector( 'details' ),
					depth = 0,
					prevDepth = 0,
					draggedClasses = e.item.className.split( ' ' );

				// Revert styling and set focus on move icon
				e.item.style.marginLeft = '';
				details.style.backgroundColor = '#f6f7f7';
				details.style.border = '1px solid #dcdcde';
				details.querySelector( 'summary' ).style.visibility = 'visible';
				details.querySelector( '.dashicons-move' ).focus();

				// Handle drop placement for RTL orientation
				if ( isRTL ) {
					e.item.style.left = 'auto';
					e.item.style.right = '';
				}

				// Get depth of previous item in list
				prevItem = e.item.previousElementSibling;
				if ( prevItem ) {
					prevDepth = menuItemDepth( prevItem );
				}

				// Set depth of current item
				for ( i = 0, n = draggedClasses.length; i < n; i++ ) {
					if ( draggedClasses[i].startsWith( 'menu-item-depth-' ) ) {
						if ( e.newDraggableIndex === 0 || prevItem == null ) { // first element
							draggedClasses[i] = 'menu-item-depth-0'; // don't indent
						} else {
							diff = Math.floor( ( newClientX - originalClientX ) / indent );
							depth = originalDepth + diff;
							if ( depth > maxDepth ) {
								depth = maxDepth;
							} else if ( depth < 0 ) {
								depth = 0;
							}
							if ( depth > ( prevDepth + 1 ) ) {
								depth = prevDepth + 1;
							}
							draggedClasses[i] = 'menu-item-depth-' + depth;
						}
					}
					e.item.className = draggedClasses.join( ' ' );

					if ( depth === 0 ) {
						e.item.querySelector( '.is-submenu' ).style.display = 'none';
						e.item.querySelector( '.menu-item-data-parent-id' ).value = 0;
					} else {
						parentDepth = depth - 1,
						parent = getPreviousSibling( e.item, '.menu-item-depth-' + parentDepth );
						e.item.querySelector( '.is-submenu' ).style.display = '';
						e.item.querySelector( '.menu-item-data-parent-id' ).value = parent.querySelector( '.menu-item-data-db-id' ).value;
					}
				}

				// Move sub-items if this is a parent
				if ( Object.keys( childrenInfo ).length > 0 ) {
					moveChildItems( childrenInfo.prevItem, childrenInfo.menuItem, depth + 1 );

					// Reset for next drag and drop
					childrenInfo = {};
				}

				// Set original clientX to current clientX to establish new starting position
				originalClientX = newClientX;

				// Update for accessibility
				refreshAdvancedAccessibility();
				refreshAdvancedAccessibilityOfItem( e.item, depth, e.newDraggableIndex );
				menusChanged = true;

				// Add message for accessibility purposes
				newLabel = '';
				newLabels = e.item.querySelector( '.item-move' ).getAttribute( 'aria-label' ).split( '. ' );
				if ( undefined !== newLabels[1] ) {
					newLabel = ' ' + newLabels[1].replace( 'Menu', 'menu' ).replace( 'Sub', menus.child );
				}
				positionSpeech = originalLabel + ' ' + menus.movedTo + ' ' + newLabel;
				wp.a11y.speak( positionSpeech, 'polite' );
			}

		} );
	}

	/**
	 * Updates state when potential menu items are shown or hidden
	 */
	postboxTogs.forEach( function( toggle ) {
		toggle.addEventListener( 'click', function() {
			var postVars,
				hidden = [ ...document.querySelectorAll( '#side-sortables .control-section.hide-if-js details' ) ].map( function( i ) { return i.id; } ).join( ',' );

			postVars = new URLSearchParams( {
				action: 'closed-postboxes',
				closedpostboxesnonce: document.getElementById( 'closedpostboxesnonce' ).value,
				hidden: hidden,
				page: 'nav-menus'
			} );

			fetch( ajaxurl, {
				method: 'POST',
				body: postVars,
				credentials: 'same-origin'
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.json(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function() {
			} )
			.catch( function( error ) {
				console.log( error );
			} );

		} );
	} );

	/*
	 * Handle searches within Add menu items boxes
	 */
	if ( document.getElementById( 'nav-menu-meta' ) ) {

		// Prevent form submission
		document.getElementById( 'nav-menu-meta' ).addEventListener( 'submit', function( e ) {
			e.preventDefault();
		} );

		// Enable live search
		document.getElementById( 'nav-menu-meta' ).addEventListener( 'input', function( e ) {
			var searchTimer, lastSearch, panel;

			if ( e.target.className.includes( 'quick-search' ) ) {
				e.target.setAttribute( 'autocomplete', 'off' );
				panel = e.target.closest( '.tabs-panel' );

				if ( searchTimer ) {
					clearTimeout( searchTimer );
				}

				searchTimer = setTimeout( function() {
					var params,
					minSearchLength = 2,
					q = e.target.value;

					/*
					* Minimum characters for a search. Also avoid a new Ajax search when
					* the pressed key (e.g. arrows) doesn't change the searched term.
					*/
					if ( q.length < minSearchLength || lastSearch === q ) {
						return;
					}

					lastSearch = q;
					panel.querySelector( '.spinner' ).classList.add( 'is-active' );

					params = new URLSearchParams( {
						'action': 'menu-quick-search',
						'response-format': 'markup',
						'menu': document.getElementById( 'menu' ).value,
						'menu-settings-column-nonce': document.getElementById( 'menu-settings-column-nonce' ).value,
						'q': q,
						'type': e.target.getAttribute( 'name' )
					} );

					fetch( ajaxurl, {
						method: 'POST',
						body: params,
						credentials: 'same-origin'
					} )
					.then( function( response ) {
						if ( response.ok ) {
							return response.text(); // no errors
						}
						throw new Error( response.status );
					} )
					.then( function( menuMarkup ) {
						/**
						* Process the quick search response into a search result
						*/
						var matched, newID, items,
							div = document.createElement( 'div' ),
							takenIDs = {},
							form = document.getElementById( 'nav-menu-meta' ),
							pattern = /menu-item[(\[^]\]*/,
							wrapper = panel.closest( '.accordion-section-content' ),
							selectAll = wrapper.querySelector( '.button-controls .select-all' );

						div.innerHTML = menuMarkup;
						items = div.querySelectorAll( 'li' );
						if ( ! items.length ) {
							panel.querySelector( '.categorychecklist' ).innerHTML = '<li><p>' + wp.i18n.__( 'No results found.' ) + '</p></li>';
							panel.querySelector( '.spinner' ).classList.remove( 'is-active' );
							wrapper.classList.add( 'has-no-menu-item' );
							return;
						}

						// Remove current list of hits
						panel.querySelectorAll( '.categorychecklist li' ).forEach( function( item ) {
							item.remove();
						} );

						// Create new list of hits
						items.forEach( function( item ) {

							// Make a unique DB ID number
							matched = pattern.exec( item.innerHTML );

							if ( matched && matched[1] ) {
								newID = matched[1];
								while( form.elements['menu-item[' + newID + '][menu-item-type]'] || takenIDs[ newID ] ) {
									newID--;
								}

								takenIDs[newID] = true;
								if ( newID !== matched[1] ) {
									item.innerHTML = item.innerHTML.replace( new RegExp(
										'menu-item\\[' + matched[1] + '\\]', 'g'),
										'menu-item[' + newID + ']'
									);
								}
							}
							panel.querySelector( '.categorychecklist' ).append( item );
						} );

						panel.querySelector( '.spinner' ).classList.remove( 'is-active' );
						wrapper.classList.remove( 'has-no-menu-item' );

						if ( selectAll.checked ) {
							selectAll.checked = false;
						}
					} )
					.catch( function( error ) {
						console.log( error );
					} );

				}, 500 );

				panel.querySelector( '.quick-search' ).addEventListener( 'blur', function() {
					lastSearch = '';
				} );
			}
		} );
	}

	/*
	 * Saving menus
	 */
	if ( updateMenu ) {
		/**
		 * When a navigation menu is saved, store a JSON representation of all form data
		 * in a single input to avoid PHP `max_input_vars` limitations. See #14134.
		 */
		updateMenu.addEventListener( 'submit', function( e ) {
			var name, value, navMenuData, index,
				locs = '',
				pairs = [],
				menuName = document.getElementById( 'menu-name' ),
				menuNameVal = menuName.value,
				menuList = [ ...editMenu.querySelectorAll( 'li' ) ];

			// Pause submission to allow for code updates
			e.preventDefault();

			// Cancel and warn if invalid menu name
			if ( ! menuNameVal || ! menuNameVal.replace( /\s+/, '' ) ) {
				menuName.parentNode.classList.add( 'form-invalid' );
				return;
			}

			// Update position of each menu item
			editMenu.querySelectorAll( '.menu-item-data-position' ).forEach( function( pos ) {
				index = menuList.indexOf( pos.closest( 'li' ) );
				pos.value = parseInt( index + 1, 10 );
			} );

			// Update menu item data
			document.querySelectorAll( '#nav-menu-theme-locations select' ).forEach( function( select ) {
				locs += '<input type="hidden" name="' + select.name + '" value="' + select.value + '">';
			} );
			updateMenu.append( locs );

			// Add each name/value pair to the array
			navMenuData = new FormData( updateMenu );
			for ( [name, value] of navMenuData ) {
				pairs.push({ name, value });
			}
			document.querySelector( '[name="nav-menu-data"]' ).value = JSON.stringify( pairs );

			// Submit form
			menusChanged = false;
			updateMenu.submit();
		} );

		/**
		 * Menu updates
		 */
		updateMenu.addEventListener( 'click', function( e ) {

			// Update menu item status when click on move icon
			if ( e.target.className.includes( 'dashicons-move' ) ) {
				var item = e.target.closest( 'li' );

				e.preventDefault();

				if ( item && item.className.includes( 'menu-item-edit-inactive' ) ) {
					editMenu.querySelectorAll( '.menu-item-edit-active' ).forEach( function( active ) {
						active.classList.remove( 'menu-item-edit-active' );
						active.classList.add( 'menu-item-edit-inactive' );
					} );
					item.classList.remove( 'menu-item-edit-inactive' );
					item.classList.add( 'menu-item-edit-active' );
				}
			}

			// Reinstate menu item data after cancelling edit
			else if ( e.target.className.includes( 'item-cancel' ) ) {
				var thisMenuItem = e.target.closest( 'li.menu-item' ),
					details = e.target.closest( 'details' ),
					settings = e.target.closest( '.menu-item-settings' ).querySelectorAll( 'p' );

				e.preventDefault();

				// Restore the title and attributes of the cancelled menu item
				settings.forEach( function( setting ) {
					var input = setting.querySelector( 'input' ) ? setting.querySelector( 'input' ) : setting.querySelector( 'textarea' );
					if ( input ) {
						if ( input.type === 'checkbox' ) {
							if ( setting.dataset.store === 'true' ) {
								input.checked = true;
							} else {
								input.checked = false;
							}
						} else {
							input.value = setting.dataset.store;
						}
					}
				} );

				// Close menu item
				details.removeAttribute( 'open' );

				// Update classes to reflect menu item's status
				thisMenuItem.classList.remove( 'menu-item-edit-active' );
				thisMenuItem.classList.add( 'menu-item-edit-inactive' );
			}

			// Delete individual menu item
			else if ( e.target.className.includes( 'item-delete' ) ) {
				var itemID = parseInt( e.target.id.replace( 'delete-', '' ), 10 );
				e.preventDefault();

				removeMenuItem( document.getElementById( 'menu-item-' + itemID ) );
				menusChanged = true;
			}

			// Bulk delete menu items
			else if ( e.target.className.includes( 'menu-items-delete' ) ) {
				var itemsPendingDeletion, itemsPendingDeletionList, deletionSpeech;
				e.preventDefault();

				if ( ! e.target.className.includes( 'disabled' ) ) {
					document.querySelectorAll( '.menu-item-checkbox:checked' ).forEach( function( element ) {
						element.closest( 'li' ).querySelector( 'a.item-delete' ).click();
					} );

					document.querySelector( '.menu-items-delete' ).classList.add( 'disabled' );
					document.querySelector( '.bulk-select-switcher' ).checked = false;

					itemsPendingDeletion     = '';
					itemsPendingDeletionList = document.querySelectorAll( '#pending-menu-items-to-delete ul li' );

					itemsPendingDeletionList.forEach( function( element, index ) {
						var itemName = element.querySelector( '.pending-menu-item-name' ).textContent;
						var itemSpeech = menus.menuItemDeletion.replace( '%s', itemName );

						itemsPendingDeletion += itemSpeech;
						if ( ( index + 1 ) < itemsPendingDeletionList.length ) {
							itemsPendingDeletion += ', ';
						}
					} );

					deletionSpeech = menus.itemsDeleted.replace( '%s', itemsPendingDeletion );
					wp.a11y.speak( deletionSpeech, 'polite' );
					disableBulkSelection();
				}
			}

			// Prevent AYS when deleting menu
			else if ( e.target.className.includes( 'menu-delete' ) ) {
				if ( window.confirm( wp.i18n.__( 'You are about to permanently delete this menu.\n\'Cancel\' to stop, \'OK\' to delete.' ) ) ) {
					menusChanged = false;
				}
				menusChanged = true;
			}

		} );

		/**
		 * Move menu items using arrow keys
		 */
		updateMenu.addEventListener( 'keydown', function( e ) {
			if ( e.target.className.includes( 'dashicons-move' ) ) {
				var arrows = [ 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown' ],
					thisItem = e.target.closest( 'li.menu-item' );

				// Allow tabbing
				if ( e.key !== 'Tab' ) {
					e.preventDefault();
				}

				// Bail if it's not an arrow key
				if ( ! arrows.includes( e.key ) ) {
					return;
				}

				// Bail if there is only one menu item.
				if ( 1 === editMenu.querySelector( 'li' ).length ) {
					return;
				}

				// Close menu item before moving
				thisItem.querySelector( 'details' ).removeAttribute( 'open' );

				switch ( e.key ) {
				case 'ArrowUp':
					moveMenuItem( thisItem, 'up' );
					break;
				case 'ArrowDown':
					moveMenuItem( thisItem, 'down' );
					break;
				case 'ArrowLeft':
					if ( document.body.className.includes( 'rtl' ) ) {
						moveMenuItem( thisItem, 'right' );
					} else {
						moveMenuItem( thisItem, 'left' );
					}
					break;
				case 'ArrowRight':
					if ( document.body.className.includes( 'rtl' ) ) {
						moveMenuItem( thisItem, 'left' );
					} else {
						moveMenuItem( thisItem, 'right' );
					}
					break;
				}

				// Keep the focus on the arrows icon
				e.target.focus();
			}
		} );
	}

	if ( document.getElementById( 'menu-name' ) ) {
		document.getElementById( 'menu-name' ).addEventListener( 'input', _.debounce( function( e ) {
			if ( ! e.target.value || ! e.target.value.replace( /\s+/, '' ) ) {

				// Add warning for invalid menu name.
				e.target.parentNode.classList.add( 'form-invalid' );
			} else {

				// Remove warning for valid menu name.
				e.target.parentNode.classList.remove( 'form-invalid' );
			}
		}, 500 ) );
	}

	document.querySelectorAll( '#add-custom-links input[type="text"]' ).forEach( function( input ) {
		input.addEventListener( 'keydown', function( e ) {
			document.getElementById( 'customlinkdiv' ).classList.remove( 'form-invalid' );

			if ( e.key === 'Enter' ) {
				e.preventDefault();
				document.getElementById( 'submit-customlinkdiv' ).click();
			}
		} );
	} );

	/*
	 * Show or hide advanced menu properties
	 */
	advancedMenuProperties.forEach( function( property ) {
		var inputs = document.querySelectorAll( '.field-' + property.value );

		if ( property.checked ) {
			inputs.forEach( function( input ) {
				input.classList.remove( 'hidden-field' );
			} );
		} else {
			inputs.forEach( function( input ) {
				input.classList.add( 'hidden-field' );
			} );
		}

		property.addEventListener( 'click', function() {
			var postVars,
				hidden = [ ...document.querySelectorAll( '#adv-settings .hide-column-tog:not(:checked)' ) ].map( function( i ) { return i.value; } ).join( ',' );

			inputs.forEach( function( input ) {
				input.classList.toggle( 'hidden-field' );
			} );

			postVars = new URLSearchParams( {
				action: 'hidden-columns',
				screenoptionnonce: document.getElementById( 'screenoptionnonce' ).value,
				hidden: hidden,
				page: 'nav-menus'
			} );

			fetch( ajaxurl, {
				method: 'POST',
				body: postVars,
				credentials: 'same-origin'
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.json(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function() {
			} )
			.catch( function( error ) {
				console.log( error );
			} );

		} );
	} );

	/*
	 * Attach listeners to the Add menu items column
	 */
	if ( document.getElementById( 'menu-settings-column' ) ) {
		document.getElementById( 'menu-settings-column' ).addEventListener( 'click', function(e) {
			var selectAreaMatch, selectAll, panelId, wrapper, items, itemsChecked, params;

			if ( e.target.className.includes( 'nav-tab-link' ) ) {
				e.preventDefault();

				panelId = e.target.dataset.type;
				wrapper = e.target.closest( '.accordion-section-content' );

				// Upon changing tabs, we want to uncheck all checkboxes.
				wrapper.querySelectorAll( 'input' ).forEach( function( box) {
					box.checked = false;
				} );

				document.querySelectorAll( '.tabs-panel' ).forEach( function( link ) {
					link.classList.add( 'tabs-panel-inactive' );
					link.classList.remove( 'tabs-panel-active' );
				} );

				document.getElementById( panelId ).classList.add( 'tabs-panel-active' );
				document.getElementById( panelId ).classList.remove( 'tabs-panel-inactive' );

				document.querySelector( '.tabs' ).classList.remove( 'tabs' );
				e.target.parentNode.classList.add( 'tabs' );

				// Select the search bar.
				wrapper.querySelector( '.quick-search' ).focus();

				// Hide controls in the search tab if no items found.
				if ( document.querySelector( '.tabs-panel-active .menu-item-title' ) ) {
					wrapper.classList.remove( 'has-no-menu-item' );
				} else {
					wrapper.classList.add( 'has-no-menu-item' );
				}

			} else if ( e.target.className.includes( 'select-all' ) ) {
				selectAreaMatch = e.target.closest( '.button-controls' ).dataset.itemsType;
				if ( selectAreaMatch ) {
					items = document.querySelectorAll( '#' + selectAreaMatch + ' .tabs-panel-active .menu-item-title input' );
					itemsChecked = document.querySelectorAll( '#' + selectAreaMatch + ' .tabs-panel-active .menu-item-title input:checked' );

					if ( items.length === itemsChecked.length && ! e.target.checked ) {
						items.forEach( function( item ) {
							item.checked = false;
						} );
					} else if ( e.target.checked ) {
						items.forEach( function( item ) {
							item.checked = true;
						} );
					}
				}

			} else if ( e.target.className.includes( 'menu-item-checkbox' ) ) {
				selectAreaMatch = e.target.closest( '.tabs-panel-active' ).parentNode.id;
				if ( selectAreaMatch ) {
					items = document.querySelectorAll( '#' + selectAreaMatch + ' .tabs-panel-active .menu-item-title input' );
					itemsChecked = document.querySelectorAll( '#' + selectAreaMatch + ' .tabs-panel-active .menu-item-title input:checked' );
					selectAll = document.querySelector( '.button-controls[data-items-type="' + selectAreaMatch + '"] .select-all' );

					if ( items.length === itemsChecked.length && ! selectAll.checked ) {
						selectAll.checked = true;
					} else if ( selectAll.checked ) {
						selectAll.checked = false;
					}
				}

			} else if ( e.target.className.includes( 'submit-add-to-menu' ) ) {
				e.preventDefault();
				menusChanged = true;

				if ( e.target.id && 'submit-customlinkdiv' === e.target.id ) {
					var url = document.getElementById( 'custom-menu-item-url' ).value.toString(),
						label = document.getElementById( 'custom-menu-item-name' ).value;

					if ( '' !== url ) {
						url = url.trim();
					}

					if ( '' === url || 'https://' == url || 'http://' == url ) {
						document.getElementById( 'customlinkdiv' ).classList.add( 'form-invalid' );
							return false;
					}

					// Show the Ajax spinner
					document.querySelector( '.customlinkdiv .spinner' ).classList.add( 'is-active' );

					// Add link to menu
					params = new URLSearchParams( {
						'action': 'add-menu-item',
						'menu': document.getElementById( 'menu' ).value,
						'menu-settings-column-nonce': document.getElementById( 'menu-settings-column-nonce' ).value,
						'menu-item[-1][menu-item-type]': 'custom',
						'menu-item[-1][menu-item-url]': url,
						'menu-item[-1][menu-item-title]': label
					} );

					fetch( ajaxurl, {
						method: 'POST',
						body: params,
						credentials: 'same-origin'
					} )
					.then( function( response ) {
						if ( response.ok ) {
							return response.text(); // no errors
						}
						throw new Error( response.status );
					} )
					.then( function( menuMarkup ) {

						editMenu.insertAdjacentHTML( 'beforeend', menuMarkup );
						advancedMenuProperties.forEach( function( property ) {
							var pendingValue = editMenu.querySelector( '.pending .field-' + property.value );
							if ( ! property.checked ) {
								pendingValue.classList.add( 'hidden-field' );
							}
						} );

						// Provide accessibility update
						wp.a11y.speak( menus.itemAdded );
						document.dispatchEvent( new CustomEvent( 'menu-item-added', {
							detail: menuMarkup
						} ) );

						// Remove the Ajax spinner
						document.querySelector( '.customlinkdiv .spinner' ).classList.remove( 'is-active' );

						// Set custom link form back to defaults
						document.getElementById( 'custom-menu-item-name' ).value = '';
						document.getElementById( 'custom-menu-item-name' ).blur();
						document.getElementById( 'custom-menu-item-url' ).value = '';
						document.getElementById( 'custom-menu-item-url' ).placeholder = 'https://';

					} )
					.catch( function( error ) {
						console.log( error );
					} );
				} else {
					addSelectedToMenu( document.getElementById( e.target.id.replace( 'submit-', '' ) ) );
				}
			}
		} );
	}

	/*
	 * Delegate the `click` event and attach it just to the pagination
	 * links thus excluding the current page `<span>`. See ticket #35577.
	 */
	if ( document.getElementById( 'nav-menu-meta' ) ) {
		document.getElementById( 'nav-menu-meta' ).addEventListener( 'click', function( e ) {
			if ( e.target.classname !== undefined && e.target.classname.includes( 'page-numbers' ) && e.target.tagName.toLowerCase() === 'a' ) {

				fetch( ajaxurl, {
					method: 'POST',
					body: e.target.href.replace( /.*\?/, '' ).replace( /action=([^&]*)/, '' ) + '&action=menu-get-metabox',
					credentials: 'same-origin'
				} )
				.then( function( response ) {
					if ( response.ok ) {
						return response.text(); // no errors
					}
					throw new Error( response.status );
				} )
				.then( function( resp ) {
					var metaBoxData = JSON.parse( resp ),
						toReplace;

					if ( -1 === resp.indexOf( 'replace-id' ) ) {
						return;
					}

					// Get the post type menu meta box to update.
					toReplace = document.getElementById( metaBoxData['replace-id'] );

					if ( ! metaBoxData.markup || ! toReplace ) {
						return;
					}

					// Update the post type menu meta box with new content from the response.
					e.target.closest( '.inside' ).innerHTML = metaBoxData.markup;
				} )
				.catch( function( error ) {
					console.log( error );
				} );
			}
		}, false );
	}

	/**
	 * List menu items awaiting deletion
	 */
	if ( document.getElementById( 'post-body-content' ) ) {
		document.getElementById( 'post-body-content' ).addEventListener( 'change', function( e ) {
			if ( e.target.className.includes( 'menu-item-checkbox' ) ) {
				var menuItemName, menuItemControls, menuItemType, menuItemID, listedMenuItem, li;

				if ( document.querySelector( '.menu-items-delete' ).getAttribute( 'aria-describedby' ) !== 'pending-menu-items-to-delete' ) {
					document.querySelector( '.menu-items-delete' ).setAttribute( 'aria-describedby', 'pending-menu-items-to-delete' );
				}

				menuItemName = e.target.nextElementSibling.textContent;
				menuItemControls = getAllNextSiblings( e.target, '.item-controls' );
				menuItemType = menuItemControls[0].querySelector( '.item-type' ).textContent;
				menuItemID   = e.target.dataset.menuItemId;

				listedMenuItem = document.querySelector( '#pending-menu-items-to-delete ul li [data-menu-item-id="' + menuItemID + '"]' );
				if ( listedMenuItem !== null ) {
					listedMenuItem.remove();
				}

				if ( e.target.checked === true ) {
					li = document.createElement( 'li' );
					li.dataset.menuItemId = menuItemID;
					li.innerHTML = '<span class="pending-menu-item-name">' + menuItemName + '</span> ' + '<span class="pending-menu-item-type">(' + menuItemType + ')</span>' + '<span class="separator"></span>';
					document.querySelector( '#pending-menu-items-to-delete ul' ).append( li );
				}

				document.querySelectorAll( '#pending-menu-items-to-delete li .separator' ).forEach( function( sep, index ) {
					if ( index === document.querySelectorAll( '#pending-menu-items-to-delete li .separator' ).length - 1 ) {
						sep.innerHTML = '.';
					} else {
						sep.innerHTML = ', ';
					}
				} );
			}
		} );
	}

	/*
	 * Watch for menu changes
	 */
	managementArray = [ ...document.querySelectorAll( '#menu-management input' ), ...document.querySelectorAll( '#menu-management select' ), document.getElementById( 'menu-management' ), ...document.querySelectorAll( '#menu-management textarea' ), ...		document.querySelectorAll( '.menu-location-menus select' ) ];

	// Remove empty elements from array
	managementArray = managementArray.filter( function( el ) {
		return el;
	} );
	managementArray.forEach( function( manager ) {
		manager.addEventListener( 'change', function() {
			menusChanged = true;
		} );
	} );

	/**
	 * Handle toggling bulk selection checkboxes for menu items
	 */
	switchers.forEach( function( switcher ) {
		switcher.addEventListener( 'change', function() {
			if ( switcher.checked ) {
				switchers.forEach( function( switching ) {
					switching.checked = true;
				} );
				enableBulkSelection();
			} else {
				switchers.forEach( function( switching ) {
					switching.checked = false;
				} );
				disableBulkSelection();
			}
		} );
	} );

	/*
	 * Show bulk action
	 */
	document.addEventListener( 'menu-item-added', function() {
		if ( ! isVisible( document.querySelector( '.bulk-actions' ) ) ) {
			document.querySelector( '.bulk-actions' ).style.display = '';
		}
	} );

	/*
	 * Hide bulk action
	 */
	document.addEventListener( 'menu-removing-item', function( e ) {
		var menuElement = e.detail.closest( '#menu-to-edit' );
		if ( menuElement.querySelector( 'li' ).length === 1 && isVisible( document.querySelector( '.bulk-actions' ) ) ) {
			document.querySelector( '.bulk-actions' ).style.display = 'none';
		}
	} );

	/*
	 * Prevent focused element from being hidden by the sticky footer.
	 */
	if ( document.querySelector( '.menu-edit' ) ) {
		document.querySelector( '.menu-edit' ).addEventListener( 'focusin', function( e ) {
			var navMenuHeight, bottomOffset, scrollTop,
				tagsArray = [ 'a', 'button', 'input', 'select', 'textarea' ];

			if ( tagsArray.includes( e.target.tagName.toLowerCase() ) ) {
				if ( window.innerWidth >= 783 ) {
					navMenuHeight = document.getElementById( 'nav-menu-footer' ).innerHeight + 20;
					bottomOffset = this.getBoundingClientRect().top - ( window.scrollY + window.innerHeight - this.innerHeight );

					if ( bottomOffset > 0 ) {
						bottomOffset = 0;
					}
					bottomOffset = bottomOffset * -1;

					if ( bottomOffset < navMenuHeight ) {
						scrollTop = document.scrollY;
						document.scrollY = scrollTop + ( navMenuHeight - bottomOffset );
					}
				}
			}
		} );
	}


	/*
	 * HELPER FUNCTIONS
	 */

	/*
	 * Get offset of item: copied from jQuery
	 */
	function getOffset( element ) {
		var rect, win;

		if ( ! element.getClientRects().length ) {
			return { top: 0, left: 0 };
		}

		rect = element.getBoundingClientRect();
		win = element.ownerDocument.defaultView;
		return ( {
			top: rect.top + win.pageYOffset,
			left: rect.left + win.pageXOffset
		} );
	}

	/*
	 * Find the first previous sibling with the requisite selector
	 */
	function getPreviousSibling( elem, selector ) {

		// Get the previous sibling element
		var sibling = elem.previousElementSibling;

		// If the sibling matches our selector, use it; otherwise move on to the next sibling
		while ( sibling ) {
			if ( sibling.matches( selector ) ) {
				return sibling;
			}
			sibling = sibling.previousElementSibling;
		}
	}

	/*
	 * Get all the following siblings with the requisite selector
	 */
	function getAllNextSiblings( elem, selector ) {

		// Get the next sibling element
		var siblings = [],
			sibling = elem.nextElementSibling;

		// If the sibling matches our selector, add to siblings array
		while ( sibling ) {
			if ( sibling.matches( selector ) ) {
				siblings.push( sibling );
			}
			sibling = sibling.nextElementSibling;
		}
		return siblings;
	}

	/*
	 * Update the item handle title when the navigation label is changed.
	 */
	function updateHandle( e ) {
		var input = e.target, title, titleEl;
		if ( e.target.className.includes( 'edit-menu-item-title' ) ) {
			title = input.value;
			titleEl = input.closest( '.menu-item' ).querySelector( '.menu-item-title' );

			// Don't update to empty title.
			if ( title ) {
				titleEl.textContent = title;
				titleEl.classList.remove( 'no-title' );
			} else {
				titleEl.textContent = wp.i18n._x( '(no label)', 'missing menu item navigation label' );
				titleEl.classList.add( 'no-title' );
			}
		}
	}

	/**
	 * Adds selected items to the menu
	 */
	function addSelectedToMenu( itemAdd ) {

		var params,
			itemData = {},
			placing = 'beforeend',
			action = 'add-menu-item',
			checks = ( menus.oneThemeLocationNoMenus && itemAdd.querySelector( '.tabs-panel-active .categorychecklist li input:checked' ) == null ) ? document.querySelectorAll( '#page-all li input[type="checkbox"]' ) : itemAdd.querySelectorAll( '.tabs-panel-active .categorychecklist li input:checked' ),
			fields = [
				'menu-item-db-id',
				'menu-item-object-id',
				'menu-item-object',
				'menu-item-parent-id',
				'menu-item-position',
				'menu-item-type',
				'menu-item-title',
				'menu-item-url',
				'menu-item-description',
				'menu-item-attr-title',
				'menu-item-target',
				'menu-item-classes',
				'menu-item-xfn'
			];

		// Bail if no items are checked or there is no menu to edit
		if ( checks.length < 1 || editMenu == null ) {
			return false;
		}

		// Show the Ajax spinner
		itemAdd.querySelector( '.button-controls .spinner' ).classList.add( 'is-active' );

		// Set up the main params
		params = new URLSearchParams( {
			'action': action,
			'menu': document.getElementById( 'menu' ).value,
			'menu-settings-column-nonce': document.getElementById( 'menu-settings-column-nonce' ).value
		} );

		// Retrieve menu item data
		checks.forEach( function( check ) {
			var id = parseInt( check.getAttribute( 'name' ).replace( 'menu-item[', '' ), 10 ),
				item = check.closest( 'li' );

			if ( isNaN( id ) ) {
				id === 0;
			}

			if ( ! id && action === 'menu-item' ) { // itemType === 'menu-item'
				id = item.querySelector( '.menu-item-data-db-id' ).value;
			}

			// Check placing for new items
			if ( check.className && check.className.includes( 'add-to-top' ) ) {
				placing = 'afterbegin';
			}

			// Build param for each selected input and append to main params above.
			if ( id ) {
				itemData = item.querySelectorAll( 'input' );
				itemData.forEach( function( input ) {
					var field,
						i = fields.length;

					while ( i-- ) {
						if ( action === 'menu-item' ) {
							field = fields[i] + '[' + id + ']';
						}
						else if ( action === 'add-menu-item' ) {
							field = 'menu-item[' + id + '][' + fields[i] + ']';
							if ( input.name && field === input.name ) {
								params.append( field, input.value );
							}
						}
					}
				} );
			}
		} );

		fetch( ajaxurl, {
			method: 'POST',
			body: params,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.text(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( menuMarkup ) {
			var ins = document.getElementById( 'menu-instructions' );

			menuMarkup = menuMarkup || '';
			menuMarkup = menuMarkup.toString().trim(); // Trim leading whitespaces.

			editMenu.insertAdjacentHTML( placing, menuMarkup );

			advancedMenuProperties.forEach( function( property ) {
				var pendingValues = editMenu.querySelectorAll( '.pending .field-' + property.value );
				if ( ! property.checked ) {
					pendingValues.forEach( function( pendingValue ) {
						pendingValue.classList.add( 'hidden-field' );
					} );
				}
			} );

			// Provide accessibility update
			wp.a11y.speak( menus.itemAdded );
			menusChanged = true;
			document.dispatchEvent( new CustomEvent( 'menu-item-added', {
				detail: menuMarkup
			} ) );

			document.querySelector( '.drag-instructions' ).style.display = '';
			if ( ! ins.className.includes( 'menu-instructions-inactive' ) && ins.parentNode.children.length ) {
				ins.classList.add( 'menu-instructions-inactive' );
			}

			// Deselect the items and hide the Ajax spinner
			checks.forEach( function( check ) {
				check.checked = false;
			} );
			itemAdd.querySelector( '.button-controls .select-all' ).checked = false;
			itemAdd.querySelector( '.button-controls .spinner' ).classList.remove( 'is-active' );
		} )
		.catch( function( error ) {
			console.log( error );
		} );

		refreshAdvancedAccessibility();
	}

	/*
	 * Function to move items without drag and drop
	 */
	function moveMenuItem( el, dir ) {

		var newItemPosition, newDepth, primaryItems, itemPosition, newLabel, newLabels, positionSpeech,
			menuItems = editMenu.querySelectorAll( 'li' ),
			menuItemsCount = menuItems.length,
			thisItem = el.closest( 'li.menu-item' ),
			originalItem = el.closest( 'li.menu-item' ),
			thisItemChildren = childMenuItems( thisItem ),
			thisItemDepth = parseInt( menuItemDepth( thisItem ), 10 ),
			thisItemPosition = parseInt( [ ...menuItems ].indexOf( thisItem ) + 1, 10 ),
			nextItem = thisItem.nextElementSibling,
			nextItemChildren = nextItem ? childMenuItems( nextItem ) : [],
			nextItemDepth = nextItem ? parseInt( menuItemDepth( nextItem ), 10 ) + 1 : 0,
			prevItem = thisItem.previousElementSibling,
			prevItemDepth = prevItem ? parseInt( menuItemDepth( prevItem ), 10 ) : 0,
			originalLabel = thisItem.querySelector( '.item-move' ).getAttribute( 'aria-label' ).split( '.' ).join( '' ).replace( 'Menu', 'menu' ).replace( 'Sub', menus.child );

		switch ( dir ) {
		case 'up':
			newItemPosition = thisItemPosition - 1;

			// Already at top
			if ( 1 === thisItemPosition ) {
				break;
			}

			// If a sub item is moved to top, shift it to 0 depth and update variable
			if ( 1 === newItemPosition ) {
				moveHorizontally( thisItem, 0, thisItemDepth );
				thisItemDepth = parseInt( menuItemDepth( thisItem ), 10 );
			}

			// If prev item is sub item, shift to match depth and update variable
			if ( 0 !== prevItemDepth ) {
				moveHorizontally( thisItem, prevItemDepth, thisItemDepth );
				thisItemDepth = parseInt( menuItemDepth( thisItem ), 10 );
			}

			prevItem.before( thisItem );
			updateParentMenuItemDBId( thisItem );

			// Does this item have sub items?
			if ( thisItemChildren.length > 0 ) {

				// Move the entire block
				thisItemChildren.forEach( function( item ) {
					thisItem.after( item );
					updateParentMenuItemDBId( item );
					thisItem = item;
				} );
			}
			break;

		case 'down':
			newItemPosition = thisItemPosition + 1;

			// Does this item have sub items?
			if ( thisItemChildren.length > 0 ) {
				nextItem = menuItems[thisItemChildren.length + thisItemPosition];
				if ( nextItem !== undefined ) {
					nextItemChildren = 0 !== childMenuItems( nextItem ).length;

					if ( nextItemChildren ) {
						newDepth = parseInt( menuItemDepth( nextItem ), 10 ) + 1;
						moveHorizontally( thisItem, newDepth, thisItemDepth );
					}

					nextItem.after( thisItem );
					thisItemChildren.forEach( function( item ) {
						thisItem.after( item );
						updateParentMenuItemDBId( item );
						thisItem = item;
					} );
				}
			} else {

				// If next item has sub items, shift depth
				if ( 0 !== nextItemChildren.length ) {
					moveHorizontally( thisItem, nextItemDepth, thisItemDepth );
				}

				// Have we reached the bottom?
				if ( menuItemsCount === thisItemPosition ) {
					break;
				}
				nextItem.after( thisItem );
				updateParentMenuItemDBId( thisItem );
			}
			break;

		case 'top':
			newItemPosition = 1;

			// Already at top
			if ( 1 === thisItemPosition ) {
				break;
			}

			thisItem.parentNode.prepend( thisItem );
			updateParentMenuItemDBId( thisItem );

			// Does this item have sub items?
			if ( thisItemChildren.length > 0 ) {

				// Move the entire block
				thisItemChildren.forEach( function( item ) {
					thisItem.after( item );
					updateParentMenuItemDBId( item );
					thisItem = item;
				} );
			}
			break;

		case 'left':
			// As far left as possible
			if ( 0 === thisItemDepth ) {
				break;
			}
			shiftHorizontally( thisItem, -1 );
			break;

		case 'right':
			// Can't be sub item at top
			if ( 1 === thisItemPosition ) {
				break;
			}

			// Already sub item of prevItem
			if ( thisItem.querySelector( '.menu-item-data-parent-id' ).value === prevItem.querySelector( '.menu-item-data-db-id' ).value ) {
				break;
			}
			shiftHorizontally( thisItem, 1 );
			break;
		}

		// Update status
		menusChanged = true;
		refreshAdvancedAccessibility();

		// Add message for accessibility purposes
		primaryItems = editMenu.querySelectorAll( '.menu-item-depth-0' );
		itemPosition = [ ...primaryItems ].indexOf( originalItem ) + 1;
		newLabel = ' menu item ' + itemPosition + ' of ' + primaryItems.length;
		newLabels = originalItem.querySelector( '.item-move' ).getAttribute( 'aria-label' ).split( '. ' );
		if ( undefined !== newLabels[1] ) {
			newLabel = ' ' + newLabels[1].replace( 'Menu', 'menu' ).replace( 'Sub', menus.child );
		}
		positionSpeech = originalLabel + ' ' + menus.movedTo + ' ' + newLabel;
		wp.a11y.speak( positionSpeech, 'polite' );
	}

	/**
	 * refreshAdvancedAccessibilityOfItem( [itemToRefresh] )
	 *
	 * With optional parameters depth and position
	 *
	 * Refreshes advanced accessibility buttons for one menu item.
	 * Shows or hides buttons based on the location of the menu item.
	 *
	 * @param {Object} itemToRefresh The menu item that might need its advanced accessibility buttons refreshed
	 */
	function refreshAdvancedAccessibilityOfItem( itemToRefresh, depth, position ) {
		var thisLink, thisLinkText, primaryItems, itemPosition, title, prevItemDepth, totalMenuItems,
			parentItem, parentItemId, parentItemName, prevItemNameLeft, prevItemNameRight,
			itemName = itemToRefresh.querySelector( '.menu-item-title' ).textContent;

		position = position || [ ...editMenu.querySelectorAll( 'li' ) ].indexOf( itemToRefresh );

		if ( depth == null ) { // catches both null and undefined
			depth = menuItemDepth( itemToRefresh );
		}
		prevItemDepth = ( depth === 0 ) ? depth : parseInt( depth - 1, 10 );

		// Determine whether to show or hide menu item
		totalMenuItems = editMenu.querySelectorAll( 'li' ).length;
		if ( totalMenuItems > 1 ) {
			itemToRefresh.querySelector( '.field-move' ).style.display = '';
		} else {
			itemToRefresh.querySelector( '.field-move' ).style.display = 'none';
		}

		// Where can they move this menu item?
		if ( 0 !== position ) {
			thisLink = itemToRefresh.querySelector( '.menus-move-up' );
			thisLink.setAttribute( 'aria-label', menus.moveUp );
			thisLink.style.display = 'inline';
		}

		if ( 0 !== position && 0 === depth ) {
			thisLink = itemToRefresh.querySelector( '.menus-move-top' );
			thisLink.setAttribute( 'aria-label', menus.moveToTop );
			thisLink.style.display = 'inline';
		}

		if ( position + 1 !== totalMenuItems && 0 !== position ) {
			thisLink = itemToRefresh.querySelector( '.menus-move-down' );
			thisLink.setAttribute( 'aria-label', menus.moveDown );
			thisLink.style.display = 'inline';
		}

		if ( 0 === position && itemToRefresh.nextElementSibling && itemToRefresh.nextElementSibling.className.includes( 'menu-item-depth-' + depth ) ) {
			thisLink = itemToRefresh.querySelector( '.menus-move-down' );
			thisLink.setAttribute( 'aria-label', menus.moveDown );
			thisLink.style.display = 'inline';
		}

		if ( 0 !== depth ) {
			prevItemNameLeft = getPreviousSibling( itemToRefresh, '.menu-item-depth-' + prevItemDepth ).querySelector( '.menu-item-title' ).textContent,
			thisLink = itemToRefresh.querySelector( '.menus-move-left' ),
			thisLinkText = menus.outFrom.replace( '%s', prevItemNameLeft );
			thisLink.style.display = 'inline';
			thisLink.setAttribute( 'aria-label', menus.moveOutFrom.replace( '%s', prevItemNameLeft ) );
			thisLink.textContent = thisLinkText;
		}

		if ( 0 !== position ) {
			if ( itemToRefresh.previousElementSibling && itemToRefresh.querySelector( '.menu-item-data-parent-id' ).value !== itemToRefresh.previousElementSibling.querySelector( '.menu-item-data-db-id' ).value ) {
				prevItemNameRight = getPreviousSibling( itemToRefresh, '.menu-item-depth-' + depth ).querySelector( '.menu-item-title' ).textContent,
				thisLink = itemToRefresh.querySelector( '.menus-move-right' );
				thisLinkText = menus.under.replace( '%s', prevItemNameRight );
				thisLink.style.display = 'inline';
				thisLink.setAttribute( 'aria-label', menus.moveUnder.replace( '%s', prevItemNameRight ) );
				thisLink.textContent = thisLinkText;
			}
		}

		if ( 0 === depth ) {
			primaryItems = editMenu.querySelectorAll( '.menu-item-depth-0' );
			itemPosition = [ ...primaryItems ].indexOf( itemToRefresh ) + 1;
			totalMenuItems = primaryItems.length;

			// String together help text for primary menu items.
			title = menus.menuFocus.replace( '%1$s', itemName ).replace( '%2$d', itemPosition ).replace( '%3$d', totalMenuItems );
		} else {
			parentItem = getPreviousSibling( itemToRefresh, '.menu-item-depth-' + parseInt( depth - 1, 10 ) );
			parentItemId = parentItem.querySelector( '.menu-item-data-db-id' ).value;
			parentItemName = parentItem.querySelector( '.menu-item-title' ).textContent;
			itemPosition = position - [ ...editMenu.querySelectorAll( 'li') ].indexOf( parentItem );

			// String together help text for sub menu items.
			title = menus.subMenuFocus.replace( '%1$s', itemName ).replace( '%2$d', itemPosition ).replace( '%3$s', parentItemName );
		}

		itemToRefresh.querySelector( '.item-move' ).setAttribute( 'aria-label', title );
	}

	/**
	 * refreshAdvancedAccessibility
	 *
	 * Hides all advanced accessibility buttons and marks them for refreshing.
	 */
	function refreshAdvancedAccessibility() {

		// Hide all the move buttons by default
		document.querySelectorAll( '.menu-item-settings .field-move .menus-move' ).forEach( function( move ) {
			move.style.display = 'none';
		} );

		// All open items have to be refreshed
		document.querySelectorAll( '.menu-item-edit-active .item-move' ).forEach( function( itemEdit ) {
			refreshAdvancedAccessibilityOfItem( itemEdit.closest( 'li.menu-item' ) );
		} );
	}

	// Get depth of menu item
	function menuItemDepth( item ) {
		var i, n, itemDepth, itemClasses = item.className.split( ' ' );
		for ( i = 0, n = itemClasses.length; i < n; i++ ) {
			if ( itemClasses[i].startsWith( 'menu-item-depth-' ) ) {
				itemDepth = parseInt( itemClasses[i].split('-').pop(), 10 );
			}
		}
		return itemDepth || 0;
	}

	function updateDepthClass( item, current, prev ) {
		prev = prev || menuItemDepth( item );
		item.classList.remove( 'menu-item-depth-' + prev );
		item.classList.add( 'menu-item-depth-' + current );
	}

	function shiftDepthClass( items, change ) {
		items.forEach( function( item ) {
			var depth = menuItemDepth( item ),
				newDepth = depth + change;

			item.classList.remove( 'menu-item-depth-' + depth );
			item.classList.add( 'menu-item-depth-' + newDepth );

			if ( 0 === newDepth ) {
				item.querySelector( '.is-submenu' ).style.display = 'none';
			}
		} );
	}

	// Get children of menu item
	function childMenuItems( item ) {
		var childrenArray = [],
			depth = menuItemDepth( item ),
			next = item.nextElementSibling;

		while( next && menuItemDepth( next ) > depth ) {
			childrenArray.push( next );
			next = next.nextElementSibling;
		}
		return childrenArray;
	}

	function shiftHorizontally( item, dir ) {
		var depth = menuItemDepth( item ),
			newDepth = depth + dir;

		// Change .menu-item-depth-n class
		moveHorizontally( item, newDepth, depth );
	}

	function moveHorizontally( item, newDepth, depth ) {
		var children = childMenuItems( item ),
			diff = newDepth - depth,
			subItemText = item.querySelector( '.is-submenu' );

		// Change .menu-item-depth-n class.
		updateDepthClass( item, newDepth, depth );
		updateParentMenuItemDBId( item );

		// If it has children, move those too.
		if ( children ) {
			children.forEach( function( child ) {
				var thisDepth = menuItemDepth( child ),
					newDepth = thisDepth + diff;

				updateDepthClass( child, newDepth, thisDepth );
				updateParentMenuItemDBId( child );
			} );
		}

		// Show "Sub item" helper text.
		if ( 0 === newDepth ) {
			subItemText.style.display = 'none';
		} else {
			subItemText.style.display = '';
		}
	}

	// Update parent ID of menu item
	function updateParentMenuItemDBId( item ) {
		var input = item.querySelector( '.menu-item-data-parent-id' ),
			depth = parseInt( menuItemDepth( item ), 10 ),
			parentDepth = depth - 1,
			parent = getPreviousSibling( item, '.menu-item-depth-' + parentDepth );

		if ( 0 === depth || parent === undefined ) { // Item is on the top level, has no parent.
			input.value = 0;
		} else { // Find the parent item, and retrieve its object id.
			input.value = parent.querySelector( '.menu-item-data-db-id' ).value;
		}
	}

	/*
	 * Move sub-items if their parent item moves after dragging
	 */
	function moveChildItems( prevItem, thisItem, depth ) {
		var i, n, startingDepth, nextDepth, newDepth, newClasses,
			nextItem = thisItem.nextElementSibling;

		// Move to new position
		prevItem.after( thisItem );

		// Set new depth of current item
		newClasses = thisItem.className.split( ' ' );
		for ( i = 0, n = newClasses.length; i < n; i++ ) {
			if ( newClasses[i].startsWith( 'menu-item-depth-' ) ) {
				startingDepth = parseInt( newClasses[i].split('-').pop(), 10 );
				newClasses[i] = 'menu-item-depth-' + depth;
			}
		}
		thisItem.className = newClasses.join( ' ' );
		thisItem.style.marginLeft = '';

		// Get depth of next item in list
		if ( nextItem ) {
			nextDepth = menuItemDepth( nextItem );

			// Trigger to move sub-items if their parent moves
			if ( startingDepth <= nextDepth ) {
				newDepth = startingDepth === nextDepth ? depth : depth + 1;
				moveChildItems( thisItem, nextItem, newDepth );
			}
		}
	}

	/**
	 * Remove a menu item.
	 *
	 * @param {Object} el The element to be removed
	 *
	 * @fires document#menu-removing-item Passes the element to be removed.
	 */
	function removeMenuItem( el ) {
		var children = childMenuItems( el );

		document.dispatchEvent( new CustomEvent( 'menu-removing-item', {
			detail: el
		} ) );

		el.classList.add( 'deleting' );
		el.animate(
			{ opacity: [1, 0] },
			{ duration: 350, iterations: 1, easing: 'ease-out' } )
			.onfinish = function() {

			var ins = document.getElementById( 'menu-instructions' );

			el.remove();

			// Update position of children, if any
			if ( children.length > 0 ) {
				updateParentMenuItemDBId( shiftDepthClass( children, -1 ) );
			}

			if ( 0 === editMenu.querySelector( 'li' ).length ) {
				document.querySelector( '.drag-instructions' ).style.display = 'none';
				ins.classList.remove( 'menu-instructions-inactive' );
			}

			refreshAdvancedAccessibility();
			wp.a11y.speak( menus.itemRemoved );
		};
	}

	/**
	 * Enable bulk selection checkboxes for menu items
	 */
	function enableBulkSelection() {
		editMenu.classList.add( 'bulk-selection' );
		document.getElementById( 'nav-menu-bulk-actions-top' ).classList.add( 'bulk-selection' );
		document.getElementById( 'nav-menu-bulk-actions-bottom' ).classList.add( 'bulk-selection' );

		checkboxes.forEach( function( checkbox ) {
			checkbox.disabled = false;
		} );
	}

	/**
	 * Disable bulk selection checkboxes for menu items
	 */
	function disableBulkSelection() {
		var pendingDeletionList = document.querySelector( '#pending-menu-items-to-delete ul' );

		editMenu.classList.remove( 'bulk-selection' );
		document.getElementById( 'nav-menu-bulk-actions-top' ).classList.remove( 'bulk-selection' );
		document.getElementById( 'nav-menu-bulk-actions-bottom' ).classList.remove( 'bulk-selection' );

		if ( document.querySelector( '.menu-items-delete' ).getAttribute( 'aria-describedby' ) === 'pending-menu-items-to-delete' ) {
			document.querySelector( '.menu-items-delete' ).removeAttribute( 'aria-describedby' );
		}

		checkboxes.forEach( function( checkbox ) {
			checkbox.disabled = true;
			checkbox.checked = false;
		} );

		document.querySelector( '.menu-items-delete' ).classList.add( 'disabled' );
		while ( pendingDeletionList.lastChild ) {
			pendingDeletionList.removeChild( pendingDeletionList.lastChild );
		}
	}

	/*
	 * Helper function copied from jQuery
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

} );
