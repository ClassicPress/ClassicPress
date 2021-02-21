module.exports = function() { // ClassicPress: defer loading via require()

var l10n = wp.media.view.l10n,
	EditImage;

/**
 * wp.media.controller.EditImage
 *
 * A state for editing (cropping, etc.) an image.
 *
 * @memberOf wp.media.controller
 *
 * @class
 * @augments wp.media.controller.State
 * @augments Backbone.Model
 *
 * @param {object}                    attributes                      The attributes hash passed to the state.
 * @param {wp.media.model.Attachment} attributes.model                The attachment.
 * @param {string}                    [attributes.id=edit-image]      Unique identifier.
 * @param {string}                    [attributes.title=Edit Image]   Title for the state. Displays in the media menu and the frame's title region.
 * @param {string}                    [attributes.content=edit-image] Initial mode for the content region.
 * @param {string}                    [attributes.toolbar=edit-image] Initial mode for the toolbar region.
 * @param {string}                    [attributes.menu=false]         Initial mode for the menu region.
 * @param {string}                    [attributes.url]                Unused. @todo Consider removal.
 */
EditImage = wp.media.controller.State.extend(/** @lends wp.media.controller.EditImage.prototype */{
	defaults: {
		id:      'edit-image',
		title:   l10n.editImage,
		menu:    false,
		toolbar: 'edit-image',
		content: 'edit-image',
		url:     ''
	},

	/**
	 * @since WP-3.9.0
	 */
	activate: function() {
		this.frame.on( 'toolbar:render:edit-image', _.bind( this.toolbar, this ) );
	},

	/**
	 * @since WP-3.9.0
	 */
	deactivate: function() {
		this.frame.off( 'toolbar:render:edit-image' );
	},

	/**
	 * @since WP-3.9.0
	 */
	toolbar: function() {
		var frame = this.frame,
			lastState = frame.lastState(),
			previous = lastState && lastState.id;

		frame.toolbar.set( new wp.media.view.Toolbar({
			controller: frame,
			items: {
				back: {
					style: 'primary',
					text:     l10n.back,
					priority: 20,
					click:    function() {
						if ( previous ) {
							frame.setState( previous );
						} else {
							frame.close();
						}
					}
				}
			}
		}) );
	}
});

return EditImage;

};
