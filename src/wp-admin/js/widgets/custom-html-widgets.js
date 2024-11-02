/**
 * @output wp-admin/js/widgets/custom-html-widgets.js
 */

/* global wp */
document.addEventListener( 'DOMContentLoaded', function() {
	var codeMirrorInstances = {};

	function initCodeMirror( textarea ) {
		if ( textarea && wp.codeEditor ) {

			// Check if CodeMirror is already initialized for this textarea
			if ( codeMirrorInstances[textarea.id] ) {
				codeMirrorInstances[textarea.id].toTextArea(); // Remove existing instance
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

			// Hide the original textarea
			textarea.style.display = 'none';
		}
	}

	function handleWidgetUpdate( event ) {
		var widget = event.detail.widget;
		if ( widget.querySelector( '.id_base' ).value === 'custom_html' ) {
			initCodeMirror( widget.querySelector( 'textarea' ) );
		}
	}

	// Listen for when widgets are added or updated
	document.addEventListener( 'widget-updated', handleWidgetUpdate );
	document.addEventListener( 'widget-added', handleWidgetUpdate );
} );
