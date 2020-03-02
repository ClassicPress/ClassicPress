var AttachmentDisplay = wp.media.view.Settings.AttachmentDisplay,
	EmbedImage;

/**
 * wp.media.view.EmbedImage
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
EmbedImage = AttachmentDisplay.extend(/** @lends wp.media.view.EmbedImage.prototype */{
	className: 'embed-media-settings',
	template:  wp.template('embed-image-settings'),

	initialize: function() {
		/**
		 * Call `initialize` directly on parent class with passed arguments
		 */
		AttachmentDisplay.prototype.initialize.apply( this, arguments );
		this.listenTo( this.model, 'change:url', this.updateImage );
	},

	updateImage: function() {
		this.$('img').attr( 'src', this.model.get('url') );
	}
});

module.exports = EmbedImage;
