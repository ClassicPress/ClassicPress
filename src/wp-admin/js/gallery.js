/**
 * @output wp-admin/js/gallery.js
 */

/* global Sortable, unescape, getUserSetting, setUserSetting, wpgallery, tinymce */

document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';
	var gallerySortable, gallerySortableInit, sortIt, clearAll, w, desc = false;

	gallerySortableInit = function() {
		gallerySortable = Sortable.create( document.getElementById( '#media-items' ), {
			group: 'items',
			sort: true,
			placeholder: 'sorthelper',
			handle: 'div.filename',
			dataIdAttr: 'data-id', // HTML attribute that is used by the `toArray()` method in OnEnd
			forceFallback: navigator.vendor.match(/apple/i) ? true : false, // forces fallback for all webkit browsers
			//forceFallback: 'GestureEvent' in window ? true : false, // forces fallback for Safari only
			fallbackTolerance: 2,

			// Element dropped
			onEnd: function() {
				// When an update has occurred, adjust the order for each item.
				var all = gallerySortable.toArray(), len = all.length;
				all.forEach( function( id, i ) {
					var order = desc ? ( len - i ) : ( 1 + i );
					document.getElementById( id + ' .menu_order input' ).value = order;
				} );
			}
		} );
	};

	sortIt = function() {
		var all = document.querySelectorAll( '.menu_order_input' ), len = all.length;
		all.forEach( function( id, i ) {
			var order = desc ? ( len - i ) : ( 1 + i );
			document.getElementById( id + ' .menu_order input' ).value = order;
		} );
	};

	clearAll = function( c ) {
		c = c || 0;
		document.querySelectorAll( '.menu_order_input' ).forEach( function( input ) {
			if ( input.value === '0' || c ) {
				input.value = '';
			}
		} );
	};

	document.getElementById( 'asc' ).addEventListener( 'click', function( e ) {
		e.preventDefault();
		desc = false;
		sortIt();
	} );
	document.getElementById( 'desc' ).addEventListener( 'click', function( e ) {
		e.preventDefault();
		desc = true;
		sortIt();
	} );
	document.getElementById( 'clear' ).addEventListener( 'click', function( e ) {
		e.preventDefault();
		clearAll(1);
	} );
	document.getElementById( 'showall' ).addEventListener( 'click', function( e ) {
		e.preventDefault();
		toggle( document.querySelector( '#sort-buttons span a' ) );
		document.querySelector( 'a.describe-toggle-on').style.display = 'none';
		document.querySelector( 'a.describe-toggle-off' ).style.display = '';
		document.querySelector( 'table.slidetoggle' ).style.display = '';
		document.querySelector( 'img.pinkynail' ).style.display = 'none';
	} );
	document.getElementById( 'hideall' ).addEventListener( 'click', function( e ) {
		e.preventDefault();
		toggle( document.querySelector( '#sort-buttons span a' ) );
		document.querySelector( 'a.describe-toggle-on' ).style.display = '';
		document.querySelector( 'a.describe-toggle-off' ).style.display = 'none';
		document.querySelector( 'table.slidetoggle' ).style.display = 'none';
		document.querySelector( 'img.pinkynail' ).style.display = '';
	} );

	// Initialize sortable.
	gallerySortableInit();
	clearAll();

	if ( document.querySelectorAll( '#media-items > *' ).length > 1 ) {
		w = wpgallery.getWin();

		document.getElementById( 'save-all' ).style.display = '';
		document.getElementById( 'gallery-settings' ).style.display = '';
		if ( typeof w.tinyMCE !== 'undefined' && w.tinyMCE.activeEditor && isVisible( w.tinyMCE.activeEditor ) ) {
			wpgallery.mcemode = true;
			wpgallery.init();
		} else {
			document.getElementById( 'insert-gallery' ).style.display = '';
		}
	}

	function toggle(el) {
		if ( el.style.display === 'none' ) {
			el.style.display = '';
		} else {
			el.style.display = 'none';
		}
	}

	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

} );

window.addEventListener( 'beforeunload', function () { window.tinymce = window.tinyMCE = window.wpgallery = null; } ); // Cleanup.

/* gallery settings */
window.tinymce = null;

window.wpgallery = {
	mcemode : false,
	editor : {},
	dom : {},
	is_update : false,
	el : {},

	I : function(e) {
		return document.getElementById(e);
	},

	init: function() {
		var t = this, li, q, i, it, w = t.getWin();

		if ( ! t.mcemode ) {
			return;
		}

		li = ('' + document.location.search).replace(/^\?/, '').split('&');
		q = {};
		for (i=0; i<li.length; i++) {
			it = li[i].split('=');
			q[unescape(it[0])] = unescape(it[1]);
		}

		if ( q.mce_rdomain ) {
			document.domain = q.mce_rdomain;
		}

		// Find window & API.
		window.tinymce = w.tinymce;
		window.tinyMCE = w.tinyMCE;
		t.editor = tinymce.EditorManager.activeEditor;

		t.setup();
	},

	getWin : function() {
		return window.dialogArguments || opener || parent || top;
	},

	setup : function() {
		var t = this, a, ed = t.editor, g, columns, link, order, orderby;
		if ( ! t.mcemode ) {
			return;
		}

		t.el = ed.selection.getNode();

		if ( t.el.nodeName !== 'IMG' || ! ed.dom.hasClass(t.el, 'wpGallery') ) {
			if ( ( g = ed.dom.select('img.wpGallery') ) && g[0] ) {
				t.el = g[0];
			} else {
				if ( getUserSetting('galfile') === '1' ) {
					t.I('linkto-file').checked = 'checked';
				}
				if ( getUserSetting('galdesc') === '1' ) {
					t.I('order-desc').checked = 'checked';
				}
				if ( getUserSetting('galcols') ) {
					t.I('columns').value = getUserSetting('galcols');
				}
				if ( getUserSetting('galord') ) {
					t.I('orderby').value = getUserSetting('galord');
				}
				jQuery('#insert-gallery').show();
				return;
			}
		}

		a = ed.dom.getAttrib(t.el, 'title');
		a = ed.dom.decode(a);

		if ( a ) {
			jQuery('#update-gallery').show();
			t.is_update = true;

			columns = a.match(/columns=['"]([0-9]+)['"]/);
			link = a.match(/link=['"]([^'"]+)['"]/i);
			order = a.match(/order=['"]([^'"]+)['"]/i);
			orderby = a.match(/orderby=['"]([^'"]+)['"]/i);

			if ( link && link[1] ) {
				t.I('linkto-file').checked = 'checked';
			}
			if ( order && order[1] ) {
				t.I('order-desc').checked = 'checked';
			}
			if ( columns && columns[1] ) {
				t.I('columns').value = '' + columns[1];
			}
			if ( orderby && orderby[1] ) {
				t.I('orderby').value = orderby[1];
			}
		} else {
			jQuery('#insert-gallery').show();
		}
	},

	update : function() {
		var t = this, ed = t.editor, all = '', s;

		if ( ! t.mcemode || ! t.is_update ) {
			s = '[gallery' + t.getSettings() + ']';
			t.getWin().send_to_editor(s);
			return;
		}

		if ( t.el.nodeName !== 'IMG' ) {
			return;
		}

		all = ed.dom.decode( ed.dom.getAttrib( t.el, 'title' ) );
		all = all.replace(/\s*(order|link|columns|orderby)=['"]([^'"]+)['"]/gi, '');
		all += t.getSettings();

		ed.dom.setAttrib(t.el, 'title', all);
		t.getWin().tb_remove();
	},

	getSettings : function() {
		var I = this.I, s = '';

		if ( I('linkto-file').checked ) {
			s += ' link="file"';
			setUserSetting('galfile', '1');
		}

		if ( I('order-desc').checked ) {
			s += ' order="DESC"';
			setUserSetting('galdesc', '1');
		}

		if ( I('columns').value !== 3 ) {
			s += ' columns="' + I('columns').value + '"';
			setUserSetting('galcols', I('columns').value);
		}

		if ( I('orderby').value !== 'menu_order' ) {
			s += ' orderby="' + I('orderby').value + '"';
			setUserSetting('galord', I('orderby').value);
		}

		return s;
	}
};
