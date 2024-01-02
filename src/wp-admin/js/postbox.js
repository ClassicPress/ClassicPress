/**
 * Contains the postboxes logic, opening and closing postboxes, reordering and saving
 * the state and ordering to the database.
 *
 * @since CP-2.1.0
 * @requires SortableJS
 * @output wp-admin/js/postbox.js
 */

/* global Sortable, ajaxurl, console */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	// Set variables for the whole file
	var moveUpButtons = document.querySelectorAll( '.postbox:not( .hide-if-js ) .handle-order-higher' ),
		moveDownButtons = document.querySelectorAll( '.postbox:not( .hide-if-js ) .handle-order-lower' ),
		allButtons = [ ...moveUpButtons, ...moveDownButtons ],
		__ = wp.i18n.__,
		boxes = document.querySelectorAll( '.postbox' ),
		columns = document.querySelectorAll( '.meta-box-sortables' ),
		nagDismiss = document.querySelector( '.postbox a.dismiss' ),
		widgetToggles = document.querySelectorAll( '.hide-postbox-tog' ),
		columnsPrefs = document.querySelectorAll( '.columns-prefs input[type="radio"]' ),
		check = document.querySelector( 'label.columns-prefs-1 input[type="radio"]' ),
		emptySortableText = ( boxes == null ) ?  __( 'Add boxes from the Screen Options menu' ) : __( 'Drag boxes here' );

	/**
	 * Disables first Up button and last Down button if they appear in
	 * the first and last sortable areas respectively.
	 *
	 * @since CP-2.1.0
	 */
	if ( [ 'dashboard', 'post' ].includes( window.pagenow ) ) {
		if ( columns[0].querySelector( '.handle-order-higher' ) != null ) {
			moveUpButtons[0].setAttribute( 'aria-disabled', 'true' );
		}
		if ( boxes[boxes.length - 1].closest( '.postbox-container' ).nextElementSibling == null ) {
			moveDownButtons[moveDownButtons.length - 1].setAttribute( 'aria-disabled', 'true' );
		}
	}

	/**
	 * Handles clicks on the move up/down buttons.
	 *
	 * @since CP-2.1.0
	 */
	allButtons.forEach( function( button ) {
		button.addEventListener( 'click', function() {
			var prevCol,
				nextCol,
				firstOrLastPositionMessage,
				widget = button.closest( 'details' ),
				prevSibling = widget.previousElementSibling,
				nextSibling = widget.nextElementSibling,
				widgetCol = button.closest( '.meta-box-sortables' );

			if ( widget.id !== 'dashboard_browser_nag' ) {

				// If on the first or last position, do nothing and send an audible message to screen reader users.
				if ( button.getAttribute( 'aria-disabled' ) === 'true' ) {
					firstOrLastPositionMessage = button.className.includes( 'handle-order-higher' ) ?
						__( 'The box is on the first position' ) :
						__( 'The box is on the last position' );

					wp.a11y.speak( firstOrLastPositionMessage );
				}

				// Move a postbox up.
				else if ( button.className.includes( 'handle-order-higher' ) ) {

					// If the box is first within a sortable area, move it to the previous sortable area.
					if ( prevSibling == null || prevSibling.className.includes( 'hide-if-js' ) ) {
						prevCol = widgetCol.parentNode.previousElementSibling.querySelector( '.meta-box-sortables' );
						prevCol.appendChild( widget );

						// Update where sortable area becomes, or ceases to be, empty.
						if ( widgetCol.querySelector( '.postbox' ) == null ) {
							widgetCol.classList.add( 'empty-container' );
							widgetCol.style.outline = '3px dashed #c3c4c7';
							widgetCol.setAttribute( 'data-emptystring', emptySortableText );
						}

						if ( prevCol.className.includes( 'empty-container' ) ) {
							prevCol.classList.remove( 'empty-container' );
							prevCol.style.outline = 'none';
							prevCol.removeAttribute( 'data-emptystring' );
						}
					} else { // Otherwise move up within same area
						widget.parentNode.insertBefore( widget, prevSibling );
					}

					button.focus();
					updateLocations();
				}

				// Move a postbox down.
				else if ( button.className.includes( 'handle-order-lower' ) ) {

					// If the box is last within a sortable area, move it to the next sortable area.
					if ( nextSibling == null || nextSibling.className.includes( 'hide-if-js' ) ) {
						nextCol = widgetCol.parentNode.nextElementSibling.querySelector( '.meta-box-sortables' );
						nextCol.insertBefore( widget, nextCol.querySelector( 'details' ) );

						// Update where sortable area becomes, or ceases to be, empty.
						if ( widgetCol.querySelector( '.postbox' ) == null ) {
							widgetCol.classList.add( 'empty-container' );
							widgetCol.style.outline = '3px dashed #c3c4c7';
							widgetCol.setAttribute( 'data-emptystring', emptySortableText );
						}

						if ( nextCol.className.includes( 'empty-container' ) ) {
							nextCol.classList.remove( 'empty-container' );
							nextCol.style.outline = 'none';
							nextCol.removeAttribute( 'data-emptystring' );
						}
					} else { // Otherwise move down within same area
						widget.parentNode.insertBefore( nextSibling, widget );
					}

					button.focus();
					updateLocations();
				}
			}
		} );
	} );

	/**
	 * Updates state when boxes toggled open and closed.
	 *
	 * @since CP-2.1.0
	 */
	boxes.forEach( function( box ) {
		box.addEventListener( 'toggle', function() {
			saveState();
		} );
	} );

	/**
	 * Makes columns sortable. Handles when a widget is dragged, dropped, or sorted.
	 *
	 * @since CP-2.1.0
	 *
	 * @requires SortableJS.
	 */
	columns.forEach( function( column ) {
		Sortable.create( document.getElementById( column.id ), {
			group: 'widgets',
			handle: '.hndle',
			forceFallback: navigator.vendor.match(/apple/i) ? true : false, // forces fallback for webkit browsers
			//forceFallback: 'GestureEvent' in window ? true : false, // forces fallback for Safari
			onStart: dragStart,
			onEnd: dragEnd,
			onChange: updateLocations
		} );

		if ( column.id !== 'advanced-sortables' && column.querySelector( '.postbox' ) == null ) {
			column.classList.add( 'empty-container' );
			column.style.outline = '3px dashed #c3c4c7';
			column.setAttribute( 'data-emptystring', emptySortableText );
		}
	} );

	/**
	 * Hides a postbox.
	 *
	 * Event handler for the postbox dismiss button. After clicking the button
	 * the postbox will be hidden.
	 *
	 * As of WordPress 5.5, this is only used for the browser update nag.
	 *
	 * @since 3.2.0
	 */
	if ( nagDismiss != null ) {
		nagDismiss.addEventListener( 'click', function( e ) {
			var hideId = e.target.parentElement.id + '-hide';
			e.preventDefault();
			document.getElementById( hideId ).checked = 'false';
			document.getElementById( hideId ).click();
		} );
	}

	/**
	 * Hides the postbox element
	 *
	 * Event handler for the screen options checkboxes. When a checkbox is
	 * clicked this function will hide or show the relevant postboxes.
	 *
	 * @since 2.7.0
	 */
	widgetToggles.forEach( function( toggle ) {
		var boxId = toggle.value,
			postbox = ( window.pagenow === 'nav-menus' ) ? document.getElementById( boxId ).closest( 'li' ) : document.getElementById( boxId );

		if ( postbox != null ) { // also catches undefined
			if ( toggle.checked ) {
				postbox.classList.remove( 'hide-if-js' );
			} else {
				postbox.classList.add( 'hide-if-js' );
			}

			toggle.addEventListener( 'click', function() {
				postbox.classList.toggle( 'hide-if-js' );
				updateLocations();
				saveState();
			} );
		}
	} );


	/**
	 * Changes the number of columns based on layout preference.
	 *
	 * @since 2.8.0
	 */
	if ( columnsPrefs != null ) {
		columnsPrefs.forEach( function( pref ) {
			pref.addEventListener( 'click', function( e ) {
				var n = parseInt( e.target.value, 10 );
				if ( n ) {
					_pbEdit( n );
					updateLocations();
				}
			} );
		} );
	}

	/**
	 * Identifies droppable areas when starting to drag widget.
	 *
	 * @since CP-2.1.0
	 */
	function dragStart() {
		columns.forEach( function( column ) {
			column.style.outline = '3px dashed gray';
		} );
	}

	/**
	 * Updates styles and attributes when drag ends.
	 *
	 * @since CP-2.1.0
	 */
	function dragEnd( e ) {
		columns.forEach( function( column ) {
			column.style.outline = 'none';
		} );

		// Update class and attribute when a sortable area becomes or ceases being empty.
		if ( e.from.querySelector( '.postbox' ) == null ) {
			e.from.classList.add( 'empty-container' );
			e.from.style.outline = '3px dashed #c3c4c7';
			e.from.setAttribute( 'data-emptystring', emptySortableText );
		}

		if ( e.to.className.includes( 'empty-container' ) ) {
			e.to.classList.remove( 'empty-container' );
			e.to.style.outline = 'none';
			e.to.removeAttribute( 'data-emptystring' );
		}
	}

	/**
	 * Updates when box position has changed.
	 *
	 * @since CP-2.1.0
	 */
	function updateLocations() {
		if ( window.pagenow === 'nav-menus' ) {
			return;
		}

		var firstWidget,
			lastWidget,
			postVars,
			cols = document.querySelector( '.columns-prefs input[type="radio"]:checked' ),
			widgetsIds = [],
			widgetsIdsList = [];

		// Remove current aria-disabled states
		document.querySelectorAll( '[aria-disabled="true"]' ).forEach( function( ariaDisabled ) {
			ariaDisabled.setAttribute( 'aria-disabled', 'false' );
		});

		// Collect variables for posting to database
		postVars = new URLSearchParams( {
			action: 'meta-box-order',
			_ajax_nonce: document.getElementById( 'meta-box-order-nonce' ).value,
			page_columns: cols ? cols.value : 0,
			page: window.pagenow
		} );

		// Generate newly-ordered array of columns and postboxes
		columns.forEach( function( column ) {
			column.querySelectorAll( 'details:not( .hide-if-js )' ).forEach( function( childWidget ) {
				widgetsIds.push( childWidget.id ); // for posting to database
				widgetsIdsList.push( childWidget.id ); // for setting aria-disabled state
			});
			postVars.append( 'order[' + column.id.split( '-' )[0] + ']', widgetsIds.join( ',' ) );
			widgetsIds = []; // reset
		} );

		// Add aria-disabled to first Up button if it's in the first sortable area
		firstWidget = document.getElementById( widgetsIdsList[0] );
		if ( firstWidget.parentNode === columns[0] ) {
			firstWidget.querySelector( '.handle-order-higher' ).setAttribute( 'aria-disabled', 'true' );
		}

		// Add aria-disabled to last Down button if it's in the last sortable area
		lastWidget = document.getElementById( widgetsIdsList[widgetsIdsList.length -1] );
		if ( lastWidget.closest( '.postbox-container' ).nextElementSibling == null ) {
			lastWidget.querySelector( '.handle-order-lower' ).setAttribute( 'aria-disabled', 'true' );
		}

		// Post the updated widget locations to the database
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
		.then( function( response ) {
			if ( response.success ) {
				wp.a11y.speak( __( 'The boxes order has been saved.' ) );
			}
		} )
		.catch( function( error ) {
			console.log( error );
		} );
	}

	/**
	 * Saves the state of the postboxes to the server.
	 *
	 * It sends two lists, one with all the closed postboxes, one with all the
	 * hidden postboxes.
	 *
	 * @since 2.7.0
	 *
	 * Closed postboxes are now identified by the lack of an open attribute
	 * in the details element rather than by a class of "closed"
	 */
	function saveState() {
		var closed, hidden, postVars;

		if ( window.pagenow !== 'nav-menus' ) {
			closed = [ ...document.querySelectorAll( '.postbox:not([open])' ) ].map( function( i ) { return i.id; } ).join( ',' );
			hidden = [ ...document.querySelectorAll( '.postbox.hide-if-js' ) ].map( function( i ) { return i.id; } ).join( ',' );

			postVars = new URLSearchParams( {
				action: 'closed-postboxes',
				closedpostboxesnonce: document.getElementById( 'closedpostboxesnonce' ).value,
				closed: closed,
				hidden: hidden,
				page: window.pagenow
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
		}
	}

	/**
	 * Changes the number of columns the postboxes are in based on the current
	 * orientation of the browser.
	 *
	 * @since 3.3.0
	 */
	if ( check != null ) {
		switch ( window.orientation ) {
			case 90:
			case -90:
				if ( !check.length || !check.checked ) {
					_pbEdit( 2 );
				}
				break;
			case 0:
			case 180:
				if ( document.getElementById( 'poststuff' ).length ) {
					_pbEdit( 1 );
				} else {
					if ( !check.length || !check.checked ) {
						_pbEdit( 2 );
					}
				}
				break;
		}
	}

	/**
	 * Changes the number of columns on the post edit page.
	 *
	 * @since 3.3.0
	 * @access private
	 *
	 * @param {number} n The number of columns to divide the post edit page in.
	 */
	function _pbEdit( n ) {
		var	el = document.querySelector( '.metabox-holder' );
		if ( el ) {
			el.className = el.className.replace( /columns-\d+/, 'columns-' + n );
		}

		/**
		* Fires when the number of columns on the post edit page has been changed.
		*
		* @since 4.0.0
		*/
		updateLocations();
	}

	/*
	 * Enable smooth scrolling up and down page when dragging item
	 *
	 * @since CP-2.1.0
	 */
	document.addEventListener( 'dragover', function( e ) {

		// How close (in pixels) to the edge of the screen before scrolling starts
		var scrollThreshold = 50;

		if ( e.clientY < scrollThreshold ) {
			window.scrollTo( {
				top: 0,
				behavior: 'smooth'
			} );
		}
		else if ( e.clientY > window.innerHeight - scrollThreshold ) {
			window.scrollTo( {
				top: document.body.scrollHeight,
				behavior: 'smooth'
			} );
		}
	} );

} );
