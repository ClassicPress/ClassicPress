/**
 * @since CP-2.8.0
 * @requires SortableJS
 *
 * @output wp-admin/js/customize-nav-menus.js
 */

/* global _wpCustomizeControlsL10n, _wpCustomizeNavMenusSettings,
console, ajaxurl, _updatedControlsWatcher, Sortable, isRtl */
document.addEventListener( 'DOMContentLoaded', function() {
	var addObserver, itemObserver, currentMenuId,
		newMenuItemIDs = [],
		availableMenuItems = document.getElementById( 'available-menu-items' ),
		menuToEdit = document.getElementById( 'menu-to-edit' ),
		form = document.querySelector( 'form' ),
		saveButton = form.querySelector( '#save' );

	// Enable menu item sorting if the page loads on a menu
	const hash = window.location.hash.replace( '#', '' );
	if ( hash.startsWith( 'sub-accordion-section-nav_menu[' ) ) {
		initSortables( hash );
	}

	/**
	 * Ensure auto_add checkbox works as intended when a new menu is created
	 */
	addObserver = new MutationObserver( function() {
		if ( menuToEdit.querySelector( '.auto_add' ) ) {
			menuToEdit.querySelector( '.auto_add' ).addEventListener( 'input', function( e ) {
				inputChanged( e.target, e.target.closest( 'li' ) );
			} );
			menuToEdit.querySelector( '.auto_add' ).addEventListener( 'change', function( e ) {
				inputChanged( e.target, e.target.closest( 'li' ) );
			} );
			addObserver.disconnect();
		}
	} );
	addObserver.observe( menuToEdit, { attributes: false, childList: true, characterData: false, subtree: true } );

	/**
	 * Make items in new menu sortable
	 */
	itemObserver = new MutationObserver( function() {
		if ( menuToEdit.querySelector( '.menu-item' ) ) {
			initSortables( menuToEdit.id );
			itemObserver.disconnect();
		}
	} );
	itemObserver.observe( menuToEdit, { attributes: false, childList: true, characterData: false, subtree: true } );

	/**
	 * Trigger activation of Publish button
	 */
	function activatePublishButton() {
		saveButton.disabled = false;
		saveButton.textContent = _wpCustomizeControlsL10n.publish;
	}

	/**
	 * Prepare changed object for publication
	 */
	function inputChanged( input, li ) {
		let menuId, title, menuLocations, assignments, span, itemId,
			settingId = li.dataset.settingId,
			value = input.value.trim(),
			menuName = li.closest( '.customize-pane-child' ).querySelector( '.menu-name-field' ).value;

		if ( settingId.startsWith( 'nav_menu_locations[' ) ) {
			assignments = document.querySelectorAll( '.assigned-to-menu-location' );
			menuLocations = document.querySelectorAll( '.assigned-to-menu-location [data-setting-id="' + settingId + '"]' );

			if ( input.tagName === 'INPUT' ) {
				span = document.createElement( 'span' );
				span.className = 'current-menu-location-name-main-nav';

				if ( input.checked ) {
					span.textContent = menuName;
					input.value = li.parentNode.dataset.menuId;
					_updatedControlsWatcher[ settingId ] = li.parentNode.dataset.menuId;
					input.nextElementSibling.querySelector( '.theme-location-set' ).innerHTML = '(' + _wpCustomizeControlsL10n.current + ' ' + span.outerHTML + ')';

					menuLocations.forEach( function( menuLocation ) {
						if ( menuLocation.querySelector( 'input' ) ) {
							menuLocation.querySelector( '.theme-location-set' ).innerHTML = '(' + _wpCustomizeControlsL10n.current + ' ' + span.outerHTML + ')';
							menuLocation.querySelector( 'input' ).value = li.parentNode.dataset.menuId;
							if ( menuLocation.closest( '.menu-location-settings' ).dataset.menuId === li.parentNode.dataset.menuId ) {
								menuLocation.querySelector( 'input' ).checked = true;
							} else {
								menuLocation.querySelector( 'input' ).checked = false;
							}
						}
					} );

					// Update parenthetical message in menus list.
					assignments.forEach( function( assign ) {
						if ( assign.querySelector( '.menu-in-location' )?.textContent.trim() === '(' + _wpCustomizeControlsL10n.currently + ' ' + input.nextElementSibling.innerHTML.split( '<span' )[0].trim() + ')' ) {
							assign.querySelector( '.menu-in-location' ).textContent = '';
						}
					} );
				} else {
					input.value = '';
					_updatedControlsWatcher[ settingId ] = '';
					input.nextElementSibling.querySelector( '.theme-location-set' ).innerHTML = '';

					menuLocations.forEach( function( menuLocation ) {
						if ( menuLocation.querySelector( 'input' ) ) {
							menuLocation.querySelector( 'input' ).value = '';
							if ( menuLocation.closest( '.menu-location-settings' ).dataset.menuId === li.parentNode.dataset.menuId ) {
								menuLocation.querySelector( 'input' ).checked = false;
								menuLocation.querySelector( '.theme-location-set' ).innerHTML = span.outerHTML;
							}
						}
					} );
				}
			}
		} else if ( settingId.startsWith( 'nav_menu[' ) ) {
			_updatedControlsWatcher[ settingId ] = {
				name: li.closest( 'ul' ).querySelector( '.menu-name-field' ).value.trim(),
				auto_add: li.closest( 'ul' ).querySelector( '.auto_add' ).checked ? 1 : 0
			};
		} else if ( settingId.startsWith( 'nav_menu_item[' ) ) {
			title = li.querySelector( '.edit-menu-item-title' ).value.trim();
			li.querySelector( '.menu-item-title' ).textContent = title;
			itemId = li.querySelector( '.menu-item-data-db-id' ).value;

			menuId = li.querySelector( '.menu-item-data-menu-id' ).value;
			menuName = li.parentNode.querySelector( '[data-setting-id="nav_menu[' + menuId + ']"]' );
			_updatedControlsWatcher[ 'nav_menu[' + menuId + ']' ] = {
				name: menuName.querySelector( 'input' ).value.trim()
			};
			_updatedControlsWatcher[ settingId ] = {
				menu_id: menuId,
				title: title,
				url: li.querySelector( '.edit-menu-item-url' )?.value.trim() || '',
				menu_item_parent: li.querySelector( '.menu-item-data-parent-id' ).value,
				position: li.querySelector( '.menu-item-data-position' ).value,
				original_title: li.querySelector( '.original-link' )?.textContent || '',
				object_id: li.querySelector( '.menu-item-data-object-id' ).value,
				object: li.querySelector( '.menu-item-data-object' ).value,
				type: li.querySelector( '.menu-item-data-type' ).value,
				type_label: li.querySelector( '.item-type' ).value,
				classes: li.querySelector( '.edit-menu-item-classes' ).value,
				xfn: li.querySelector( '.edit-menu-item-xfn' ).value,
				target: li.querySelector( '.edit-menu-item-target' ).value,
				attr_title: li.querySelector( '.edit-menu-item-attr-title' ).value,
				description: li.querySelector( '.edit-menu-item-description' ).value,
				status: 'publish',
				// Fields for Nav Menu Roles plugin
				display_mode: li.querySelector( 'input[name="nav-menu-display-mode[' + itemId + ']"]:checked' )?.value || '',
				roles: Array.from( li.querySelectorAll( '.edit-menu-item-role[value]' ) ).filter( cb => cb.checked ).map( cb => cb.value )
			};
		} else {
			_updatedControlsWatcher[ settingId ] = value;
		}
		activatePublishButton();
	}

	function handleMenuEvent( e ) { console.log(e);
		const input = e.target,
			li = input.closest( 'li[data-setting-id]' );

		if ( ! li ) {
			return;
		}

		const settingId = li.dataset.settingId;
		if ( ! settingId.startsWith( 'nav_menu_locations[' ) && ! settingId.startsWith( 'nav_menu[' ) && ! settingId.startsWith( 'nav_menu_item[' ) ) {
			return;
		}

		inputChanged( input, li );
	}

	form.addEventListener( 'input', handleMenuEvent );
	form.addEventListener( 'change', handleMenuEvent );

	/**
	 * Makes menu items sortable
	 *
	 * @since CP-2.8.0
	 */
	function initSortables( listId ) {
		var originalClientX, originalDepth, baseClientX, newClientX,
			maxDepth = 11,
			childrenInfo = {},
			indent = 30,
			editMenu = document.getElementById( listId ),
			menuEdge = getOffset( editMenu ).left,
			menusChanged = false,
			isRTL = !! ( 'undefined' != typeof isRtl && isRtl );

		// Use the right edge if RTL
		menuEdge += isRTL ? editMenu.innerWidth : 0;

		/**
		 * Attach SortableJS to current menu
		 */
		var sortable = new Sortable( editMenu, {
			group: 'menu',
			handle: 'summary',
			filter: '.no-drag',
			preventOnFilter: false,
			setData: function( dataTransfer, dragEl ) {
				var ghostImage = document.createElement( 'li' );
				ghostImage.id = 'sortable-ghost';
				ghostImage.className = 'menu-item';
				ghostImage.style.listStyle = 'none';
				ghostImage.innerHTML = '<div class="menu-item-bar"><details class="menu-item-handle"><summary><span class="item-title"><span class="menu-item-title">' + dragEl.querySelector( '.menu-item-title' ).textContent + '</span></span></summary></details></div>';
				ghostImage.style.position = 'absolute';
				ghostImage.style.top = '-1000px';
				ghostImage.style.width = dragEl.getBoundingClientRect().width + 'px';
				document.body.appendChild( ghostImage );
				dataTransfer.setDragImage( ghostImage, 30, 20 );
			},
			dataIdAttr: 'data-setting-id', // HTML attribute that is used by the `toArray()` method in OnEnd

			// Get position of menu item when chosen
			onChoose: function( e ) {
				originalClientX = e.originalEvent.clientX;
				originalDepth = menuItemDepth( e.item );
				baseClientX = e.originalEvent.clientX - ( originalDepth * indent );
			},

			// Start dragging
			onStart: function( e ) {
				var prevItem, children;

				// Close menu item
				if ( e.item.querySelector( 'details' ).hasAttribute( 'open' ) ) {
					e.item.querySelector( 'details' ).removeAttribute( 'open' );
				}

				// Register event and create data ids for every menu item
				editMenu.dispatchEvent( new CustomEvent( 'sortstart' ) );
				editMenu.querySelectorAll( 'li' ).forEach( function( el ) {
					el.dataset.id = el.id;
				} );

				// Continually update horizontal position of current item while dragging
				editMenu.addEventListener( 'dragover', function( evt ) {
					var xPos, prevDepth, diff;

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
						if ( prevItem === null || xPos < 0 ) {
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

			// Keeps undraggable elements in fixed position in list
			onMove: function( e ) {
				if ( e.related.className.includes( 'no-drag' ) ) {
					return false;
				}
			},

			// Element dropped
			onEnd: function( e ) {
				var i, n, diff, prevItem, parent, parentDepth,
					details = e.item.querySelector( 'details' ),
					depth = 0,
					prevDepth = 0,
					draggedClasses = e.item.className.split( ' ' ),
					menuItems = Array.from( editMenu.querySelectorAll( ':scope > .menu-item' ) );

				// Revert styling and set focus on move icon
				e.item.style.marginLeft = '';
				details.querySelector( 'summary' ).style.visibility = 'visible';
				details.querySelector( 'summary' ).focus();

				// Send list of menu items, ordered by IDs
				editMenu.dispatchEvent( new CustomEvent( 'sortstop', {
					detail: sortable.toArray()
				} ) );

				// Handle drop placement for RTL orientation
				if ( isRTL ) {
					e.item.style.marginLeft = 'auto';
					e.item.style.marginRight = '';
				}

				// Get depth of previous item in list, allowing for two extra initial items
				prevItem = e.item.previousElementSibling;
				if ( prevItem && prevItem.className.includes( 'menu-item' ) ) {
					prevDepth = menuItemDepth( prevItem );
				}

				// Set depth of current item
				for ( i = 0, n = draggedClasses.length; i < n; i++ ) {
					if ( draggedClasses[i].startsWith( 'menu-item-depth-' ) ) {
						if ( e.newDraggableIndex < 3 || prevItem.className.includes( 'section-meta' ) || prevItem.className.includes( 'customize-control-nav_menu_name' ) ) { // first element
							draggedClasses[i] = 'menu-item-depth-0'; // don't indent
						} else {
							diff = Math.floor( ( newClientX - originalClientX ) / indent );
							depth = parseInt( originalDepth + diff, 10 );
							if ( depth > prevDepth ) {
								parent = prevItem;
								depth = prevDepth + 1;
								e.item.querySelector( '.menu-item-data-parent-id' ).value = parent.querySelector( '.menu-item-data-db-id' ).value;
							}
							if ( depth > maxDepth ) {
								depth = maxDepth;
							} else if ( depth < 0 ) {
								depth = 0;
							}
							draggedClasses[i] = 'menu-item-depth-' + depth;
						}
					}
					e.item.className = draggedClasses.join( ' ' );

					if ( depth === 0 ) {
						e.item.querySelector( '.menu-item-data-parent-id' ).value = 0;
					} else {
						parentDepth = parseInt( depth - 1, 10 );
						parent = getPreviousSibling( e.item, '.menu-item-depth-' + parentDepth );
						if ( ! parent ) {
							e.item.querySelector( '.menu-item-data-parent-id' ).value = 0;
						} else {
							e.item.querySelector( '.menu-item-data-parent-id' ).value = parent.querySelector( '.menu-item-data-db-id' ).value;
						}
					}
				}

				// Set original clientX to current clientX to establish new starting position
				originalClientX = newClientX;
				menusChanged = true;

				// Move sub-items if this is a parent
				if ( Object.keys( childrenInfo ).length > 0 ) {
					moveChildItems( childrenInfo.prevItem, childrenInfo.menuItem, depth + 1 );

					// Reset for next drag and drop
					childrenInfo = {};
				}

				// Prepare updatedControls object with new order of menu items
				menuItems.forEach( function( li, idx ) {
					var newDepth, newPrevDepth,
						settingId = li.dataset.settingId,
						parentId = li.querySelector( '.menu-item-data-parent-id' ).value;

					li.querySelector( '.menu-item-data-position' ).value = idx + 1; // update hidden input field
					li.className = li.className.split( 'menu-item-edit-inactive' )[0] + 'menu-item-edit-inactive';
					if ( idx === 0 ) {
						li.classList.add( 'move-up-disabled' );
						li.classList.add( 'move-left-disabled' );
						li.classList.add( 'move-right-disabled' );
					}
					if ( idx === menuItems.length - 1 ) {
						li.classList.add( 'move-down-disabled' );
					}

					newDepth = parseInt( li.className.split( 'menu-item-depth-' )[1], 10 );
					newPrevDepth = li.previousElementSibling.classList.contains( 'menu-item' ) ? parseInt( li.previousElementSibling.className.split( 'menu-item-depth-' )[1], 10 ) : 0;
					if ( parentId === 0 || newDepth === 0 ) {
						li.classList.add( 'move-left-disabled' );
					} else if ( newDepth === maxDepth ) {
						li.classList.add( 'move-right-disabled' );
					}
					if ( newDepth > newPrevDepth ) {
						li.classList.add( 'move-up-disabled' );
						li.classList.add( 'move-down-disabled' );
						li.classList.add( 'move-right-disabled' );
					} else if ( newDepth < newPrevDepth ) {
						li.classList.add( 'move-up-disabled' );
						li.classList.add( 'move-down-disabled' );
						li.classList.add( 'move-left-disabled' );
					}

					_updatedControlsWatcher[ settingId ] = {
						nav_menu_term_id: li.querySelector( '.menu-item-data-menu-id' ).value,
						position: idx + 1,
						menu_item_parent: parentId,
						title: li.querySelector( '.edit-menu-item-title' ).value.trim(),
						url: li.querySelector( '.edit-menu-item-url' )?.value.trim() || '',
						original_title: li.querySelector( '.original-link' )?.textContent || '',
						object_id: li.querySelector( '.menu-item-data-object-id' ).value,
						object: li.querySelector( '.menu-item-data-object' ).value,
						type: li.querySelector( '.menu-item-data-type' ).value,
						type_label: li.querySelector( '.item-type' ).value,
						classes: li.querySelector( '.edit-menu-item-classes' ).value,
						xfn: li.querySelector( '.edit-menu-item-xfn' ).value,
						target: li.querySelector( '.edit-menu-item-target' ).value,
						attr_title: li.querySelector( '.edit-menu-item-attr-title' ).value,
						description: li.querySelector( '.edit-menu-item-description' ).value,
						status: 'publish'
					};
				} );

				activatePublishButton();
			}

		} );
	}

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

	// Get depth of menu item
	function menuItemDepth( item ) {
		var i, n, itemDepth,
			itemClasses = item.className.split( ' ' );

		for ( i = 0, n = itemClasses.length; i < n; i++ ) {
			if ( itemClasses[i].startsWith( 'menu-item-depth-' ) ) {
				itemDepth = parseInt( itemClasses[i].split('-').pop(), 10 );
			}
		}
		return itemDepth || 0;
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

	/**
	 * Move sub-items if their parent item moves after dragging
	 */
	function moveChildItems( prevItem, thisItem, depth ) {
		var i, n, startingDepth, nextDepth, newDepth,
			newClasses = thisItem.className.split( ' ' ),
			nextItem = thisItem.nextElementSibling;

		// Move to new position
		prevItem.after( thisItem );

		// Set new depth of current item
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
	 * Add menu item
	 */
	function addMenuItem( type, object, objectId, title, label, url ) {
		var data,
			menu       = document.getElementById( 'sub-accordion-section-nav_menu[' + currentMenuId + ']' ) || menuToEdit,
			menuItems  = menu.querySelectorAll( '.menu-item' ),
			lastItem   = menuItems[menuItems.length - 1],
			menuItemId = '-' + Date.now(),
			template   = document.getElementById( 'tmpl-new-menu-item' ),
			clone      = template.content.cloneNode( true );

		const addItemsPanel = document.getElementById( 'available-menu-items' );

		if ( type === 'custom' ) {
			clone.querySelector( '.field-url' ).removeAttribute( 'hidden' );
			clone.querySelector( '.edit-menu-item-url' ).value = url;
			clone.querySelector( '.link-to-original' ).remove();
		} else {
			clone.querySelector( '.link-to-original a' ).href = url;
			clone.querySelector( '.link-to-original a' ).textContent = title;
		}
		clone.querySelector( '.edit-menu-item-title' ).value = title;

		clone.querySelector( 'li' ).id = 'customize-control-nav_menu_item-' + menuItemId;
		clone.querySelector( 'li' ).dataset.settingId = 'nav_menu_item[' + menuItemId + ']';
		clone.querySelector( '.menu-item-title' ).textContent = title;
		clone.querySelector( '.item-controls .screen-reader-text' ).textContent = clone.querySelector( '.item-controls .screen-reader-text' ).textContent + ' ' + title + ' (' + label + ')';
		clone.querySelector( '.item-type' ).textContent = label;
		clone.querySelector( '.menu-item-data-db-id' ).value = menuItemId;
		clone.querySelector( '.menu-item-data-object-id' ).value = objectId;
		clone.querySelector( '.menu-item-data-object' ).value = object;
		clone.querySelector( '.menu-item-data-position' ).value = menuItems.length + 1;
		clone.querySelector( '.menu-item-data-type' ).value = type;

		// Update property values
		clone.querySelector( '.menu-item-settings' ).id = 'menu-item-settings-' + menuItemId;
		clone.querySelectorAll( '.menu-item-settings input' ).forEach( function( el ) {
			if ( el.id ) {
				el.id = el.id.replace( 'brand-new', menuItemId );
			}
			el.name = el.name.replace( 'brand-new', menuItemId );
		} );
		clone.querySelectorAll( '.menu-item-settings label' ).forEach( function( el ) {
			if ( el.htmlFor ) {
				el.htmlFor = el.htmlFor.replace( 'brand-new', menuItemId );
			}
		} );

		clone.querySelectorAll( '.menu-item-settings p:not( .link-to-original )' ).forEach( function( para ) {
			para.querySelector( 'label' ).htmlFor = para.querySelector( 'label' ).htmlFor + menuItemId;
			if ( para.querySelector( 'textarea' ) ) {
				para.querySelector( 'textarea' ).id = para.querySelector( 'textarea' ).id + menuItemId;
			} else {
				para.querySelector( 'input' ).id = para.querySelector( 'input' ).id + menuItemId;
			}
		} );

		// Prepare JS object for publishing
		_updatedControlsWatcher[ 'nav_menu_item[' + menuItemId + ']' ] = {
			nav_menu_term_id: currentMenuId,
			title: title,
			original_title: title,
			url: url,
			type: type,
			type_label: label,
			object: object,
			object_id: objectId,
			position: menuItems.length + 1,
			status: 'publish'
		};

		// Save to changeset
		data = new URLSearchParams( {
			action:                     'customize_save',
			wp_customize:               'on',
			customize_changeset_status: 'draft',
			nonce:                      document.getElementById( 'customizer_nonce' ).value,
			customize_changeset_uuid:   document.getElementById( 'customize_changeset_uuid' ).value,
			customized:                 JSON.stringify( window._cpDirtySettings || {} )
		} );

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
		.then( function( result ) {
			var target;
			if ( result.success ) {
				// Trigger partial refresh
				target = window.getPreviewChannel();
				if ( target ) {
					target.iframe.contentWindow.postMessage(
						JSON.stringify( {
							channel: target.channel,
							type: 'setting',
							data: 'nav_menu[' + currentMenuId + ']'
						} ),
						location.origin
					);
				}

				// Clear dirty settings that have now been saved to the changeset
				window._cpDirtySettings = {};
				window._cpPreviewSettings = {};

				// Add to menu
				if ( lastItem ) { // menu currently has at least one item
					lastItem.classList.remove( 'move-down-disabled' );
					lastItem.after( clone ); // add as last item to populated menu
					menu.querySelector( '.reorder-toggle' ).style.display = '';
				} else { // menu is currently empty
					clone.querySelector( 'li' ).classList.add( 'move-up-disabled' );
					clone.querySelector( 'li' ).classList.add( 'move-right-disabled' );
					menu.querySelector( '.customize-control-nav_menu_name' ).after( clone );
					menu.querySelector( '.no-items-message' )?.remove();
				}

				// Remove overlay and close "Add items" panel
				document.body.classList.remove( 'adding-menu-items' );
				if ( addItemsPanel ) {
					addItemsPanel.style.display = 'none';
				}

				// Reset toggle buttons
				document.querySelectorAll( '.add-new-menu-item' ).forEach( function( btn ) {
					btn.setAttribute( 'aria-expanded', 'false' );
				} );

				activatePublishButton();
			}
		} )
		.catch( function( err ) {
			console.error( err );
		} );
	}

	/**
	 * Delete menu item
	 */
	function deleteMenuItem( item ) {
		var data,
			menuItemId = item.querySelector( '.menu-item-data-db-id' ).value,
			deletionData = {};

		_updatedControlsWatcher[ 'nav_menu_item[' + menuItemId + ']' ] = false;
		deletionData[ 'nav_menu_item[' + menuItemId + ']' ] = {
			value: false
		};

		// Save to changeset
		data = new URLSearchParams( {
			action:                     'customize_save',
			wp_customize:               'on',
			customize_changeset_status: 'draft',
			nonce:                      document.getElementById( 'customizer_nonce' ).value,
			customize_changeset_uuid:   document.getElementById( 'customize_changeset_uuid' ).value,
			customize_changeset_data:   JSON.stringify( deletionData ),
			customized:                 JSON.stringify( deletionData )
		} );

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
		.then( function( result ) {
			if ( result.success ) {

				// Don't clear _cpDirtySettings — keep deletion flag for Publish
				// Instead mark this item as deleted so Publish sends false for it
				window._cpDeletedItems = window._cpDeletedItems || {};
				window._cpDeletedItems[ 'nav_menu_item[' + menuItemId + ']' ] = false;

				item.remove();
				activatePublishButton();
			}
		} )
		.catch( function( err ) {
			console.error( err );
		} );
	}

	/**
	 * Delete nav menu
	 */
	function deleteNavMenu( menuSettingId ) {
		const menuId = menuSettingId.split( '[' )[1].replace( ']', '' );

		// Prepare the nav_menu[] object for sending to the back-end
		_updatedControlsWatcher[ menuSettingId ] = 'delete-menu'; // will be changed to false later, but cannot set that here

		// Update the Customizer's visual appearance
		document.getElementById( 'accordion-section-' + menuSettingId ).remove();
		document.getElementById( 'sub-accordion-section-' + menuSettingId ).style.display = 'none';
		document.getElementById( 'sub-accordion-panel-nav_menus' ).style.display = 'block';

		// Remove from menu locations and prepare menu_locations[] objects for sending to back-end if appropriate
		document.getElementById( 'sub-accordion-section-menu_locations' ).querySelectorAll( 'select' ).forEach( function( select ) {
			if ( select.value === menuId ) {
				_updatedControlsWatcher[ select.closest( 'li' ).dataset.settingId  ] = '';
				select.value = '0';
				select.querySelector( 'option[value="' + menuId + '"]' ).remove();
				select.nextElementSibling.classList.remove( 'hidden' );
				select.nextElementSibling.nextElementSibling.classList.add( 'hidden' );
			}
		} );
		activatePublishButton();
	}

	/**
	 * Create a new post or page
	 */
	function createNewPostOrPage( title, object, type, label, itemsList ) {
		var li,
			data = new URLSearchParams( {
				action: 'customize-nav-menus-insert-auto-draft',
				wp_customize: 'on',
				'params[post_title]': title,
				'params[post_type]': object, // post or page
				'customize-menus-nonce': _wpCustomizeControlsL10n.menusNonce
			} );

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
		.then( function( result ) {
			if ( result.success ) {
				newMenuItemIDs.push( result.data.post_id );
				li = document.createElement( 'li' );
				li.id = result.data.post_id;
				li.className = 'menu-item-tpl';
				li.dataset.menuItemId = object + '-' + result.data.post_id;
				li.innerHTML = '<div class="menu-item-bar">' +
					'<div class="menu-item-handle">' +
					'<button type="button" class="button-link item-add">' +
					'<span class="screen-reader-text">Add to menu: ' + result.data.title + ' (' + label + ')</span>' +
					'</button>' +
					'<span class="item-split">' +
					'<span class="item-title" aria-hidden="true">' +
					'<span class="menu-item-title">' + result.data.title + '</span>' +
					'</span>' +
					'<span class="item-type" aria-hidden="true">' +	label + '</span>' +
					'</span>' +
					'</div>' +
					'</div>' +
					'<span class="item-url" hidden="">' + result.data.url + '</span>';
				itemsList.prepend( li );
				addMenuItem( type, object, result.data.post_id, result.data.title, label, result.data.url );
			}
		} )
		.catch( function( err ) {
			console.error( err );
		} );
	}

	/**
	 * Replaces the substring 'brand-new' in new menu attributes with negative integer.
	 * Then replaces the negative integer with menuId on new menu publication.
	 *
	 * @since CP-2.8.0
	 */
	function replaceSubstringInAttributes( original, replacement ) {
		let all = menuToEdit.querySelectorAll( '*' );
		if ( all.length < 1 ) { // menu already moved to new place
			all = document.getElementById( 'sub-accordion-section-nav_menu[' + original + ']' ).querySelectorAll( '*' ); // locate moved menu and its attributes
			document.getElementById( 'sub-accordion-section-nav_menu[' + original + ']' ).id = 'sub-accordion-section-nav_menu[' + replacement + ']';
		}

		all.forEach( function( el ) {
			const attrs = el.getAttributeNames();
			attrs.forEach( function( attrName ) {
				const value = el.getAttribute( attrName );
				if ( value && value.includes( original ) ) {
					const newValue = value.replaceAll( original, replacement );
					el.setAttribute( attrName, newValue );
				}
			} );
		} );
	}

	/**
	 * Move new menu to its own ul element
	 */
	function moveNewMenu( id ) {
		const range = document.createRange(),
			ul = document.createElement( 'ul' ),
			currentMenuId = id.split( '[' )[1].replace( ']', '' );
		let fragment, newMenuId, newMenuString;

		range.selectNodeContents( menuToEdit );
		fragment = range.extractContents();
		newMenuId = fragment.querySelector( '[data-menu-id]' ).dataset.menuId;
		newMenuString = 'accordion-section-nav_menu[' + newMenuId + ']';

		ul.id = 'sub-' + id.replace( currentMenuId, newMenuId );
		ul.className = 'customize-pane-child accordion-section-content accordion-section control-section control-section-nav_menu menu assigned-to-menu-location';
		ul.append( fragment );
		ul.querySelector( '.new-menu-title' ).textContent = ul.querySelector( '.menu-name-field' ).value.trim();
		document.getElementById( 'sub-accordion-section-menu_locations' ).after( ul );

		// Update menu on menu list
		document.getElementById( 'accordion-section-nav_menu[' + currentMenuId + ']' ).id = newMenuString;
		return newMenuString;
	}

	/**
	 * Handle clicks on buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var title, navMenuId, type, object, objectId, label, url, li,
			template, clone, newMenuId, position, depth, classNameSplits,
			allItems, updatedItems, liIndex, targetSibling,
			targetSiblingIndex, insertAfter, newDepth, newParentId,
			menuName = '',
			children = [],
			targetChildren = [],
			id = e.target.closest( 'li' )?.id,
			ul = e.target.closest( 'ul' );

		// Open New Menu panel
		if ( e.target.id === 'customize-add-menu-button' || e.target.classList.contains( 'create-menu' ) ) {
			document.getElementById( 'sub-accordion-section-add_menu' ).querySelectorAll( 'input' ).forEach( function( input ) {
				input.value = ''; // reset
				input.checked = false; // reset
			} );

		// Open Next panel to create new menu
		} else if ( e.target.id === 'customize-new-menu-submit' ) {
			if ( ul.querySelector( '#menu-title' ).value === '' ) {
				e.preventDefault(); // prevent opening if the menu has no title
				return;
			}

			// If menu-to-edit is currently populated, move sub-nodes to their own ul element and hide it.
			if ( menuToEdit.querySelector( 'li' ) ) {
				id = menuToEdit.querySelector( '[data-setting-id]' ).dataset.settingId;
				id = id.replace( 'nav_menu', 'accordion-section-nav_menu' );
				moveNewMenu( id );
				document.getElementById( 'sub-' + id ).style.display = 'none';
			}

			navMenuId = '-' + Date.now();
			title = ul.querySelector( '#menu-title' ).value;
			template = document.getElementById( 'tmpl-new-nav-menu' );
			clone = template.content.cloneNode( true );
			menuToEdit.append( clone );

			// Update attributes and values
			document.getElementById( 'menu-name-title-brand-new' ).value = title;
			replaceSubstringInAttributes( 'brand-new', navMenuId );
			menuToEdit.querySelectorAll( '.assigned-menu-location input' ).forEach( function( input, index ) {
				if ( ul.querySelectorAll( '.assigned-menu-location input' )[index]?.checked ) {
					input.checked = true;
					menuName = input.nextElementSibling.innerHTML.split( '<span' )[0].trim();
					inputChanged( input, input.closest( 'li' ) );
				}
				input.addEventListener( 'input', function() {
					inputChanged( input, input.closest( 'li' ) );
				} );
				input.addEventListener( 'change', function() {
					inputChanged( input, input.closest( 'li' ) );
				} );
			} );
			inputChanged( menuToEdit.querySelector( '.menu-name-field' ), menuToEdit.querySelector( '.menu-name-field' ).closest( 'li' ) );
			menuToEdit.querySelector( 'a' ).href = '#' + menuToEdit.dataset.parentId;
			menuToEdit.querySelector( 'a' ).focus();

			// Add menu to list of menus
			li = document.createElement( 'li' );
			li.id = 'accordion-section-nav_menu[' + navMenuId + ']';
			li.className = 'accordion-section control-section control-section-nav_menu control-subsection assigned-to-menu-location';
			li.setAttribute( 'aria-owns', 'sub-accordion-section-nav_menu[' + navMenuId + ']' );
			li.innerHTML = '<h3 class="accordion-section-title" tabindex="0">' +
				title +
				'<span class="screen-reader-text">' +
				menuToEdit.dataset.instruction +
				'</span>' +
				'<span class="menu-in-location"></span>' +
				'</h3>';
			if ( menuName !== '' ) {
				li.querySelector( '.menu-in-location' ).textContent = '(' + _wpCustomizeControlsL10n.currently + ' ' + menuName + ')';
			}
			document.getElementById( 'accordion-section-add_menu' ).before( li );
			activatePublishButton();

		// Enable adding of a menu item
		} else if ( e.target.classList && e.target.classList.contains( 'add-new-menu-item' ) ) {
			currentMenuId = e.target.closest( 'li' ).dataset.menuId;
			document.body.classList.toggle( 'adding-menu-items' );
			if ( document.body.classList.contains( 'adding-menu-items' ) ) {
				availableMenuItems.style.display = 'block';
				e.target.setAttribute( 'aria-expanded', true );
				ul.querySelectorAll( 'details' ).forEach( function( accordion ) {
					accordion.removeAttribute( 'open' );
				} );
			} else {
				availableMenuItems.style.display = 'none';
				e.target.setAttribute( 'aria-expanded', false );
			}

		// Add a menu item
		} else if ( availableMenuItems.contains( e.target ) ) {
			if ( e.target.classList && e.target.className === 'button add-content' ) {
				title  = e.target.previousElementSibling.value;
				object = e.target.parentNode.nextElementSibling.dataset.object;
				type   = e.target.parentNode.nextElementSibling.dataset.type;
				label  = e.target.parentNode.nextElementSibling.dataset.type_label;
				createNewPostOrPage( title, object, type, label, e.target.parentNode.nextElementSibling );
				e.target.previousElementSibling.value = ''; // reset
			} else if ( e.target.classList && e.target.className === 'button-link item-add' ) {
				type     = ul.dataset.type;
				object   = ul.dataset.object;
				objectId = e.target.closest( 'li' ).id.split( '-' ).pop();
				title    = e.target.parentNode.querySelector( '.menu-item-title' ).textContent.trim();
				label    = e.target.parentNode.querySelector( '.item-type' ).textContent.trim();
				url      = e.target.closest( 'li' ).querySelector( '.item-url' ).textContent.trim();
				e.target.parentNode.classList.add( 'item-added' );
				addMenuItem( type, object, objectId, title, label, url );
			} else if ( e.target.id && e.target.id === 'custom-menu-item-submit'  ) {
				title = document.getElementById( 'custom-menu-item-name' ).value.trim();
				url   = document.getElementById( 'custom-menu-item-url' ).value.trim();
				addMenuItem( 'custom', 'custom', '', title, 'Custom Link', url );
			}

		// Delete a menu item
		} else if ( e.target.classList && e.target.className.includes( 'item-delete submitdelete deletion' ) ) {
			deleteMenuItem( e.target.closest( 'li' ) );

		// Delete a nav menu
		} else if ( e.target.classList && e.target.className === 'button-link button-link-delete' ) {
			deleteNavMenu( e.target.closest( 'li' ).dataset.settingId );

		// Make menus sortable
		} else if ( id?.startsWith( 'accordion-section-nav_menu[' ) ) {
			ul.style.display = 'none';
			if ( id.startsWith( 'accordion-section-nav_menu[-' ) && ! document.getElementById( 'sub-' + id ) ) { // new nav menu
				newMenuId = moveNewMenu( id );
				id = newMenuId;
			}
			if ( document.getElementById( 'sub-' + id ).querySelectorAll( '.menu-item' ).length > 1 ) {
				document.getElementById( 'sub-' + id ).querySelector( '.reorder-toggle' ).style.display = '';
			}
			document.getElementById( 'sub-' + id ).style.display = 'block';
			initSortables( 'sub-' + id ); // enable sorting of menu items
			document.getElementById( 'sub-' + id ).querySelector( 'button' ).focus();

		// Enable reordering of menu items by keyboard
		} else if ( e.target.classList && e.target.className === 'reorder' ) {

			// Update which items can be moved up or down
			allItems = Array.from( ul.querySelectorAll( ':scope > .menu-item' ) );
			ul.querySelectorAll( ':scope > .menu-item' ).forEach( function( li ) {
				const liIndex = allItems.indexOf( li ),
					itemParentId = li.querySelector( '.menu-item-data-parent-id' ).value;

				let canMoveUp = false,
					canMoveDown = false;

				classNameSplits = li.className.split( 'menu-item-depth-' );
				depth = parseInt( classNameSplits[1], 10 );

				// Find previous sibling with same depth and same parent
				for ( let k = liIndex - 1; k >= 0; k-- ) {
					const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-' )[1], 10 );
					if ( kDepth === depth && allItems[k].querySelector( '.menu-item-data-parent-id' ).value === itemParentId ) {
						canMoveUp = true;
						break;
					}
					if ( kDepth < depth ) {
						break;
					}
				}

				// Find next sibling with same depth and same parent
				for ( let k = liIndex + 1, n = allItems.length; k < n; k++ ) {
					const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-')[1], 10 );
					if ( kDepth === depth && allItems[k].querySelector( '.menu-item-data-parent-id' ).value === itemParentId ) {
						canMoveDown = true;
						break;
					}
					if ( kDepth < depth ) {
						break;
					}
				}

				if ( ! canMoveUp ) {
					li.classList.add( 'move-up-disabled' );
				}
				if ( ! canMoveDown ) {
					li.classList.add( 'move-down-disabled' );
				}
			} );
			ul.classList.add( 'reordering' );

		// Finish reordering
		} else if ( e.target.classList && e.target.className === 'reorder-done' ) {
			ul.classList.remove( 'reordering' );

		// Reposition menu item with the arrows
		} else if ( e.target.parentNode.classList.contains( 'menu-item-reorder-nav' ) ) {
			li = e.target.closest( 'li' );
			position = parseInt(li.querySelector( '.menu-item-data-position').value, 10 );
			classNameSplits = li.className.split( 'menu-item-depth-' );
			depth = parseInt(  classNameSplits[1], 10 );

			// Collect all items and this item's children (depth greater than current)
			allItems = Array.from( ul.querySelectorAll( ':scope > .menu-item' ) );
			liIndex = allItems.indexOf( li );
			children = [];
			for ( let k = liIndex + 1, n = allItems.length; k < n; k++ ) {
				const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-')[1], 10 );
				if ( kDepth > depth ) {
					children.push( allItems[k] );
				} else {
					break;
				}
			}

			if ( e.target.className.includes( 'menus-move-up' ) ) {

				// Find the previous sibling with same depth and same parent
				targetSibling = null;
				for ( let k = liIndex - 1; k >= 0; k-- ) {
					const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-' )[1], 10 );
					if ( kDepth === depth && allItems[k].querySelector( '.menu-item-data-parent-id' ).value === li.querySelector( '.menu-item-data-parent-id' ).value ) {
						targetSibling = allItems[k];
						break;
					}
					if ( kDepth < depth ) {
						break;
					}
				}
				if ( targetSibling ) {
					targetSibling.querySelector( '.menu-item-data-position' ).value = position;
					li.querySelector( '.menu-item-data-position' ).value = position - 1;
					targetSibling.before( li );
					children.forEach( child => li.after( child ) );
				}
				wp.a11y.speak( _wpCustomizeNavMenusSettings.l10n.movedUp );

			} else if ( e.target.className.includes( 'menus-move-down' ) ) {

				// Find the next sibling with same depth and same parent, plus collect its children
				targetSibling = null;
				targetSiblingIndex = -1;
				for ( let k = liIndex + children.length + 1, n = allItems.length; k < n; k++ ) {
					const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-' )[1], 10 );
					if ( kDepth === depth && allItems[k].querySelector( '.menu-item-data-parent-id' ).value === li.querySelector( '.menu-item-data-parent-id' ).value ) {
						targetSibling = allItems[k];
						targetSiblingIndex = k;
						break;
					}
					if ( kDepth < depth ) {
						break;
					}
				}
				if ( targetSibling ) { // Collect target sibling's children
					for ( let k = targetSiblingIndex + 1, n = allItems.length; k < n; k++ ) {
						const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-' )[1], 10 );
						if ( kDepth > depth ) {
							targetChildren.push( allItems[k] );
						} else {
							break;
						}
					}
					insertAfter = targetChildren.length ? targetChildren[targetChildren.length - 1] : targetSibling;
					targetSibling.querySelector( '.menu-item-data-position' ).value = position;
					li.querySelector( '.menu-item-data-position' ).value = position + 1;
					insertAfter.after( li );
					children.forEach( child => li.after( child ) );
				}
				wp.a11y.speak( _wpCustomizeNavMenusSettings.l10n.movedDown );

			} else if ( e.target.className.includes( 'menus-move-left' ) ) {
				newDepth = depth - 1;
				li.className = classNameSplits[0] + 'menu-item-depth-' + newDepth + classNameSplits[1].slice( String( depth ).length );

				// Find new parent: walk back to first item at newDepth - 1
				newParentId = 0;
				if ( newDepth > 0 ) {
					for ( let k = liIndex - 1; k >= 0; k-- ) {
						const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-' )[1], 10 );
						if ( kDepth === newDepth - 1 ) {
							newParentId = allItems[k].querySelector( '.menu-item-data-db-id' ).value;
							break;
						}
					}
				}
				li.querySelector( '.menu-item-data-parent-id' ).value = newParentId;

				// Update children depths and their parent IDs
				children.forEach( function( child ) {
					const childSplits = child.className.split( 'menu-item-depth-' );
					const childDepth = parseInt( childSplits[1], 10 );
					child.className = childSplits[0] + 'menu-item-depth-' + ( childDepth - 1 ) + childSplits[1].slice( String( childDepth ).length );
				} );
				wp.a11y.speak( _wpCustomizeNavMenusSettings.l10n.movedLeft );

			} else if ( e.target.className.includes( 'menus-move-right' ) ) {
				newDepth = depth + 1;
				li.className = classNameSplits[0] + 'menu-item-depth-' + newDepth + classNameSplits[1].slice( String( depth ).length );

				// New parent is the item immediately above at newDepth - 1
				newParentId = 0;
				for ( let k = liIndex - 1; k >= 0; k-- ) {
					const kDepth = parseInt( allItems[k].className.split( 'menu-item-depth-' )[1], 10 );
					if ( kDepth === newDepth - 1 ) {
						newParentId = allItems[k].querySelector( '.menu-item-data-db-id' ).value;
						break;
					}
				}
				li.querySelector( '.menu-item-data-parent-id' ).value = newParentId;

				// Update children depths
				children.forEach( function( child ) {
					const childSplits = child.className.split( 'menu-item-depth-' );
					const childDepth = parseInt( childSplits[1], 10 );
					child.className = childSplits[0] + 'menu-item-depth-' + ( childDepth + 1 ) + childSplits[1].slice( String( childDepth ).length );
				} );
				wp.a11y.speak( _wpCustomizeNavMenusSettings.l10n.movedRight );
			}

			// Recalculate move classes for all items after every action
			updatedItems = Array.from( ul.querySelectorAll( ':scope > .menu-item' ) );
			updatedItems.forEach( function( item, i ) {
				const maxDepth = 11,
					itemDepth = parseInt( item.className.split( 'menu-item-depth-' )[1], 10 ),
					prevDepth = i > 0 ? parseInt( updatedItems[i - 1].className.split( 'menu-item-depth-' )[1], 10 ) : -1,
					itemParentId = item.querySelector( '.menu-item-data-parent-id' ).value;

				let canMoveUp = false,
					canMoveDown = false;

				// Find if there's any item above with same depth and same parent
				for ( let k = i - 1; k >= 0; k-- ) {
					const kDepth = parseInt( updatedItems[k].className.split( 'menu-item-depth-' )[1], 10 );
					if ( kDepth === itemDepth ) {
						if ( updatedItems[k].querySelector( '.menu-item-data-parent-id').value === itemParentId ) {
							canMoveUp = true;
						}
						break;
					}
					if ( kDepth < itemDepth ) {
						break; // hit a shallower item, no valid sibling above
					}
				}

				// Find if there's any item below with same depth and same parent
				for ( let k = i + 1, n = updatedItems.length; k < n; k++ ) {
					const kDepth = parseInt( updatedItems[k].className.split( 'menu-item-depth-' )[1], 10 );
					if ( kDepth === itemDepth ) {
						if ( updatedItems[k].querySelector( '.menu-item-data-parent-id' ).value === itemParentId ) {
							canMoveDown = true;
						}
						break;
					}
					if ( kDepth < itemDepth ) {
						break; // hit a shallower item, no valid sibling below
					}
				}

				item.classList.remove( 'move-up-disabled', 'move-down-disabled', 'move-left-disabled', 'move-right-disabled' );

				if ( ! canMoveUp ) {
					item.classList.add( 'move-up-disabled' );
				}
				if ( ! canMoveDown ) {
					item.classList.add( 'move-down-disabled' );
				}
				if ( itemDepth === 0 ) {
					item.classList.add( 'move-left-disabled' );
				}
				if ( i === 0 || itemDepth >= maxDepth || itemDepth >= prevDepth + 1 ) {
					item.classList.add( 'move-right-disabled' );
				}

				_updatedControlsWatcher[ item.dataset.settingId ] = {
					nav_menu_term_id: item.querySelector( '.menu-item-data-menu-id' ).value,
					position: i + 1,
					menu_item_parent: parseInt( item.querySelector( '.menu-item-data-parent-id' ).value, 10 ) || 0,
					title: item.querySelector( '.edit-menu-item-title' ).value.trim(),
					url: item.querySelector( '.edit-menu-item-url' )?.value.trim() || '',
					original_title: item.querySelector( '.original-link' )?.textContent || '',
					object_id: item.querySelector( '.menu-item-data-object-id' ).value,
					object: item.querySelector( '.menu-item-data-object' ).value,
					type: item.querySelector( '.menu-item-data-type' ).value,
					type_label: item.querySelector( '.item-type' ).value,
					classes: item.querySelector( '.edit-menu-item-classes' ).value,
					xfn: item.querySelector( '.edit-menu-item-xfn' ).value,
					target: item.querySelector( '.edit-menu-item-target' ).value,
					attr_title: item.querySelector( '.edit-menu-item-attr-title' ).value,
					description: item.querySelector( '.edit-menu-item-description' ).value,
					status: 'publish'
				};
			} );

			activatePublishButton();
		}
	} );
} );
