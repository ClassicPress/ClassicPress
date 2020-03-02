/**
 * wp.media.view.Embed
 *
 * @memberOf wp.media.view
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var Embed = wp.media.View.extend(/** @lends wp.media.view.Ember.prototype */{
	className: 'media-embed',

	initialize: function() {
		/**
		 * @member {wp.media.view.EmbedUrl}
		 */
		this.url = new wp.media.view.EmbedUrl({
			controller: this.controller,
			model:      this.model.props
		}).render();

		this.views.set([ this.url ]);
		this.refresh();
		this.listenTo( this.model, 'change:type', this.refresh );
		this.listenTo( this.model, 'change:loading', this.loading );
	},

	/**
	 * @param {Object} view
	 */
	settings: function( view ) {
		if ( this._settings ) {
			this._settings.remove();
		}
		this._settings = view;
		this.views.add( view );
	},

	refresh: function() {
		var type = this.model.get('type'),
			constructor;

		if ( 'image' === type ) {
			constructor = wp.media.view.EmbedImage;
		} else if ( 'link' === type ) {
			constructor = wp.media.view.EmbedLink;
		} else {
			return;
		}

		this.settings( new constructor({
			controller: this.controller,
			model:      this.model.props,
			priority:   40
		}) );
	},

	loading: function() {
		this.$el.toggleClass( 'embed-loading', this.model.get('loading') );
	}
});

module.exports = Embed;
