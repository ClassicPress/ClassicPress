/**
 * @output wp-admin/js/dashboard.js
 */

/* global pagenow, ajaxurl, wpActiveEditor:true, ajaxWidgets */
/* global ajaxPopulateWidgets, quickPressLoad,  */
window.wp = window.wp || {};
window.communityEventsData = window.communityEventsData || {};

/**
 * Initializes the dashboard widget functionality.
 *
 * @since 2.7.0
 */
jQuery( function($) {
	var welcomePanel = $( '#welcome-panel' ),
		welcomePanelHide = $('#wp_welcome_panel-hide'),
		updateWelcomePanel;

	/**
	 * Saves the visibility of the welcome panel.
	 *
	 * @since 3.3.0
	 *
	 * @param {boolean} visible Should it be visible or not.
	 *
	 * @return {void}
	 */
	updateWelcomePanel = function( visible ) {
		$.post( ajaxurl, {
			action: 'update-welcome-panel',
			visible: visible,
			welcomepanelnonce: $( '#welcomepanelnonce' ).val()
		});
	};

	// Unhide the welcome panel if the Welcome Option checkbox is checked.
	if ( welcomePanel.hasClass('hidden') && welcomePanelHide.prop('checked') ) {
		welcomePanel.removeClass('hidden');
	}

	// Hide the welcome panel when the dismiss button or close button is clicked.
	$('.welcome-panel-close, .welcome-panel-dismiss a', welcomePanel).on( 'click', function(e) {
		e.preventDefault();
		welcomePanel.addClass('hidden');
		updateWelcomePanel( 0 );
		$('#wp_welcome_panel-hide').prop('checked', false);
	});

	// Set welcome panel visibility based on Welcome Option checkbox value.
	welcomePanelHide.on( 'click', function() {
		welcomePanel.toggleClass('hidden', ! this.checked );
		updateWelcomePanel( this.checked ? 1 : 0 );
	});

	/**
	 * These widgets can be populated via ajax.
	 *
	 * @since 2.7.0
	 *
	 * @type {string[]}
	 *
	 * @global
 	 */
	window.ajaxWidgets = ['dashboard_primary'];

	/**
	 * Triggers widget updates via Ajax.
	 *
	 * @since 2.7.0
	 *
	 * @global
	 *
	 * @param {string} el Optional. Widget to fetch or none to update all.
	 *
	 * @return {void}
	 */
	window.ajaxPopulateWidgets = function(el) {
		/**
		 * Fetch the latest representation of the widget via Ajax and show it.
		 *
		 * @param {number} i Number of half-seconds to use as the timeout.
		 * @param {string} id ID of the element which is going to be checked for changes.
		 *
		 * @return {void}
		 */
		function show(i, id) {
			var p, e = $('#' + id + ' div.inside:visible').find('.widget-loading');
			// If the element is found in the dom, queue to load latest representation.
			if ( e.length ) {
				p = e.parent();
				setTimeout( function(){
					// Request the widget content.
					p.load( ajaxurl + '?action=dashboard-widgets&widget=' + id + '&pagenow=' + pagenow, '', function() {
						// Hide the parent and slide it out for visual fancyness.
						p.hide().slideDown('normal', function(){
							$(this).css('display', '');
						});
					});
				}, i * 500 );
			}
		}

		// If we have received a specific element to fetch, check if it is valid.
		if ( el ) {
			el = el.toString();
			// If the element is available as Ajax widget, show it.
			if ( $.inArray(el, ajaxWidgets) !== -1 ) {
				// Show element without any delay.
				show(0, el);
			}
		} else {
			// Walk through all ajaxWidgets, loading them after each other.
			$.each( ajaxWidgets, show );
		}
	};

	// Initially populate ajax widgets.
	ajaxPopulateWidgets();

	/**
	 * Control the Quick Press (Quick Draft) widget.
	 *
	 * @since 2.7.0
	 *
	 * @global
	 *
	 * @return {void}
	 */
	window.quickPressLoad = function() {
		var act = $('#quickpost-action'), t;

		// Enable the submit buttons.
		$( '#quick-press .submit input[type="submit"], #quick-press .submit input[type="reset"]' ).prop( 'disabled' , false );

		t = $('#quick-press').on( 'submit', function( e ) {
			e.preventDefault();

			// Show a spinner.
			$('#dashboard_quick_press #publishing-action .spinner').show();

			// Disable the submit button to prevent duplicate submissions.
			$('#quick-press .submit input[type="submit"], #quick-press .submit input[type="reset"]').prop('disabled', true);

			// Post the entered data to save it.
			$.post( t.attr( 'action' ), t.serializeArray(), function( data ) {
				// Replace the form, and prepend the published post.
				$('#dashboard_quick_press .inside').html( data );
				$('#quick-press').removeClass('initial-form');
				quickPressLoad();
				highlightLatestPost();

				// Focus the title to allow for quickly drafting another post.
				$('#title').trigger( 'focus' );
			});

			/**
			 * Highlights the latest post for one second.
			 *
			 * @return {void}
 			 */
			function highlightLatestPost () {
				var latestPost = $('.drafts ul li').first();
				latestPost.css('background', '#fffbe5');
				setTimeout(function () {
					latestPost.css('background', 'none');
				}, 1000);
			}
		} );

		// Change the QuickPost action to the publish value.
		$('#publish').on( 'click', function() { act.val( 'post-quickpress-publish' ); } );

		$('#quick-press').on( 'click focusin', function() {
			wpActiveEditor = 'content';
		});

		autoResizeTextarea();
	};
	window.quickPressLoad();

	/**
	 * Adjust the height of the textarea based on the content.
	 *
	 * @since 3.6.0
	 *
	 * @return {void}
	 */
	function autoResizeTextarea() {
		// When IE8 or older is used to render this document, exit.
		if ( document.documentMode && document.documentMode < 9 ) {
			return;
		}

		// Add a hidden div. We'll copy over the text from the textarea to measure its height.
		$('body').append( '<div class="quick-draft-textarea-clone" style="display: none;"></div>' );

		var clone = $('.quick-draft-textarea-clone'),
			editor = $('#content'),
			editorHeight = editor.height(),
			/*
			 * 100px roughly accounts for browser chrome and allows the
			 * save draft button to show on-screen at the same time.
			 */
			editorMaxHeight = $(window).height() - 100;

		/*
		 * Match up textarea and clone div as much as possible.
		 * Padding cannot be reliably retrieved using shorthand in all browsers.
		 */
		clone.css({
			'font-family': editor.css('font-family'),
			'font-size':   editor.css('font-size'),
			'line-height': editor.css('line-height'),
			'padding-bottom': editor.css('paddingBottom'),
			'padding-left': editor.css('paddingLeft'),
			'padding-right': editor.css('paddingRight'),
			'padding-top': editor.css('paddingTop'),
			'white-space': 'pre-wrap',
			'word-wrap': 'break-word',
			'display': 'none'
		});

		// The 'propertychange' is used in IE < 9.
		editor.on('focus input propertychange', function() {
			var $this = $(this),
				// Add a non-breaking space to ensure that the height of a trailing newline is
				// included.
				textareaContent = $this.val() + '&nbsp;',
				// Add 2px to compensate for border-top & border-bottom.
				cloneHeight = clone.css('width', $this.css('width')).text(textareaContent).outerHeight() + 2;

			// Default to show a vertical scrollbar, if needed.
			editor.css('overflow-y', 'auto');

			// Only change the height if it has changed and both heights are below the max.
			if ( cloneHeight === editorHeight || ( cloneHeight >= editorMaxHeight && editorHeight >= editorMaxHeight ) ) {
				return;
			}

			/*
			 * Don't allow editor to exceed the height of the window.
			 * This is also bound in CSS to a max-height of 1300px to be extra safe.
			 */
			if ( cloneHeight > editorMaxHeight ) {
				editorHeight = editorMaxHeight;
			} else {
				editorHeight = cloneHeight;
			}

			// Disable scrollbars because we adjust the height to the content.
			editor.css('overflow', 'hidden');

			$this.css('height', editorHeight + 'px');
		});
	}

} );
