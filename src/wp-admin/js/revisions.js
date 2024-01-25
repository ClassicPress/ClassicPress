/**
 * @file Revisions interface functions, Backbone classes and
 * the revisions.php document.ready bootstrap.
 *
 * @output wp-admin/js/revisions.js
 */

/* global isRtl */

window.wp = window.wp || {};

(function($) {

	/*
	 * Native HTML5 range sliders
	 *
	 * Lays one slider over another to create illusion of two handles on one track
	 *
	 * @since CP-2.1.0
	 */
	var list, revisions, frame,
		fromSlider = document.getElementById( 'from-slider' ),
		toSlider = document.getElementById( 'to-slider' ),
		fromSliderDiv = document.querySelector( '.from-slider-wrapper' ),
		ticksOptions = document.querySelectorAll( '#ticks option' ),
		url = location.href;

	// Track changes in From slider
	fromSlider.addEventListener( 'input', function() {
		// In Compare Two mode, ensure lower value is not moved to be equal to or greater than higher value
		if ( isVisible( fromSlider ) && fromSlider.value >= toSlider.value ) {
			fromSlider.value = parseInt( toSlider.value, 10 ) - 1;
			fromSlider.setAttribute( 'value', fromSlider.value );
		}

		// Call appropriate revisions: frame is set on initialization at line 180
		frame.set( {
			from: isVisible( fromSlider ) ? frame.revisions._byId[ list[ fromSlider.value ] ] : frame.revisions._byId[ list[ 0 ] ],
			to: frame.revisions._byId[ list[ toSlider.value ] ]
		} );

		// Update URL
		history.pushState( 'Title', '', url.replace( parseInt( url.substring( url.indexOf( '=' ) + 1 ), 10 ).toString(), list[ fromSlider.value ] ) );
	} );

	// Track changes in To slider
	toSlider.addEventListener( 'input', function() {
		// In Compare Two mode, ensure higher value is not moved to be equal to or less than lower value
		if ( isVisible( fromSlider ) && toSlider.value <= fromSlider.value ) {
			toSlider.value = parseInt( fromSlider.value, 10 ) + 1;
			toSlider.setAttribute( 'value', toSlider.value );
		}

		// Call appropriate revisions: frame is set on initialization at line 180
		frame.set( {
			from: isVisible( fromSlider ) ? frame.revisions._byId[ list[ fromSlider.value ] ] : frame.revisions._byId[ list[ parseInt( toSlider.value ) - 1 ] ],
			to: frame.revisions._byId[ list[ toSlider.value ] ]
		} );

		// Update URL
		history.pushState( 'Title', '', url.replace( url.split( '&to=' )[ 1 ], list[ toSlider.value ] ) );
	} );

	// Update slider on external change
	document.addEventListener( 'change', function() {
		url = location.href;
		toSlider.setAttribute( 'value', list.indexOf( parseInt( url.substring( url.indexOf( '&to=' ) + 4 ), 10 ).toString() ) );
		if ( document.querySelector( '.compare-two-revisions' ).checked ) {
			fromSliderDiv.style.display = 'inline';
			fromSlider.setAttribute( 'value', parseInt( toSlider.value, 10 ) - 1 );
		} else {
			fromSliderDiv.style.display = 'none';
		}
	} );

	// Turn comma-separated list of revision IDs into array
	list = document.getElementById( 'revisions-list' ).value.split( ', ' );

	// Get initial integer in query string for either mode, then convert back to string
	fromSlider.value = list.indexOf( parseInt( url.substring( url.indexOf( '=' ) + 1 ), 10 ).toString() );
	fromSlider.setAttribute( 'value', fromSlider.value );

	// Set higher value slider if in Compare Two mode
	if ( url.includes( '&to=' ) ) {
		fromSliderDiv.style.display = 'inline';
		fromSlider.value = list.indexOf( location.href.split( '&from=' )[ 1 ] );
		fromSlider.setAttribute( 'value', fromSlider.value );
	}

	// Update slider after Previous or Next button pressed
	document.addEventListener( 'click', function( e ) {
		if ( e.target.className === 'button' ) {
			if ( e.target.parentNode.className === 'revisions-previous' ) {
				toSlider.value = parseInt( toSlider.value, 10 ) - 1;
			} else {
				toSlider.value = parseInt( toSlider.value, 10 ) + 1;
			}
			toSlider.setAttribute( 'value', toSlider.value );
		}
	} );

	// Provide fuller info about a revision when hovering over its summary
	ticksOptions.forEach( function( option ) {
		var tooltip = document.getElementById( 'current-tooltip' );

		option.addEventListener( 'mouseover', function() {
			tooltip.style.backgroundColor = '#fff';
			tooltip.innerHTML = option.dataset.tooltip.replace( '{{', '<span style="color:#d63638">' ).replace( '}}', '</span>' ).replace( '[[', '<strong>' ).replace( ']]', '</strong><br>' );
		} );
		option.addEventListener( 'mouseout', function() {
			tooltip.style.backgroundColor = 'transparent';
			tooltip.innerHTML = '';
		} );
	} );

	/*
	 * Helper function copied from jQuery
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

	/**
	 * Expose the module in window.wp.revisions.
	 */
	revisions = wp.revisions = { model: {}, view: {}, controller: {} };

	// Link post revisions data served from the back end.
	revisions.settings = window._wpRevisionsSettings || {};

	// For debugging.
	revisions.debug = false;

	/**
	 * wp.revisions.log
	 *
	 * A debugging utility for revisions. Works only when a
	 * debug flag is on and the browser supports it.
	 */
	revisions.log = function() {
		if ( window.console && revisions.debug ) {
			window.console.log.apply( window.console, arguments );
		}
	};

	// Handy functions to help with positioning.
	$.fn.allOffsets = function() {
		var offset = this.offset() || {top: 0, left: 0}, win = $(window);
		return _.extend( offset, {
			right:  win.width()  - offset.left - this.outerWidth(),
			bottom: win.height() - offset.top  - this.outerHeight()
		});
	};

	$.fn.allPositions = function() {
		var position = this.position() || {top: 0, left: 0}, parent = this.parent();
		return _.extend( position, {
			right:  parent.outerWidth()  - position.left - this.outerWidth(),
			bottom: parent.outerHeight() - position.top  - this.outerHeight()
		});
	};

	/**
	 * ========================================================================
	 * MODELS
	 * ========================================================================
	 */
	revisions.model.Slider = Backbone.Model.extend({
		defaults: {
			value: null,
			values: null,
			min: 0,
			max: 1,
			step: 1,
			range: false,
			compareTwoMode: false
		},

		initialize: function( options ) {
			this.frame = frame = options.frame;
			this.revisions = options.revisions;

			// Listen for changes to the revisions or mode from outside.
			this.listenTo( this.frame, 'update:revisions', this.receiveRevisions );
			this.listenTo( this.frame, 'change:compareTwoMode', this.updateMode );

			// Listen for internal changes.
			this.on( 'change:compareTwoMode', this.updateSliderSettings );
			this.on( 'update:revisions', this.updateSliderSettings );

			this.set({
				max: this.revisions.length - 1,
				compareTwoMode: this.frame.get('compareTwoMode'),
				from: this.frame.get('from'),
				to: this.frame.get('to')
			});
			this.updateSliderSettings();
		},

		getSliderValue: function( a, b ) {
			return isRtl ? this.revisions.length - this.revisions.indexOf( this.get(a) ) - 1 : this.revisions.indexOf( this.get(b) );
		},

		updateSliderSettings: function() {
			if ( this.get('compareTwoMode') ) {
				this.set({
					values: [
						this.getSliderValue( 'to', 'from' ),
						this.getSliderValue( 'from', 'to' )
					],
					value: null,
					range: true // Ensures handles cannot cross.
				});
			} else {
				this.set({
					value: this.getSliderValue( 'to', 'to' ),
					values: null,
					range: false
				});
			}
			this.trigger( 'update:slider' );
		},

		// Called when `compareTwoMode` changes.
		updateMode: function( model, value ) {
			this.set({ compareTwoMode: value });
		},

		// Receives revisions changes from outside the model.
		receiveRevisions: function( from, to ) {
			// Bail if nothing changed.
			if ( this.get('from') === from && this.get('to') === to ) {
				return;
			}

			this.set({ from: from, to: to }, { silent: true });
			this.trigger( 'update:revisions', from, to );
		}

	});

	revisions.model.Tooltip = Backbone.Model.extend({
		defaults: {
			revision: null,
			offset: {},
			hovering: false, // Whether the mouse is hovering.
			scrubbing: false // Whether the mouse is scrubbing.
		},

		initialize: function( options ) {
			this.frame = options.frame;
			this.revisions = options.revisions;
			this.slider = options.slider;

			this.listenTo( this.slider, 'change:scrubbing', this.setScrubbing );
		},


		updateRevision: function( revision ) {
			this.set({ revision: revision });
		},

		setHovering: function( model, value ) {
			this.set({ hovering: value });
		},

		setScrubbing: function( model, value ) {
			this.set({ scrubbing: value });
		}
	});

	revisions.model.Revision = Backbone.Model.extend({});

	/**
	 * wp.revisions.model.Revisions
	 *
	 * A collection of post revisions.
	 */
	revisions.model.Revisions = Backbone.Collection.extend({
		model: revisions.model.Revision,

		initialize: function() {
			_.bindAll( this, 'next', 'prev' );
		},

		next: function( revision ) {
			var index = this.indexOf( revision );

			if ( index !== -1 && index !== this.length - 1 ) {
				return this.at( index + 1 );
			}
		},

		prev: function( revision ) {
			var index = this.indexOf( revision );

			if ( index !== -1 && index !== 0 ) {
				return this.at( index - 1 );
			}
		}
	});

	revisions.model.Field = Backbone.Model.extend({});

	revisions.model.Fields = Backbone.Collection.extend({
		model: revisions.model.Field
	});

	revisions.model.Diff = Backbone.Model.extend({
		initialize: function() {
			var fields = this.get('fields');
			this.unset('fields');

			this.fields = new revisions.model.Fields( fields );
		}
	});

	revisions.model.Diffs = Backbone.Collection.extend({
		initialize: function( models, options ) {
			_.bindAll( this, 'getClosestUnloaded' );
			this.loadAll = _.once( this._loadAll );
			this.revisions = options.revisions;
			this.postId = options.postId;
			this.requests  = {};
		},

		model: revisions.model.Diff,

		ensure: function( id, context ) {
			var diff     = this.get( id ),
				request  = this.requests[ id ],
				deferred = $.Deferred(),
				ids      = {},
				from     = id.split(':')[0],
				to       = id.split(':')[1];
			ids[id] = true;

			wp.revisions.log( 'ensure', id );

			this.trigger( 'ensure', ids, from, to, deferred.promise() );

			if ( diff ) {
				deferred.resolveWith( context, [ diff ] );
			} else {
				this.trigger( 'ensure:load', ids, from, to, deferred.promise() );
				_.each( ids, _.bind( function( id ) {
					// Remove anything that has an ongoing request.
					if ( this.requests[ id ] ) {
						delete ids[ id ];
					}
					// Remove anything we already have.
					if ( this.get( id ) ) {
						delete ids[ id ];
					}
				}, this ) );
				if ( ! request ) {
					// Always include the ID that started this ensure.
					ids[ id ] = true;
					request   = this.load( _.keys( ids ) );
				}

				request.done( _.bind( function() {
					deferred.resolveWith( context, [ this.get( id ) ] );
				}, this ) ).fail( _.bind( function() {
					deferred.reject();
				}) );
			}

			return deferred.promise();
		},

		// Returns an array of proximal diffs.
		getClosestUnloaded: function( ids, centerId ) {
			var self = this;
			return _.chain([0].concat( ids )).initial().zip( ids ).sortBy( function( pair ) {
				return Math.abs( centerId - pair[1] );
			}).map( function( pair ) {
				return pair.join(':');
			}).filter( function( diffId ) {
				return _.isUndefined( self.get( diffId ) ) && ! self.requests[ diffId ];
			}).value();
		},

		_loadAll: function( allRevisionIds, centerId, num ) {
			var self = this, deferred = $.Deferred(),
				diffs = _.first( this.getClosestUnloaded( allRevisionIds, centerId ), num );
			if ( _.size( diffs ) > 0 ) {
				this.load( diffs ).done( function() {
					self._loadAll( allRevisionIds, centerId, num ).done( function() {
						deferred.resolve();
					});
				}).fail( function() {
					if ( 1 === num ) { // Already tried 1. This just isn't working. Give up.
						deferred.reject();
					} else { // Request fewer diffs this time.
						self._loadAll( allRevisionIds, centerId, Math.ceil( num / 2 ) ).done( function() {
							deferred.resolve();
						});
					}
				});
			} else {
				deferred.resolve();
			}
			return deferred;
		},

		load: function( comparisons ) {
			wp.revisions.log( 'load', comparisons );
			// Our collection should only ever grow, never shrink, so `remove: false`.
			return this.fetch({ data: { compare: comparisons }, remove: false }).done( function() {
				wp.revisions.log( 'load:complete', comparisons );
			});
		},

		sync: function( method, model, options ) {
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'get-revision-diffs',
					post_id: this.postId
				});

				var deferred = wp.ajax.send( options ),
					requests = this.requests;

				// Record that we're requesting each diff.
				if ( options.data.compare ) {
					_.each( options.data.compare, function( id ) {
						requests[ id ] = deferred;
					});
				}

				// When the request completes, clear the stored request.
				deferred.always( function() {
					if ( options.data.compare ) {
						_.each( options.data.compare, function( id ) {
							delete requests[ id ];
						});
					}
				});

				return deferred;

			// Otherwise, fall back to `Backbone.sync()`.
			} else {
				return Backbone.Model.prototype.sync.apply( this, arguments );
			}
		}
	});


	/**
	 * wp.revisions.model.FrameState
	 *
	 * The frame state.
	 *
	 * @see wp.revisions.view.Frame
	 *
	 * @param {object}                    attributes        Model attributes - none are required.
	 * @param {object}                    options           Options for the model.
	 * @param {revisions.model.Revisions} options.revisions A collection of revisions.
	 */
	revisions.model.FrameState = Backbone.Model.extend({
		defaults: {
			loading: false,
			error: false,
			compareTwoMode: false
		},

		initialize: function( attributes, options ) {
			var state = this.get( 'initialDiffState' );
			_.bindAll( this, 'receiveDiff' );
			this._debouncedEnsureDiff = _.debounce( this._ensureDiff, 200 );

			this.revisions = options.revisions;

			this.diffs = new revisions.model.Diffs( [], {
				revisions: this.revisions,
				postId: this.get( 'postId' )
			} );

			// Set the initial diffs collection.
			this.diffs.set( this.get( 'diffData' ) );

			// Set up internal listeners.
			this.listenTo( this, 'change:from', this.changeRevisionHandler );
			this.listenTo( this, 'change:to', this.changeRevisionHandler );
			this.listenTo( this, 'change:compareTwoMode', this.changeMode );
			this.listenTo( this, 'update:revisions', this.updatedRevisions );
			this.listenTo( this.diffs, 'ensure:load', this.updateLoadingStatus );
			this.listenTo( this, 'update:diff', this.updateLoadingStatus );

			// Set the initial revisions, baseUrl, and mode as provided through attributes.

			this.set( {
				to : this.revisions.get( state.to ),
				from : this.revisions.get( state.from ),
				compareTwoMode : state.compareTwoMode
			} );
		},

		updateLoadingStatus: function() {
			this.set( 'error', false );
			this.set( 'loading', ! this.diff() );
		},

		changeMode: function( model, value ) {
			var toIndex = this.revisions.indexOf( this.get( 'to' ) );

			// If we were on the first revision before switching to two-handled mode,
			// bump the 'to' position over one.
			if ( value && 0 === toIndex ) {
				this.set({
					from: this.revisions.at( toIndex ),
					to:   this.revisions.at( toIndex + 1 )
				});
			}

			// When switching back to single-handled mode, reset 'from' model to
			// one position before the 'to' model.
			if ( ! value && 0 !== toIndex ) { // '! value' means switching to single-handled mode.
				this.set({
					from: this.revisions.at( toIndex - 1 ),
					to:   this.revisions.at( toIndex )
				});
			}
		},

		updatedRevisions: function( from, to ) {
			if ( this.get( 'compareTwoMode' ) ) {
				// @todo Compare-two loading strategy.
			} else {
				this.diffs.loadAll( this.revisions.pluck('id'), to.id, 40 );
			}
		},

		// Fetch the currently loaded diff.
		diff: function() {
			return this.diffs.get( this._diffId );
		},

		/*
		 * So long as `from` and `to` are changed at the same time, the diff
		 * will only be updated once. This is because Backbone updates all of
		 * the changed attributes in `set`, and then fires the `change` events.
		 */
		updateDiff: function( options ) {
			var from, to, diffId, diff;

			options = options || {};
			from = this.get('from');
			to = this.get('to');
			diffId = ( from ? from.id : 0 ) + ':' + to.id;

			// Check if we're actually changing the diff id.
			if ( this._diffId === diffId ) {
				return $.Deferred().reject().promise();
			}

			this._diffId = diffId;
			this.trigger( 'update:revisions', from, to );

			diff = this.diffs.get( diffId );

			// If we already have the diff, then immediately trigger the update.
			if ( diff ) {
				this.receiveDiff( diff );
				return $.Deferred().resolve().promise();
			// Otherwise, fetch the diff.
			} else {
				if ( options.immediate ) {
					return this._ensureDiff();
				} else {
					this._debouncedEnsureDiff();
					return $.Deferred().reject().promise();
				}
			}
		},

		// A simple wrapper around `updateDiff` to prevent the change event's
		// parameters from being passed through.
		changeRevisionHandler: function() {
			this.updateDiff();
		},

		receiveDiff: function( diff ) {
			// Did we actually get a diff?
			if ( _.isUndefined( diff ) || _.isUndefined( diff.id ) ) {
				this.set({
					loading: false,
					error: true
				});
			} else if ( this._diffId === diff.id ) { // Make sure the current diff didn't change.
				this.trigger( 'update:diff', diff );
			}
		},

		_ensureDiff: function() {
			return this.diffs.ensure( this._diffId, this ).always( this.receiveDiff );
		}
	});


	/**
	 * ========================================================================
	 * VIEWS
	 * ========================================================================
	 */

	/**
	 * wp.revisions.view.Frame
	 *
	 * Top level frame that orchestrates the revisions experience.
	 *
	 * @param {object}                     options       The options hash for the view.
	 * @param {revisions.model.FrameState} options.model The frame state model.
	 */
	revisions.view.Frame = wp.Backbone.View.extend({
		className: 'revisions',
		template: wp.template('revisions-frame'),

		initialize: function() {
			this.listenTo( this.model, 'update:diff', this.renderDiff );
			this.listenTo( this.model, 'change:compareTwoMode', this.updateCompareTwoMode );
			this.listenTo( this.model, 'change:loading', this.updateLoadingStatus );
			this.listenTo( this.model, 'change:error', this.updateErrorStatus );

			this.views.set( '.revisions-control-frame', new revisions.view.Controls({
				model: this.model
			}) );
		},

		render: function() {
			wp.Backbone.View.prototype.render.apply( this, arguments );

			$('html').css( 'overflow-y', 'scroll' );
			$('#wpbody-content .wrap').append( this.el );
			this.updateCompareTwoMode();
			this.renderDiff( this.model.diff() );
			this.views.ready();

			return this;
		},

		renderDiff: function( diff ) {
			this.views.set( '.revisions-diff-frame', new revisions.view.Diff({
				model: diff
			}) );
		},

		updateLoadingStatus: function() {
			this.$el.toggleClass( 'loading', this.model.get('loading') );
		},

		updateErrorStatus: function() {
			this.$el.toggleClass( 'diff-error', this.model.get('error') );
		},

		updateCompareTwoMode: function() {
			this.$el.toggleClass( 'comparing-two-revisions', this.model.get('compareTwoMode') );
		}
	});

	/**
	 * wp.revisions.view.Controls
	 *
	 * The controls view.
	 *
	 * Contains the revision slider, previous/next buttons, the meta info and the compare checkbox.
	 */
	revisions.view.Controls = wp.Backbone.View.extend({
		className: 'revisions-controls',

		initialize: function() {
			_.bindAll( this, 'setWidth' );

			// Add the button view.
			this.views.add( new revisions.view.Buttons({
				model: this.model
			}) );

			// Add the checkbox view.
			this.views.add( new revisions.view.Checkbox({
				model: this.model
			}) );

			// Prep the slider model.
			var slider = new revisions.model.Slider({
				frame: this.model,
				revisions: this.model.revisions
			});

			// Add the slider view.
			this.views.add( new revisions.view.Slider({
				model: slider
			}) );

			// Add the Metabox view.
			this.views.add( new revisions.view.Metabox({
				model: this.model
			}) );
		},

		ready: function() {
			this.top = this.$el.offset().top;
			this.window = $(window);
			this.window.on( 'scroll.wp.revisions', {controls: this}, function(e) {
				var controls  = e.data.controls,
					container = controls.$el.parent(),
					scrolled  = controls.window.scrollTop(),
					frame     = controls.views.parent;

				if ( scrolled >= controls.top ) {
					if ( ! frame.$el.hasClass('pinned') ) {
						controls.setWidth();
						container.css('height', container.height() + 'px' );
						controls.window.on('resize.wp.revisions.pinning click.wp.revisions.pinning', {controls: controls}, function(e) {
							e.data.controls.setWidth();
						});
					}
					frame.$el.addClass('pinned');
				} else if ( frame.$el.hasClass('pinned') ) {
					controls.window.off('.wp.revisions.pinning');
					controls.$el.css('width', 'auto');
					frame.$el.removeClass('pinned');
					container.css('height', 'auto');
					controls.top = controls.$el.offset().top;
				} else {
					controls.top = controls.$el.offset().top;
				}
			});
		},

		setWidth: function() {
			this.$el.css('width', this.$el.parent().width() + 'px');
		}
	});

	// The metabox view.
	revisions.view.Metabox = wp.Backbone.View.extend({
		className: 'revisions-meta',

		initialize: function() {
			// Add the 'from' view.
			this.views.add( new revisions.view.MetaFrom({
				model: this.model,
				className: 'diff-meta diff-meta-from'
			}) );

			// Add the 'to' view.
			this.views.add( new revisions.view.MetaTo({
				model: this.model
			}) );
		}
	});

	// The revision meta view (to be extended).
	revisions.view.Meta = wp.Backbone.View.extend({
		template: wp.template('revisions-meta'),

		events: {
			'click .restore-revision': 'restoreRevision'
		},

		initialize: function() {
			this.listenTo( this.model, 'update:revisions', this.render );
		},

		prepare: function() {
			return _.extend( this.model.toJSON()[this.type] || {}, {
				type: this.type
			});
		},

		restoreRevision: function() {
			document.location = this.model.get('to').attributes.restoreUrl;
		}
	});

	// The revision meta 'from' view.
	revisions.view.MetaFrom = revisions.view.Meta.extend({
		className: 'diff-meta diff-meta-from',
		type: 'from'
	});

	// The revision meta 'to' view.
	revisions.view.MetaTo = revisions.view.Meta.extend({
		className: 'diff-meta diff-meta-to',
		type: 'to'
	});

	// The checkbox view.
	revisions.view.Checkbox = wp.Backbone.View.extend({
		className: 'revisions-checkbox',
		template: wp.template('revisions-checkbox'),

		events: {
			'click .compare-two-revisions': 'compareTwoToggle'
		},

		initialize: function() {
			this.listenTo( this.model, 'change:compareTwoMode', this.updateCompareTwoMode );
		},

		ready: function() {
			if ( this.model.revisions.length < 3 ) {
				$('.revision-toggle-compare-mode').hide();
			}
		},

		updateCompareTwoMode: function() {
			this.$('.compare-two-revisions').prop( 'checked', this.model.get('compareTwoMode') );
		},

		// Toggle the compare two mode feature when the compare two checkbox is checked.
		compareTwoToggle: function() {
			// Activate compare two mode?
			this.model.set({ compareTwoMode: $('.compare-two-revisions').prop('checked') });
		}
	});

	// The buttons view.
	// Encapsulates all of the configuration for the previous/next buttons.
	revisions.view.Buttons = wp.Backbone.View.extend({
		className: 'revisions-buttons',
		template: wp.template('revisions-buttons'),

		events: {
			'click .revisions-next .button': 'nextRevision',
			'click .revisions-previous .button': 'previousRevision'
		},

		initialize: function() {
			this.listenTo( this.model, 'update:revisions', this.disabledButtonCheck );
		},

		ready: function() {
			this.disabledButtonCheck();
		},

		// Go to a specific model index.
		gotoModel: function( toIndex ) {
			var attributes = {
				to: this.model.revisions.at( toIndex )
			};
			// If we're at the first revision, unset 'from'.
			if ( toIndex ) {
				attributes.from = this.model.revisions.at( toIndex - 1 );
			} else {
				this.model.unset('from', { silent: true });
			}

			this.model.set( attributes );
		},

		// Go to the 'next' revision.
		nextRevision: function() {
			var toIndex = this.model.revisions.indexOf( this.model.get('to') ) + 1;
			this.gotoModel( toIndex );
		},

		// Go to the 'previous' revision.
		previousRevision: function() {
			var toIndex = this.model.revisions.indexOf( this.model.get('to') ) - 1;
			this.gotoModel( toIndex );
		},

		// Check to see if the Previous or Next buttons need to be disabled or enabled.
		disabledButtonCheck: function() {
			var maxVal   = this.model.revisions.length - 1,
				minVal   = 0,
				next     = $('.revisions-next .button'),
				previous = $('.revisions-previous .button'),
				val      = this.model.revisions.indexOf( this.model.get('to') );

			// Disable "Next" button if you're on the last node.
			next.prop( 'disabled', ( maxVal === val ) );

			// Disable "Previous" button if you're on the first node.
			previous.prop( 'disabled', ( minVal === val ) );
		}
	});


	// The slider view.
	revisions.view.Slider = wp.Backbone.View.extend({
		initialize: function() {
			this.listenTo( this.model, 'update:slider', this.applySliderSettings );
		},

		ready: function() {
			this.applySliderSettings();
		},

		applySliderSettings: function() {
			if ( this.model.get('compareTwoMode') ) {
				fromSlider.value = this.model.toJSON().values[0];
				toSlider.value = this.model.toJSON().values[1];
			} else {
				fromSlider.value = this.model.toJSON().value;
			}
		}
	});

	// The diff view.
	// This is the view for the current active diff.
	revisions.view.Diff = wp.Backbone.View.extend({
		className: 'revisions-diff',
		template:  wp.template('revisions-diff'),

		// Generate the options to be passed to the template.
		prepare: function() {
			return _.extend({ fields: this.model.fields.toJSON() }, this.options );
		}
	});

	/**
	 * Initialize the revisions UI for revision.php.
	 */
	revisions.init = function() {
		var state;

		// Bail if the current page is not revision.php.
		if ( ! window.adminpage || 'revision-php' !== window.adminpage ) {
			return;
		}

		state = new revisions.model.FrameState({
			initialDiffState: {
				// wp_localize_script doesn't stringifies ints, so cast them.
				to: parseInt( revisions.settings.to, 10 ),
				from: parseInt( revisions.settings.from, 10 ),
				// wp_localize_script does not allow for top-level booleans so do a comparator here.
				compareTwoMode: ( revisions.settings.compareTwoMode === '1' )
			},
			diffData: revisions.settings.diffData,
			baseUrl: revisions.settings.baseUrl,
			postId: parseInt( revisions.settings.postId, 10 )
		}, {
			revisions: new revisions.model.Revisions( revisions.settings.revisionData )
		});

		revisions.view.frame = new revisions.view.Frame({
			model: state
		}).render();
	};

	$( revisions.init );
}(jQuery));
