var Menu = wp.media.view.Menu,
	Router;

/**
 * wp.media.view.Router
 *
 * @memberOf wp.media.view
 *
 * @class
 * @augments wp.media.view.Menu
 * @augments wp.media.view.PriorityList
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
Router = Menu.extend(/** @lends wp.media.view.Router.prototype */{
	tagName:   'div',
	className: 'media-router',
	property:  'contentMode',
	ItemView:  wp.media.view.RouterItem,
	region:    'router',

	initialize: function() {
		this.controller.on( 'content:render', this.update, this );
		// Call 'initialize' directly on the parent class.
		Menu.prototype.initialize.apply( this, arguments );
	},

	update: function() {
		var mode = this.controller.content.mode();
		if ( mode ) {
			this.select( mode );
		}
	}
});

module.exports = Router;
