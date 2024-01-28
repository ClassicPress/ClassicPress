/**
 * @file Functionality for the plugin install screens.
 *
 * @output wp-admin/js/plugin-install.js
 */

/* global tb_click, tb_remove, tb_position */

jQuery( function( $ ) {

	var tbWindow,
		iframeBody,
		tabbables,
		firstTabbable,
		lastTabbable,
		focusedBefore = '',
		uploadViewToggle = document.querySelector( '.upload-view-toggle' ),
		wrap = document.querySelector( '.wrap' ),
		body = document.body;

	window.tb_position = function() {
		var width = window.innerWidth,
			H = window.innerHeight - ( ( 792 < width ) ? 60 : 20 ),
			W = ( 792 < width ) ? 772 : width - 20;

		tbWindow = document.querySelector( '#TB_window' );
		tbWindow.style.width = W + 'px';
		tbWindow.style.height = H + 'px';
		document.querySelector( '#TB_iframeContent' ).style.width = W + 'px';
		document.querySelector( '#TB_iframeContent' ).style.height = H + 'px';
		tbWindow.style.marginLeft = '-' + parseInt( ( W / 2 ), 10 ) + 'px';
		if ( typeof document.body.style.maxWidth !== 'undefined' ) {
			tbWindow.style.top = '30px',
			tbWindow.style.marginTop = '0';
		}

		return document.querySelectorAll( 'a.thickbox' ).forEach( function( thickbox ) {
			var href = thickbox.href;
			if ( ! href ) {
				return;
			}
			href = href.replace( /&width=[0-9]+/g, '' );
			href = href.replace( /&height=[0-9]+/g, '' );
			thickbox.href = href + '&width=' + W + '&height=' + H;
		} );
	};

	window.addEventListener( 'resize', function() {
		tb_position();
	} );

	/*
	 * Custom events: when a Thickbox iframe has loaded and when the Thickbox
	 * modal gets removed from the DOM.
	 */
	$(body).on( 'thickbox:iframe:loaded', tbWindow, function() {
		/*
		 * Return if it's not the modal with the plugin details iframe. Other
		 * thickbox instances might want to load an iframe with content from
		 * an external domain. Avoid to access the iframe contents when we're
		 * not sure the iframe loads from the same domain.
		 */
		if ( ! tbWindow.className.includes( 'plugin-details-modal' ) ) {
			return;
		}

		iframeLoaded();
	} );

	$(body).on( 'thickbox:removed', function() {
		// Set focus back to the element that opened the modal dialog.
		focusedBefore.focus();
	} );

	function iframeLoaded() {
		var iframe = tbWindow.querySelector( '#TB_iframeContent' );

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

		// Close the modal when pressing Escape.
		iframeBody.addEventListener( 'keydown', function( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}
			tb_remove();
		} );
	}

	/*
	 * Get the tabbable elements and detach/attach the keydown event.
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

		// The first tabbable element is always the "Close" button.
		firstTabbable = tbWindow.querySelector( '#TB_closeWindowButton' );
		firstTabbable.addEventListener( 'keydown', function( event ) {
			constrainTabbing( event );
		} );

		// Get the last tabbable element, ignoring those listed in hidden tab panels.
		// Cannot do this above because the tab panels are set too late.
		length = tabbables.length;
		lastTabbable = tabbables[ length - 1 ];
		while ( lastTabbable.closest( '.section' ) && ! isVisible( lastTabbable.closest( '.section' ) ) ) {
			length = length - 1;
			lastTabbable = tabbables[ length - 1 ];
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

	// Constrain tabbing within the plugin modal dialog.
	function constrainTabbing( event ) {
		if ( 'Tab' !== event.key ) {
			return;
		}

		if ( lastTabbable === event.target && ! event.shiftKey ) {
			event.preventDefault();
			firstTabbable.focus();
		} else if ( firstTabbable === event.target && event.shiftKey ) {
			event.preventDefault();
			lastTabbable.focus();
		}
	}

	/*
	 * Open the Plugin details modal. The event is delegated to get also the links
	 * in the plugins search tab, after the Ajax search rebuilds the HTML. It's
	 * delegated on the closest ancestor and not on the body to avoid conflicts
	 * with other handlers, see Trac ticket #43082.
	 */
	if ( wrap != null ) { // also includes undefined
		wrap.addEventListener( 'click', function( e ) {
			if ( e.target.className === 'thickbox open-plugin-details-modal' ) {

				// The `data-title` attribute is used only in the Plugin screens.
				var title = e.target.dataset.title ?
					wp.i18n.sprintf(
						// translators: %s: Plugin name.
						wp.i18n.__( 'Plugin: %s' ),
						e.target.dataset.title
					) :
					wp.i18n.__( 'Plugin details' );

				e.preventDefault();
				e.stopPropagation();

				// Store the element that has focus before opening the modal dialog, i.e. the control which opens it.
				focusedBefore = e.target;

				tb_click.call( e.target );

				// Set ARIA role, ARIA label, and add a CSS class.
				tbWindow.setAttribute( 'role', 'dialog' );
				tbWindow.setAttribute( 'aria-label', wp.i18n.__( 'Plugin details' ) );
				tbWindow.classList.add( 'plugin-details-modal' );

				// Set title attribute on the iframe.
				tbWindow.querySelector( '#TB_iframeContent' ).setAttribute( 'title', title );
			}
		} );

		/*
		 * When a user presses the "Upload Plugin" button, show the upload form in place
		 * rather than sending them to the devoted upload plugin page.
		 * The `?tab=upload` page still exists for no-js support and for plugins that
		 * might access it directly. When we're in this page, let the link behave
		 * like a link. Otherwise we're in the normal plugin installer pages and the
		 * link should behave like a toggle button.
		 */
		if ( ! wrap.className.includes( 'plugin-install-tab-upload' ) ) {
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
