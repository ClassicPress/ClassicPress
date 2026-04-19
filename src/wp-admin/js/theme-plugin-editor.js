/**
 * @output wp-admin/js/theme-plugin-editor.js
 */

/* eslint no-magic-numbers: ["error", { "ignore": [-1, 0, 1] }] */

if ( ! window.wp ) {
	window.wp = {};
}

wp.themePluginEditor = ( function() {
	'use strict';

	var component, TreeLinks,
		__ = wp.i18n.__, _n = wp.i18n._n, sprintf = wp.i18n.sprintf;

	component = {
		codeEditor: {},
		instance: null,
		noticeElements: {},
		dirty: false,
		lintErrors: []
	};

	/**
	 * Initialize component.
	 *
	 * @since 4.9.0
	 *
	 * @param {HTMLElement} form     - Form element.
	 * @param {Object}      settings - Settings.
	 * @param {Object|boolean} settings.codeEditor - Code editor settings (or `false` if syntax highlighting is disabled).
	 * @return {void}
	 */
	component.init = function init( form, settings ) {
		// Unwrap a jQuery object if one is passed, for back-compatibility.
		component.form = ( form && form.jquery ) ? form[0] : form;

		if ( settings ) {
			Object.assign( component, settings );
		}

		component.noticesContainer = component.form.querySelector( '.editor-notices' );
		component.submitButton     = component.form.querySelector( ':is(input,button)[name="submit"]' );
		component.spinner          = component.form.querySelector( '.submit .spinner' );
		component.textarea         = component.form.querySelector( '#newcontent' );
		component.warning          = document.querySelector( '.file-editor-warning' );
		component.docsLookUpButton = component.form.querySelector( '#docs-lookup' );
		component.docsLookUpList   = component.form.querySelector( '#docs-list' );

		if ( component.docsLookUpList && component.docsLookUpButton ) {
			component.docsLookUpList.addEventListener( 'change', function() {
				component.docsLookUpButton.disabled = ( '' === component.docsLookUpList.value );
			} );
		}

		component.form.addEventListener( 'submit', component.submit );
		component.textarea.addEventListener( 'change', component.onChange );

		if ( component.warning ) {
			component.showWarning();
		}

		if ( false !== component.codeEditor ) {
			/*
			 * Defer adding notices until after DOM ready as workaround for WP Admin injecting
			 * its own managed dismiss buttons and also to prevent the editor from showing a notice
			 * when the file had linting errors to begin with.
			 */
			setTimeout( function() {
				component.initCodeEditor();
			}, 0 );
		}

		document.addEventListener( 'DOMContentLoaded', component.initFileBrowser );

		window.addEventListener( 'beforeunload', function( event ) {
			if ( component.dirty ) {
				// Modern browsers ignore custom messages but still show a generic prompt
				// when returnValue is set.
				event.preventDefault();
				event.returnValue = '';
			}
		} );

		component.docsLookUpList?.addEventListener( 'change', function() {
			component.docsLookUpButton.disabled = ( '' === component.docsLookUpList.value );
		} );
	};

	/**
	 * Set up and display the warning modal.
	 *
	 * @since 4.9.0
	 * @return {void}
	 */
	component.showWarning = function() {
		var oneSecond = 1000,
			rawMessage = component.warning.querySelector( '.file-editor-warning-message' ).textContent;

		document.getElementById( 'wpwrap' ).setAttribute( 'aria-hidden', 'true' );

		document.body.classList.add( 'modal-open' );
		document.body.appendChild( component.warning );

		component.warning.classList.remove( 'hidden' );
		component.warning.querySelector( '.file-editor-warning-go-back' ).focus();

		component.warningTabbables = Array.from( component.warning.querySelectorAll( 'a, button' ) );

		component.warningTabbables.forEach( function( el ) {
			el.addEventListener( 'keydown', component.constrainTabbing );
		} );

		component.warning.addEventListener( 'click', function( event ) {
			if ( event.target.closest( '.file-editor-warning-dismiss' ) ) {
				component.dismissWarning();
			}
		} );

		setTimeout( function() {
			wp.a11y.speak( wp.sanitize.stripTags( rawMessage.replace( /\s+/g, ' ' ) ), 'assertive' );
		}, oneSecond );
	};

	/**
	 * Constrain tabbing within the warning modal.
	 *
	 * @since 4.9.0
	 * @param {KeyboardEvent} event
	 * @return {void}
	 */
	component.constrainTabbing = function( event ) {
		if ( 'Tab' !== event.key ) {
			return;
		}

		var firstTabbable = component.warningTabbables[0],
			lastTabbable = component.warningTabbables[ component.warningTabbables.length - 1 ];

		if ( lastTabbable === event.target && ! event.shiftKey ) {
			firstTabbable.focus();
			event.preventDefault();
		} else if ( firstTabbable === event.target && event.shiftKey ) {
			lastTabbable.focus();
			event.preventDefault();
		}
	};

	/**
	 * Dismiss the warning modal.
	 *
	 * @since 4.9.0
	 * @return {void}
	 */
	component.dismissWarning = function() {
		wp.ajax.post( 'dismiss-wp-pointer', {
			pointer: component.themeOrPlugin + '_editor_notice'
		} );

		component.warning.remove();
		document.getElementById( 'wpwrap' ).removeAttribute( 'aria-hidden' );
		document.body.classList.remove( 'modal-open' );
	};

	/**
	 * Callback for when a change happens.
	 *
	 * @since 4.9.0
	 * @return {void}
	 */
	component.onChange = function() {
		component.dirty = true;
		component.removeNotice( 'file_saved' );
	};

	/**
	 * Submit file via Ajax.
	 *
	 * @since 4.9.0
	 * @param {Event} event
	 * @return {void}
	 */
	component.submit = function( event ) {
		var request,
			data = {},
			fields = new FormData( component.form );

		event.preventDefault();

		fields.forEach( function( value, name ) {
			data[ name ] = value;
		} );

		// Use value from codemirror if present.
		if ( component.instance ) {
			data.newcontent = component.instance.codemirror.getValue();
		}

		if ( component.isSaving ) {
			return;
		}

		// Scroll to the line that has the error.
		if ( component.lintErrors.length ) {
			component.instance.codemirror.setCursor( component.lintErrors[ 0 ].from.line );
			return;
		}

		component.isSaving          = true;
		component.textarea.readOnly = true;

		if ( component.instance ) {
			component.instance.codemirror.setOption( 'readOnly', true );
		}

		component.spinner.classList.add( 'is-active' );

		// Remove previous save notice before saving.
		if ( component.lastSaveNoticeCode ) {
			component.removeNotice( component.lastSaveNoticeCode );
		}

		request = wp.ajax.post( 'edit-theme-plugin-file', data );

		request.done( function( response ) {
			component.lastSaveNoticeCode = 'file_saved';
			component.addNotice( {
				code: component.lastSaveNoticeCode,
				type: 'success',
				message: response.message,
				dismissible: true
			} );
			component.dirty = false;
		} );

		request.fail( function( response ) {
			var notice = Object.assign(
				{
					code: 'save_error',
					message: __( 'Something went wrong. Your change may not have been saved. Please try again. There is also a chance that you may need to manually fix and upload the file over FTP.' )
				},
				response,
				{
					type: 'error',
					dismissible: true
				}
			);
			component.lastSaveNoticeCode = notice.code;
			component.addNotice( notice );
		} );

		request.always( function() {
			component.spinner.classList.remove( 'is-active' );
			component.isSaving          = false;
			component.textarea.readOnly = false;

			if ( component.instance ) {
				component.instance.codemirror.setOption( 'readOnly', false );
			}
		} );
	};

	/**
	 * Add notice.
	 *
	 * @since 4.9.0
	 *
	 * @param {Object}   notice
	 * @param {string}   notice.code
	 * @param {string}   notice.type
	 * @param {string}   notice.message
	 * @param {boolean}  [notice.dismissible=false]
	 * @param {Function} [notice.onDismiss]
	 * @return {HTMLElement} Notice element.
	 */
	component.addNotice = function( notice ) {
		var noticeElement, dismissBtn, fullHeight;
		if ( ! notice.code ) {
			throw new Error( 'Missing code.' );
		}

		// Only one notice of a given code at a time.
		component.removeNotice( notice.code );

		noticeElement = renderFileEditorNotice( notice );
		noticeElement.style.display = 'none';

		dismissBtn = noticeElement.querySelector( '.notice-dismiss' );
		if ( dismissBtn ) {
			dismissBtn.addEventListener( 'click', function() {
				component.removeNotice( notice.code );
				if ( notice.onDismiss ) {
					notice.onDismiss( notice );
				}
			} );
		}

		wp.a11y.speak( notice.message );

		component.noticesContainer.appendChild( noticeElement );

		// Measure height before animating.
		noticeElement.style.display = '';
		fullHeight = noticeElement.offsetHeight;
		noticeElement.style.display = 'none';

		// Slide down.
		noticeElement.style.display = '';
		noticeElement.style.overflow = 'hidden';
		noticeElement.animate(
			[
				{ height: '0px' },
				{ height: fullHeight + 'px' }
			],
			{ duration: 200, easing: 'ease' }
		).finished.then( function() {
			noticeElement.style.overflow = '';
			noticeElement.style.height   = '';
		} );

		component.noticeElements[ notice.code ] = noticeElement;
		return noticeElement;
	};

	/**
	 * Remove notice.
	 *
	 * @since 4.9.0
	 *
	 * @param {string} code
	 * @return {boolean} Whether a notice was removed.
	 */
	component.removeNotice = function( code ) {
		var fullHeight,
			noticeElement = component.noticeElements[ code ];

		if ( ! noticeElement ) {
			return false;
		}

		// slideUp
		fullHeight = noticeElement.offsetHeight;
		noticeElement.style.overflow = 'hidden';
		noticeElement.animate(
			[
				{ height: fullHeight + 'px' },
				{ height: '0px' }
			],
			{ duration: 200, easing: 'ease' }
		).finished.then( function() {
			noticeElement.remove();
		} );

		delete component.noticeElements[ code ];
		return true;
	};

	/**
	 * Initialize code editor.
	 *
	 * @since 4.9.0
	 * @return {void}
	 */
	component.initCodeEditor = function initCodeEditor() {
		var editor, lineDiv,
			codeEditorSettings = Object.assign( {}, component.codeEditor );

		/**
		 * Handle tabbing to the field before the editor.
		 *
		 * @since 4.9.0
		 * @return {void}
		 */
		codeEditorSettings.onTabPrevious = function() {
			var templateside = document.getElementById( 'templateside' ),
				tabbables = Array.from(
					templateside.querySelectorAll( 'a[href], button, input, textarea, select, [tabindex]:not([tabindex="-1"])' )
				).filter( isVisible );

			tabbables[ tabbables.length - 1 ].focus();
		};

		/**
		 * Handle tabbing to the field after the editor.
		 *
		 * @since 4.9.0
		 * @return {void}
		 */
		codeEditorSettings.onTabNext = function() {
			var template = document.getElementById( 'template' ),
				tabbables = Array.from(
					template.querySelectorAll( 'a[href], button, input, textarea, select, [tabindex]:not([tabindex="-1"])' )
				).filter( function( el ) {
					return isVisible( el ) && ! el.className.includes( 'CodeMirror-code' );
				} );

			tabbables[0].focus();
		};

		/**
		 * Handle change to the linting errors.
		 *
		 * @since 4.9.0
		 * @param {Array} errors
		 * @return {void}
		 */
		codeEditorSettings.onChangeLintingErrors = function( errors ) {
			component.lintErrors = errors;

			if ( 0 === errors.length ) {
				component.submitButton.classList.remove( 'disabled' );
			}
		};

		/**
		 * Update error notice.
		 *
		 * @since 4.9.0
		 * @param {Array} errorAnnotations
		 * @return {void}
		 */
		codeEditorSettings.onUpdateErrorNotice = function onUpdateErrorNotice( errorAnnotations ) {
			var noticeElement;

			component.submitButton.classList.toggle( 'disabled', errorAnnotations.length > 0 );

			if ( 0 !== errorAnnotations.length ) {
				noticeElement = component.addNotice( {
					code: 'lint_errors',
					type: 'error',
					message: sprintf(
						/* translators: %s: Error count. */
						_n(
							'There is %s error which must be fixed before you can update this file.',
							'There are %s errors which must be fixed before you can update this file.',
							errorAnnotations.length
						),
						String( errorAnnotations.length )
					),
					dismissible: false
				} );

				var checkbox = noticeElement.querySelector( 'input[type=checkbox]' );
				if ( checkbox ) {
					checkbox.addEventListener( 'click', function() {
						codeEditorSettings.onChangeLintingErrors( [] );
						component.removeNotice( 'lint_errors' );
					} );
				}
			} else {
				component.removeNotice( 'lint_errors' );
			}
		};

		editor = wp.codeEditor.initialize( document.getElementById( 'newcontent' ), codeEditorSettings );
		editor.codemirror.on( 'change', component.onChange );

		// Improve the editor accessibility.
		lineDiv = editor.codemirror.display.lineDiv;
		lineDiv.setAttribute( 'role', 'textbox' );
		lineDiv.setAttribute( 'aria-multiline', 'true' );
		lineDiv.setAttribute( 'aria-labelledby', 'theme-plugin-editor-label' );
		lineDiv.setAttribute( 'aria-describedby', 'editor-keyboard-trap-help-1 editor-keyboard-trap-help-2 editor-keyboard-trap-help-3 editor-keyboard-trap-help-4' );

		// Focus the editor when clicking on its label.
		document.getElementById( 'theme-plugin-editor-label' ).addEventListener( 'click', function() {
			editor.codemirror.focus();
		} );

		component.instance = editor;
	};

	/**
	 * Initialization of the file browser's folder states.
	 *
	 * @since 4.9.0
	 * @return {void}
	 */
	component.initFileBrowser = function initFileBrowser() {
		var templateside = document.getElementById( 'templateside' );

		// Collapse all folders.
		templateside.querySelectorAll( '[role="group"]' ).forEach( function( group ) {
			group.parentElement.setAttribute( 'aria-expanded', 'false' );
		} );

		// Expand ancestors to the current file.
		templateside.querySelectorAll( '.notice' ).forEach( function( notice ) {
			var el = notice.parentElement;
			while ( el && el !== templateside ) {
				if ( el.hasAttribute( 'aria-expanded' ) ) {
					el.setAttribute( 'aria-expanded', 'true' );
				}
				el = el.parentElement;
			}
		} );

		// Find Tree elements and enhance them.
		templateside.querySelectorAll( '[role="tree"]' ).forEach( function( tree ) {
			var treeLinks = new TreeLinks( tree );
			treeLinks.init();
		} );

		// Scroll the current file into view.
		var currentFile = templateside.querySelector( '.current-file' );
		if ( currentFile ) {
			if ( currentFile.scrollIntoViewIfNeeded ) {
				currentFile.scrollIntoViewIfNeeded();
			} else {
				currentFile.scrollIntoView( false );
			}
		}
	};

	/*
	 * Helper function: returns true if the element occupies layout space.
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

	/* jshint ignore:start */
	/* jscs:disable */
	/* eslint-disable */

	/**
	 * Creates a new TreeitemLink.
	 *
	 * @since 4.9.0
	 * @class
	 * @private
	 * @see {@link https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-2/treeview-2b.html|W3C Treeview Example}
	 * @license W3C-20150513
	 */
	var TreeitemLink = (function() {
		var TreeitemLink = function( node, treeObj, group ) {
			var elem;
			if ( typeof node !== 'object' ) {
				return;
			}

			node.tabIndex = -1;
			this.tree = treeObj;
			this.groupTreeitem = group;
			this.domNode = node;
			this.label = node.textContent.trim();
			this.stopDefaultClick = false;

			if ( node.getAttribute( 'aria-label' ) ) {
				this.label = node.getAttribute( 'aria-label' ).trim();
			}

			this.isExpandable = false;
			this.isVisible = false;
			this.inGroup = false;

			if ( group ) {
				this.inGroup = true;
			}

			elem = node.firstElementChild;
			while ( elem ) {
				if ( elem.tagName.toLowerCase() == 'ul' ) {
					elem.setAttribute( 'role', 'group' );
					this.isExpandable = true;
					break;
				}
				elem = elem.nextElementSibling;
			}

			this.keyCode = Object.freeze( {
				RETURN: 13,
				SPACE: 32,
				PAGEUP: 33,
				PAGEDOWN: 34,
				END: 35,
				HOME: 36,
				LEFT: 37,
				UP: 38,
				RIGHT: 39,
				DOWN: 40
			} );
		};

		TreeitemLink.prototype.init = function() {
			this.domNode.tabIndex = -1;

			if ( ! this.domNode.getAttribute( 'role' ) ) {
				this.domNode.setAttribute( 'role', 'treeitem' );
			}

			this.domNode.addEventListener( 'keydown', this.handleKeydown.bind( this ) );
			this.domNode.addEventListener( 'click', this.handleClick.bind( this ) );
			this.domNode.addEventListener( 'focus', this.handleFocus.bind( this ) );
			this.domNode.addEventListener( 'blur', this.handleBlur.bind( this ) );

			if ( this.isExpandable ) {
				this.domNode.firstElementChild.addEventListener( 'mouseover', this.handleMouseOver.bind( this ) );
				this.domNode.firstElementChild.addEventListener( 'mouseout', this.handleMouseOut.bind( this ) );
			} else {
				this.domNode.addEventListener( 'mouseover', this.handleMouseOver.bind( this ) );
				this.domNode.addEventListener( 'mouseout', this.handleMouseOut.bind( this ) );
			}
		};

		TreeitemLink.prototype.isExpanded = function() {
			if ( this.isExpandable ) {
				return this.domNode.getAttribute( 'aria-expanded' ) === 'true';
			}
			return false;
		};

		TreeitemLink.prototype.handleKeydown = function( event ) {
			var tgt = event.currentTarget,
				flag = false,
				_char = event.key,
				clickEvent;

			function isPrintableCharacter( str ) {
				return str.length === 1 && str.match( /\S/ );
			}

			function printableCharacter( item ) {
				if (_char == '*') {
					item.tree.expandAllSiblingItems( item );
					flag = true;
				} else {
					if ( isPrintableCharacter( _char ) ) {
						item.tree.setFocusByFirstCharacter( item, _char );
						flag = true;
					}
				}
			}

			this.stopDefaultClick = false;

			if ( event.altKey || event.ctrlKey || event.metaKey ) {
				return;
			}

			if ( event.shift ) {
				if ( event.keyCode == this.keyCode.SPACE || event.keyCode == this.keyCode.RETURN ) {
					event.stopPropagation();
					this.stopDefaultClick = true;
				} else {
					if ( isPrintableCharacter( _char ) ) {
						printableCharacter( this );
					}
				}
			} else {
				switch ( event.keyCode ) {
					case this.keyCode.SPACE:
					case this.keyCode.RETURN:
						if ( this.isExpandable ) {
							if ( this.isExpanded() ) {
								this.tree.collapseTreeitem( this );
							} else {
								this.tree.expandTreeitem( this );
							}
							flag = true;
						} else {
							event.stopPropagation();
							this.stopDefaultClick = true;
						}
						break;
					case this.keyCode.UP:
						this.tree.setFocusToPreviousItem( this );
						flag = true;
						break;
					case this.keyCode.DOWN:
						this.tree.setFocusToNextItem( this );
						flag = true;
						break;
					case this.keyCode.RIGHT:
						if ( this.isExpandable ) {
							if ( this.isExpanded() ) {
								this.tree.setFocusToNextItem( this );
							} else {
								this.tree.expandTreeitem( this );
							}
						}
						flag = true;
						break;
					case this.keyCode.LEFT:
						if ( this.isExpandable && this.isExpanded() ) {
							this.tree.collapseTreeitem( this );
							flag = true;
						} else {
							if ( this.inGroup ) {
								this.tree.setFocusToParentItem( this );
								flag = true;
							}
						}
						break;
					case this.keyCode.HOME:
						this.tree.setFocusToFirstItem();
						flag = true;
						break;
					case this.keyCode.END:
						this.tree.setFocusToLastItem();
						flag = true;
						break;
					default:
						if ( isPrintableCharacter( _char ) ) {
							printableCharacter( this );
						}
						break;
				}
			}

			if ( flag ) {
				event.stopPropagation();
				event.preventDefault();
			}
		};

		TreeitemLink.prototype.handleClick = function( event ) {
			if ( event.target !== this.domNode && event.target !== this.domNode.firstElementChild ) {
				return;
			}
			if ( this.isExpandable ) {
				if ( this.isExpanded() ) {
					this.tree.collapseTreeitem( this );
				} else {
					this.tree.expandTreeitem( this );
				}
				event.stopPropagation();
			}
		};

		TreeitemLink.prototype.handleFocus = function( event ) {
			var node = this.domNode;
			if ( this.isExpandable ) {
				node = node.firstElementChild;
			}
			node.classList.add( 'focus' );
		};

		TreeitemLink.prototype.handleBlur = function( event ) {
			var node = this.domNode;
			if ( this.isExpandable ) {
				node = node.firstElementChild;
			}
			node.classList.remove( 'focus' );
		};

		TreeitemLink.prototype.handleMouseOver = function( event ) {
			event.currentTarget.classList.add( 'hover' );
		};

		TreeitemLink.prototype.handleMouseOut = function( event ) {
			event.currentTarget.classList.remove( 'hover' );
		};

		return TreeitemLink;
	} )();

	/**
	 * Creates a new TreeLinks.
	 *
	 * @since 4.9.0
	 * @class
	 * @private
	 * @see {@link https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-2/treeview-2b.html|W3C Treeview Example}
	 * @license W3C-20150513
	 */
	TreeLinks = ( function() {
		var TreeLinks = function( node ) {
			if ( typeof node !== 'object' ) {
				return;
			}
			this.domNode = node;
			this.treeitems = [];
			this.firstChars = [];
			this.firstTreeitem = null;
			this.lastTreeitem = null;
		};

		TreeLinks.prototype.init = function() {
			function findTreeitems( node, tree, group ) {
				var elem = node.firstElementChild,
					ti = group;

				while ( elem ) {
					if ( ( elem.tagName.toLowerCase() === 'li' && elem.firstElementChild.tagName.toLowerCase() === 'span' ) || elem.tagName.toLowerCase() === 'a' ) {
						ti = new TreeitemLink( elem, tree, group );
						ti.init();
						tree.treeitems.push( ti );
						tree.firstChars.push( ti.label.substring( 0, 1 ).toLowerCase() );
					}
					if ( elem.firstElementChild ) {
						findTreeitems( elem, tree, ti );
					}
					elem = elem.nextElementSibling;
				}
			}

			if ( ! this.domNode.getAttribute( 'role' ) ) {
				this.domNode.setAttribute( 'role', 'tree' );
			}

			findTreeitems( this.domNode, this, false );
			this.updateVisibleTreeitems();
			this.firstTreeitem.domNode.tabIndex = 0;
		};

		TreeLinks.prototype.setFocusToItem = function( treeitem ) {
			for ( var i = 0, n = this.treeitems.length; i < n; i++ ) {
				var ti = this.treeitems[i];
				if ( ti === treeitem ) {
					ti.domNode.tabIndex = 0;
					ti.domNode.focus();
				} else {
					ti.domNode.tabIndex = -1;
				}
			}
		};

		TreeLinks.prototype.setFocusToNextItem = function( currentItem ) {
			var nextItem = false;
			for ( var i = ( this.treeitems.length - 1 ); i >= 0; i-- ) {
				var ti = this.treeitems[i];
				if ( ti === currentItem ) {
					break;
				}
				if ( ti.isVisible ) {
					nextItem = ti;
				}
			}
			if ( nextItem ) {
				this.setFocusToItem( nextItem );
			}
		};

		TreeLinks.prototype.setFocusToPreviousItem = function( currentItem ) {
			var prevItem = false;
			for ( var i = 0, n = this.treeitems.length; i < n; i++ ) {
				var ti = this.treeitems[i];
				if ( ti === currentItem ) {
					break;
				}
				if ( ti.isVisible ) {
					prevItem = ti;
				}
			}
			if ( prevItem ) {
				this.setFocusToItem( prevItem );
			}
		};

		TreeLinks.prototype.setFocusToParentItem = function( currentItem ) {
			if ( currentItem.groupTreeitem ) {
				this.setFocusToItem( currentItem.groupTreeitem );
			}
		};

		TreeLinks.prototype.setFocusToFirstItem = function() {
			this.setFocusToItem( this.firstTreeitem );
		};

		TreeLinks.prototype.setFocusToLastItem = function() {
			this.setFocusToItem( this.lastTreeitem );
		};

		TreeLinks.prototype.expandTreeitem = function( currentItem ) {
			if ( currentItem.isExpandable ) {
				currentItem.domNode.setAttribute( 'aria-expanded', true );
				this.updateVisibleTreeitems();
			}
		};

		TreeLinks.prototype.expandAllSiblingItems = function( currentItem ) {
			for ( var i = 0, n = this.treeitems.length; i < n; i++ ) {
				var ti = this.treeitems[i];
				if ( ( ti.groupTreeitem === currentItem.groupTreeitem ) && ti.isExpandable ) {
					this.expandTreeitem(ti);
				}
			}
		};

		TreeLinks.prototype.collapseTreeitem = function( currentItem ) {
			var groupTreeitem = false;
			if ( currentItem.isExpanded() ) {
				groupTreeitem = currentItem;
			} else {
				groupTreeitem = currentItem.groupTreeitem;
			}
			if ( groupTreeitem ) {
				groupTreeitem.domNode.setAttribute( 'aria-expanded', false );
				this.updateVisibleTreeitems();
				this.setFocusToItem( groupTreeitem );
			}
		};

		TreeLinks.prototype.updateVisibleTreeitems = function() {
			this.firstTreeitem = this.treeitems[0];
			for ( var i = 0, n = this.treeitems.length; i < n; i++ ) {
				var ti = this.treeitems[i];
				var parent = ti.domNode.parentNode;
				ti.isVisible = true;
				while ( parent && ( parent !== this.domNode ) ) {
					if ( parent.getAttribute( 'aria-expanded' ) == 'false' ) {
						ti.isVisible = false;
					}
					parent = parent.parentNode;
				}
				if ( ti.isVisible ) {
					this.lastTreeitem = ti;
				}
			}
		};

		TreeLinks.prototype.setFocusByFirstCharacter = function( currentItem, _char ) {
			var start, index;
			_char = _char.toLowerCase();
			start = this.treeitems.indexOf( currentItem ) + 1;
			if ( start === this.treeitems.length)  {
				start = 0;
			}
			index = this.getIndexFirstChars( start, _char );
			if ( index === -1 ) {
				index = this.getIndexFirstChars( 0, _char );
			}
			if ( index > -1 ) {
				this.setFocusToItem( this.treeitems[index] );
			}
		};

		TreeLinks.prototype.getIndexFirstChars = function( startIndex, _char ) {
			for ( var i = startIndex, n = this.firstChars.length; i < n; i++ ) {
				if ( this.treeitems[i].isVisible ) {
					if ( _char === this.firstChars[i] ) {
						return i;
					}
				}
			}
			return -1;
		};

		return TreeLinks;
	})();

	/**
	 * Renders a file editor notice element from native <template> elements.
	 *
	 * @since CP-2.8.0
	 *
	 * @param {Object}  data
	 * @param {string}  data.code
	 * @param {string}  [data.type]        Defaults to 'info'.
	 * @param {string}  [data.message]
	 * @param {number}  [data.line]        php_error only.
	 * @param {string}  [data.file]        php_error only.
	 * @param {boolean} [data.alt]
	 * @param {boolean} [data.dismissible]
	 * @param {string}  [data.classes]
	 * @returns {HTMLElement}
	 */
	function renderFileEditorNotice( data ) {
		var tmpl, content, msgEl, preEl, lintP, elementId, input, label, dismissTmpl,
			wrapperTmpl = document.getElementById( 'tmpl-wp-file-editor-notice' ),
			wrapper = wrapperTmpl.content.cloneNode( true ).querySelector( 'div' );

		wrapper.classList.add( 'notice-' + ( data.type || 'info' ) );
		if ( data.alt ) {
			wrapper.classList.add( 'notice-alt' );
		}
		if ( data.dismissible ) {
			wrapper.classList.add( 'is-dismissible' );
		}
		if ( data.classes ) {
			data.classes.split( ' ' ).filter( Boolean ).forEach( function( c ) {
				wrapper.classList.add( c );
			} );
		}

		if ( 'php_error' === data.code ) {
			tmpl = document.getElementById( 'tmpl-wp-file-editor-notice-php-error' );
			content = tmpl.content.cloneNode( true );
			msgEl = content.querySelector( '[data-message]' );

			if ( msgEl ) {
				msgEl.textContent = fileEditorL10n.phpErrorTemplate.replace( '%1$s', data.line ).replace( '%2$s', data.file );
				msgEl.removeAttribute( 'data-message' );
			}

			preEl = content.querySelector( '[data-raw-message]' );
			if ( preEl ) {
				preEl.textContent = data.message;
				preEl.removeAttribute( 'data-raw-message' );
			}
			wrapper.appendChild( content );

		} else if ( 'file_not_writable' === data.code ) {
			tmpl = document.getElementById( 'tmpl-wp-file-editor-notice-not-writable' );
			wrapper.appendChild( tmpl.content.cloneNode( true ) );

		} else {
			tmpl = document.getElementById( 'tmpl-wp-file-editor-notice-generic' );
			content = tmpl.content.cloneNode( true );
			msgEl = content.querySelector( '[data-message]' );

			if ( msgEl ) {
				msgEl.textContent = data.message || data.code;
				msgEl.removeAttribute( 'data-message' );
			}

			if ( 'lint_errors' === data.code ) {
				lintP = content.querySelector( '.lint-errors-confirm' );
				if ( lintP ) {
					lintP.hidden = false;
					elementId = 'el-' + Math.random().toString( 36 ).slice( 2 );
					input = lintP.querySelector( 'input' );

					if ( input ) {
						input.id = elementId;
					}
		
					label = lintP.querySelector( 'label' );
					if ( label ) {
						label.setAttribute( 'for', elementId );
					}
				}
			}
			wrapper.appendChild( content );
		}

		if ( data.dismissible ) {
			dismissTmpl = document.getElementById( 'tmpl-wp-file-editor-notice-dismiss' );
			wrapper.appendChild( dismissTmpl.content.cloneNode( true ) );
		}

		return wrapper;
	}

	/* jshint ignore:end */
	/* jscs:enable */
	/* eslint-enable */

	return component;
} )();

/**
 * Removed in 5.5.0, needed for back-compatibility.
 *
 * @since 4.9.0
 * @deprecated 5.5.0
 *
 * @type {object}
 */
wp.themePluginEditor.l10n = wp.themePluginEditor.l10n || {
	saveAlert: '',
	saveError: '',
	lintError: {
		alternative: 'wp.i18n',
		func: function() {
			return {
				singular: '',
				plural: ''
			};
		}
	}
};

wp.themePluginEditor.l10n = window.wp.deprecateL10nObject( 'wp.themePluginEditor.l10n', wp.themePluginEditor.l10n, '5.5.0' );
