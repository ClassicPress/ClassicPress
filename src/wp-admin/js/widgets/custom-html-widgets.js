/**
 * @output wp-admin/js/widgets/custom-html-widgets.js
 */

/* global wp */
document.addEventListener( 'DOMContentLoaded', function() {
	var codeMirrorInstances = {};

	function initCodeMirror( textarea ) {
		if ( textarea && wp.codeEditor ) {

			// Within the Customizer only, check if CodeMirror is already initialized for this textarea
			if ( document.body.className.includes( 'wp-customizer' ) ) {
				if ( codeMirrorInstances[textarea.id] !== undefined ) {
					return;
				}
			}

			var editor = wp.codeEditor.initialize( textarea, {
				codemirror: {
					mode: 'htmlmixed',
					lineNumbers: true,
					lineWrapping: true,
					indentUnit: 2,
					tabSize: 2,
					autoCloseTags: true,
					autoCloseBrackets: true,
					matchBrackets: true,
					foldGutter: true,
					gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
				}
			} );

			codeMirrorInstances[textarea.id] = editor.codemirror;

			editor.codemirror.on( 'change', function( cm ) {
				textarea.value = cm.getValue();
				textarea.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );

			// Specify explicit values for when a new widget is added in the Customizer
			textarea.parentNode.querySelector( '.CodeMirror-sizer' ).style.marginLeft = '39px';
			textarea.parentNode.querySelector( '.CodeMirror-gutter.CodeMirror-linenumbers' ).style.width = '29px';
		}
	}

	function handleWidgetUpdate( event ) {
		var widget = event.detail.widget;
		if ( widget.querySelector( '.id_base' ).value === 'custom_html' ) {
			initCodeMirror( widget.querySelector( 'textarea' ) );
		}
	}

	// Listen for when widgets are added, synced, or updated
	document.addEventListener( 'widget-added', handleWidgetUpdate );
	document.addEventListener( 'widget-synced', handleWidgetUpdate );
	document.addEventListener( 'widget-updated', handleWidgetUpdate );

	// Ensure Code Mirror loads on page load
	document.querySelectorAll( '#widgets-right .id_base' ).forEach( function( base ) {
		if ( base.value === 'custom_html' ) {
			initCodeMirror( base.closest ( '.widget' ).querySelector( 'textarea' ) );
		}
	} );
} );
