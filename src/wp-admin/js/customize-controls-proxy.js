/**
 * @output wp-admin/js/customize-controls-proxy.js
 *
 * @since CP-2.8.0
 */

/* global wp, updatedControls, _updatedControlsWatcher
 */
window.updatedControls = window.updatedControls || {};

if ( ! wp || ! wp.customize || ! wp.customize.bind ) {
	var wp = window.wp = window.wp || {};
	wp.customize = {
		control: {
			bind: function() {}
		},
		Menus: {
			MenuItemControl: function() {}
		}
	};
	wp.customize.bind = function() {};
}

window._previewSenders = {};

window._customizePublishing = false;

window.sendSettingToPreview = function( id, value ) {
	if ( window._customizePublishing ) {
		return;
	}
	var iframe = document.querySelector( '#customize-preview iframe' );
	if ( ! iframe || ! iframe.contentWindow ) {
		return;
	}
	var src = iframe.src || '';
	var match = src.match( /customize_messenger_channel=([^&]+)/ );
	if ( ! match ) {
		return;
	}
	var channel = match[1];
	iframe.contentWindow.postMessage(
		JSON.stringify( {
			channel: channel,
			type: 'setting',
			data: [ id, value ]
		} ),
		location.origin
	);
};

window._updatedControlsWatcher = new Proxy( window.updatedControls, {
	set: function( target, key, value ) {
		target[ key ] = value;

		if ( ! window._previewSenders[ key ] ) {
			window._previewSenders[ key ] = _.debounce( function() {
				window.sendSettingToPreview( key, target[ key ] );
			}, 300 );
		}

		window._previewSenders[ key ]();

		return true;
	}
} );
