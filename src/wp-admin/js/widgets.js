/**
 * @output wp-admin/js/widgets.js
 */

/* global ajaxurl, isRtl, wpWidgets */

(function($) {
	var $document = $( document );

window.wpWidgets = {
	/**
	 * A closed Sidebar that gets a Widget dragged over it.
	 *
	 * @var {element|null}
	 */
	hoveredSidebar: null,

	/**
	 * Lookup of which widgets have had change events triggered.
	 *
	 * @var {object}
	 */
	dirtyWidgets: {},

	init : function() {
		var rem, the_id,
			self = this,
			chooser = $('.widgets-chooser'),
			selectSidebar = chooser.find('.widgets-chooser-sidebars'),
			sidebars = $('ul.widgets-sortables'),
			sidebarWrappers = $( '.widgets-holder-wrap' ),
			isRTL = !! ( 'undefined' !== typeof isRtl && isRtl );// Refresh the widgets containers in the right column.

		document.querySelector( '#widgets-right details' ).setAttribute( 'open', 'open' );
		document.querySelector( '.inactive-sidebar .sidebar-name' ).parentElement.setAttribute( 'open', 'open' );

		// Hide elements on the Inactive Sidebar.
		document.querySelector( '.inactive-sidebar .sidebar-name' ).addEventListener( 'click', function( e ) {
			e.target.parentElement.nextElementSibling.classList.toggle( 'hidden' );
			e.target.closest( '.widget-holder' ).nextElementSibling.classList.toggle( 'hidden' );
		});

		// Update the admin menu "sticky" state.
		$( '#widgets-left .sidebar-name' ).on( 'click', function() {
			$document.triggerHandler( 'wp-pin-menu' );
		});

		$( '#widgets-right summary' ).on( 'click', function() {
			$document.triggerHandler( 'wp-pin-menu' );
		});

		// Show AYS dialog when there are unsaved widget changes.
		$( window ).on( 'beforeunload.widgets', function( event ) {
			var dirtyWidgetIds = [], unsavedWidgetsElements;
			$.each( self.dirtyWidgets, function( widgetId, dirty ) {
				if ( dirty ) {
					dirtyWidgetIds.push( widgetId );
				}
			});
			if ( 0 !== dirtyWidgetIds.length ) {
				unsavedWidgetsElements = $( '#widgets-right' ).find( '.widget' ).filter( function() {
					return -1 !== dirtyWidgetIds.indexOf( $( this ).prop( 'id' ).replace( /^widget-\d+_/, '' ) );
				});
				unsavedWidgetsElements.each( function() {
					if ( ! $( this )[0].hasAttribute( 'open' ) ) {
						$( this ).children( 'details' ).attr( 'open', 'open' );
					}
				});

				// Bring the first unsaved widget into view and focus on the first tabbable field.
				unsavedWidgetsElements.first().each( function() {
					if ( this.scrollIntoViewIfNeeded ) {
						this.scrollIntoViewIfNeeded();
					} else {
						this.scrollIntoView();
					}
					$( this ).find( '.widget-inside :tabbable:first' ).trigger( 'focus' );
				} );

				event.returnValue = wp.i18n.__( 'The changes you made will be lost if you navigate away from this page.' );
				return event.returnValue;
			}
		});

		$(document.body).on('click.widgets-toggle', function(e) {
			var target = $(e.target), css = {},
				widget, inside, targetWidth, widgetWidth, margin, saveButton, widgetId;

			if ( target.parents('.widget-top').length && ! target.parents('.widget-inside').length && ! target.parents('#available-widgets').length ) {
				widget = target.closest('li.widget');
				inside = widget.find('.widget-inside');
				targetWidth = parseInt( widget.find('input.widget-width').val(), 10 );
				widgetWidth = widget.parent().width();
				widgetId = inside.find( '.widget-id' ).val();

				// Save button is initially disabled, but is enabled when a field is changed.
				if ( ! widget.data( 'dirty-state-initialized' ) ) {
					saveButton = inside.find( '.widget-control-save' );
					saveButton.prop( 'disabled', true ).val( wp.i18n.__( 'Saved' ) );
					inside.on( 'input change', function() {
						self.dirtyWidgets[ widgetId ] = true;
						widget.addClass( 'widget-dirty' );
						saveButton.prop( 'disabled', false ).val( wp.i18n.__( 'Save' ) );
					});
					widget.data( 'dirty-state-initialized', true );
				}

				if ( ! widget.children('details')[0].hasAttribute( 'open' ) ) {
					if ( targetWidth > 250 && ( targetWidth + 30 > widgetWidth ) && widget.closest('ul.widgets-sortables').length ) {
						if ( widget.closest('div.widget-liquid-right').length ) {
							margin = isRTL ? 'margin-right' : 'margin-left';
						} else {
							margin = isRTL ? 'margin-left' : 'margin-right';
						}

						css[ margin ] = widgetWidth - ( targetWidth + 30 ) + 'px';
						widget.css( css );
					}
				}
			} else if ( target.hasClass('widget-control-save') ) {
				wpWidgets.save( target.closest('li.widget'), 0, 1, 0 );
				e.preventDefault();
			} else if ( target.hasClass('widget-control-remove') ) {
				wpWidgets.save( target.closest('li.widget'), 1, 1, 0 );
			} else if ( target.hasClass('widget-control-close') ) {
				widget = target.closest('li.widget');
				widget.children('details').removeAttr('open').children('summary').focus();
			} else if ( target.attr( 'id' ) === 'inactive-widgets-control-remove' ) {
				wpWidgets.removeInactiveWidgets();
				e.preventDefault();
			}
		});

		sidebars.children('.widget').each( function() {
			var $this = $(this);

			wpWidgets.appendTitle( this );

			if ( $this.find( 'p.widget-error' ).length ) {
				$this.find( '.widget-action' ).trigger( 'click' ).attr( 'aria-expanded', 'true' );
			}
		});

		$('#widget-list').find('.widget').draggable({
			connectToSortable: 'ul.widgets-sortables',
			handle: '> .widget-top > .widget-title',
			distance: 2,
			helper: 'clone',
			zIndex: 101,
			containment: '#wpwrap',
			refreshPositions: true,
			start: function( event, ui ) {
				var chooser = $(this).find('.widgets-chooser');

				ui.helper.find('div.widget-description').hide();
				the_id = this.id;

				if ( chooser.length ) {
					// Hide the chooser and move it out of the widget.
					$( '#wpbody-content' ).append( chooser.hide() );
					// Delete the cloned chooser from the drag helper.
					ui.helper.find('.widgets-chooser').remove();
					self.clearWidgetSelection();
				}
			},
			stop: function() {
				if ( rem ) {
					$(rem).hide();
				}

				rem = '';
			}
		});

		/**
		 * Opens and closes previously closed Sidebars when Widgets are dragged over/out of them.
		 */
		sidebars.droppable( {
			tolerance: 'intersect',
			over: function() {
				$( this ).sortable( 'refresh' );
			}
		} );

		sidebarWrappers.droppable( {
			tolerance: 'intersect',
			greedy: true,
			/**
			 * Open Sidebar when a Widget gets dragged over it.
			 *
			 * @ignore
			 *
			 * @param {Object} event jQuery event object.
			 */
			over: function( event ) {
				var details = $( event.target ).children( 'details' );

				if ( wpWidgets.hoveredSidebar && ! details.children('ul').is( wpWidgets.hoveredSidebar ) ) {
					// Close the previous Sidebar as the Widget has been dragged onto another Sidebar.
					details.removeAttr( 'open' );
				}

				if ( ! details.attr( 'open' ) ) {
					wpWidgets.hoveredSidebar = details.children('ul');
					details.attr( 'open', 'open' );
				}
			},

			/**
			 * Close Sidebar when the Widget gets dragged out of it.
			 *
			 * @ignore
			 *
			 * @param {Object} event jQuery event object.
			 */
			out: function( event ) {
				setTimeout( function() {
					var sidebar = event.target.querySelector( 'ul' );
					if ( ! sidebar.classList.contains( 'ui-droppable-hover' ) ) {
						event.target.querySelector( 'details' ).removeAttribute( 'open' );
					}
				}, 0 );
			}
		} );

		sidebars.sortable({
			placeholder: 'widget-placeholder',
			items: '> .widget',
			handle: '> .widget-top > .widget-title',
			cursor: 'move',
			distance: 2,
			containment: '#wpwrap',
			tolerance: 'pointer',
			refreshPositions: true,
			start: function( event, ui ) {
				var height, $this = $(this),
					$wrap = $this.closest( '.widgets-holder-wrap' ),
					details = ui.item.children('details');

				if ( details[0].hasAttribute( 'open' ) ) {
					details.removeAttr( 'open' );
					$(this).sortable('refreshPositions');
				}

				if ( $wrap.find('details')[0].hasAttribute( 'open' ) ) {
					// Lock all open sidebars min-height when starting to drag.
					// Prevents jumping when dragging a widget from an open sidebar to a closed sidebar below.
					height = ui.item.hasClass('ui-draggable') ? $this.height() : 1 + $this.height();
					$this.css( 'min-height', height + 'px' );
				}
			},

			stop: function( event, ui ) {
				var addNew, widgetNumber, $sidebar, $children, child, item,
					$widget = ui.item,
					id = the_id;

				// Reset the var to hold a previously closed sidebar.
				wpWidgets.hoveredSidebar = null;

				if ( $widget.hasClass('deleting') ) {
					wpWidgets.save( $widget, 1, 0, 1 ); // Delete widget.
					$widget.remove();
					return;
				}

				addNew = $widget.find('input.add_new').val();
				widgetNumber = $widget.find('input.multi_number').val();

				$widget.attr( 'style', '' ).removeClass('ui-draggable').find( '.widget-inside' ).removeAttr( 'inert' );
				the_id = '';

				if ( addNew ) {
					if ( 'multi' === addNew ) {
						$widget.html(
							$widget.html().replace( /<[^<>]+>/g, function( tag ) {
								return tag.replace( /__i__|%i%/g, widgetNumber );
							})
						);

						$widget.attr( 'id', id.replace( '__i__', widgetNumber ) );
						widgetNumber++;

						$( 'li#' + id ).find( 'input.multi_number' ).val( widgetNumber );
					} else if ( 'single' === addNew ) {
						$widget.attr( 'id', 'new-' + id );
						rem = 'li#' + id;
					}

					wpWidgets.save( $widget, 0, 0, 1 );
					$widget.find('input.add_new').val('');
					$document.trigger( 'widget-added', [ $widget ] );
				}

				$sidebar = $widget.parent();

				if ( ! $sidebar.parent()[0].hasAttribute('open') ) {
					$sidebar.parent().attr( 'open', 'open' );

					$children = $sidebar.children('.widget');

					// Make sure the dropped widget is at the top.
					if ( $children.length > 1 ) {
						child = $children.get(0);
						item = $widget.get(0);

						if ( child.id && item.id && child.id !== item.id ) {
							$( child ).before( $widget );
						}
					}
				}

				if ( addNew ) {
					$widget.children( 'details' ).attr( 'open', 'open' );
				} else {
					wpWidgets.saveOrder( $sidebar.attr('id') );
				}
			},

			activate: function() {
				$(this).parent().addClass( 'widget-hover' );
			},

			deactivate: function() {
				// Remove all min-height added on "start".
				$(this).css( 'min-height', '' ).parent().removeClass( 'widget-hover' );
			},

			receive: function( event, ui ) {
				var $sender = $( ui.sender );

				// Don't add more widgets to orphaned sidebars.
				if ( this.id.indexOf('orphaned_widgets') > -1 ) {
					$sender.sortable('cancel');
					return;
				}

				// If the last widget was moved out of an orphaned sidebar, close and remove it.
				if ( $sender.attr('id').indexOf('orphaned_widgets') > -1 && ! $sender.children('.widget').length ) {
					$sender.parents('.orphan-sidebar').slideUp( 400, function(){ $(this).remove(); } );
				}
			}
		}).sortable( 'option', 'connectWith', 'ul.widgets-sortables' );

		$('#available-widgets').droppable({
			tolerance: 'pointer',
			accept: function(o){
				return $(o).parent().attr('id') !== 'widget-list';
			},
			drop: function(e,ui) {
				ui.draggable.addClass('deleting');
				$('#removing-widget').hide().children('span').empty();
			},
			over: function(e,ui) {
				ui.draggable.addClass('deleting');
				$('div.widget-placeholder').hide();

				if ( ui.draggable.hasClass('ui-sortable-helper') ) {
					$('#removing-widget').show().children('span')
					.html( ui.draggable.find( 'summary.widget-title' ).html() );
				}
			},
			out: function(e,ui) {
				ui.draggable.removeClass('deleting');
				$('div.widget-placeholder').show();
				$('#removing-widget').hide().children('span').empty();
			}
		});

		// Area Chooser.
		$( '#widgets-right .widgets-holder-wrap' ).each( function( index, element ) {
			var $element = $( element ),
				name = $element.find( 'summary.sidebar-name' ).text() || '',
				ariaLabel = $element.find( '.sidebar-name' ).data( 'add-to' ),
				id = $element.find( '.widgets-sortables' ).attr( 'id' ),
				li = $( '<li>' ),
				button = $( '<button>', {
					type: 'button',
					'aria-pressed': 'false',
					'class': 'widgets-chooser-button',
					'aria-label': ariaLabel
				} ).text( name.toString().trim() );

			li.append( button );

			if ( index === 0 ) {
				li.addClass( 'widgets-chooser-selected' );
				button.attr( 'aria-pressed', 'true' );
			}

			selectSidebar.append( li );
			li.data( 'sidebarId', id );
		});

		// Add sidebar chooser on the widgets screen but not the Customizer.
		var widgetTops = document.querySelectorAll( '#available-widgets .widget .widget-top' ),
			chooserButtons = selectSidebar.find( '.widgets-chooser-button' );

		if ( document.querySelector( 'body' ).classList.contains( 'widgets-php' ) ) {
			widgetTops.forEach( function ( top ) {
				top.addEventListener( 'click', function( e ) {

					// Open the chooser.
					if ( ! e.target.closest( 'details' ).hasAttribute( 'open' ) ) {
						self.clearWidgetSelection();
						document.getElementById( 'widgets-left' ).classList.add( 'chooser' );

						chooserButtons.on( 'click.widgets-chooser', function() {
							selectSidebar.find( '.widgets-chooser-selected' ).removeClass( 'widgets-chooser-selected' );
							chooserButtons.attr( 'aria-pressed', 'false' );
							$( this )
								.attr( 'aria-pressed', 'true' )
								.closest( 'li' ).addClass( 'widgets-chooser-selected' );
						} );

						// Add CSS class and insert the chooser at the end of the details element.
						chooser[0].style.display = 'block'; // ensure that chooser remains available after dragging widget
						e.target.closest( 'li' ).classList.add( 'widget-in-question' );
						e.target.closest( '.widget-top' ).append( chooser[0] );
						e.target.focus();
					} else {
						document.querySelector( '.chooser' ).classList.remove( 'chooser' );
					}
				});
			});
		}

		// Add event handlers for choosers.
		var widgetActions = document.querySelectorAll( '.widgets-chooser-actions' );
		widgetActions.forEach( function ( action ) {
			action.addEventListener( 'click', function( e ) {
				if ( e.target.classList.contains( 'button-primary' ) ) {
					self.addWidget( chooser );
				}
				e.target.closest( '.widget-top' ).removeAttribute( 'open' );
				setTimeout( function () {
					document.querySelector( '.chooser' ).classList.remove( 'chooser' );
				}, 0 );
			});
		});

		// Enable Escape key.
		var openWidgets = document.querySelectorAll( '.widget.open' );
		openWidgets.forEach( function ( widget ) {
			widget.addEventListener( 'keyup', function( e ) {
				if ( e.key === 'Escape' ) {
					e.target.closest( '.widget-top' ).removeAttribute( 'open' );
					document.querySelector( '.chooser' ).classList.remove( 'chooser' );
				}
			});
		});
	},

	saveOrder : function( sidebarId ) {
		var data = {
			action: 'widgets-order',
			savewidgets: $('#_wpnonce_widgets').val(),
			sidebars: []
		};

		if ( sidebarId ) {
			$( '#' + sidebarId ).find( '.spinner:first' ).addClass( 'is-active' );
		}

		$('ul.widgets-sortables').each( function() {
			if ( $(this).sortable ) {
				data['sidebars[' + $(this).attr('id') + ']'] = $(this).sortable('toArray').join(',');
			}
		});

		$.post( ajaxurl, data, function() {
			$( '#inactive-widgets-control-remove' ).prop( 'disabled' , ! $( '#wp_inactive_widgets .widget' ).length );
			$( '.spinner' ).removeClass( 'is-active' );
		});
	},

	save : function( widget, del, animate, order ) {
		var self = this, data, a,
			sidebarId = widget.closest( 'ul.widgets-sortables' ).attr( 'id' ),
			form = widget.find( 'form' ),
			isAdd = widget.find( 'input.add_new' ).val();

		if ( ! del && ! isAdd && form.prop( 'checkValidity' ) && ! form[0].checkValidity() ) {
			return;
		}

		data = form.serialize();

		widget = $(widget);
		$( '.spinner', widget ).addClass( 'is-active' );

		a = {
			action: 'save-widget',
			savewidgets: $('#_wpnonce_widgets').val(),
			sidebar: sidebarId
		};

		if ( del ) {
			a.delete_widget = 1;
		}

		data += '&' + $.param(a);

		$.post( ajaxurl, data, function(r) {
			var id = $('input.widget-id', widget).val();

			if ( del ) {
				if ( ! $('input.widget_number', widget).val() ) {
					$('#available-widgets').find('input.widget-id').each(function(){
						if ( $(this).val() === id ) {
							$(this).closest('li.widget').show();
						}
					});
				}

				if ( animate ) {
					order = 0;
					widget.slideUp( 'fast', function() {
						$( this ).remove();
						wpWidgets.saveOrder();
						delete self.dirtyWidgets[ id ];
					});
				} else {
					widget.remove();
					delete self.dirtyWidgets[ id ];

					if ( sidebarId === 'wp_inactive_widgets' ) {
						$( '#inactive-widgets-control-remove' ).prop( 'disabled' , ! $( '#wp_inactive_widgets .widget' ).length );
					}
				}
			} else {
				$( '.spinner' ).removeClass( 'is-active' );
				if ( r && r.length > 2 ) {
					$( 'div.widget-content', widget ).html( r );
					wpWidgets.appendTitle( widget );

					// Re-disable the save button.
					widget.find( '.widget-control-save' ).prop( 'disabled', true ).val( wp.i18n.__( 'Saved' ) );

					widget.removeClass( 'widget-dirty' );

					// Clear the dirty flag from the widget.
					delete self.dirtyWidgets[ id ];

					$document.trigger( 'widget-updated', [ widget ] );

					if ( sidebarId === 'wp_inactive_widgets' ) {
						$( '#inactive-widgets-control-remove' ).prop( 'disabled' , ! $( '#wp_inactive_widgets .widget' ).length );
					}
				}
			}

			if ( order ) {
				wpWidgets.saveOrder();
			}
		});
	},

	removeInactiveWidgets : function() {
		var $element = $( '.remove-inactive-widgets' ), self = this, a, data;

		$( '.spinner', $element ).addClass( 'is-active' );

		a = {
			action : 'delete-inactive-widgets',
			removeinactivewidgets : $( '#_wpnonce_remove_inactive_widgets' ).val()
		};

		data = $.param( a );

		$.post( ajaxurl, data, function() {
			$( '#wp_inactive_widgets .widget' ).each(function() {
				var $widget = $( this );
				delete self.dirtyWidgets[ $widget.find( 'input.widget-id' ).val() ];
				$widget.remove();
			});
			$( '#inactive-widgets-control-remove' ).prop( 'disabled', true );
			$( '.spinner', $element ).removeClass( 'is-active' );
		} );
	},

	appendTitle : function(widget) {
		var title = $('input[id*="-title"]', widget).val() || '';

		if ( title ) {
			title = ': ' + title.replace(/<[^<>]+>/g, '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		$(widget).children('.widget-top').children('.widget-title').children()
				.children('.in-widget-title').html(title);

	},

	addWidget: function( chooser ) {
		var widget, widgetId, add, n, viewportTop, viewportBottom, sidebarBounds,
			sidebarId = chooser.find( '.widgets-chooser-selected' ).data('sidebarId'),
			sidebar = $( '#' + sidebarId );

		widget = $('#available-widgets').find('.widget-in-question').clone();
		widgetId = widget.attr('id');
		add = widget.find( 'input.add_new' ).val();
		n = widget.find( 'input.multi_number' ).val();

		// Remove the cloned chooser from the widget.
		widget.find('.widgets-chooser').remove();

		if ( 'multi' === add ) {
			widget.html(
				widget.html().replace( /<[^<>]+>/g, function(m) {
					return m.replace( /__i__|%i%/g, n );
				})
			);

			widget.attr( 'id', widgetId.replace( '__i__', n ) );
			n++;
			$( '#' + widgetId ).find('input.multi_number').val(n);
		} else if ( 'single' === add ) {
			widget.attr( 'id', 'new-' + widgetId );
			$( '#' + widgetId ).hide();
		}

		// Open the widgets container.
		sidebar.append( widget );
		sidebar.sortable('refresh');

		wpWidgets.save( widget, 0, 0, 1 );
		// No longer "new" widget.
		widget.find( 'input.add_new' ).val('');
		widget.find( '.widgets-chooser' ).removeAttr( 'inert' );

		$document.trigger( 'widget-added', [ widget ] );

		/*
		 * Check if any part of the sidebar is visible in the viewport. If it is, don't scroll.
		 * Otherwise, scroll up to so the sidebar is in view.
		 *
		 * We do this by comparing the top and bottom, of the sidebar so see if they are within
		 * the bounds of the viewport.
		 */
		viewportTop = $(window).scrollTop();
		viewportBottom = viewportTop + $(window).height();
		sidebarBounds = sidebar.offset();

		sidebarBounds.bottom = sidebarBounds.top + sidebar.outerHeight();

		if ( viewportTop > sidebarBounds.bottom || viewportBottom < sidebarBounds.top ) {
			$( 'html, body' ).animate({
				scrollTop: sidebarBounds.top - 130
			}, 200 );
		}

		window.setTimeout( function() {
			// Cannot use a callback in the animation above as it fires twice,
			// have to queue this "by hand".
			// At the end of the animation, announce the widget has been added.
			window.wp.a11y.speak( wp.i18n.__( 'Widget has been added to the selected sidebar' ), 'assertive' );
		}, 250 );
	},

	clearWidgetSelection: function() {
		$( '#widgets-left' ).removeClass( 'chooser' );
		$( '.widget-in-question' ).removeClass( 'widget-in-question' );
	}
};

$( function(){ wpWidgets.init(); } );

})(jQuery);

/**
 * Removed in 5.5.0, needed for back-compatibility.
 *
 * @since 4.9.0
 * @deprecated 5.5.0
 *
 * @type {object}
*/
wpWidgets.l10n = wpWidgets.l10n || {
	save: '',
	saved: '',
	saveAlert: '',
	widgetAdded: ''
};

wpWidgets.l10n = window.wp.deprecateL10nObject( 'wpWidgets.l10n', wpWidgets.l10n, '5.5.0' );
