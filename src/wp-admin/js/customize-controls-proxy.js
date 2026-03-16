/**
 * @output wp-admin/js/customize-controls-proxy.js
 *
 * Runs in the CONTROLS frame.
 *
 * @since CP-2.8.0
 */
/* global wp, updatedControls, _updatedControlsWatcher */

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
				};
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
		api.panelConstructor   = {};
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
window._cpPreviewSettings = {}; // converted keys, for partial refresh

window.sendSettingToPreview = function( id, value ) {
    var iframe, src, match;

    if ( window._customizePublishing ) {
		return;
	}

    iframe = document.querySelector( '#customize-preview iframe' );
    if ( ! iframe || ! iframe.contentWindow ) {
		return;
	}

    src = iframe.src || '';
    match = src.match( /customize_messenger_channel=([^&]+)/ );
    if ( ! match ) {
		return;
	}

    iframe.contentWindow.postMessage(
        JSON.stringify( {
            channel: match[1],
            type: 'setting',
            data: [ id, value ]
        } ),
        location.origin
    );
};

window.sendCustomizedToPreview = function() {
    var iframe, src, match;

    if ( window._customizePublishing ) {
		return;
	}

    iframe = document.querySelector( '#customize-preview iframe' );
    if ( ! iframe || ! iframe.contentWindow ) {
		return;
	}

    src = iframe.src || '';
    match = src.match( /customize_messenger_channel=([^&]+)/ );
    if ( ! match ) {
		return;
	}

    // Merge both formats:
    // _cpPreviewSettings: Customizer JS format (widget[id_base][n]) for preview-side setValue()
    // _cpDirtySettings:   PHP format (widget_id_base[n]) for server-side register_settings()
    merged = Object.assign( {}, window._cpPreviewSettings, window._cpDirtySettings );

    iframe.contentWindow.postMessage(
        JSON.stringify( {
            channel: match[1],
            type: 'customized',
            data: merged
        } ),
        location.origin
    );
};

window.updatedControls = window.updatedControls || {};

window._updatedControlsWatcher = new Proxy( window.updatedControls, {
    set: function( target, key, value ) {
		var previewKey, val;

        target[ key ] = value;
        window._cpDirtySettings[ key ] = value;
		previewKey = window.toPreviewSettingId( key );
		window._cpPreviewSettings[ previewKey ] = value;

		// Keep api.instance(previewKey).get() from throwing/being undefined
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
			(function( pk, k ) {
				window._previewSenders[ pk ] = _.debounce( function() {
					window.sendSettingToPreview( pk, target[ k ] );
				}, 300 );
			})( previewKey, key );
		}
		window._previewSenders[ previewKey ]();

        if ( ! window._customizedSender ) {
			window._customizedSender = _.debounce( function() {
				window.sendCustomizedToPreview();
			}, 350 ); // slightly longer than _previewSenders debounce of 300
		}
		window._customizedSender();

        return true;
    }
} );
window.updatedControls = window._updatedControlsWatcher;
