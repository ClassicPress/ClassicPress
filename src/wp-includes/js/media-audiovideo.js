(function () {
	'use strict';

	/**
	 * wp.media.model.PostMedia
	 *
	 * Shared model class for audio and video. Updates the model after
	 *   "Add Audio|Video Source" and "Replace Audio|Video" states return
	 *
	 * @memberOf wp.media.model
	 *
	 * @class
	 * @augments Backbone.Model
	 */
	var PostMedia = Backbone.Model.extend(/** @lends wp.media.model.PostMedia.prototype */{
		initialize: function() {
			this.attachment = false;
		},

		setSource: function( attachment ) {
			this.attachment = attachment;
			this.extension = attachment.get( 'filename' ).split('.').pop();

			if ( this.get( 'src' ) && this.extension === this.get( 'src' ).split('.').pop() ) {
				this.unset( 'src' );
			}

			if ( _.contains( wp.media.view.settings.embedExts, this.extension ) ) {
				this.set( this.extension, this.attachment.get( 'url' ) );
			} else {
				this.unset( this.extension );
			}
		},

		changeAttachment: function( attachment ) {
			this.setSource( attachment );

			this.unset( 'src' );
			_.each( _.without( wp.media.view.settings.embedExts, this.extension ), function( ext ) {
				this.unset( ext );
			}, this );
		}
	});

	var postMedia = PostMedia;

	var State = wp.media.controller.State,
		l10n = wp.media.view.l10n,
		AudioDetails;

	/**
	 * wp.media.controller.AudioDetails
	 *
	 * The controller for the Audio Details state
	 *
	 * @memberOf wp.media.controller
	 *
	 * @class
	 * @augments wp.media.controller.State
	 * @augments Backbone.Model
	 */
	AudioDetails = State.extend(/** @lends wp.media.controller.AudioDetails.prototype */{
		defaults: {
			id: 'audio-details',
			toolbar: 'audio-details',
			title: l10n.audioDetailsTitle,
			content: 'audio-details',
			menu: 'audio-details',
			router: false,
			priority: 60
		},

		initialize: function( options ) {
			this.media = options.media;
			State.prototype.initialize.apply( this, arguments );
		}
	});

	var audioDetails = AudioDetails;

	/**
	 * wp.media.controller.VideoDetails
	 *
	 * The controller for the Video Details state
	 *
	 * @memberOf wp.media.controller
	 *
	 * @class
	 * @augments wp.media.controller.State
	 * @augments Backbone.Model
	 */
	var State$1 = wp.media.controller.State,
		l10n$1 = wp.media.view.l10n,
		VideoDetails;

	VideoDetails = State$1.extend(/** @lends wp.media.controller.VideoDetails.prototype */{
		defaults: {
			id: 'video-details',
			toolbar: 'video-details',
			title: l10n$1.videoDetailsTitle,
			content: 'video-details',
			menu: 'video-details',
			router: false,
			priority: 60
		},

		initialize: function( options ) {
			this.media = options.media;
			State$1.prototype.initialize.apply( this, arguments );
		}
	});

	var videoDetails = VideoDetails;

	var Select = wp.media.view.MediaFrame.Select,
		l10n$2 = wp.media.view.l10n,
		MediaDetails;

	/**
	 * wp.media.view.MediaFrame.MediaDetails
	 *
	 * @memberOf wp.media.view.MediaFrame
	 *
	 * @class
	 * @augments wp.media.view.MediaFrame.Select
	 * @augments wp.media.view.MediaFrame
	 * @augments wp.media.view.Frame
	 * @augments wp.media.View
	 * @augments wp.Backbone.View
	 * @augments Backbone.View
	 * @mixes wp.media.controller.StateMachine
	 */
	MediaDetails = Select.extend(/** @lends wp.media.view.MediaFrame.MediaDetails.prototype */{
		defaults: {
			id:      'media',
			url:     '',
			menu:    'media-details',
			content: 'media-details',
			toolbar: 'media-details',
			type:    'link',
			priority: 120
		},

		initialize: function( options ) {
			this.DetailsView = options.DetailsView;
			this.cancelText = options.cancelText;
			this.addText = options.addText;

			this.media = new wp.media.model.PostMedia( options.metadata );
			this.options.selection = new wp.media.model.Selection( this.media.attachment, { multiple: false } );
			Select.prototype.initialize.apply( this, arguments );
		},

		bindHandlers: function() {
			var menu = this.defaults.menu;

			Select.prototype.bindHandlers.apply( this, arguments );

			this.on( 'menu:create:' + menu, this.createMenu, this );
			this.on( 'content:render:' + menu, this.renderDetailsContent, this );
			this.on( 'menu:render:' + menu, this.renderMenu, this );
			this.on( 'toolbar:render:' + menu, this.renderDetailsToolbar, this );
		},

		renderDetailsContent: function() {
			var view = new this.DetailsView({
				controller: this,
				model: this.state().media,
				attachment: this.state().media.attachment
			}).render();

			this.content.set( view );
		},

		renderMenu: function( view ) {
			var lastState = this.lastState(),
				previous = lastState && lastState.id,
				frame = this;

			view.set({
				cancel: {
					text:     this.cancelText,
					priority: 20,
					click:    function() {
						if ( previous ) {
							frame.setState( previous );
						} else {
							frame.close();
						}
					}
				},
				separateCancel: new wp.media.View({
					className: 'separator',
					priority: 40
				})
			});

		},

		setPrimaryButton: function(text, handler) {
			this.toolbar.set( new wp.media.view.Toolbar({
				controller: this,
				items: {
					button: {
						style:    'primary',
						text:     text,
						priority: 80,
						click:    function() {
							var controller = this.controller;
							handler.call( this, controller, controller.state() );
							// Restore and reset the default state.
							controller.setState( controller.options.state );
							controller.reset();
						}
					}
				}
			}) );
		},

		renderDetailsToolbar: function() {
			this.setPrimaryButton( l10n$2.update, function( controller, state ) {
				controller.close();
				state.trigger( 'update', controller.media.toJSON() );
			} );
		},

		renderReplaceToolbar: function() {
			this.setPrimaryButton( l10n$2.replace, function( controller, state ) {
				var attachment = state.get( 'selection' ).single();
				controller.media.changeAttachment( attachment );
				state.trigger( 'replace', controller.media.toJSON() );
			} );
		},

		renderAddSourceToolbar: function() {
			this.setPrimaryButton( this.addText, function( controller, state ) {
				var attachment = state.get( 'selection' ).single();
				controller.media.setSource( attachment );
				state.trigger( 'add-source', controller.media.toJSON() );
			} );
		}
	});

	var mediaDetails = MediaDetails;

	var MediaDetails$1 = wp.media.view.MediaFrame.MediaDetails,
		MediaLibrary = wp.media.controller.MediaLibrary,

		l10n$3 = wp.media.view.l10n,
		AudioDetails$1;

	/**
	 * wp.media.view.MediaFrame.AudioDetails
	 *
	 * @memberOf wp.media.view.MediaFrame
	 *
	 * @class
	 * @augments wp.media.view.MediaFrame.MediaDetails
	 * @augments wp.media.view.MediaFrame.Select
	 * @augments wp.media.view.MediaFrame
	 * @augments wp.media.view.Frame
	 * @augments wp.media.View
	 * @augments wp.Backbone.View
	 * @augments Backbone.View
	 * @mixes wp.media.controller.StateMachine
	 */
	AudioDetails$1 = MediaDetails$1.extend(/** @lends wp.media.view.MediaFrame.AudioDetails.prototype */{
		defaults: {
			id:      'audio',
			url:     '',
			menu:    'audio-details',
			content: 'audio-details',
			toolbar: 'audio-details',
			type:    'link',
			title:    l10n$3.audioDetailsTitle,
			priority: 120
		},

		initialize: function( options ) {
			options.DetailsView = wp.media.view.AudioDetails;
			options.cancelText = l10n$3.audioDetailsCancel;
			options.addText = l10n$3.audioAddSourceTitle;

			MediaDetails$1.prototype.initialize.call( this, options );
		},

		bindHandlers: function() {
			MediaDetails$1.prototype.bindHandlers.apply( this, arguments );

			this.on( 'toolbar:render:replace-audio', this.renderReplaceToolbar, this );
			this.on( 'toolbar:render:add-audio-source', this.renderAddSourceToolbar, this );
		},

		createStates: function() {
			this.states.add([
				new wp.media.controller.AudioDetails( {
					media: this.media
				} ),

				new MediaLibrary( {
					type: 'audio',
					id: 'replace-audio',
					title: l10n$3.audioReplaceTitle,
					toolbar: 'replace-audio',
					media: this.media,
					menu: 'audio-details'
				} ),

				new MediaLibrary( {
					type: 'audio',
					id: 'add-audio-source',
					title: l10n$3.audioAddSourceTitle,
					toolbar: 'add-audio-source',
					media: this.media,
					menu: false
				} )
			]);
		}
	});

	var audioDetails$1 = AudioDetails$1;

	var MediaDetails$2 = wp.media.view.MediaFrame.MediaDetails,
		MediaLibrary$1 = wp.media.controller.MediaLibrary,
		l10n$4 = wp.media.view.l10n,
		VideoDetails$1;

	/**
	 * wp.media.view.MediaFrame.VideoDetails
	 *
	 * @memberOf wp.media.view.MediaFrame
	 *
	 * @class
	 * @augments wp.media.view.MediaFrame.MediaDetails
	 * @augments wp.media.view.MediaFrame.Select
	 * @augments wp.media.view.MediaFrame
	 * @augments wp.media.view.Frame
	 * @augments wp.media.View
	 * @augments wp.Backbone.View
	 * @augments Backbone.View
	 * @mixes wp.media.controller.StateMachine
	 */
	VideoDetails$1 = MediaDetails$2.extend(/** @lends wp.media.view.MediaFrame.VideoDetails.prototype */{
		defaults: {
			id:      'video',
			url:     '',
			menu:    'video-details',
			content: 'video-details',
			toolbar: 'video-details',
			type:    'link',
			title:    l10n$4.videoDetailsTitle,
			priority: 120
		},

		initialize: function( options ) {
			options.DetailsView = wp.media.view.VideoDetails;
			options.cancelText = l10n$4.videoDetailsCancel;
			options.addText = l10n$4.videoAddSourceTitle;

			MediaDetails$2.prototype.initialize.call( this, options );
		},

		bindHandlers: function() {
			MediaDetails$2.prototype.bindHandlers.apply( this, arguments );

			this.on( 'toolbar:render:replace-video', this.renderReplaceToolbar, this );
			this.on( 'toolbar:render:add-video-source', this.renderAddSourceToolbar, this );
			this.on( 'toolbar:render:select-poster-image', this.renderSelectPosterImageToolbar, this );
			this.on( 'toolbar:render:add-track', this.renderAddTrackToolbar, this );
		},

		createStates: function() {
			this.states.add([
				new wp.media.controller.VideoDetails({
					media: this.media
				}),

				new MediaLibrary$1( {
					type: 'video',
					id: 'replace-video',
					title: l10n$4.videoReplaceTitle,
					toolbar: 'replace-video',
					media: this.media,
					menu: 'video-details'
				} ),

				new MediaLibrary$1( {
					type: 'video',
					id: 'add-video-source',
					title: l10n$4.videoAddSourceTitle,
					toolbar: 'add-video-source',
					media: this.media,
					menu: false
				} ),

				new MediaLibrary$1( {
					type: 'image',
					id: 'select-poster-image',
					title: l10n$4.videoSelectPosterImageTitle,
					toolbar: 'select-poster-image',
					media: this.media,
					menu: 'video-details'
				} ),

				new MediaLibrary$1( {
					type: 'text',
					id: 'add-track',
					title: l10n$4.videoAddTrackTitle,
					toolbar: 'add-track',
					media: this.media,
					menu: 'video-details'
				} )
			]);
		},

		renderSelectPosterImageToolbar: function() {
			this.setPrimaryButton( l10n$4.videoSelectPosterImageTitle, function( controller, state ) {
				var urls = [], attachment = state.get( 'selection' ).single();

				controller.media.set( 'poster', attachment.get( 'url' ) );
				state.trigger( 'set-poster-image', controller.media.toJSON() );

				_.each( wp.media.view.settings.embedExts, function (ext) {
					if ( controller.media.get( ext ) ) {
						urls.push( controller.media.get( ext ) );
					}
				} );

				wp.ajax.send( 'set-attachment-thumbnail', {
					data : {
						urls: urls,
						thumbnail_id: attachment.get( 'id' )
					}
				} );
			} );
		},

		renderAddTrackToolbar: function() {
			this.setPrimaryButton( l10n$4.videoAddTrackTitle, function( controller, state ) {
				var attachment = state.get( 'selection' ).single(),
					content = controller.media.get( 'content' );

				if ( -1 === content.indexOf( attachment.get( 'url' ) ) ) {
					content += [
						'<track srclang="en" label="English" kind="subtitles" src="',
						attachment.get( 'url' ),
						'" />'
					].join('');

					controller.media.set( 'content', content );
				}
				state.trigger( 'add-track', controller.media.toJSON() );
			} );
		}
	});

	var videoDetails$1 = VideoDetails$1;

	/* global MediaElementPlayer */
	var AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay,
		$ = jQuery,
		MediaDetails$3;

	/**
	 * wp.media.view.MediaDetails
	 *
	 * @memberOf wp.media.view
	 *
	 * @class
	 * @augments wp.media.view.Settings.AttachmentDisplay
	 * @augments wp.media.view.Settings
	 * @augments wp.media.View
	 * @augments wp.Backbone.View
	 * @augments Backbone.View
	 */
	MediaDetails$3 = AttachmentDisplay.extend(/** @lends wp.media.view.MediaDetails.prototype */{
		initialize: function() {
			_.bindAll(this, 'success');
			this.players = [];
			this.listenTo( this.controller, 'close', wp.media.mixin.unsetPlayers );
			this.on( 'ready', this.setPlayer );
			this.on( 'media:setting:remove', wp.media.mixin.unsetPlayers, this );
			this.on( 'media:setting:remove', this.render );
			this.on( 'media:setting:remove', this.setPlayer );

			AttachmentDisplay.prototype.initialize.apply( this, arguments );
		},

		events: function(){
			return _.extend( {
				'click .remove-setting' : 'removeSetting',
				'change .content-track' : 'setTracks',
				'click .remove-track' : 'setTracks',
				'click .add-media-source' : 'addSource'
			}, AttachmentDisplay.prototype.events );
		},

		prepare: function() {
			return _.defaults({
				model: this.model.toJSON()
			}, this.options );
		},

		/**
		 * Remove a setting's UI when the model unsets it
		 *
		 * @fires wp.media.view.MediaDetails#media:setting:remove
		 *
		 * @param {Event} e
		 */
		removeSetting : function(e) {
			var wrap = $( e.currentTarget ).parent(), setting;
			setting = wrap.find( 'input' ).data( 'setting' );

			if ( setting ) {
				this.model.unset( setting );
				this.trigger( 'media:setting:remove', this );
			}

			wrap.remove();
		},

		/**
		 *
		 * @fires wp.media.view.MediaDetails#media:setting:remove
		 */
		setTracks : function() {
			var tracks = '';

			_.each( this.$('.content-track'), function(track) {
				tracks += $( track ).val();
			} );

			this.model.set( 'content', tracks );
			this.trigger( 'media:setting:remove', this );
		},

		addSource : function( e ) {
			this.controller.lastMime = $( e.currentTarget ).data( 'mime' );
			this.controller.setState( 'add-' + this.controller.defaults.id + '-source' );
		},

		loadPlayer: function () {
			this.players.push( new MediaElementPlayer( this.media, this.settings ) );
			this.scriptXhr = false;
		},

		setPlayer : function() {
			var src;

			if ( this.players.length || ! this.media || this.scriptXhr ) {
				return;
			}

			src = this.model.get( 'src' );

			if ( src && src.indexOf( 'vimeo' ) > -1 && ! ( 'Vimeo' in window ) ) {
				this.scriptXhr = $.getScript( 'https://player.vimeo.com/api/player.js', _.bind( this.loadPlayer, this ) );
			} else {
				this.loadPlayer();
			}
		},

		/**
		 * @abstract
		 */
		setMedia : function() {
			return this;
		},

		success : function(mejs) {
			var autoplay = mejs.attributes.autoplay && 'false' !== mejs.attributes.autoplay;

			if ( 'flash' === mejs.pluginType && autoplay ) {
				mejs.addEventListener( 'canplay', function() {
					mejs.play();
				}, false );
			}

			this.mejs = mejs;
		},

		/**
		 * @returns {media.view.MediaDetails} Returns itself to allow chaining
		 */
		render: function() {
			AttachmentDisplay.prototype.render.apply( this, arguments );

			setTimeout( _.bind( function() {
				this.resetFocus();
			}, this ), 10 );

			this.settings = _.defaults( {
				success : this.success
			}, wp.media.mixin.mejsSettings );

			return this.setMedia();
		},

		resetFocus: function() {
			this.$( '.embed-media-settings' ).scrollTop( 0 );
		}
	},/** @lends wp.media.view.MediaDetails */{
		instances : 0,
		/**
		 * When multiple players in the DOM contain the same src, things get weird.
		 *
		 * @param {HTMLElement} elem
		 * @returns {HTMLElement}
		 */
		prepareSrc : function( elem ) {
			var i = MediaDetails$3.instances++;
			_.each( $( elem ).find( 'source' ), function( source ) {
				source.src = [
					source.src,
					source.src.indexOf('?') > -1 ? '&' : '?',
					'_=',
					i
				].join('');
			} );

			return elem;
		}
	});

	var mediaDetails$1 = MediaDetails$3;

	var MediaDetails$4 = wp.media.view.MediaDetails,
		AudioDetails$2;

	/**
	 * wp.media.view.AudioDetails
	 *
	 * @memberOf wp.media.view
	 *
	 * @class
	 * @augments wp.media.view.MediaDetails
	 * @augments wp.media.view.Settings.AttachmentDisplay
	 * @augments wp.media.view.Settings
	 * @augments wp.media.View
	 * @augments wp.Backbone.View
	 * @augments Backbone.View
	 */
	AudioDetails$2 = MediaDetails$4.extend(/** @lends wp.media.view.AudioDetails.prototype */{
		className: 'audio-details',
		template:  wp.template('audio-details'),

		setMedia: function() {
			var audio = this.$('.wp-audio-shortcode');

			if ( audio.find( 'source' ).length ) {
				if ( audio.is(':hidden') ) {
					audio.show();
				}
				this.media = MediaDetails$4.prepareSrc( audio.get(0) );
			} else {
				audio.hide();
				this.media = false;
			}

			return this;
		}
	});

	var audioDetails$2 = AudioDetails$2;

	var MediaDetails$5 = wp.media.view.MediaDetails,
		VideoDetails$2;

	/**
	 * wp.media.view.VideoDetails
	 *
	 * @memberOf wp.media.view
	 *
	 * @class
	 * @augments wp.media.view.MediaDetails
	 * @augments wp.media.view.Settings.AttachmentDisplay
	 * @augments wp.media.view.Settings
	 * @augments wp.media.View
	 * @augments wp.Backbone.View
	 * @augments Backbone.View
	 */
	VideoDetails$2 = MediaDetails$5.extend(/** @lends wp.media.view.VideoDetails.prototype */{
		className: 'video-details',
		template:  wp.template('video-details'),

		setMedia: function() {
			var video = this.$('.wp-video-shortcode');

			if ( video.find( 'source' ).length ) {
				if ( video.is(':hidden') ) {
					video.show();
				}

				if ( ! video.hasClass( 'youtube-video' ) && ! video.hasClass( 'vimeo-video' ) ) {
					this.media = MediaDetails$5.prepareSrc( video.get(0) );
				} else {
					this.media = video.get(0);
				}
			} else {
				video.hide();
				this.media = false;
			}

			return this;
		}
	});

	var videoDetails$2 = VideoDetails$2;

	var media = wp.media,
		baseSettings = window._wpmejsSettings || {},
		l10n$5 = window._wpMediaViewsL10n || {};

	/**
	 *
	 * @summary Defines the wp.media.mixin object.
	 *
	 * @mixin
	 *
	 * @since WP-4.2.0
	 */
	wp.media.mixin = {
		mejsSettings: baseSettings,

		/**
		 * @summary Pauses and removes all players.
		 *
		 * @since WP-4.2.0
		 *
		 * @return {void}
		 */
		removeAllPlayers: function() {
			var p;

			if ( window.mejs && window.mejs.players ) {
				for ( p in window.mejs.players ) {
					window.mejs.players[p].pause();
					this.removePlayer( window.mejs.players[p] );
				}
			}
		},

		/**
		 * @summary Removes the player.
		 *
		 * Override the MediaElement method for removing a player.
		 * MediaElement tries to pull the audio/video tag out of
		 * its container and re-add it to the DOM.
		 *
		 * @since WP-4.2.0
		 *
		 * @return {void}
		 */
		removePlayer: function(t) {
			var featureIndex, feature;

			if ( ! t.options ) {
				return;
			}

			// invoke features cleanup
			for ( featureIndex in t.options.features ) {
				feature = t.options.features[featureIndex];
				if ( t['clean' + feature] ) {
					try {
						t['clean' + feature](t);
					} catch (e) {}
				}
			}

			if ( ! t.isDynamic ) {
				t.node.remove();
			}

			if ( 'html5' !== t.media.rendererName ) {
				t.media.remove();
			}

			delete window.mejs.players[t.id];

			t.container.remove();
			t.globalUnbind('resize', t.globalResizeCallback);
			t.globalUnbind('keydown', t.globalKeydownCallback);
			t.globalUnbind('click', t.globalClickCallback);
			delete t.media.player;
		},

		/**
		 *
		 * @summary Removes and resets all players.
		 *
		 * Allows any class that has set 'player' to a MediaElementPlayer
		 * instance to remove the player when listening to events.
		 *
		 * Examples: modal closes, shortcode properties are removed, etc.
		 *
		 * @since WP-4.2.0
		 */
		unsetPlayers : function() {
			if ( this.players && this.players.length ) {
				_.each( this.players, function (player) {
					player.pause();
					wp.media.mixin.removePlayer( player );
				} );
				this.players = [];
			}
		}
	};

	/**
	 * @summary Shortcode modeling for playlists.
	 *
	 * @since WP-4.2.0
	 */
	wp.media.playlist = new wp.media.collection({
		tag: 'playlist',
		editTitle : l10n$5.editPlaylistTitle,
		defaults : {
			id: wp.media.view.settings.post.id,
			style: 'light',
			tracklist: true,
			tracknumbers: true,
			images: true,
			artists: true,
			type: 'audio'
		}
	});

	/**
	 * @summary Shortcode modeling for audio.
	 *
	 * `edit()` prepares the shortcode for the media modal.
	 * `shortcode()` builds the new shortcode after an update.
	 *
	 * @namespace
	 *
	 * @since WP-4.2.0
	 */
	wp.media.audio = {
		coerce : wp.media.coerce,

		defaults : {
			id : wp.media.view.settings.post.id,
			src : '',
			loop : false,
			autoplay : false,
			preload : 'none',
			width : 400
		},

		/**
		 * @summary Instantiates a new media object with the next matching shortcode.
		 *
		 * @since WP-4.2.0
		 *
		 * @param {string} data The text to apply the shortcode on.
		 * @returns {wp.media} The media object.
		 */
		edit : function( data ) {
			var frame, shortcode = wp.shortcode.next( 'audio', data ).shortcode;

			frame = wp.media({
				frame: 'audio',
				state: 'audio-details',
				metadata: _.defaults( shortcode.attrs.named, this.defaults )
			});

			return frame;
		},

		/**
		 * @summary Generates an audio shortcode.
		 *
		 * @since WP-4.2.0
		 *
		 * @param {Array} model Array with attributes for the shortcode.
		 * @returns {wp.shortcode} The audio shortcode object.
		 */
		shortcode : function( model ) {
			var content;

			_.each( this.defaults, function( value, key ) {
				model[ key ] = this.coerce( model, key );

				if ( value === model[ key ] ) {
					delete model[ key ];
				}
			}, this );

			content = model.content;
			delete model.content;

			return new wp.shortcode({
				tag: 'audio',
				attrs: model,
				content: content
			});
		}
	};

	/**
	 * @summary Shortcode modeling for video.
	 *
	 *  `edit()` prepares the shortcode for the media modal.
	 *  `shortcode()` builds the new shortcode after update.
	 *
	 * @since WP-4.2.0
	 *
	 * @namespace
	 */
	wp.media.video = {
		coerce : wp.media.coerce,

		defaults : {
			id : wp.media.view.settings.post.id,
			src : '',
			poster : '',
			loop : false,
			autoplay : false,
			preload : 'metadata',
			content : '',
			width : 640,
			height : 360
		},

		/**
		 * @summary Instantiates a new media object with the next matching shortcode.
		 *
		 * @since WP-4.2.0
		 *
		 * @param {string} data The text to apply the shortcode on.
		 * @returns {wp.media} The media object.
		 */
		edit : function( data ) {
			var frame,
				shortcode = wp.shortcode.next( 'video', data ).shortcode,
				attrs;

			attrs = shortcode.attrs.named;
			attrs.content = shortcode.content;

			frame = wp.media({
				frame: 'video',
				state: 'video-details',
				metadata: _.defaults( attrs, this.defaults )
			});

			return frame;
		},

		/**
		 * @summary Generates an video shortcode.
		 *
		 * @since WP-4.2.0
		 *
		 * @param {Array} model Array with attributes for the shortcode.
		 * @returns {wp.shortcode} The video shortcode object.
		 */
		shortcode : function( model ) {
			var content;

			_.each( this.defaults, function( value, key ) {
				model[ key ] = this.coerce( model, key );

				if ( value === model[ key ] ) {
					delete model[ key ];
				}
			}, this );

			content = model.content;
			delete model.content;

			return new wp.shortcode({
				tag: 'video',
				attrs: model,
				content: content
			});
		}
	};

	media.model.PostMedia = postMedia;
	media.controller.AudioDetails = audioDetails;
	media.controller.VideoDetails = videoDetails;
	media.view.MediaFrame.MediaDetails = mediaDetails;
	media.view.MediaFrame.AudioDetails = audioDetails$1;
	media.view.MediaFrame.VideoDetails = videoDetails$1;
	media.view.MediaDetails = mediaDetails$1;
	media.view.AudioDetails = audioDetails$2;
	media.view.VideoDetails = videoDetails$2;

	var audiovideo_manifest = {

	};

	return audiovideo_manifest;

}());
