/**
 * @file Functionality for the plugin install screens.
 *
 * @output wp-admin/js/plugin-install.js
 */
/* global pluginL10n */
document.addEventListener( 'DOMContentLoaded', function() {

	var iframe, iframeBody, tabbables, firstTabbable, lastTabbable, closeButton,
		uploadViewToggle = document.querySelector( '.upload-view-toggle' ),
		wrap = document.querySelector( '.wrap' ),
		body = document.body,
		openers = document.querySelectorAll( '.thickbox' ),
		width = window.innerWidth,
		height = window.innerHeight,
		dialog = document.createElement( 'dialog' );

	dialog.className = 'plugin-details-modal';
	dialog.style.padding = '0';
	body.append( dialog ); // append dialog element to page

	/*
	 * Open modal dialog (replacing previous thickbox)
	 *
	 * @since CP-2.1.0
	 */
	openers.forEach( function( opener ) {
		opener.addEventListener( 'click', function( e ) {
			var urlNoQuery,
				url = opener.href || opener.alt,
				title = opener.dataset.title ?
					wp.i18n.sprintf(
						// translators: %s: Plugin name.
						wp.i18n.__( 'Plugin: %s' ),
						opener.dataset.title
					) :
					wp.i18n.__( 'Plugin details' );

			e.preventDefault();
			e.stopPropagation();

			urlNoQuery = url.split('TB_');

			dialog.classList.add( 'modal-loading' );
			dialog.showModal();
			dialog.insertAdjacentHTML( 'beforeend', '<button type="button" id="dialog-close-button" autofocus><span class="screen-reader-text">' + pluginL10n.close + '</span></button><iframe frameborder="0" hspace="0" allowtransparency="true" src="' + urlNoQuery[0] + '" id="TB_iframeContent" name="TB_iframeContent' + Math.round( Math.random() * 1000 ) + '" style="width: ' + ( width * 9 / 10 ) + 'px;max-width:800px;height: ' + ( height * 9 / 10 ) + 'px;" title="' + title + '">' + pluginL10n.noiframes + '</iframe>' );

			iframe = dialog.querySelector( 'iframe' );
			if ( iframe ) {
				iframe.addEventListener( 'load', function() {
					dialog.classList.remove( 'modal-loading' );
					iframeLoaded();
				} );
			}

			closeButton = dialog.querySelector( '#dialog-close-button' );
			closeButton.addEventListener( 'click', function() {
				dialog.close();
				if ( iframe != null ) {
					iframe.remove();
				}
				closeButton.remove();
			} );

			// Remove iframe contents when hitting the Escape button
			dialog.addEventListener( 'keydown', function( e ) {
				if ( e.key === 'Escape' ) {
					if ( iframe != null ) {
						iframe.remove();
					}
					closeButton.remove();
				}
				else if ( e.key === 'Enter' && e.target.id === 'dialog-close-button' ) {
					e.preventDefault();
					dialog.close();
					if ( iframe != null ) {
						iframe.remove();
					}
					closeButton.remove();
				}
			} );
		} );
	} );

	/*
	 * Remove iframe contents when closing modal dialog with Escape key
	 *
	 * @since CP-2.1.0
	 */
	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) {
			if ( iframe != null ) {
				iframe.remove();
			}
			closeButton.remove();
		}
	} );

	/*
	 * Called when iframe has loaded
	 *
	 * @since CP-2.1.0
	 */
	function iframeLoaded() {

		// Get the iframe body.
		iframeBody = iframe.contentWindow.document.querySelector( 'body' );

		// Get the tabbable elements and handle the keydown event on first load.
		handleTabbables();

		// Set initial focus on the "Close" button.
		firstTabbable.focus();

		/*
		 * When the "Install" button is disabled (e.g. the Plugin is already installed)
		 * then we can't predict where the last focusable element is. We need to get
		 * the tabbable elements and handle the keydown event again and again,
		 * each time the active tab panel changes.
		 */
		document.querySelectorAll( '#plugin-information-tabs a' ).forEach( function( tab ) {
			tab.addEventListener( 'click', function() {
				handleTabbables();
			} );
		} );

		iframeBody.addEventListener( 'click', function() {
			handleTabbables();
		} );
	}

	/*
	 * Get the tabbable elements.
	 * Called after the iframe has fully loaded so we have all the elements we need.
	 * Called again each time a Tab gets clicked.
	 *
	 * @since CP-2.1.0
	 * Implemented without jQuery UI.
	 */
	function handleTabbables() {
		var length;

		// Get all the tabbable elements
		tabbables = [ ...iframeBody.querySelectorAll( 'a[href], button, input, textarea, select, [tabindex]:not( [tabindex="-1"] )' ) ];
		tabbables.forEach( function( tabbable ) {
			var index;
			if ( ! isVisible( tabbable ) ) {
				index = tabbables.indexOf( tabbable );
				tabbables.splice( index, 1 );
			}
			if ( hasAncestorWithMatchingSelector( tabbable, '.hidden' ) ) {
				index = tabbables.indexOf( tabbable );
				tabbables.splice( index, 1 );
			}
		} );

		// The first tabbable element is always the "Close" button
		firstTabbable = document.getElementById( 'dialog-close-button' );
		firstTabbable.addEventListener( 'keydown', function( event ) {
			constrainTabbing( event );
		} );

		// Get the last tabbable element, ignoring those listed in hidden tab panels.
		// Cannot do this above because the tab panels are set too late.
		if ( tabbables.at( -1 ).id === 'plugin_install_from_iframe' ) {
			lastTabbable = tabbables.at( -1 );
		} else { // we need the last but one element instead
			length = tabbables.length;
			lastTabbable = tabbables[ length - 2 ];
			while ( lastTabbable.closest( '.section' ) && ! isVisible( lastTabbable.closest( '.section' ) ) ) {
				length = length - 1;
				lastTabbable = tabbables[ length - 1 ];
			}
		}
		lastTabbable.addEventListener( 'keydown', function( event ) {
			constrainTabbing( event );
		} );
	}

	/*
	 * Helper function copied from jQuery
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

	/*
	 * Helper function to find ancesors with specific selector (e.g. class)
	 */
	function hasAncestorWithMatchingSelector( target, selector ) {
		return [ ...document.querySelectorAll( selector ) ].some( function( el ) {
			el !== target && el.contains( target );
		} );
	}

	// Constrain tabbing within the plugin modal dialog, but allow closing
	function constrainTabbing( event ) {
		if ( 'Tab' === event.key ) {
			if ( lastTabbable === event.target && ! event.shiftKey ) {
				event.preventDefault();
				firstTabbable.focus();
			} else if ( firstTabbable === event.target && event.shiftKey ) {
				event.preventDefault();
				lastTabbable.focus();
			}
		}
	}

	/*
	 * When a user presses the "Upload Plugin" button, show the upload form in place
	 * rather than sending them to the devoted upload plugin page.
	 * The `?tab=upload` page still exists for no-js support and for plugins that
	 * might access it directly. When we're in this page, let the link behave
	 * like a link. Otherwise we're in the normal plugin installer pages and the
	 * link should behave like a toggle button.
	 */
	if ( wrap != null ) {
		if ( uploadViewToggle && ! wrap.className.includes( 'plugin-install-tab-upload' ) ) {
			uploadViewToggle.setAttribute( 'role', 'button' );
			uploadViewToggle.setAttribute( 'aria-expanded', 'false' );
			uploadViewToggle.addEventListener( 'click', function( event ) {
				event.preventDefault();
				body.classList.toggle( 'show-upload-view' );
				uploadViewToggle.setAttribute( 'aria-expanded', body.className.includes( 'show-upload-view' ) );
			} );
		}
	}

	/* Plugin install related JS */
	document.querySelectorAll( '#plugin-information-tabs a' ).forEach( function( infoLink ) {
		infoLink.addEventListener( 'click', function( event ) {
			var tab = infoLink.getAttribute( 'name' );
			event.preventDefault();

			// Flip the tab.
			document.querySelector( '#plugin-information-tabs a.current' ).classList.remove( 'current' );
			infoLink.classList.add( 'current' );

			// Only show the fyi box in the description section, on smaller screens,
			// where it's otherwise always displayed at the top.
			if ( 'description' !== tab && window.innerWidth < 772 ) {
				document.querySelector( '#plugin-information-content .fyi' ).style.display = 'none';
			} else {
				document.querySelector( '#plugin-information-content .fyi' ).style.display = 'block';
			}

			// Flip the content.
			document.querySelectorAll( '#section-holder div.section' ).forEach( function( section ) {
				section.style.display = 'none'; // Hide them all.
			} );
			document.querySelector( '#section-' + tab ).style.display = 'block';
		} );
	} );

	/* Plugin install Category filter JS */
	document.querySelectorAll( '.plugin-categories-filter a' ).forEach( function( filter ) {
		filter.addEventListener( 'click', function( event ) {
			event.preventDefault();
			var category = filter.dataset.pluginTag;
			document.querySelector( '#typeselector' ).value = 'tag';
			document.querySelector( '.plugin-install-php .wp-filter-search' ).value = category;
			document.querySelector( '.plugin-install-php .wp-filter-search' ).dispatchEvent( new Event( 'input' ) );
		} );
	} );
} );
