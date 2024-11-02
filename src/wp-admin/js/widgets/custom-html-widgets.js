/**
 * @output wp-admin/js/widgets/custom-html-widgets.js
 */

/* global wp */
document.addEventListener( 'DOMContentLoaded', function() {
	var codeMirrorInstances = {},
		widgetContainerWraps = document.querySelectorAll( '.widgets-holder-wrap:not(#available-widgets)' );

	function initCodeMirror( textarea ) {
		if ( textarea && wp.codeEditor ) {

			// Check if CodeMirror is already initialized for this textarea
			if ( codeMirrorInstances[textarea.id] ) {
				codeMirrorInstances[textarea.id].codemirror.toTextArea(); // Remove existing instance
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
		}
	}

	function handleWidgetUpdate( event ) {
		var widget = event.detail.widget;
		if ( widget.querySelector( '.id_base' ).value === 'custom_html' ) {
			initCodeMirror( widget.querySelector( 'textarea' ) );
		}
	}

	// Trigger 'widget-added' event on page load
	widgetContainerWraps.forEach( function( wrap ) {
		wrap.querySelectorAll( 'input.id_base[value="custom_html"]' ).forEach( function( input ) {
			input.closest( 'details' ).addEventListener( 'toggle', function() {
				document.dispatchEvent( new CustomEvent( 'widget-added', {
					detail: { widget: input.closest( '.widget' ) }
				} ) );
			}, { once: true } );
		} );
	} );

	// Listen for when widgets are added, synced, or updated
	document.addEventListener( 'widget-added', handleWidgetUpdate );
	document.addEventListener( 'widget-synced', handleWidgetUpdate );
	document.addEventListener( 'widget-updated', handleWidgetUpdate );
} );
