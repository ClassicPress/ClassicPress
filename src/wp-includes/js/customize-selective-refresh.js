/**
 * @output wp-includes/js/customize-selective-refresh.js
 */

/* global wp, _customizePartialRefreshExports, console */

wp.customize.selectiveRefresh = ( function( api ) {
	'use strict';

	var self, Partial, Placement;

	// -----------------------------------------------------------------------
	// Minimal event emitter
	// -----------------------------------------------------------------------
	function makeEmitter( obj ) {
		obj._eventHandlers = {};
		obj.bind = function( event, handler ) {
			if ( ! obj._eventHandlers[ event ] ) {
				obj._eventHandlers[ event ] = [];
			}
			obj._eventHandlers[ event ].push( handler );
		};
		obj.unbind = function( event, handler ) {
			if ( ! obj._eventHandlers[ event ] ) {
				return;
			}
			obj._eventHandlers[ event ] = obj._eventHandlers[ event ].filter( function( h ) {
				return h !== handler;
			} );
		};
		obj.trigger = function( event, data ) {
			if ( ! obj._eventHandlers[ event ] ) {
				return;
			}
			obj._eventHandlers[ event ].forEach( function( h ) {
				h( data );
			} );
		};
		return obj;
	}

	// -----------------------------------------------------------------------
	// Minimal deferred/promise
	// -----------------------------------------------------------------------
	function makeDeferred() {
		var deferred = {
			_state: 'pending',
			_doneCallbacks: [],
			_failCallbacks: [],
			_alwaysCallbacks: [],
			_context: null,
			_args: null
		};

		deferred.promise = function() {
			return {
				done: function( cb ) {
					deferred._doneCallbacks.push( cb ); return this;
				},
				fail: function( cb ) {
					deferred._failCallbacks.push( cb ); return this;
				},
				always: function( cb ) {
					deferred._alwaysCallbacks.push( cb ); return this;
				},
				state: function() {
					return deferred._state;
				}
			};
		};

		deferred.resolve = function() {
			deferred._state = 'resolved';
			deferred._args = arguments;
			deferred._doneCallbacks.forEach( function( cb ) {
				cb.apply( deferred._context, deferred._args );
			} );
			deferred._alwaysCallbacks.forEach( function( cb ) {
				cb.apply( deferred._context, deferred._args );
			} );
		};

		deferred.resolveWith = function( context, args ) {
			deferred._context = context;
			deferred._state = 'resolved';
			deferred._args = args;
			deferred._doneCallbacks.forEach( function( cb ) {
				cb.apply( context, args );
			} );
			deferred._alwaysCallbacks.forEach( function( cb ) {
				cb.apply( context, args );
			} );
		};

		deferred.reject = function() {
			deferred._state = 'rejected';
			deferred._args = arguments;
			deferred._failCallbacks.forEach( function( cb ) {
				cb.apply( deferred._context, deferred._args );
			} );
			deferred._alwaysCallbacks.forEach( function( cb ) {
				cb.apply( deferred._context, deferred._args );
			} );
		};

		deferred.rejectWith = function( context, args ) {
			deferred._context = context;
			deferred._state = 'rejected';
			deferred._args = args;
			deferred._failCallbacks.forEach( function( cb ) {
				cb.apply( context, args );
			} );
			deferred._alwaysCallbacks.forEach( function( cb ) {
				cb.apply( context, args );
			} );
		};

		deferred.state = function() {
			return deferred._state;
		};

		return deferred;
	}

	// -----------------------------------------------------------------------
	// Minimal Values collection
	// -----------------------------------------------------------------------
	function makeValues( defaultConstructor ) {
		var items = {};

		function collection( id ) {
			return items[ id ] || null;
		}

		collection._items = items;
		collection._defaultConstructor = defaultConstructor || null;

		collection.has = function( id ) {
			return id in items;
		};

		collection.get = function( id ) {
			return items[ id ] || null;
		};

		collection.add = function( item ) {
			items[ item.id ] = item;
			collection.trigger( 'add', item );
			return item;
		};

		collection.remove = function( id ) {
			var item = items[ id ];
			if ( item ) {
				delete items[ id ];
				collection.trigger( 'remove', item );
			}
		};

		collection.each = function( callback ) {
			Object.keys( items ).forEach( function( id ) {
				callback( items[ id ] );
			} );
		};

		makeEmitter( collection );

		return collection;
	}


	// -----------------------------------------------------------------------
	// Minimal observable value
	// -----------------------------------------------------------------------
	function makeValue( initialValue ) {
		var currentValue = initialValue,
			handlers = [];

		var value = function( newValue ) {
			if ( arguments.length ) {
				var oldValue = currentValue;
				currentValue = newValue;
				handlers.forEach( function( h ) {
					h( newValue, oldValue );
				} );
				return value;
			}
			return currentValue;
		};

		value.get = function() {
			return currentValue;
		};

		value.set = function( newValue ) {
			return value( newValue );
		};

		value.bind = function( handler ) {
			handlers.push( handler );
		};

		value.unbind = function( handler ) {
			handlers = handlers.filter( function( h ) {
				return h !== handler;
			} );
		};

		return value;
	}

	// -----------------------------------------------------------------------
	// Self (the selectiveRefresh namespace)
	// -----------------------------------------------------------------------
	self = makeEmitter( {
		ready: makeDeferred(),
		editShortcutVisibility: makeValue(),
		data: {
			partials: {},
			renderQueryVar: '',
			l10n: {
				shiftClickToEdit: ''
			}
		},
		currentRequest: null
	} );

	// -----------------------------------------------------------------------
	// Partial class
	// -----------------------------------------------------------------------
	function Partial( id, options ) {
		var partial = this;
		options = options || {};
		partial.id = id;

		partial.params = Object.assign(
			{ settings: [] },
			partial.defaults,
			options.params || options
		);

		partial.deferred = {};
		partial.deferred.ready = makeDeferred();

		partial.deferred.ready._doneCallbacks.push( function() {
			partial.ready();
		} );
	}

	Partial.prototype.defaults = {
		selector: null,
		primarySetting: null,
		containerInclusive: false,
		fallbackRefresh: true
	};

	Partial.prototype._pendingRefreshPromise = null;

	Partial.prototype.ready = function() {
		var partial = this;
		partial.placements().forEach( function( placement ) {
			if ( placement.container ) {
				placement.container.setAttribute( 'title', self.data.l10n.shiftClickToEdit );
			}
			partial.createEditShortcutForPlacement( placement );
		} );
		if ( partial.params.selector ) {
			document.addEventListener( 'click', function( e ) {
				if ( ! e.shiftKey ) {
					return;
				}
				var target = e.target.closest( partial.params.selector );
				if ( ! target ) {
					return;
				}
				e.preventDefault();
				partial.placements().forEach( function( placement ) {
					if ( placement.container === target ) {
						partial.showControl();
					}
				} );
			} );
		}
	};

	Partial.prototype.createEditShortcutForPlacement = function( placement ) {
		var partial = this,
			illegalContainerSelector,
			shortcut;

		if ( ! placement.container ) {
			return;
		}

		illegalContainerSelector = 'area, audio, base, bdi, bdo, br, button, canvas, col, colgroup, command, datalist, embed, head, hr, html, iframe, img, input, keygen, label, link, map, math, menu, meta, noscript, object, optgroup, option, param, progress, rp, rt, ruby, script, select, source, style, svg, table, tbody, textarea, tfoot, thead, title, tr, track, video, wbr';

		if ( ! placement.container ) {
			return;
		}
		if ( placement.container.closest( 'head' ) ) {
			return;
		}
		if ( placement.container.matches( illegalContainerSelector ) ) {
			return;
		}

		shortcut = partial.createEditShortcut();
		shortcut.addEventListener( 'click', function( event ) {
			event.preventDefault();
			event.stopPropagation();
			partial.showControl();
		} );
		partial.addEditShortcutToPlacement( placement, shortcut );
	};

	Partial.prototype.addEditShortcutToPlacement = function( placement, editShortcut ) {
		if ( ! placement.container ) {
			return;
		}
		placement.container.prepend( editShortcut );
		if ( ! placement.container.offsetParent && 'none' === getComputedStyle( placement.container ).display ) {
			editShortcut.classList.add( 'customize-partial-edit-shortcut-hidden' );
		}
	};

	Partial.prototype.getEditShortcutClassName = function() {
		var partial = this,
			cleanId = partial.id.replace( /]/g, '' ).replace( /\[/g, '-' );
		return 'customize-partial-edit-shortcut-' + cleanId;
	};

	Partial.prototype.getEditShortcutTitle = function() {
		var partial = this,
			l10n = self.data.l10n;
		switch ( partial.getType() ) {
			case 'widget':
				return l10n.clickEditWidget;
			case 'blogname':
				return l10n.clickEditTitle;
			case 'blogdescription':
				return l10n.clickEditTitle;
			case 'nav_menu':
				return l10n.clickEditMenu;
			default:
				return l10n.clickEditMisc;
		}
	};

	Partial.prototype.getType = function() {
		var partial = this,
			settingId;
		settingId = partial.params.primarySetting || ( partial.settings()[0] ) || 'unknown';
		if ( partial.params.type ) {
			return partial.params.type;
		}
		if ( settingId.match( /^nav_menu_instance\[/ ) ) {
			return 'nav_menu';
		}
		if ( settingId.match( /^widget_.+\[\d+]$/ ) ) {
			return 'widget';
		}
		return settingId;
	};

	Partial.prototype.createEditShortcut = function() {
		var partial = this,
			shortcutTitle = partial.getEditShortcutTitle(),
			buttonContainer = document.createElement( 'span' ),
			button = document.createElement( 'button' ),
			svgNS = 'http://www.w3.org/2000/svg',
			svg, path;

		buttonContainer.className = 'customize-partial-edit-shortcut ' + partial.getEditShortcutClassName();
		button.setAttribute( 'aria-label', shortcutTitle );
		button.setAttribute( 'title', shortcutTitle );
		button.className = 'customize-partial-edit-shortcut-button';

		svg = document.createElementNS( svgNS, 'svg' );
		svg.setAttribute( 'xmlns', svgNS );
		svg.setAttribute( 'viewBox', '0 0 20 20' );

		path = document.createElementNS( svgNS, 'path' );
		path.setAttribute( 'd', 'M13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61 1.11 1.11 5.54-5.65zm-2.97 8.23l5.58-5.6-1.07-1.08-5.59 5.6z' );
		svg.appendChild( path );
		button.appendChild( svg );
		buttonContainer.appendChild( button );

		return buttonContainer;
	};

	Partial.prototype.placements = function() {
		var partial = this,
			selector = partial.params.selector || '',
			elements;

		if ( selector ) {
			selector += ', ';
		}
		selector += '[data-customize-partial-id="' + partial.id + '"]';

		elements = document.querySelectorAll( selector );
		return Array.prototype.slice.call( elements ).map( function( el ) {
			var contextAttr = el.dataset.customizePartialPlacementContext,
				context;
			try {
				context = contextAttr ? JSON.parse( contextAttr ) : {};
			} catch ( e ) {
				context = {};
			}
			return new Placement( {
				partial: partial,
				container: el,
				context: context
			} );
		} );
	};

	Partial.prototype.settings = function() {
		var partial = this;
		if ( partial.params.settings && partial.params.settings.length ) {
			return partial.params.settings;
		} else if ( partial.params.primarySetting ) {
			return [ partial.params.primarySetting ];
		} else {
			return [ partial.id ];
		}
	};

	Partial.prototype.showControl = function() {
		var partial = this,
			settingId = partial.params.primarySetting;
		if ( ! settingId ) {
			settingId = partial.settings()[0];
		}
		if ( 'nav_menu' === partial.getType() ) {
			if ( partial.params.navMenuArgs && partial.params.navMenuArgs.theme_location ) {
				settingId = 'nav_menu_locations[' + partial.params.navMenuArgs.theme_location + ']';
			} else if ( partial.params.navMenuArgs && partial.params.navMenuArgs.menu ) {
				settingId = 'nav_menu[' + String( partial.params.navMenuArgs.menu ) + ']';
			}
		}
		api.preview.send( 'focus-control-for-setting', settingId );
	};

	Partial.prototype.preparePlacement = function( placement ) {
		if ( placement.container ) {
			placement.container.classList.add( 'customize-partial-refreshing' );
		}
	};

	Partial.prototype.refresh = function() {
		var partial = this,
			refreshPromise;

		refreshPromise = self.requestPartial( partial );

		if ( ! partial._pendingRefreshPromise ) {
			partial.placements().forEach( function( placement ) {
				partial.preparePlacement( placement );
			} );

			refreshPromise.done( function( placements ) {
				placements.forEach( function( placement ) {
					partial.renderContent( placement );
				} );
			} );

			refreshPromise.fail( function( data, placements ) {
				partial.fallback( data, placements );
			} );

			partial._pendingRefreshPromise = refreshPromise;
			refreshPromise.always( function() {
				partial._pendingRefreshPromise = null;
			} );
		}

		return refreshPromise;
	};

	Partial.prototype.renderContent = function( placement ) {
		var partial = this,
			content,
			newContainerElement,
			fragment;

		if ( ! placement.container ) {
			partial.fallback( new Error( 'no_container' ), [ placement ] );
			return false;
		}
		if ( false === placement.addedContent ) {
			partial.fallback( new Error( 'missing_render' ), [ placement ] );
			return false;
		}
		if ( 'string' !== typeof placement.addedContent ) {
			partial.fallback( new Error( 'non_string_content' ), [ placement ] );
			return false;
		}

		self.orginalDocumentWrite = document.write;
		document.write = function() {
			throw new Error( self.data.l10n.badDocumentWrite );
		};

		try {
			content = placement.addedContent;

			if ( wp.emoji && wp.emoji.parse && ! document.head.contains( placement.container ) ) {
				content = wp.emoji.parse( content );
			}

			if ( partial.params.containerInclusive ) {
				var tempDiv = document.createElement( 'div' );
				tempDiv.innerHTML = content;
				newContainerElement = tempDiv.firstChild;

				var newContext = newContainerElement.dataset.customizePartialPlacementContext;
				if ( newContext ) {
					try {
						placement.context = Object.assign( placement.context, JSON.parse( newContext ) );
					} catch ( e ) {}
				}
				newContainerElement.dataset.customizePartialPlacementContext = JSON.stringify( placement.context );

				placement.removedNodes = placement.container;
				placement.container.parentNode.replaceChild( newContainerElement, placement.container );
				placement.container = newContainerElement;
				placement.container.setAttribute( 'title', self.data.l10n.shiftClickToEdit );
			} else {
				fragment = document.createDocumentFragment();
				while ( placement.container.firstChild ) {
					fragment.appendChild( placement.container.firstChild );
				}
				placement.removedNodes = fragment;
				placement.container.innerHTML = content;
			}

			placement.container.classList.remove( 'customize-render-content-error' );

		} catch ( error ) {
			if ( 'undefined' !== typeof console && console.error ) {
				console.error( partial.id, error );
			}
			partial.fallback( error, [ placement ] );
		}

		document.write = self.orginalDocumentWrite;
		self.orginalDocumentWrite = null;

		partial.createEditShortcutForPlacement( placement );
		placement.container.classList.remove( 'customize-partial-refreshing' );
		placement.container.dataset.customizePartialContentRendered = 'true';

		if ( wp.mediaelement ) {
			wp.mediaelement.initialize();
		}
		if ( wp.playlist ) {
			wp.playlist.initialize();
		}

		self.trigger( 'partial-content-rendered', placement );
		return true;
	};

	Partial.prototype.fallback = function() {
		var partial = this;
		if ( partial.params.fallbackRefresh ) {
			self.requestFullRefresh();
		}
	};

	self.Partial = Partial;

	// -----------------------------------------------------------------------
	// Placement class
	// -----------------------------------------------------------------------
	function Placement( args ) {
		args = Object.assign( {}, args || {} );
		if ( ! args.partial || ! ( args.partial instanceof Partial ) ) {
			throw new Error( 'Missing partial' );
		}
		args.context = args.context || {};
		Object.assign( this, args );
	}

	Placement.prototype.partial = null;
	Placement.prototype.container = null;
	Placement.prototype.startNode = null;
	Placement.prototype.endNode = null;
	Placement.prototype.context = null;
	Placement.prototype.addedContent = null;
	Placement.prototype.removedNodes = null;

	self.Placement = Placement;

	// -----------------------------------------------------------------------
	// Partial collection and static methods
	// -----------------------------------------------------------------------
	self.partialConstructor = {};
	self.partial = makeValues( Partial );

	self._pendingPartialRequests = {};
	self._debouncedTimeoutId = null;
	self._currentRequest = null;

	self.requestFullRefresh = function() {
		api.preview.send( 'refresh' );
	};

	self.requestPartial = function( partial ) {
		var partialRequest;

		if ( self._debouncedTimeoutId ) {
			clearTimeout( self._debouncedTimeoutId );
			self._debouncedTimeoutId = null;
		}
		if ( self._currentRequest ) {
			self._currentRequest.abort();
			self._currentRequest = null;
		}

		partialRequest = self._pendingPartialRequests[ partial.id ];
		if ( ! partialRequest || 'pending' !== partialRequest.deferred.state() ) {
			partialRequest = {
				deferred: makeDeferred(),
				partial: partial
			};
			self._pendingPartialRequests[ partial.id ] = partialRequest;
		}

		partial = null;

		self._debouncedTimeoutId = setTimeout( function() {
			var data, partialPlacementContexts, partialsPlacements, controller, formData;

			self._debouncedTimeoutId = null;
			data = {
				wp_customize: 'on',
				nonce: api.settings.nonce.preview,
				customize_theme: api.settings.theme.stylesheet,
				customized: JSON.stringify( window.updatedControls || {} ),
				customize_changeset_uuid: api.settings.changeset.uuid
			};
			partialsPlacements = {};
			partialPlacementContexts = {};

			Object.keys( self._pendingPartialRequests ).forEach( function( partialId ) {
				var pending = self._pendingPartialRequests[ partialId ];
				partialsPlacements[ partialId ] = pending.partial.placements();
				if ( ! self.partial.has( partialId ) ) {
					pending.deferred.rejectWith(
						pending.partial,
						[ new Error( 'partial_removed' ), partialsPlacements[ partialId ] ]
					);
				} else {
					partialPlacementContexts[ partialId ] = partialsPlacements[ partialId ].map( function( placement ) {
						return placement.context || {};
					} );
				}
			} );

			data.partials = JSON.stringify( partialPlacementContexts );
			data[ self.data.renderQueryVar ] = '1';

			formData = new URLSearchParams( data ); // serializes to x-www-form-urlencoded[web:15][web:17]

			// Abort any in-flight request, then create a new one for fetch
			if ( self._currentRequest && self._currentRequest.abort ) {
				self._currentRequest.abort();
				self._currentRequest = null;
			}

			controller = new AbortController(); // fetch abort support[web:14][web:18][web:22]
			self._currentRequest = controller;

			fetch( api.settings.url.self, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With': 'XMLHttpRequest'
				},
				body: formData,
				signal: controller.signal
			} )
			.then( function( response ) {
				if ( ! response.ok ) {
					throw response;
				}
				return response.text();
			} )
			.then( function( text ) {
				var responseData;
				try {
					responseData = JSON.parse( text );
				} catch ( e ) {
					Object.keys( self._pendingPartialRequests ).forEach( function( partialId ) {
						var pending = self._pendingPartialRequests[ partialId ];
						pending.deferred.rejectWith(
							pending.partial,
							[ new Error( 'json_parse_error' ), partialsPlacements[ partialId ] ]
						);
					} );
					self._pendingPartialRequests = {};
					return;
				}

				self.trigger( 'render-partials-response', responseData );

				if ( responseData.data.errors && 'undefined' !== typeof console && console.warn ) {
					responseData.data.errors.forEach( function( error ) {
						console.warn( error );
					} );
				}

				Object.keys( self._pendingPartialRequests ).forEach( function( partialId ) {
					var pending = self._pendingPartialRequests[ partialId ],
						placementsContents;

					if ( ! Array.isArray( responseData.data.contents[ partialId ] ) ) {
						pending.deferred.rejectWith(
							pending.partial,
							[ new Error( 'unrecognized_partial' ), partialsPlacements[ partialId ] ]
						);
					} else {
						placementsContents = responseData.data.contents[ partialId ].map( function( content, i ) {
							var partialPlacement = partialsPlacements[ partialId ][ i ];
							if ( partialPlacement ) {
								partialPlacement.addedContent = content;
							} else {
								partialPlacement = new Placement( {
									partial: pending.partial,
									addedContent: content
								} );
							}
							return partialPlacement;
						} );
						pending.deferred.resolveWith( pending.partial, [ placementsContents ] );
					}
				} );
				self._pendingPartialRequests = {};
			} )
			.catch( function( error ) {
				// Abort is intentional: keep deferreds pending for reuse, like old onabort
				if ( error && error.name === 'AbortError' ) {
					return;
				}

				// Network or HTTP error: reject all pending partials
				Object.keys( self._pendingPartialRequests ).forEach( function( partialId ) {
					var pending = self._pendingPartialRequests[ partialId ];
					pending.deferred.rejectWith(
						pending.partial,
						[ error, partialsPlacements[ partialId ] ]
					);
				} );
				self._pendingPartialRequests = {};
			} );

		}, api.settings.timeouts.selectiveRefresh );

		return partialRequest.deferred.promise();
	};

	self.addPartials = function( rootElement, options ) {
		var containerElements;

		if ( ! rootElement ) {
			rootElement = document.documentElement;
		}
		options = Object.assign( { triggerRendered: true }, options || {} );

		containerElements = Array.prototype.slice.call( rootElement.querySelectorAll( '[data-customize-partial-id]' ) );
		if ( rootElement.dataset && rootElement.dataset.customizePartialId ) {
			containerElements.unshift( rootElement );
		}

		containerElements.forEach( function( containerElement ) {
			var id, partial, placement, Constructor, partialOptions, containerContext;

			id = containerElement.dataset.customizePartialId;
			if ( ! id ) {
				return;
			}

			containerContext = {};
			try {
				containerContext = JSON.parse( containerElement.dataset.customizePartialPlacementContext || '{}' );
			} catch ( e ) {}

			partial = self.partial( id );
			if ( ! partial ) {
				partialOptions = {};
				try {
					partialOptions = JSON.parse( containerElement.dataset.customizePartialOptions || '{}' );
				} catch ( e ) {}
				partialOptions.constructingContainerContext = containerContext;
				Constructor = self.partialConstructor[ containerElement.dataset.customizePartialType ] || self.Partial;
				partial = new Constructor( id, partialOptions );
				self.partial.add( partial );
			}

			if ( options.triggerRendered && ! containerElement.dataset.customizePartialContentRendered ) {
				placement = new Placement( {
					partial: partial,
					context: containerContext,
					container: containerElement
				} );

				containerElement.setAttribute( 'title', self.data.l10n.shiftClickToEdit );
				partial.createEditShortcutForPlacement( placement );
				self.trigger( 'partial-content-rendered', placement );
			}

			containerElement.dataset.customizePartialContentRendered = 'true';
		} );
	};

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------
	( function init() {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', init );
			return;
		}

		Object.assign( self.data, _customizePartialRefreshExports );

		Object.keys( self.data.partials ).forEach( function( id ) {
			var data = self.data.partials[ id ],
				Constructor,
				partial = self.partial( id );

			if ( ! partial ) {
				Constructor = self.partialConstructor[ data.type ] || self.Partial;
				partial = new Constructor(
					id,
					Object.assign( { params: data }, data )
				);
				self.partial.add( partial );
			} else {
				Object.assign( partial.params, data );
			}
		} );

		self.addPartials( document.documentElement, { triggerRendered: false } );

		self.partial.each( function( partial ) {
			partial.deferred.ready.resolve();
		} );

		document.body.classList.add( 'customize-partial-edit-shortcuts-shown' );

	} )();

	return self;

} )( wp.customize );

