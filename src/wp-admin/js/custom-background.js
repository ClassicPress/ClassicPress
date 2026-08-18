/**
 * @output wp-admin/js/custom-background.js
 */

/* global ajaxurl, Coloris, AdminMediaModal */

/**
 * Registers all events for customizing the background.
 *
 * @since 3.0.0
 * @since CP-2.8.0 Rewritten to use vanilla JavaScript and Coloris color picker.
 *
 * @return {void}
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	const bgImage = document.getElementById( 'custom-background-image' );

	/**
	 * Instantiates the Coloris color picker and binds the change and clear events.
	 *
	 * @since 3.5.0
	 * @since CP-2.8.0 Uses Coloris instead of wp-color-picker.
	 *
	 * @return {void}
	 */
	function initColorPicker() {
		const colorInput = document.getElementById( 'background-color' );

		if ( ! colorInput ) {
			return;
		}

		Coloris( {
			alpha: false,
			format: 'hex',
			a11y: {
				open: 'Open color picker',
				close: 'Close color picker',
				clear: 'Clear the selected color',
				marker: 'Saturation: {s}. Brightness: {v}.',
				hueSlider: 'Hue slider',
				alphaSlider: 'Opacity slider',
				input: 'Color value field',
				format: 'Color format',
				swatch: 'Color swatch',
				instruction: 'Saturation and brightness selector. Use up, down, left and right arrow keys to select.'
			},
			swatches: [
				'#264653',
				'#2a9d8f',
				'#e9c46a',
				'#f4a261',
				'#e76f51',
				'#d62828',
				'#000080',
				'#0077bb',
				'#0096c7',
				'#00b4d8',
				'#0077b6'
			],
			clearButton: true,
			onChange: ( color, inputEl ) => {
				const effectiveColor = color || inputEl.dataset.defaultColor || '';

				if ( bgImage ) {
					bgImage.style.backgroundColor = effectiveColor;
				}
			}
		} );
	}

	/**
	 * Alters the background size CSS property.
	 *
	 * @since 4.7.0
	 *
	 * @return {void}
	 */
	function initBackgroundSize() {
		const select = document.querySelector( 'select[name="background-size"]' );
		if ( ! select || ! bgImage ) {
			return;
		}

		select.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundSize = e.target.value;
		} );
	}

	/**
	 * Alters the background position CSS property.
	 *
	 * @since 4.7.0
	 *
	 * @return {void}
	 */
	function initBackgroundPosition() {
		const input = document.querySelector( 'input[name="background-position"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundPosition = e.target.value;
		} );
	}

	/**
	 * Alters the background repeat CSS property.
	 *
	 * @since 3.0.0
	 *
	 * @return {void}
	 */
	function initBackgroundRepeat() {
		const input = document.querySelector( 'input[name="background-repeat"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundRepeat = e.target.checked ? 'repeat' : 'no-repeat';
		} );
	}

	/**
	 * Alters the background attachment CSS property.
	 *
	 * @since 4.7.0
	 *
	 * @return {void}
	 */
	function initBackgroundAttachment() {
		const input = document.querySelector( 'input[name="background-attachment"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundAttachment = e.target.checked ? 'scroll' : 'fixed';
		} );
	}

	// Initialize all components.
	initColorPicker();
	initBackgroundSize();
	initBackgroundPosition();
	initBackgroundRepeat();
	initBackgroundAttachment();
	AdminMediaModal( 'set-background-image', 'full' );
} );
