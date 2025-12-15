/**
 * @output wp-admin/js/customize-controls.js
 */

/* global _wpCustomizeHeader, _wpCustomizeBackground, _wpMediaViewsL10n, MediaElementPlayer, console, confirm */
document.addEventListener( 'DOMContentLoaded', function() {
	var reducedMotionMediaQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' ),
		isReducedMotion = reducedMotionMediaQuery.matches,
		childPanes = document.querySelectorAll( 'customize-pane-child' );

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
	 * Constrain focus on focus container.
	 *
	 * @since 4.9.0
	 *
	 * @param {jQuery.Event} event - Event.
	 * @return {void}
	 *
	function constrainFocus( event ) {
		var focusableElements, index,
			collection = this;

		// Prevent keys from escaping.
		event.stopPropagation();

		if ( 'Tab' !== event.key ) {
			return;
		}

		focusableElements = [ ...collection.focusContainer[0].querySelectorAll( 'a[href], button, input, textarea, select, [tabindex]' ) ];
		focusableElements.forEach( function( elem ) {
			if ( ! isVisible( elem ) ) {
				index = focusableElements.indexOf( elem );
				focusableElements.splice( index, 1 );
			}
		} );

		if ( 0 === focusableElements.length ) {
			focusableElements = collection.focusContainer;
		}

		if ( ! $.contains( collection.focusContainer[0], event.target ) || ! $.contains( collection.focusContainer[0], document.activeElement ) ) {
			event.preventDefault();
			focusableElements[0].focus();
		} else if ( focusableElements.last().is( event.target ) && ! event.shiftKey ) {
			event.preventDefault();
			focusableElements[0].focus();
		} else if ( focusableElements.first().is( event.target ) && event.shiftKey ) {
			event.preventDefault();
			focusableElements[ focusableElements.length - 1 ].focus();
		}
	}
*/
	// Go down to the next level and back
	document.addEventListener( 'click', function( e ) {
		var id;
		if ( e.target.tagName === 'H3' && e.target.parentNode.tagName === 'LI' ) {
			e.preventDefault();
			id = e.target.parentNode.id;
			e.target.closest( 'ul' ).style.display = 'none';
			childPanes.forEach( function( childPane ) {
				e.preventDefault();
				childPane.style.display = 'none';
			} );
			document.getElementById( 'customize-info' ).style.display = 'none';
			document.getElementById( 'sub-' + id ).style.display = 'block';
		} else if ( e.target.className === 'customize-section-back' ) {
			e.preventDefault();
			id = e.target.closest( 'ul' ).id;
			document.getElementById( id ).style.display = 'none';
			document.getElementById( 'customize-info' ).style.display = 'block';
			document.querySelector( '.customize-pane-parent' ).style.display = 'block';
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
		}
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




} );
