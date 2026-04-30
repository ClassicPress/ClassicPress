/**
 * @output wp-admin/js/customize-controls-proxy.js
 *
 * Runs in the CONTROLS frame.
 *
 * @since CP-2.8.0
 */
/* global wp */

/* CONTROLS-frame compat shim: keep the pane stable if 3rd-party code runs early. */
(function() {

	// Ensure wp.customize exists
	if ( ! window.wp ) {
		window.wp = {};
	}
	if ( ! wp.customize ) {
		wp.customize = {};
	}

	// No-op bind so early code like api.bind(...) doesn't fail.
	var api = wp.customize;
	if ( typeof api.bind !== 'function' ) {
		api.bind = function() {}; // no-op
	}

	// Minimal Backbone-like base so Control/Section/Panel.extend(...) is safe
	function makeExtendableBase() {
		function Base() {}
		Base.extend = function( proto ) {
			function Ctor() {
				if ( typeof this.initialize === 'function' ) {
					this.initialize.apply( this, arguments );
				}
			}
			Ctor.prototype = Object.assign( {}, proto || {} );
			Ctor.extend = Base.extend; // allow chaining
			return Ctor;
		};
		return Base;
	}

	// Define only if missing; the real classes will exist once customize-controls.js loads.
	if ( typeof wp.customize.Control === 'undefined' ) {
		wp.customize.Control = makeExtendableBase();
	}
	if ( typeof wp.customize.Section === 'undefined' ) {
		wp.customize.Section = makeExtendableBase();
	}
	if ( typeof wp.customize.Panel === 'undefined' ) {
		wp.customize.Panel = makeExtendableBase();
	}

	// Ensure api.instance('id').get() won't throw errors.
	if ( typeof api.Value === 'undefined' ) {
		api.Value = function Value( initial ) {
			var val = initial, subs = [];
			this.get = function() {
				return val;
			};
			this.set = function( v ) {
				val = v;
				for ( var i = 0, n = subs.length; i < n; i++ ) {
					try {
						subs[i]( v );
					} catch(_) {}
				}
			};
			this.bind = function( fn ) {
				if ( typeof fn === 'function' ) {
					subs.push( fn );
				}
			};
		};
	}

	if ( ! api._values ) {
		api._values = Object.create( null );
	}

	if ( typeof api.instance !== 'function' ) {
		api.instance = function( id ) {
			if ( id && Object.prototype.hasOwnProperty.call( api._values, id ) ) {
				return api._values[id];
			}
			if ( typeof id === 'string' && id ) {
				return ( api._values[id] = new api.Value( undefined ) );
			}
			return undefined;
		};
	}
	if ( typeof api.add !== 'function' ) {
		api.add = function( id, value ) {
			var v = ( value instanceof api.Value ) ? value : new api.Value( value );
			api._values[id] = v;
			return v;
		};
	}
	if ( typeof api.has !== 'function' ) {
		api.has = function( id ) {
			return Object.prototype.hasOwnProperty.call( api._values, id );
		};
	}
	if ( typeof api.each !== 'function' ) {
		api.each = function( cb ) {
			Object.keys( api._values ).forEach( function( k ) {
				cb( api._values[k] );
			} );
		};
	}

	// Ensure the usual registries/collections exist (themes touch these early).
	function makeValuesCollection() {
		var map = Object.create( null );
		return {
			_map: map,
			instance: function( id ) {
				return map[id];
			},
			add: function( id, obj ) {
				map[id] = obj;
				return obj;
			},
			has: function( id ) {
				return Object.prototype.hasOwnProperty.call( map, id );
			},
			each: function( cb ) {
				Object.keys( map ).forEach( function( k ) {
					cb( map[k] );
				} );
			}
		};
	}

	if ( ! api.controlConstructor ) {
		api.controlConstructor = {};
	}
	if ( ! api.sectionConstructor ) {
		api.sectionConstructor = {};
	}
	if ( ! api.panelConstructor ) {
		api.panelConstructor = {};
	}

	if ( typeof api.control === 'undefined' || typeof api.control.instance !== 'function' ) {
		api.control = makeValuesCollection();
	}
	if ( typeof api.section === 'undefined' || typeof api.section.instance !== 'function' ) {
		api.section = makeValuesCollection();
	}
	if ( typeof api.panel === 'undefined'   || typeof api.panel.instance   !== 'function' ) {
		api.panel = makeValuesCollection();
	}

	// Suppress uncaught errors from non-core files.
	(function() {
		var seen = new Set();
		function isCoreFile( src ) {
			return /\/wp-admin\/js\/customize-controls(\.min)?\.js$/.test( src || '' ) ||
			 /customize-controls-proxy\.js$/.test( src || '' );
		}
		window.addEventListener( 'error', function( e ) {
			var src = ( e && e.filename ) || '';
			if ( ! isCoreFile( src ) ) {
				var key = ( src || 'inline' ) + ':' + ( e && e.lineno );
				if ( ! seen.has( key ) ) {
					seen.add( key );
				}
				e.preventDefault();
			}
		} );
		window.addEventListener( 'unhandledrejection', function( e ) {
			e.preventDefault();
		} );
	} )();
} )(); // End of shim

/**
 * Converts PHP changeset widget key to preview-side wp.customize setting ID.
 * widget_calendar[5]  →  widget[calendar-5]
 * widget_text[2]      →  widget[text-2]
 * Non-widget keys are returned unchanged.
 */
window.toPreviewSettingId = function( key ) {
	var m = key.match( /^widget_([^\[]+)\[(\d+)\]$/ );
	if ( m ) {
		return 'widget[' + m[1] + '-' + m[2] + ']';
	}
	return key;
};

// Send data to iframe
window._previewSenders = {};
window._customizePublishing = false;
window._cpDirtySettings = {}; // raw keys, for publishing
window._cpPreviewSettings = {}; // converted keys, for partial refresh AJAX

/**
 * Gets the customize messenger channel from the preview iframe src.
 * Returns null if the iframe or channel is not available.
 */
window.getPreviewChannel = function() {
	var src, match,
		iframe = document.querySelector( '#customize-preview iframe' );

	if ( ! iframe || ! iframe.contentWindow ) {
		return null;
	}
	src = iframe.src || '';
	match = src.match( /customize_messenger_channel=([^&]+)/ );
	return match ? { iframe: iframe, channel: match[1] } : null;
};

window.sendSettingToPreview = function( id, value ) {
	var target;

	if ( window._customizePublishing ) {
		return;
	}

	target = window.getPreviewChannel();
	if ( ! target ) {
		return;
	}

	target.iframe.contentWindow.postMessage(
		JSON.stringify( {
			channel: target.channel,
			type: 'setting',
			data: [ id, value ]
		} ),
		location.origin
	);
};

window.sendCustomizedToPreview = function() {
	var target, merged;

	if ( window._customizePublishing ) {
		return;
	}

	target = window.getPreviewChannel();
	if ( ! target ) {
		return;
	}

	// Merge both key formats into a single customized blob:
	// _cpPreviewSettings uses the JS format (widget[id_base-n]) for preview-side setValue()
	// _cpDirtySettings uses the PHP format (widget_id_base[n]) for server-side register_settings()
	merged = Object.assign( {}, window._cpPreviewSettings, window._cpDirtySettings );

	target.iframe.contentWindow.postMessage(
		JSON.stringify( {
			channel: target.channel,
			type: 'customized',
			data: merged
		} ),
		location.origin
	);
};

window.updatedControls = window.updatedControls || {};

window._updatedControlsWatcher = new Proxy( window.updatedControls, {
	set: function( target, key, value ) {
		var val,
			previewKey = window.toPreviewSettingId( key );

		target[ key ] = value;
		window._cpDirtySettings[ key ] = value;
		window._cpPreviewSettings[ previewKey ] = value;

		// Keep api.instance(previewKey).get() from throwing or being undefined
		if ( window.wp && wp.customize ) {
			// Create/update a Value so early reader code can call .get()
			if ( typeof wp.customize.add === 'function' ) {
				wp.customize.add( previewKey, value );
			} else if ( typeof wp.customize.instance === 'function' ) {
				val = wp.customize.instance( previewKey );
				if ( val && typeof val.set === 'function' ) {
					val.set( value );
				}
			}
		}

		// Send data to iframe pane
		if ( ! window._previewSenders[ previewKey ] ) {
			window._previewSenders[ previewKey ] = makeDebouncedSender( previewKey, key, target );
		}
		window._previewSenders[ previewKey ]();

		// Send the full customized blob slightly after the individual setting,
		// ensuring _cpPreviewSettings is fully populated before the AJAX fires.
		if ( ! window._customizedSender ) {
			window._customizedSender = _.debounce( window.sendCustomizedToPreview, 350 );
		}
		window._customizedSender();

		return true;
	}
} );
window.updatedControls = window._updatedControlsWatcher;

/**
 * Creates a debounced function that sends a single setting to the preview.
 * Captures previewKey, rawKey and target at creation time to avoid closure issues.
 */
function makeDebouncedSender( previewKey, rawKey, target ) {
	return _.debounce( function() {
		window.sendSettingToPreview( previewKey, target[ rawKey ] );
	}, 300 );
}

/**
 * Receive postMessages from the preview iframe (apart from menu item updates, for which see below).
 */
window.addEventListener( 'message', function( event ) {
	var message, target, data, id, type, place, menuId, hash, itemId, sidebarId;

	if ( event.origin !== location.origin ) {
		return;
	}
	try {
		message = JSON.parse( event.data );
	} catch( e ) {
		return;
	}
	if ( ! message || ! message.type || message.type !== 'focus-partial' ) {
		return;
	}

	// Verify it came from our preview channel
	target = window.getPreviewChannel();
	if ( target && message.channel && message.channel !== target.channel ) {
		return;
	}

	data = message.data;
	if ( ! data ) {
		return;
	}

	id        = data.id;
	type      = data.type || null;
	place     = data.place || null;
	menuId    = data.menuId || null;
	sidebarId = data.sidebarId || null;

	if ( type === 'nav_menu_instance' ) {
		if ( menuId ) {
			hash = 'sub-accordion-section-nav_menu[' + menuId + ']';
		} else {
			hash = 'sub-accordion-panel-nav_menus';
		}
	} else if ( type === 'widget' ) {
		if ( sidebarId ) {
			hash = 'sub-accordion-section-sidebar-widgets-' + sidebarId;
		} else {
			hash = 'sub-accordion-panel-widgets';
		}
	} else {
		if ( id.startsWith( 'sidebar-' ) ) {
			hash = 'sub-accordion-section-sidebar-widgets-' + id;
		} else {
			itemId = 'customize-control-' + id;
			if ( document.getElementById( itemId ) ) {
				hash = document.getElementById( itemId ).parentNode.id;
			} else {
				return;
			}
		}
	}

	location.hash = '#' + hash;
	[...document.getElementById( 'customize-theme-controls' ).children].forEach( function( child ) {
		child.style.display = 'none';
	} );
	document.getElementById( hash ).style.display = 'block';
	setTimeout( function() {
		document.getElementById( hash ).querySelector( 'button' ).focus();
	}, 0 );
} );

/**
 * Receive postMessages from the preview iframe about menu item updates
 */
window.addEventListener( 'message', function( event ) {
	var message, target, url;

	if ( event.origin !== location.origin ) {
		return;
	}
	try {
		message = JSON.parse( event.data );
	} catch( e ) {
		return;
	}
	if ( ! message || message.type !== 'refresh' ) {
		return;
	}

	target = window.getPreviewChannel();
	if ( ! target ) {
		return;
	}
	if ( message.channel && message.channel !== target.channel ) {
		return;
	}

	url = new URL( target.iframe.src );
	url.searchParams.set( 'customized', JSON.stringify( window._cpDirtySettings ) );

	target.iframe.src = '';
	target.iframe.src = url.toString();
} );

/**
 * Re-apply dirty settings after navigation
 */
window.addEventListener( 'message', function( event ) {
    var message, target;

    if ( event.origin !== location.origin ) {
        return;
    }
    try {
        message = JSON.parse( event.data );
    } catch( e ) {
        return;
    }
    if ( ! message || message.type !== 'ready' ) {
        return;
    }
    if ( ! message.data || ! message.data.currentUrl ) {
        return;
    }

    target = window.getPreviewChannel();
    if ( ! target ) {
        return;
    }

    window.sendCustomizedToPreview();

    target.iframe.contentWindow.postMessage(
        JSON.stringify( {
            channel: target.channel,
            type: 'settings',
            data: window._cpPreviewSettings
        } ),
        location.origin
    );

	setTimeout( function() {
		target.iframe.style.visibility = 'visible';
	}, 0 );
} );

