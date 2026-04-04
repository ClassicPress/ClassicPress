/**
 * Script run inside a Customizer preview frame.
 * Vanilla JS rewrite — no jQuery, no Backbone.
 *
 * @output wp-includes/js/customize-preview.js
 *
 * @since CP-2.8.0
 */

/* global wp */
( function( exports ) {
	'use strict';

	// Create api as a callable function from the outset
	function api( id, callback ) {
		if ( callback ) {
			if ( api._settings[ id ] ) {
				callback( api._settings[ id ] );
			}
			return;
		}
		return api._settings[ id ] ? api._settings[ id ].get() : undefined;
	}

	// Attach all properties directly to this function
	api._settings = {};
	api.settings = null;
	api.messageHandlers = {};

	api.has = function( id ) {
		return id in api._settings;
	};

	api.each = function( callback ) {
		Object.keys( api._settings ).forEach( function( id ) {
			callback( api._settings[ id ] );
		} );
	};

	api.create = function( id, value, options ) {
		var setting = {
			id: id,
			value: value,
			_dirty: ( options && options._dirty ) ? true : false,
			_handlers: [],
			get: function() {
				return this.value;
			},
			set: function( newValue ) {
				this.value = newValue;
				this._handlers.forEach( function( h ) {
					h( newValue );
				} );
			},
			bind: function( handler ) {
				this._handlers.push( handler );
			}
		};
		api._settings[ id ] = setting;
		return setting;
	};

	api.utils = {
		parseQueryString: function( queryString ) {
			var params = {};
			if ( ! queryString ) {
				return params;
			}
			queryString.split( '&' ).forEach( function( pair ) {
				var parts = pair.split( '=' );
				if ( parts[0] ) {
					params[ decodeURIComponent( parts[0] ) ] = parts[1] ? decodeURIComponent( parts[1].replace( /\+/g, ' ' ) ) : '';
				}
			} );
			return params;
		}
	};

	var currentHistoryState = {},
		parentOrigin = location.ancestorOrigins ? location.ancestorOrigins[0] : document.referrer.split( '/' ).slice( 0, 3 ).join( '/' );

	api.preview = {
		channel: null,
		scheme: {
			_value: location.protocol.replace( /:$/, '' ),
			get: function() {
				return this._value;
			}
		},
		send: function( type, data ) {
			if ( ! this.channel ) {
				return;
			}
			window.parent.postMessage( JSON.stringify( {
				channel: this.channel,
				type: type,
				data: data !== undefined ? data : null
			} ), parentOrigin || '*' );
		},
		bind: function( type, handler ) {
			if ( ! api.messageHandlers[ type ] ) {
				api.messageHandlers[ type ] = [];
			}
			api.messageHandlers[ type ].push( handler );
		},
		trigger: function( type, data ) {
			if ( api.messageHandlers[ type ] ) {
				api.messageHandlers[ type ].forEach( function( handler ) {
					handler( data );
				} );
			}
		}
	};

	// Listen for messages from parent frame
	window.addEventListener( 'message', function( event ) {
		var message;

		if ( event.origin !== parentOrigin ) {
			return;
		}
		if ( ! event.data ) {
			return;
		}

		try {
			message = JSON.parse( event.data );
		} catch( e ) { // Malformed JSON in data attribute — not a fatal error.
			return;
		}
		if ( ! message || ! message.type ) {
			return;
		}
		if ( api.preview.channel && message.channel && message.channel !== api.preview.channel ) {
			return;
		}
		api.preview.trigger( message.type, message.data );
	} );

	/**
	 * Link previewing
	 *
	 * @since CP-2.8.0
	 */
	api.isLinkPreviewable = function( element, options ) {
		var args = Object.assign( {}, { allowAdminAjax: false }, options || {} ),
			elementHost,
			matchesAllowedUrl;

		if ( 'https:' !== element.protocol && 'http:' !== element.protocol ) {
			return false;
		}

		elementHost = element.host.replace( /:(80|443)$/, '' );
		matchesAllowedUrl = api.settings.url.allowed.some( function( allowedUrl ) {
			var parsedAllowedUrl = document.createElement( 'a' );
			parsedAllowedUrl.href = allowedUrl;
			return parsedAllowedUrl.protocol === element.protocol &&
				parsedAllowedUrl.host.replace( /:(80|443)$/, '' ) === elementHost &&
				element.pathname.indexOf( parsedAllowedUrl.pathname.replace( /\/$/, '' ) ) === 0;
		} );

		if ( ! matchesAllowedUrl ) {
			return false;
		}
		if ( /\/wp-(login|signup)\.php$/.test( element.pathname ) ) {
			return false;
		}
		if ( /\/wp-admin\/admin-ajax\.php$/.test( element.pathname ) ) {
			return args.allowAdminAjax;
		}
		if ( /\/wp-(admin|includes|content)(\/|$)/.test( element.pathname ) ) {
			return false;
		}

		return true;
	};

	api.prepareLinkPreview = function( element ) {
		var queryParams;

		if ( ! element.hasAttribute( 'href' ) ) {
			return;
		}
		if ( element.closest( '#wpadminbar' ) ) {
			return;
		}
		if ( '#' === element.getAttribute( 'href' ).substr( 0, 1 ) || ! /^https?:$/.test( element.protocol ) ) {
			return;
		}
		if ( api.settings.channel && 'https' === api.preview.scheme.get() && 'http:' === element.protocol && api.settings.url.allowedHosts.indexOf( element.host ) !== -1 ) {
			element.protocol = 'https:';
		}
		if ( element.classList.contains( 'wp-playlist-caption' ) ) {
			return;
		}
		if ( ! api.isLinkPreviewable( element ) ) {
			if ( api.settings.channel ) {
				element.classList.add( 'customize-unpreviewable' );
			}
			return;
		}

		element.classList.remove( 'customize-unpreviewable' );
		queryParams = api.utils.parseQueryString( element.search.substring( 1 ) );
		queryParams.customize_changeset_uuid = api.settings.changeset.uuid;

		if ( api.settings.changeset.autosaved ) {
			queryParams.customize_autosaved = 'on';
		}
		if ( ! api.settings.theme.active ) {
			queryParams.customize_theme = api.settings.theme.stylesheet;
		}
		if ( api.settings.channel ) {
			queryParams.customize_messenger_channel = api.settings.channel;
		}

		element.search = new URLSearchParams( queryParams ).toString();
	};

	api.addLinkPreviewing = function() {
		document.body.querySelectorAll( 'a[href], area[href]' ).forEach( function( el ) {
			api.prepareLinkPreview( el );
		} );

		api.mutationObserver = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.target.querySelectorAll( 'a[href], area[href]' ).forEach( function( el ) {
					api.prepareLinkPreview( el );
				} );
			} );
		} );
		api.mutationObserver.observe( document.documentElement, { childList: true, subtree: true } );
	};

	/**
	 * Form previewing
	 *
	 * @since CP-2.8.0
	 */
	api.prepareFormPreview = function( form ) {
		var urlParser = document.createElement( 'a' ),
			stateParams = {};

		if ( ! form.action ) {
			form.action = location.href;
		}

		urlParser.href = form.action;

		if ( api.settings.channel && 'https' === api.preview.scheme.get() && 'http:' === urlParser.protocol && api.settings.url.allowedHosts.indexOf( urlParser.host ) !== -1 ) {
			urlParser.protocol = 'https:';
			form.action = urlParser.href;
		}

		if ( 'GET' !== form.method.toUpperCase() || ! api.isLinkPreviewable( urlParser ) ) {
			if ( api.settings.channel ) {
				form.classList.add( 'customize-unpreviewable' );
			}
			return;
		}

		form.classList.remove( 'customize-unpreviewable' );
		stateParams.customize_changeset_uuid = api.settings.changeset.uuid;

		if ( api.settings.changeset.autosaved ) {
			stateParams.customize_autosaved = 'on';
		}
		if ( ! api.settings.theme.active ) {
			stateParams.customize_theme = api.settings.theme.stylesheet;
		}
		if ( api.settings.channel ) {
			stateParams.customize_messenger_channel = api.settings.channel;
		}

		Object.keys( stateParams ).forEach( function( name ) {
			var input = form.querySelector( 'input[name="' + name + '"]' );
			if ( input ) {
				input.value = stateParams[ name ];
			} else {
				var hidden = document.createElement( 'input' );
				hidden.type = 'hidden';
				hidden.name = name;
				hidden.value = stateParams[ name ];
				form.prepend( hidden );
			}
		} );

		if ( api.settings.channel ) {
			form.target = '_self';
		}
	};

	api.addFormPreviewing = function() {
		document.body.querySelectorAll( 'form' ).forEach( function( form ) {
			api.prepareFormPreview( form );
		} );

		new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.target.querySelectorAll( 'form' ).forEach( function( form ) {
					api.prepareFormPreview( form );
				} );
			} );
		} ).observe( document.documentElement, { childList: true, subtree: true } );
	};

	/**
	 * Handle Shift + clicks on icons indicating live preview availability
	 *
	 * @since CP-2.8.0
	 */
	api.addShortcutFocusing = function() {
		function handleShortcutClick( e ) {
			var container, partialId, partialType, shortcut, splits,
				context = {};

			if ( ! e.shiftKey ) { // must use Shift key
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			container = e.currentTarget.closest( '[data-customize-partial-id]' );
			if ( container ) {
				partialId   = container.dataset.customizePartialId;
				partialType = container.dataset.customizePartialType;
			} else {
				// Fall back to reading the partial ID from the shortcut span class
				shortcut = e.currentTarget.closest( '.customize-partial-edit-shortcut' );
				if ( ! shortcut ) {
					return;
				}
				partialId   = shortcut.className.replace( 'customize-partial-edit-shortcut customize-partial-edit-shortcut-', '' );
				partialType = 'default';
				if ( partialId.includes( '-' ) ) {
					splits = partialId.split( '-' );
					splits.forEach( function( split ) {
						if ( split === 'widget' ) {
							partialType = 'widget';
						}
					} );
				}
				container = shortcut.parentElement;
			}

			// Map known partial types to their focus target
			if ( partialType === 'nav_menu_instance' ) {
				// Use getAttribute instead of dataset to avoid potential problems with parsing escaped values
				context = JSON.parse( container.getAttribute( 'data-customize-partial-placement-context' ) || '{}' );
				api.preview.send( 'focus-partial', {
					id:     partialId,
					type:   partialType,
					place:  context.theme_location || null,
					menuId: context.menu_id || null
				} );
			} else if ( partialType === 'widget' ) {
				// Use getAttribute instead of dataset to avoid potential problems with parsing escaped values
				context = JSON.parse( container.getAttribute( 'data-customize-partial-placement-context' ) || '{}' );
				api.preview.send( 'focus-partial', {
					id: partialId,
					type: partialType,
					sidebarId: context.sidebar_id || null,
					widgetId: container.dataset.customizeWidgetId || null
				} );
			} else { // partialType === 'default'
				api.preview.send( 'focus-partial', {
					id: partialId,
					type: partialType
				} );
			}
		}

		document.body.querySelectorAll( '.customize-partial-edit-shortcut-button' ).forEach( function( btn ) {
			btn.addEventListener( 'click', handleShortcutClick );
		} );

		// Cover dynamically refreshed partials
		new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.addedNodes.forEach( function( node ) {
					var btns = [];
					if ( node.nodeType !== 1 ) {
						return;
					}
					if ( node.matches( '.customize-partial-edit-shortcut-button' ) ) {
						btns.push( node );
					}
					node.querySelectorAll( '.customize-partial-edit-shortcut-button' ).forEach( function( button ) {
						btns.push( button );
					} );
					btns.forEach( function( btn ) {
						btn.addEventListener( 'click', handleShortcutClick );
					} );
				} );
			} );
		} ).observe( document.body, { childList: true, subtree: true } );
	};

	/**
	 * Setting preview handlers
	 *
	 * @since CP-2.8.0
	 */
	api.settingPreviewHandlers = {
		custom_logo: function( attachmentId ) {
			document.body.classList.toggle( 'wp-custom-logo', !! attachmentId );
		},
		custom_css: function( value ) {
			var el = document.getElementById( 'wp-custom-css' );
			if ( el ) {
				el.textContent = value;
			}
		},
		background: function() {
			var css = '',
				props = [ 'color', 'image', 'preset', 'position_x', 'position_y', 'size', 'repeat', 'attachment' ],
				settings = {},
				bgCss;

			props.forEach( function( prop ) {
				settings[ prop ] = api( 'background_' + prop );
			} );

			document.body.classList.toggle( 'custom-background', !! ( settings.color || settings.image ) );

			if ( settings.color ) {
				css += 'background-color: ' + settings.color + ';';
			}
			if ( settings.image ) {
				css += 'background-image: url("' + settings.image + '");';
				css += 'background-size: ' + settings.size + ';';
				css += 'background-position: ' + settings.position_x + ' ' + settings.position_y + ';';
				css += 'background-repeat: ' + settings.repeat + ';';
				css += 'background-attachment: ' + settings.attachment + ';';
			}

			bgCss = document.getElementById( 'custom-background-css' );
			if ( bgCss ) { // css values come from api.settings, which is server-populated
				bgCss.textContent = 'body.custom-background { ' + css + ' }';
			}
		}
	};

	/**
	 * Keep-alive
	 *
	 * @since CP-2.8.0
	 */
	api.keepAliveCurrentUrl = ( function() {
		var previousPathName = location.pathname,
			previousQueryString = location.search.substr( 1 ),
			previousQueryParams = null,
			stateQueryParams = [ 'customize_theme', 'customize_changeset_uuid', 'customize_messenger_channel', 'customize_autosaved' ];

		return function keepAliveCurrentUrl() {
			var urlParser, currentQueryParams;

			if ( previousQueryString === location.search.substr( 1 ) && previousPathName === location.pathname ) {
				api.preview.send( 'keep-alive' );
				return;
			}

			urlParser = document.createElement( 'a' );

			if ( null === previousQueryParams ) {
				urlParser.search = previousQueryString;
				previousQueryParams = api.utils.parseQueryString( previousQueryString );
				stateQueryParams.forEach( function( name ) {
					delete previousQueryParams[ name ];
				} );
			}

			urlParser.href = location.href;
			currentQueryParams = api.utils.parseQueryString( urlParser.search.substr( 1 ) );
			stateQueryParams.forEach( function( name ) {
				delete currentQueryParams[ name ];
			} );

			if ( previousPathName !== location.pathname || JSON.stringify( previousQueryParams ) !== JSON.stringify( currentQueryParams ) ) {
				urlParser.search = new URLSearchParams( currentQueryParams ).toString();
				urlParser.hash = '';
				api.settings.url.self = urlParser.href;
				api.preview.send( 'ready', {
					currentUrl: api.settings.url.self,
					activePanels: api.settings.activePanels,
					activeSections: api.settings.activeSections,
					activeControls: api.settings.activeControls,
					settingValidities: api.settings.settingValidities
				} );
			} else {
				api.preview.send( 'keep-alive' );
			}

			previousQueryParams = currentQueryParams;
			previousQueryString = location.search.substr( 1 );
			previousPathName = location.pathname;
		};
	} )();

	/**
	 * DOM ready
	 *
	 * @since CP-2.8.0
	 */
	document.addEventListener( 'DOMContentLoaded', function() {
		var handleUpdatedChangesetUuid, cssSettingId;

		api.settings = window._wpCustomizeSettings;
		if ( ! api.settings ) {
			return;
		}

		api.preview.channel = api.settings.channel;

		api.addLinkPreviewing();
		api.addFormPreviewing();
		api.addShortcutFocusing();

		if ( api.settings.channel ) {
			document.body.addEventListener( 'click', function( e ) {
				var link = e.target.closest( 'a' ),
					isInternalJumpLink;

				if ( ! link || ! link.hasAttribute( 'href' ) ) {
					return;
				}

				isInternalJumpLink = '#' === link.getAttribute( 'href' ).substr( 0, 1 );

				if ( isInternalJumpLink || ! /^https?:$/.test( link.protocol ) ) {
					return;
				}
				if ( ! api.isLinkPreviewable( link ) ) {
					wp.a11y.speak( api.settings.l10n.linkUnpreviewable );
					e.preventDefault();
					return;
				}

				e.preventDefault();

				if ( e.shiftKey ) {
					return;
				}

				api.preview.send( 'url', link.href );
			} );

			document.body.addEventListener( 'submit', function( e ) {
				var formParams,
					form = e.target,
					urlParser = document.createElement( 'a' );

				urlParser.href = form.action;

				if ( 'GET' !== form.method.toUpperCase() || ! api.isLinkPreviewable( urlParser ) ) {
					wp.a11y.speak( api.settings.l10n.formUnpreviewable );
					e.preventDefault();
					return;
				}

				if ( ! e.defaultPrevented ) {
					formParams = new URLSearchParams( new FormData( form ) ).toString();
					if ( formParams ) {
						urlParser.search += ( urlParser.search.length > 1 ? '&' : '?' ) + formParams;
					}
					api.preview.send( 'url', urlParser.href );
				}

				e.preventDefault();
			} );

			window.addEventListener( 'scroll', _.debounce( function() {
				api.preview.send( 'scroll', window.scrollY );
			}, 200 ) );

			api.preview.bind( 'scroll', function( distance ) {
				window.scrollTo( 0, distance );
			} );
		}

		function setValue( id, value, createDirty ) {
			var setting = api._settings[ id ];
			if ( setting ) {
				setting.set( value );
			} else {
				setting = api.create( id, value, { id: id } );
				if ( createDirty ) {
					setting._dirty = true;
				}
			}
		}

		api.preview.bind( 'settings', function( values ) {
			Object.keys( values ).forEach( function( id ) {
				setValue( id, values[ id ] );
			} );
		} );

		api.preview.trigger( 'settings', api.settings.values );

		( api.settings._dirty || [] ).forEach( function( id ) {
			if ( api._settings[ id ] ) {
				api._settings[ id ]._dirty = true;
			}
		} );

		api.preview.bind( 'setting', function( args ) {
			var sidebarId, menuId,
				found = false,
				id = args[0],
				value = args[1];

			setValue( id, value, true );

			if ( id.startsWith( 'sidebars_widgets[' ) ) {
				sidebarId = id.replace( 'sidebars_widgets[', '' ).replace( ']', '' );
				window._lastDirtySidebarId = sidebarId; // stored for use with newly-added widgets
				wp.customize.selectiveRefresh.partial.each( function( partial ) {
					if ( partial.id === sidebarId ) {
						partial.refresh();
					}
				} );
			} else if ( id.startsWith( 'widget[' ) ) {
				wp.customize.selectiveRefresh.partial.each( function( partial ) {
					if ( partial.id === id ) {
						found = true;
						partial.refresh();
					}
				} );

				if ( ! found && window._lastDirtySidebarId ) {
					// Refresh the sidebar that was just updated
					wp.customize.selectiveRefresh.partial.each( function( partial ) {
						if ( partial.id === window._lastDirtySidebarId ) {
							partial.refresh();
						}
					} );
				}
			} else if ( id.startsWith( 'nav_menu_item[' ) && value && value.nav_menu_term_id ) {
				menuId = String( value.nav_menu_term_id );
				wp.customize.selectiveRefresh.partial.each( function( partial ) {
					partial.placements().forEach( function( placement ) {
						if ( placement.context && String( placement.context.menu_id ) === menuId ) {
							partial.refresh();
						}
					} );
				} );
			} else {
				wp.customize.selectiveRefresh.partial.each( function( partial ) {
					if ( partial.params.settings && partial.params.settings.indexOf( id ) !== -1 ) {
						partial.refresh();
					}
				} );
			}
		} );

		api.preview.bind( 'sync', function( events ) {
			if ( events.settings && events['settings-modified-while-loading'] ) {
				Object.keys( events.settings ).forEach( function( syncedSettingId ) {
					if ( api._settings[ syncedSettingId ] && ! events['settings-modified-while-loading'][ syncedSettingId ] ) {
						delete events.settings[ syncedSettingId ];
					}
				} );
			}
			Object.keys( events ).forEach( function( event ) {
				api.preview.trigger( event, events[ event ] );
			} );
			api.preview.send( 'synced' );
		} );

		api.preview.bind( 'active', function() {
			api.preview.send( 'nonce', api.settings.nonce );
			api.preview.send( 'documentTitle', document.title );
			api.preview.send( 'scroll', window.scrollY );
		} );

		api.preview.bind( 'customized', function( data ) {
			api._customized = data;
			window.updatedControls = data;
		} );

		handleUpdatedChangesetUuid = function( uuid ) {
			api.settings.changeset.uuid = uuid;
			document.body.querySelectorAll( 'a[href], area[href]' ).forEach( function( el ) {
				api.prepareLinkPreview( el );
			} );
			document.body.querySelectorAll( 'form' ).forEach( function( form ) {
				api.prepareFormPreview( form );
			} );
			if ( history.replaceState ) {
				history.replaceState( currentHistoryState, '', location.href );
			}
		};

		api.preview.bind( 'changeset-uuid', handleUpdatedChangesetUuid );

		api.preview.bind( 'saved', function( response ) {
			if ( response.next_changeset_uuid ) {
				handleUpdatedChangesetUuid( response.next_changeset_uuid );
			}
		} );

		api.preview.bind( 'autosaving', function() {
			if ( api.settings.changeset.autosaved ) {
				return;
			}
			api.settings.changeset.autosaved = true;
			document.body.querySelectorAll( 'a[href], area[href]' ).forEach( function( el ) {
				api.prepareLinkPreview( el );
			} );
			document.body.querySelectorAll( 'form' ).forEach( function( form ) {
				api.prepareFormPreview( form );
			} );
			if ( history.replaceState ) {
				history.replaceState( currentHistoryState, '', location.href );
			}
		} );

		api.preview.bind( 'changeset-saved', function( data ) {
			Object.keys( data.saved_changeset_values ).forEach( function( settingId ) {
				var setting = api._settings[ settingId ];
				if ( setting && JSON.stringify( setting.get() ) === JSON.stringify( data.saved_changeset_values[ settingId ] ) ) {
					setting._dirty = false;
				}
			} );
		} );

		api.preview.bind( 'nonce-refresh', function( nonce ) {
			Object.assign( api.settings.nonce, nonce );
		} );

		// Background settings
		[ 'color', 'image', 'preset', 'position_x', 'position_y', 'size', 'repeat', 'attachment' ].forEach( function( prop ) {
			var setting = api._settings[ 'background_' + prop ];
			if ( setting ) {
				setting.bind( api.settingPreviewHandlers.background );
			}
		} );

		// Custom logo
		if ( api._settings.custom_logo ) {
			api.settingPreviewHandlers.custom_logo( api._settings.custom_logo.get() );
			api._settings.custom_logo.bind( api.settingPreviewHandlers.custom_logo );
		}

		// Custom CSS
		cssSettingId = 'custom_css[' + api.settings.theme.stylesheet + ']';
		if ( api._settings[ cssSettingId ] ) {
			api._settings[ cssSettingId ].bind( api.settingPreviewHandlers.custom_css );
		}

		// Core standard setting → DOM bindings
		var coreTextBindings = {
			'blogname':        '.site-title a, .site-title',
			'blogdescription': '.site-description'
		};
		Object.keys( coreTextBindings ).forEach( function( id ) {
			if ( ! api._settings[ id ] ) {
				api.create( id, '' );
			}
			api._settings[ id ].bind( function( value ) {
				document.querySelectorAll( coreTextBindings[ id ] ).forEach( function( el ) {
					el.textContent = value;
				} );
			} );
		} );

		// Header text color
		if ( ! api._settings.header_textcolor ) {
			api.create( 'header_textcolor', '' );
		}
		api._settings.header_textcolor.bind( function( value ) {
			var style = document.getElementById( 'customize-preview-header-textcolor' );
			if ( ! style ) {
				style = document.createElement( 'style' );
				style.id = 'customize-preview-header-textcolor';
				document.head.appendChild( style );
			}
			style.textContent = value === 'blank' ? 'body .site-title a, body .site-description { visibility: hidden; }' : 'body.has-header-image .site-title a, body.has-header-video .site-title a, body .site-title a, body .site-description { color: #' + value.replace( /^#/, '' ) + '; visibility: visible; }';
		} );

		api.preview.send( 'ready', {
			currentUrl: api.settings.url.self,
			activePanels: api.settings.activePanels,
			activeSections: api.settings.activeSections,
			activeControls: api.settings.activeControls,
			settingValidities: api.settings.settingValidities
		} );

		setInterval( api.keepAliveCurrentUrl, api.settings.timeouts.keepAliveSend );

		api.preview.bind( 'loading-initiated', function() {
			document.body.classList.add( 'wp-customizer-unloading' );
		} );

		api.preview.bind( 'loading-failed', function() {
			document.body.classList.remove( 'wp-customizer-unloading' );
		} );
	} );

	// Expose as wp.customize
	exports.customize = api;

} )( wp );
