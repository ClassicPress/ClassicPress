/*! Iris Color Picker - v1.1.1 - 2021-10-05
* https://github.com/Automattic/Iris
* Copyright (c) 2021 Matt Wiebe; Licensed GPLv2
* Modified 2024-01-18 by Tim Kaye
* to use native HTML5 drag-and-drop and sliders */
/* global Color */
(function( $, undef ){
	var _html, nonGradientIE, gradientType, vendorPrefixes, Iris, UA, isIE, IEVersion;

	_html = '<div class="iris-picker"><div class="iris-picker-inner"><div class="iris-square"><a class="iris-square-value" href="#"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz"></div><div class="iris-square-inner iris-square-vert"></div></div><div class="iris-slider iris-strip"><input class="iris-slider-offset" type="range"></input></div></div><img class="img-ghost" src="' + IRIS.blank + '" alt="" aria-hidden="true" role="presentation"></div>';

	// IE9 doesn't support gradients. Elaborate sigh.
	UA = navigator.userAgent.toLowerCase();
	isIE = navigator.appName === 'Microsoft Internet Explorer';
	IEVersion = isIE ? parseFloat( UA.match( /msie ([0-9]{1,}[\.0-9]{0,})/ )[1] ) : 0;
	nonGradientIE = ( isIE && IEVersion < 10 );
	gradientType = false;

	// we don't bother with an unprefixed version, as it has a different syntax
	vendorPrefixes = [ '-moz-', '-webkit-', '-o-', '-ms-' ];

	// Bail for IE <= 7
	if ( nonGradientIE && IEVersion <= 7 ) {
		$.fn.iris = $.noop;
		$.support.iris = false;
		return;
	}

	$.support.iris = true;

	function testGradientType() {
		var el, base,
			bgImageString = 'backgroundImage';

		if ( nonGradientIE ) {
			gradientType = 'filter';
		}
		else {
			el = $( '<div id="iris-gradtest" />' );
			base = 'linear-gradient(top,#fff,#000)';
			$.each( vendorPrefixes, function( i, val ){
				el.css( bgImageString, val + base );
				if ( el.css( bgImageString ).match( 'gradient' ) ) {
					gradientType = i;
					return false;
				}
			});
			// check for legacy webkit gradient syntax
			if ( gradientType === false ) {
				el.css( 'background', '-webkit-gradient(linear,0% 0%,0% 100%,from(#fff),to(#000))' );
				if ( el.css( bgImageString ).match( 'gradient' ) ) {
					gradientType = 'webkit';
				}
			}
			el.remove();
		}

	}

	/**
	* Only for CSS3 gradients. oldIE will use a separate function.
	*
	* Accepts as many color stops as necessary from 2nd arg on, or 2nd
	* arg can be an array of color stops
	*
	* @param  {string} origin Gradient origin - top or left, defaults to left.
	* @return {string}        Appropriate CSS3 gradient string for use in
	*/
	function createGradient( origin, stops ) {
		origin = ( origin === 'top' ) ? 'top' : 'left';
		stops = Array.isArray( stops ) ? stops : Array.prototype.slice.call( arguments, 1 );
		if ( gradientType === 'webkit' ) {
			return legacyWebkitGradient( origin, stops );
		} else {
			return vendorPrefixes[ gradientType ] + 'linear-gradient(' + origin + ', ' + stops.join(', ') + ')';
		}
	}

	/**
	* Stupid gradients for a stupid browser.
	*/
	function stupidIEGradient( origin, stops ) {
		var type, self, lastIndex, filter, startPosProp, endPosProp, dimensionProp, template, html;

		origin = ( origin === 'top' ) ? 'top' : 'left';
		stops = Array.isArray( stops ) ? stops : Array.prototype.slice.call( arguments, 1 );
		// 8 hex: AARRGGBB
		// GradientType: 0 vertical, 1 horizontal
		type = ( origin === 'top' ) ? 0 : 1;
		self = $( this );
		lastIndex = stops.length - 1;
		filter = 'filter';
		startPosProp = ( type === 1 ) ? 'left' : 'top';
		endPosProp = ( type === 1 ) ? 'right' : 'bottom';
		dimensionProp = ( type === 1 ) ? 'height' : 'width';
		template = '<div class="iris-ie-gradient-shim" style="position:absolute;' + dimensionProp + ':100%;' + startPosProp + ':%start%;' + endPosProp + ':%end%;' + filter + ':%filter%;" data-color:"%color%"></div>';
		html = '';
		// need a positioning context
		if ( self.css('position') === 'static' ) {
			self.css( {position: 'relative' } );
		}

		stops = fillColorStops( stops );
		$.each(stops, function( i, startColor ) {
			var endColor, endStop, filterVal;

			// we want two at a time. if we're on the last pair, bail.
			if ( i === lastIndex ) {
				return false;
			}

			endColor = stops[ i + 1 ];
			//if our pairs are at the same color stop, moving along.
			if ( startColor.stop === endColor.stop ) {
				return;
			}

			endStop = 100 - parseFloat( endColor.stop ) + '%';
			startColor.octoHex = new Color( startColor.color ).toIEOctoHex();
			endColor.octoHex = new Color( endColor.color ).toIEOctoHex();

			filterVal = 'progid:DXImageTransform.Microsoft.Gradient(GradientType=' + type + ', StartColorStr=\'' + startColor.octoHex + '\', EndColorStr=\'' + endColor.octoHex + '\')';
			html += template.replace( '%start%', startColor.stop ).replace( '%end%', endStop ).replace( '%filter%', filterVal );
		});
		self.find( '.iris-ie-gradient-shim' ).remove();
		$( html ).prependTo( self );
	}

	function legacyWebkitGradient( origin, colorList ) {
		var stops = [];
		origin = ( origin === 'top' ) ? '0% 0%,0% 100%,' : '0% 100%,100% 100%,';
		colorList = fillColorStops( colorList );
		$.each( colorList, function( i, val ){
			stops.push( 'color-stop(' + ( parseFloat( val.stop ) / 100 ) + ', ' + val.color + ')' );
		});
		return '-webkit-gradient(linear,' + origin + stops.join(',') + ')';
	}

	function fillColorStops( colorList ) {
		var colors = [],
			percs = [],
			newColorList = [],
			lastIndex = colorList.length - 1;

		$.each( colorList, function( index, val ) {
			var color = val,
				perc = false,
				match = val.match( /1?[0-9]{1,2}%$/ );

			if ( match ) {
				color = val.replace( /\s?1?[0-9]{1,2}%$/, '' );
				perc = match.shift();
			}
			colors.push( color );
			percs.push( perc );
		});

		// back fill first and last
		if ( percs[0] === false ) {
			percs[0] = '0%';
		}

		if ( percs[lastIndex] === false ) {
			percs[lastIndex] = '100%';
		}

		percs = backFillColorStops( percs );

		$.each( percs, function( i ){
			newColorList[i] = { color: colors[i], stop: percs[i] };
		});
		return newColorList;
	}

	function backFillColorStops( stops ) {
		var first = 0,
			last = stops.length - 1,
			i = 0,
			foundFirst = false,
			incr,
			steps,
			step,
			firstVal;

		if ( stops.length <= 2 || $.inArray( false, stops ) < 0 ) {
			return stops;
		}
		while ( i < stops.length - 1 ) {
			if ( ! foundFirst && stops[i] === false ) {
				first = i - 1;
				foundFirst = true;
			} else if ( foundFirst && stops[i] !== false ) {
				last = i;
				i = stops.length;
			}
			i++;
		}
		steps = last - first;
		firstVal = parseInt( stops[first].replace('%'), 10 );
		incr = ( parseFloat( stops[last].replace('%') ) - firstVal ) / steps;
		i = first + 1;
		step = 1;
		while ( i < last ) {
			stops[i] = ( firstVal + ( step * incr ) ) + '%';
			step++;
			i++;
		}
		return backFillColorStops( stops );
	}

	$.fn.gradient = function() {
		var args = arguments;
		return this.each( function() {
			// this'll be oldishIE
			if ( nonGradientIE ) {
				stupidIEGradient.apply( this, args );
			} else {
				// new hotness
				$( this ).css( 'backgroundImage', createGradient.apply( this, args ) );
			}
		});
	};

	$.fn.rainbowGradient = function( origin, args ) {
		var opts, template, i, steps;

		origin = origin || 'top';
		opts = $.extend( {}, { s: 100, l: 50 }, args );
		template = 'hsl(%h%,' + opts.s + '%,' + opts.l + '%)';
		i = 0;
		steps = [];
		while ( i <= 360 ) {
			steps.push( template.replace('%h%', i) );
			i += 30;
		}
		return this.each(function() {
			$(this).gradient( origin, steps );
		});
	};

	// the colorpicker widget def.
	Iris = {
		options: {
			color: false,
			mode: 'hsl',
			controls: {
				horiz: 's', // horizontal defaults to saturation
				vert: 'l', // vertical defaults to lightness
				strip: 'h' // right strip defaults to hue
			},
			hide: true, // hide the color picker by default
			border: true, // draw a border around the collection of UI elements
			target: false, // a DOM element / jQuery selector that the element will be appended within. Only used when called on an input.
			width: 200, // the width of the collection of UI elements
			palettes: false, // show a palette of basic colors beneath the square.
			type: 'full', // use `hue` to show a hue-only slider.
			slider: 'horizontal' // or vertical. only applies when type = 'hue'
		},
		_color: '',
		_palettes: [ '#000', '#fff', '#d33', '#d93', '#ee2', '#81d742', '#1e73be', '#8224e3' ],
		_inited: false,
		_defaultHSLControls: {
			horiz: 's',
			vert: 'l',
			strip: 'h'
		},
		_defaultHSVControls: {
			horiz: 'h',
			vert: 'v',
			strip: 's'
		},
		_scale: {
			h: 360,
			s: 100,
			l: 100,
			v: 100
		},
		_create: function() {
			var self = this,
				el = self.element,
				color = self.options.color || el.val();

			if ( gradientType === false ) {
				testGradientType();
			}

			if ( el.is( 'input' ) ) {
				if ( self.options.target ) {
					self.picker = $( _html ).appendTo( self.options.target );
				} else {
					self.picker = $( _html ).insertAfter( el );
				}

				self._addInputListeners( el );
			} else {
				el.append( _html );
				self.picker = el.find( '.iris-picker' );
			}

			// Browsers / Versions
			// Feature detection doesn't work for these, and $.browser is deprecated
			if ( isIE ) {
				if ( IEVersion === 9 ) {
					self.picker.addClass( 'iris-ie-9' );
				} else if ( IEVersion <= 8 ) {
					self.picker.addClass( 'iris-ie-lt9' );
				}
			} else if ( UA.indexOf('compatible') < 0 && UA.indexOf('khtml') < 0 && UA.match( /mozilla/ ) ) {
				self.picker.addClass( 'iris-mozilla' );
			}

			if ( self.options.palettes ) {
				self._addPalettes();
			}

			self.onlySlider = self.options.type === 'hue';
			self.horizontalSlider = self.onlySlider && self.options.slider === 'horizontal';
			if ( self.onlySlider ) {
				self.options.controls.strip = 'h';
				if ( ! color ) {
					// set a color for a hue-only slider if one wasn't provided
					color = 'hsl(10,100,50)';
				}
			}

			self._color = new Color( color ).setHSpace( self.options.mode );
			self.options.color = self._color.toString();

			// prep 'em for re-use
			self.controls = {
				square:      self.picker.find( '.iris-square' ),
				squareDrag:  self.picker.find( '.iris-square-value' ),
				horiz:       self.picker.find( '.iris-square-horiz' ),
				vert:        self.picker.find( '.iris-square-vert' ),
				strip:       self.picker.find( '.iris-strip' ),
				stripSlider: self.picker.find( '.iris-strip .iris-slider-offset' )
			};

			// small sanity check - if we chose hsv, change default controls away from hsl
			if ( self.options.mode === 'hsv' && self._has('l', self.options.controls) ) {
				self.options.controls = self._defaultHSVControls;
			} else if ( self.options.mode === 'hsl' && self._has('v', self.options.controls) ) {
				self.options.controls = self._defaultHSLControls;
			}

			// store it. HSL gets squirrely
			self.hue = self._color.h();

			if ( self.options.hide ) {
				self.picker.hide();
			}

			if ( self.options.border && ! self.onlySlider ) {
				self.picker.addClass( 'iris-border' );
			}

			self._initControls();
			self.active = 'external';
			self._dimensions();
			self._change();
		},
		_has: function(needle, haystack) {
			var ret = false;
			$.each(haystack, function(i,v){
				if ( needle === v ) {
					ret = true;
					// exit the loop
					return false;
				}
			});
			return ret;
		},
		_addPalettes: function () {
			var container = $( '<div class="iris-palette-container" />' ),
				palette = $( '<a class="iris-palette" tabindex="0" />' ),
				colors = Array.isArray( this.options.palettes ) ? this.options.palettes : this._palettes;

			// do we have an existing container? Empty and reuse it.
			if ( this.picker.find( '.iris-palette-container' ).length ) {
				container = this.picker.find( '.iris-palette-container' ).detach().html( '' );
			}

			$.each(colors, function(index, val) {
				palette.clone().data( 'color', val )
					.css( 'backgroundColor', val ).appendTo( container )
					.height( 10 ).width( 10 );
			});

			this.picker.append(container);
		},
		_paint: function() {
			var self = this;
			if ( self.horizontalSlider ) {
				self._paintDimension( 'left', 'strip' );
			} else {
				self._paintDimension( 'top', 'strip' );
			}
			self._paintDimension( 'top', 'vert' );
			self._paintDimension( 'left', 'horiz' );
		},
		_paintDimension: function( origin, control ) {
			var self = this,
				c = self._color,
				mode = self.options.mode,
				color = self._getHSpaceColor(),
				target = self.controls[ control ],
				controlOpts = self.options.controls,
				stops;

			// don't paint the active control
			if ( control === self.active || ( self.active === 'square' && control !== 'strip' ) ) {
				return;
			}

			switch ( controlOpts[ control ] ) {
				case 'h':
					if ( mode === 'hsv' ) {
						color = c.clone();
						switch ( control ) {
							case 'horiz':
								color[controlOpts.vert](100);
								break;
							case 'vert':
								color[controlOpts.horiz](100);
								break;
							case 'strip':
								color.setHSpace('hsl');
								break;
						}
						stops = color.toHsl();
					} else {
						if ( control === 'strip' ) {
							stops = { s: color.s, l: color.l };
						} else {
							stops = { s: 100, l: color.l };
						}
					}

					target.rainbowGradient( origin, stops );
					break;
				case 's':
					if ( mode === 'hsv' ) {
						if ( control === 'vert' ) {
							stops = [ c.clone().a(0).s(0).toCSS('rgba'), c.clone().a(1).s(0).toCSS('rgba') ];
						} else if ( control === 'strip' ) {
							stops = [ c.clone().s(100).toCSS('hsl'), c.clone().s(0).toCSS('hsl') ];
						} else if ( control === 'horiz' ) {
							stops = [ '#fff', 'hsl(' + color.h + ',100%,50%)' ];
						}
					} else { // implicit mode === 'hsl'
						if ( control === 'vert' && self.options.controls.horiz === 'h' ) {
							stops = ['hsla(0, 0%, ' + color.l + '%, 0)', 'hsla(0, 0%, ' + color.l + '%, 1)'];
						} else {
							stops = ['hsl('+ color.h +',0%,50%)', 'hsl(' + color.h + ',100%,50%)'];
						}
					}


					target.gradient( origin, stops );
					break;
				case 'l':
					if ( control === 'strip' ) {
						stops = ['hsl(' + color.h + ',100%,100%)', 'hsl(' + color.h + ', ' + color.s + '%,50%)', 'hsl('+ color.h +',100%,0%)'];
					} else {
						stops = ['#fff', 'rgba(255,255,255,0) 50%', 'rgba(0,0,0,0) 50%', 'rgba(0,0,0,1)'];
					}
					target.gradient( origin, stops );
					break;
				case 'v':
						if ( control === 'strip' ) {
							stops = [ c.clone().v(100).toCSS(), c.clone().v(0).toCSS() ];
						} else {
							stops = ['rgba(0,0,0,0)', '#000'];
						}
						target.gradient( origin, stops );
					break;
				default:
					break;
			}
		},

		_getHSpaceColor: function() {
			return ( this.options.mode === 'hsv' ) ? this._color.toHsv() : this._color.toHsl();
		},

		_stripOnlyDimensions: function() {
			var self = this,
				width = this.options.width,
				height = width * 0.12;

			// "width" = long dimension, "height" = short dimension
			if ( self.horizontalSlider ) {
				self.picker.css( { width: width, height: height } ).addClass( 'iris-only-strip iris-strip-horiz' );
			} else {
				self.picker.css( { width: height, height: width } ).addClass( 'iris-only-strip iris-strip-vert' );
			}
		},

		_dimensions: function( reset ) {
			if ( this.options.type === 'hue' ) {
				return this._stripOnlyDimensions();
			}
			// whatever size
			var self = this,
				opts = self.options,
				controls = self.controls,
				square = controls.square,
				strip = self.picker.find( '.iris-strip' ),
				squareWidth = '77.5%',
				stripWidth = '12%',
				totalPadding = 20,
				innerWidth = opts.border ? opts.width - totalPadding : opts.width,
				controlsHeight,
				paletteCount = Array.isArray( opts.palettes ) ? opts.palettes.length : self._palettes.length,
				paletteMargin, paletteWidth, paletteContainerWidth;

			if ( reset ) {
				square.css( 'width', '' );
				strip.css( 'width', '' );
				self.picker.css( {width: '', height: ''} );
			}

			squareWidth = innerWidth * ( parseFloat( squareWidth ) / 100 );
			stripWidth = innerWidth * ( parseFloat( stripWidth ) / 100 );
			controlsHeight = opts.border ? squareWidth + totalPadding : squareWidth;

			square.width( squareWidth ).height( squareWidth );
			strip.height( squareWidth ).width( stripWidth );
			self.picker.css( { width: opts.width, height: controlsHeight } );

			if ( ! opts.palettes ) {
				return self.picker.css( 'paddingBottom', '' );
			}

			// single margin at 2%
			paletteMargin = squareWidth * 2 / 100;
			paletteContainerWidth = squareWidth - ( ( paletteCount - 1 ) * paletteMargin );
			paletteWidth = paletteContainerWidth / paletteCount;
			self.picker.find('.iris-palette').each( function( i ) {
				var margin = i === 0 ? 0 : paletteMargin;
				$( this ).css({
					width: paletteWidth,
					height: paletteWidth,
					marginLeft: margin
				});
			});
			self.picker.css( 'paddingBottom', paletteWidth + paletteMargin );
			strip.height( paletteWidth + paletteMargin + squareWidth );
		},

		_addInputListeners: function( input ) {
			var self = this,
				debounceTimeout = 100,
				callback = function( event ){
					var color = new Color( input.val() ),
						val = input.val().replace( /^#/, '' );

					input.removeClass( 'iris-error' );
					// we gave a bad color
					if ( color.error ) {
						// don't error on an empty input - we want those allowed
						if ( val !== '' ) {
							input.addClass( 'iris-error' );
						}
					} else {
						if ( color.toString() !== self._color.toString() ) {
							// let's not do this on keyup for hex shortcodes
							if ( ! ( event.type === 'keyup' && val.match( /^[0-9a-fA-F]{3}$/ ) ) ) {
								self._setOption( 'color', color.toString() );
							}
						}
					}
				};

			input.on( 'change', callback ).on( 'keyup', self._debounce( callback, debounceTimeout ) );

			// If we initialized hidden, show on first focus. The rest is up to you.
			if ( self.options.hide ) {
				input.one( 'focus', function() {
					self.show();
				});
			}
		},

		_initControls: function() {
			var self = this,
				userAgent = navigator.userAgent,
				controls = self.controls,
				square = controls.square,
				controlOpts = self.options.controls,
				stripScale = self._scale[controlOpts.strip],
				stripOrientation = self.horizontalSlider ? 'horizontal' : 'vertical';

			if ( stripOrientation === 'vertical' ) {
				// Use non-standard attribute to ensure Firefox doesn't reverse the scale
				if ( userAgent.match( /firefox|fxios/i ) ) {
					controls.stripSlider[0].setAttribute( 'orient', 'vertical' );
				} else {
					controls.stripSlider[0].style.writingMode = 'vertical-rl'; // scale runs from bottom to top
					controls.stripSlider[0].style.webkitAppearance = 'slider-vertical'; // for Safari
				}
			}

			controls.stripSlider[0].setAttribute( 'min', '0' );
			controls.stripSlider[0].setAttribute( 'max', stripScale );

			controls.stripSlider.on( 'input', function( e ) {
				self.active = 'strip';
				self._color[controlOpts.strip]( e.currentTarget.value );
				self._change.apply( self, arguments );
			} );

			/*
			 * Use HTML5 Drag and Drop API for color picker
			 *
			 * @since CP-2.1.0
			 */
			// Create draggable picker
			controls.squareDrag[0].style.cursor = 'move';
			controls.squareDrag[0].setAttribute( 'draggable', 'true' );

			// Keep jQuery UI classes to avoid possible breaking changes
			controls.squareDrag[0].classList.add( 'ui-draggable' );
			controls.squareDrag[0].querySelector( 'span' ).classList.add( 'ui-draggable-handle' );

			// Set focus when dragging and hide ghost image
			controls.squareDrag[0].addEventListener( 'dragstart', function( e ) {
				e.dataTransfer.setDragImage( document.querySelector( '.img-ghost' ), 0, 0 );
				controls.squareDrag[0].parentNode.classList.add( 'iris-dragging' );
				controls.squareDrag[0].classList.add( 'ui-state-focus' );
				controls.squareDrag[0].classList.add( 'ui-draggable-dragging' );
				controls.squareDrag[0].style.zIndex = '1000';
				controls.squareDrag[0].focus();
			} );

			// Restore settings when dragging stops
			controls.squareDrag[0].addEventListener( 'dragend', function() {
				controls.squareDrag[0].style.zIndex = 'auto';
				controls.squareDrag[0].classList.remove( 'ui-state-focus' );
				controls.squareDrag[0].parentNode.classList.remove( 'iris-dragging' );
			} );

			// Update color selection
			controls.square[0].addEventListener( 'dragover', function( event ) {
				var pos,
					container = controls.square,
					draggable = controls.squareDrag,
					squareOffset = self.controls.square.offset();

				// Keep draggable within container
				pos = {
					top: event.pageY - squareOffset.top,
					left: event.pageX - squareOffset.left
				};

				if ( pos.top < 0 ) {
					pos.top = 0;
				} else if ( pos.top > container.height() ) {
					pos.top = container.height();
				}

				if ( pos.left < 0 ) {
					pos.left = 0;
				} else if ( pos.left > container.width() ) {
					pos.left = container.width();
				}

				draggable.css( pos );
				self._squareDrag( event, { position: pos });
			} );

			controls.squareDrag.on( 'keydown', function( event ) {
				var container = controls.square,
					draggable = controls.squareDrag,
					position = draggable.position(),
					distance = self.options.width / 100; // Distance in pixels the draggable should be moved: 1 "stop"

				// make alt key go "10"
				if ( event.altKey ) {
					distance *= 10;
				}

				// Reposition if one of the directional keys is pressed
				switch ( event.key ) {
					case 'ArrowLeft': position.left -= distance; break;
					case 'ArrowUp': position.top  -= distance; break;
					case 'ArrowRight': position.left += distance; break;
					case 'ArrowDown': position.top  += distance; break;
					default: return true; // Exit and bubble
				}

				// Keep draggable within container
				position.left = Math.max( 0, Math.min( position.left, container.width() ) );
				position.top =  Math.max( 0, Math.min( position.top, container.height() ) );

				draggable.css( position );
				self._squareDrag( event, { position: position });
				event.preventDefault();
			});

			// allow clicking on the square to move there and keep dragging
			square.on( 'mousedown', function( event ) {
				var squareOffset, pos;
				// only left click
				if ( event.which !== 1 ) {
					return;
				}

				// prevent bubbling from the handle: no infinite loops
				if ( ! $( event.target ).is( 'div' ) ) {
					return;
				}

				squareOffset = self.controls.square.offset();
				pos = {
					top: event.pageY - squareOffset.top,
					left: event.pageX - squareOffset.left
				};
				event.preventDefault();
				self._squareDrag( event, { position: pos } );
				event.target = self.controls.squareDrag.get(0);
				self.controls.squareDrag.css( pos ).trigger( event );
			});

			// palettes
			if ( self.options.palettes ) {
				self._paletteListeners();
			}
		},

		_paletteListeners: function() {
			var self = this;
			self.picker.find('.iris-palette-container').on('click.palette', '.iris-palette', function() {
				self._color.fromCSS( $(this).data('color') );
				self.active = 'external';
				self._change();
			}).on( 'keydown.palette', '.iris-palette', function( event ) {
				if ( ! ( event.keyCode === 13 || event.keyCode === 32 ) ) {
					return true;
				}
				event.stopPropagation();
				$( this ).trigger( 'click' );
			});
		},

		_squareDrag: function( event, ui ) {
			var self = this,
				controlOpts = self.options.controls,
				dimensions = self._squareDimensions(),
				vertVal = Math.round( ( dimensions.h - ui.position.top ) / dimensions.h * self._scale[controlOpts.vert] ),
				horizVal = self._scale[controlOpts.horiz] - Math.round( ( dimensions.w - ui.position.left ) / dimensions.w * self._scale[controlOpts.horiz] );

			self._color[controlOpts.horiz]( horizVal )[controlOpts.vert]( vertVal );

			self.active = 'square';
			self._change.apply( self, arguments );
		},

		_setOption: function( key, value ) {
			var self = this,
				oldValue = self.options[key],
				doDimensions = false,
				hexLessColor,
				newColor,
				method;

			// ensure the new value is set. We can reset to oldValue if some check wasn't met.
			self.options[key] = value;

			switch(key) {
				case 'color':
					if ( self.onlySlider ) {
						value = parseInt( value, 10 );
						if ( isNaN( value ) || value < 0 || value > 359 ) {
							value = oldValue;
						} else {
							value = 'hsl(' + value + ',100,50)';
						}
						self.options.color = self.options[key] = value;
						self._color = new Color( value ).setHSpace( self.options.mode );
						self.active = 'external';
						self._change();
					} else {
						// cast to string in case we have a number
						value = '' + value;
						hexLessColor = value.replace( /^#/, '' );
						newColor = new Color( value ).setHSpace( self.options.mode );
						if ( newColor.error ) {
							self.options[key] = oldValue;
						} else {
							self._color = newColor;
							self.options.color = self.options[key] = self._color.toString();
							self.active = 'external';
							self._change();
						}
					}

					break;
				case 'palettes':
					doDimensions = true;

					if ( value ) {
						self._addPalettes();
					} else {
						self.picker.find('.iris-palette-container').remove();
					}

					// do we need to add events?
					if ( ! oldValue ) {
						self._paletteListeners();
					}
					break;
				case 'width':
					doDimensions = true;
					break;
				case 'border':
					doDimensions = true;
					method = value ? 'addClass' : 'removeClass';
					self.picker[method]('iris-border');
					break;
				case 'mode':
				case 'controls':
					// if nothing's changed, let's bail, since this causes re-rendering the whole widget
					if ( oldValue === value ) {
						return;
					}

					// we're using these poorly named variables because they're already scoped.
					// method is the element that Iris was called on. oldValue will be the options
					method = self.element;
					oldValue = self.options;
					oldValue.hide = ! self.picker.is( ':visible' );
					self.destroy();
					self.picker.remove();
					return $(self.element).iris(oldValue);
			}

			// Do we need to recalc dimensions?
			if ( doDimensions ) {
				self._dimensions(true);
			}
		},

		_squareDimensions: function( forceRefresh ) {
			var square = this.controls.square,
				dimensions,
				control;

			if ( forceRefresh !== undef && square.data('dimensions') ) {
				return square.data('dimensions');
			}

			control = this.controls.squareDrag;
			dimensions = {
				w: square.width(),
				h: square.height()
			};
			square.data( 'dimensions', dimensions );
			return dimensions;
		},

		_isNonHueControl: function( active, type ) {
			if ( active === 'square' && this.options.controls.strip === 'h' ) {
				return true;
			} else if ( type === 'external' || ( type === 'h' && active === 'strip' ) ) {
				return false;
			}

			return true;
		},

		_change: function() {
			var self = this,
				controls = self.controls,
				color = self._getHSpaceColor(),
				actions = [ 'square', 'strip' ],
				controlOpts = self.options.controls,
				type = controlOpts[self.active] || 'external',
				oldHue = self.hue;

			if ( self.active === 'strip' ) {
				// take no action on any of the square sliders if we adjusted the strip
				actions = [];
			} else if ( self.active !== 'external' ) {
				// for non-strip, non-external, strip should never change
				actions.pop(); // conveniently the last item
			}

			$.each( actions, function(index, item) {
				var value, dimensions, cssObj;
				if ( item !== self.active ) {
					switch ( item ) {
						case 'strip':
							// reverse for hue if vertical
							value = ( controlOpts.strip === 'h' && ! self.horizontalSlider ) ? self._scale[ controlOpts.strip ] - color[ controlOpts.strip ] : color[controlOpts.strip];
							controls.stripSlider.val( value );
							break;
						case 'square':
							dimensions = self._squareDimensions();
							cssObj = {
								left: color[controlOpts.horiz] / self._scale[controlOpts.horiz] * dimensions.w,
								top: dimensions.h - ( color[controlOpts.vert] / self._scale[controlOpts.vert] * dimensions.h )
							};

							self.controls.squareDrag.css( cssObj );
							break;
					}
				}
			});

			// Ensure that we don't change hue if we triggered a hue reset
			if ( color.h !== oldHue && self._isNonHueControl( self.active, type ) ) {
				self._color.h(oldHue);
			}

			// store hue for repeating above check next time
			self.hue = self._color.h();

			self.options.color = self._color.toString();

			// only run after the first time
			if ( self._inited ) {
				self._trigger( 'change', { type: self.active }, { color: self._color } );
			}

			if ( self.element.is( ':input' ) && ! self._color.error ) {
				self.element.removeClass( 'iris-error' );

				if ( self.onlySlider ) {
					if ( self.element.val() !== self.hue ) {
						self.element.val( self.hue );
					}
				} else {
					if ( self.element.val() !== self._color.toString() ) {
						self.element.val( self._color.toString() );
					}
				}
			}

			self._paint();
			self._inited = true;
			self.active = false;
		},
		// taken from underscore.js _.debounce method
		_debounce: function( func, wait, immediate ) {
			var timeout, result;
			return function() {
				var context = this,
					args = arguments,
					later,
					callNow;

				later = function() {
					timeout = null;
					if ( ! immediate) {
						result = func.apply( context, args );
					}
				};

				callNow = immediate && !timeout;
				clearTimeout( timeout );
				timeout = setTimeout( later, wait );
				if ( callNow ) {
					result = func.apply( context, args );
				}
				return result;
			};
		},
		show: function() {
			this.picker.show();
		},
		hide: function() {
			this.picker.hide();
		},
		toggle: function() {
			this.picker.toggle();
		},
		color: function(newColor) {
			if ( newColor === true ) {
				return this._color.clone();
			} else if ( newColor === undef ) {
				return this._color.toString();
			}
			this.option('color', newColor);
		}
	};
	// initialize the widget
	$.widget( 'a8c.iris', Iris );

}( jQuery ));

/*! Color.js - v1.1.0 - 2015-12-17
* https://github.com/Automattic/Color.js
* Copyright (c) 2015 Matt Wiebe; Licensed GPLv2 */
(function(global, undef) {

	var Color = function( color, type ) {
		if ( ! ( this instanceof Color ) )
			return new Color( color, type );

		return this._init( color, type );
	};

	Color.fn = Color.prototype = {
		_color: 0,
		_alpha: 1,
		error: false,
		// for preserving hue/sat in fromHsl().toHsl() flows
		_hsl: { h: 0, s: 0, l: 0 },
		// for preserving hue/sat in fromHsv().toHsv() flows
		_hsv: { h: 0, s: 0, v: 0 },
		// for setting hsl or hsv space - needed for .h() & .s() functions to function properly
		_hSpace: 'hsl',
		_init: function( color ) {
			var func = 'noop';
			switch ( typeof color ) {
					case 'object':
						// alpha?
						if ( color.a !== undef )
							this.a( color.a );
						func = ( color.r !== undef ) ? 'fromRgb' :
							( color.l !== undef ) ? 'fromHsl' :
							( color.v !== undef ) ? 'fromHsv' : func;
						return this[func]( color );
					case 'string':
						return this.fromCSS( color );
					case 'number':
						return this.fromInt( parseInt( color, 10 ) );
			}
			return this;
		},

		_error: function() {
			this.error = true;
			return this;
		},

		clone: function() {
			var newColor = new Color( this.toInt() ),
				copy = ['_alpha', '_hSpace', '_hsl', '_hsv', 'error'];
			for ( var i = copy.length - 1; i >= 0; i-- ) {
				newColor[ copy[i] ] = this[ copy[i] ];
			}
			return newColor;
		},

		setHSpace: function( space ) {
			this._hSpace = ( space === 'hsv' ) ? space : 'hsl';
			return this;
		},

		noop: function() {
			return this;
		},

		fromCSS: function( color ) {
			var list,
				leadingRE = /^(rgb|hs(l|v))a?\(/;
			this.error = false;

			// whitespace and semicolon trim
			color = color.replace(/^\s+/, '').replace(/\s+$/, '').replace(/;$/, '');

			if ( color.match(leadingRE) && color.match(/\)$/) ) {
				list = color.replace(/(\s|%)/g, '').replace(leadingRE, '').replace(/,?\);?$/, '').split(',');

				if ( list.length < 3 )
					return this._error();

				if ( list.length === 4 ) {
					this.a( parseFloat( list.pop() ) );
					// error state has been set to true in .a() if we passed NaN
					if ( this.error )
						return this;
				}

				for (var i = list.length - 1; i >= 0; i--) {
					list[i] = parseInt(list[i], 10);
					if ( isNaN( list[i] ) )
						return this._error();
				}

				if ( color.match(/^rgb/) ) {
					return this.fromRgb( {
						r: list[0],
						g: list[1],
						b: list[2]
					} );
				} else if ( color.match(/^hsv/) ) {
					return this.fromHsv( {
						h: list[0],
						s: list[1],
						v: list[2]
					} );
				} else {
					return this.fromHsl( {
						h: list[0],
						s: list[1],
						l: list[2]
					} );
				}
			} else {
				// must be hex amirite?
				return this.fromHex( color );
			}
		},

		fromRgb: function( rgb, preserve ) {
			if ( typeof rgb !== 'object' || rgb.r === undef || rgb.g === undef || rgb.b === undef )
				return this._error();

			this.error = false;
			return this.fromInt( parseInt( ( rgb.r << 16 ) + ( rgb.g << 8 ) + rgb.b, 10 ), preserve );
		},

		fromHex: function( color ) {
			color = color.replace(/^#/, '').replace(/^0x/, '');
			if ( color.length === 3 ) {
				color = color[0] + color[0] + color[1] + color[1] + color[2] + color[2];
			}

			// rough error checking - this is where things go squirrely the most
			this.error = ! /^[0-9A-F]{6}$/i.test( color );
			return this.fromInt( parseInt( color, 16 ) );
		},

		fromHsl: function( hsl ) {
			var r, g, b, q, p, h, s, l;

			if ( typeof hsl !== 'object' || hsl.h === undef || hsl.s === undef || hsl.l === undef )
				return this._error();

			this._hsl = hsl; // store it
			this._hSpace = 'hsl'; // implicit
			h = hsl.h / 360; s = hsl.s / 100; l = hsl.l / 100;
			if ( s === 0 ) {
				r = g = b = l; // achromatic
			}
			else {
				q = l < 0.5 ? l * ( 1 + s ) : l + s - l * s;
				p = 2 * l - q;
				r = this.hue2rgb( p, q, h + 1/3 );
				g = this.hue2rgb( p, q, h );
				b = this.hue2rgb( p, q, h - 1/3 );
			}
			return this.fromRgb( {
				r: r * 255,
				g: g * 255,
				b: b * 255
			}, true ); // true preserves hue/sat
		},

		fromHsv: function( hsv ) {
			var h, s, v, r, g, b, i, f, p, q, t;
			if ( typeof hsv !== 'object' || hsv.h === undef || hsv.s === undef || hsv.v === undef )
				return this._error();

			this._hsv = hsv; // store it
			this._hSpace = 'hsv'; // implicit

			h = hsv.h / 360; s = hsv.s / 100; v = hsv.v / 100;
			i = Math.floor( h * 6 );
			f = h * 6 - i;
			p = v * ( 1 - s );
			q = v * ( 1 - f * s );
			t = v * ( 1 - ( 1 - f ) * s );

			switch( i % 6 ) {
				case 0:
					r = v; g = t; b = p;
					break;
				case 1:
					r = q; g = v; b = p;
					break;
				case 2:
					r = p; g = v; b = t;
					break;
				case 3:
					r = p; g = q; b = v;
					break;
				case 4:
					r = t; g = p; b = v;
					break;
				case 5:
					r = v; g = p; b = q;
					break;
			}

			return this.fromRgb( {
				r: r * 255,
				g: g * 255,
				b: b * 255
			}, true ); // true preserves hue/sat

		},
		// everything comes down to fromInt
		fromInt: function( color, preserve ) {
			this._color = parseInt( color, 10 );

			if ( isNaN( this._color ) )
				this._color = 0;

			// let's coerce things
			if ( this._color > 16777215 )
				this._color = 16777215;
			else if ( this._color < 0 )
				this._color = 0;

			// let's not do weird things
			if ( preserve === undef ) {
				this._hsv.h = this._hsv.s = this._hsl.h = this._hsl.s = 0;
			}
			// EVENT GOES HERE
			return this;
		},

		hue2rgb: function( p, q, t ) {
			if ( t < 0 ) {
				t += 1;
			}
			if ( t > 1 ) {
				t -= 1;
			}
			if ( t < 1/6 ) {
				return p + ( q - p ) * 6 * t;
			}
			if ( t < 1/2 ) {
				return q;
			}
			if ( t < 2/3 ) {
				return p + ( q - p ) * ( 2/3 - t ) * 6;
			}
			return p;
		},

		toString: function() {
			var hex = parseInt( this._color, 10 ).toString( 16 );
			if ( this.error )
				return '';
			// maybe left pad it
			if ( hex.length < 6 ) {
				for (var i = 6 - hex.length - 1; i >= 0; i--) {
					hex = '0' + hex;
				}
			}
			return '#' + hex;
		},

		toCSS: function( type, alpha ) {
			type = type || 'hex';
			alpha = parseFloat( alpha || this._alpha );
			switch ( type ) {
				case 'rgb':
				case 'rgba':
					var rgb = this.toRgb();
					if ( alpha < 1 ) {
						return 'rgba( ' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + alpha + ' )';
					}
					else {
						return 'rgb( ' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ' )';
					}
					break;
				case 'hsl':
				case 'hsla':
					var hsl = this.toHsl();
					if ( alpha < 1 ) {
						return 'hsla( ' + hsl.h + ', ' + hsl.s + '%, ' + hsl.l + '%, ' + alpha + ' )';
					}
					else {
						return 'hsl( ' + hsl.h + ', ' + hsl.s + '%, ' + hsl.l + '% )';
					}
					break;
				default:
					return this.toString();
			}
		},

		toRgb: function() {
			return {
				r: 255 & ( this._color >> 16 ),
				g: 255 & ( this._color >> 8 ),
				b: 255 & ( this._color )
			};
		},

		toHsl: function() {
			var rgb = this.toRgb();
			var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
			var max = Math.max( r, g, b ), min = Math.min( r, g, b );
			var h, s, l = ( max + min ) / 2;

			if ( max === min ) {
				h = s = 0; // achromatic
			} else {
				var d = max - min;
				s = l > 0.5 ? d / ( 2 - max - min ) : d / ( max + min );
				switch ( max ) {
					case r: h = ( g - b ) / d + ( g < b ? 6 : 0 );
						break;
					case g: h = ( b - r ) / d + 2;
						break;
					case b: h = ( r - g ) / d + 4;
						break;
				}
				h /= 6;
			}

			// maintain hue & sat if we've been manipulating things in the HSL space.
			h = Math.round( h * 360 );
			if ( h === 0 && this._hsl.h !== h ) {
				h = this._hsl.h;
			}
			s = Math.round( s * 100 );
			if ( s === 0 && this._hsl.s ) {
				s = this._hsl.s;
			}

			return {
				h: h,
				s: s,
				l: Math.round( l * 100 )
			};

		},

		toHsv: function() {
			var rgb = this.toRgb();
			var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
			var max = Math.max( r, g, b ), min = Math.min( r, g, b );
			var h, s, v = max;
			var d = max - min;
			s = max === 0 ? 0 : d / max;

			if ( max === min ) {
				h = s = 0; // achromatic
			} else {
				switch( max ){
					case r:
						h = ( g - b ) / d + ( g < b ? 6 : 0 );
						break;
					case g:
						h = ( b - r ) / d + 2;
						break;
					case b:
						h = ( r - g ) / d + 4;
						break;
				}
				h /= 6;
			}

			// maintain hue & sat if we've been manipulating things in the HSV space.
			h = Math.round( h * 360 );
			if ( h === 0 && this._hsv.h !== h ) {
				h = this._hsv.h;
			}
			s = Math.round( s * 100 );
			if ( s === 0 && this._hsv.s ) {
				s = this._hsv.s;
			}

			return {
				h: h,
				s: s,
				v: Math.round( v * 100 )
			};
		},

		toInt: function() {
			return this._color;
		},

		toIEOctoHex: function() {
			// AARRBBGG
			var hex = this.toString();
			var AA = parseInt( 255 * this._alpha, 10 ).toString(16);
			if ( AA.length === 1 ) {
				AA = '0' + AA;
			}
			return '#' + AA + hex.replace(/^#/, '' );
		},

		// http://www.w3.org/TR/2008/REC-WCAG20-20081211/#relativeluminancedef
		toLuminosity: function() {
			var rgb = this.toRgb();
			var lum = {};
			for ( var i in rgb ) {
				if ( ! rgb.hasOwnProperty( i ) ) {
					continue;
				}
				var chan = rgb[ i ] / 255;
				lum[ i ] = ( chan <= 0.03928 ) ? chan / 12.92 : Math.pow( ( ( chan + 0.055 ) / 1.055 ), 2.4 );
			}

			return 0.2126 * lum.r + 0.7152 * lum.g + 0.0722 * lum.b;
		},

		// http://www.w3.org/TR/2008/REC-WCAG20-20081211/#contrast-ratiodef
		getDistanceLuminosityFrom: function( color ) {
			if ( ! ( color instanceof Color ) ) {
				throw 'getDistanceLuminosityFrom requires a Color object';
			}
			var lum1 = this.toLuminosity();
			var lum2 = color.toLuminosity();
			if ( lum1 > lum2 ) {
				return ( lum1 + 0.05 ) / ( lum2 + 0.05 );
			}
			else {
				return ( lum2 + 0.05 ) / ( lum1 + 0.05 );
			}
		},

		getMaxContrastColor: function() {
			var withBlack = this.getDistanceLuminosityFrom( new Color( '#000' ) );
			var withWhite = this.getDistanceLuminosityFrom( new Color( '#fff' ) );
			var hex = ( withBlack >= withWhite ) ? '#000' : '#fff';
			return new Color( hex );
		},

		getReadableContrastingColor: function( bgColor, minContrast ) {
			if ( ! ( bgColor instanceof Color ) ) {
				return this;
			}

			// you shouldn't use less than 5, but you might want to.
			var targetContrast = ( minContrast === undef ) ? 5 : minContrast,
				contrast = bgColor.getDistanceLuminosityFrom( this ),
				maxContrastColor, maxContrast, incr;

			// if we have sufficient contrast already, cool
			if ( contrast >= targetContrast ) {
				return this;
			}


			maxContrastColor = bgColor.getMaxContrastColor();
			maxContrast = maxContrastColor.getDistanceLuminosityFrom( bgColor );

			// if current max contrast is less than the target contrast, we had wishful thinking.
			// still, go max
			if ( maxContrast <= targetContrast ) {
				return maxContrastColor;
			}

			incr = ( 0 === maxContrastColor.toInt() ) ? -1 : 1;
			while ( contrast < targetContrast ) {
				this.l( incr, true ); // 2nd arg turns this into an incrementer
				contrast = this.getDistanceLuminosityFrom( bgColor );
				// infininite loop prevention: you never know.
				if ( this._color === 0 || this._color === 16777215 ) {
					break;
				}
			}

			return this;
		},

		a: function( val ) {
			if ( val === undef )
				return this._alpha;

			var a = parseFloat( val );

			if ( isNaN( a ) )
				return this._error();

			this._alpha = a;
			return this;
		},

		// TRANSFORMS

		darken: function( amount ) {
			amount = amount || 5;
			return this.l( - amount, true );
		},

		lighten: function( amount ) {
			amount = amount || 5;
			return this.l( amount, true );
		},

		saturate: function( amount ) {
			amount = amount || 15;
			return this.s( amount, true );
		},

		desaturate: function( amount ) {
			amount = amount || 15;
			return this.s( - amount, true );
		},

		toGrayscale: function() {
			return this.setHSpace('hsl').s( 0 );
		},

		getComplement: function() {
			return this.h( 180, true );
		},

		getSplitComplement: function( step ) {
			step = step || 1;
			var incr = 180 + ( step * 30 );
			return this.h( incr, true );
		},

		getAnalog: function( step ) {
			step = step || 1;
			var incr = step * 30;
			return this.h( incr, true );
		},

		getTetrad: function( step ) {
			step = step || 1;
			var incr = step * 60;
			return this.h( incr, true );
		},

		getTriad: function( step ) {
			step = step || 1;
			var incr = step * 120;
			return this.h( incr, true );
		},

		_partial: function( key ) {
			var prop = shortProps[key];
			return function( val, incr ) {
				var color = this._spaceFunc('to', prop.space);

				// GETTER
				if ( val === undef )
					return color[key];

				// INCREMENT
				if ( incr === true )
					val = color[key] + val;

				// MOD & RANGE
				if ( prop.mod )
					val = val % prop.mod;
				if ( prop.range )
					val = ( val < prop.range[0] ) ? prop.range[0] : ( val > prop.range[1] ) ? prop.range[1] : val;

				// NEW VALUE
				color[key] = val;

				return this._spaceFunc('from', prop.space, color);
			};
		},

		_spaceFunc: function( dir, s, val ) {
			var space = s || this._hSpace,
				funcName = dir + space.charAt(0).toUpperCase() + space.substr(1);
			return this[funcName](val);
		}
	};

	var shortProps = {
		h: {
			mod: 360
		},
		s: {
			range: [0,100]
		},
		l: {
			space: 'hsl',
			range: [0,100]
		},
		v: {
			space: 'hsv',
			range: [0,100]
		},
		r: {
			space: 'rgb',
			range: [0,255]
		},
		g: {
			space: 'rgb',
			range: [0,255]
		},
		b: {
			space: 'rgb',
			range: [0,255]
		}
	};

	for ( var key in shortProps ) {
		if ( shortProps.hasOwnProperty( key ) )
			Color.fn[key] = Color.fn._partial(key);
	}

	// play nicely with Node + browser
	if ( typeof exports === 'object' )
		module.exports = Color;
	else
		global.Color = Color;

}(this));
