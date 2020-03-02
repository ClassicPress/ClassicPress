var $ = jQuery,
	MenuItem;

/**
 * wp.media.view.MenuItem
 *
 * @memberOf wp.media.view
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
MenuItem = wp.media.View.extend(/** @lends wp.media.view.MenuItem.prototype */{
	tagName:   'a',
	className: 'media-menu-item',

	attributes: {
		href: '#'
	},

	events: {
		'click': '_click'
	},
	/**
	 * @param {Object} event
	 */
	_click: function( event ) {
		var clickOverride = this.options.click;

		if ( event ) {
			event.preventDefault();
		}

		if ( clickOverride ) {
			clickOverride.call( this );
		} else {
			this.click();
		}

		// When selecting a tab along the left side,
		// focus should be transferred into the main panel
		if ( ! wp.media.isTouchDevice ) {
			$('.media-frame-content input').first().focus();
		}
	},

	click: function() {
		var state = this.options.state;

		if ( state ) {
			this.controller.setState( state );
			this.views.parent.$el.removeClass( 'visible' ); // TODO: or hide on any click, see below
		}
	},
	/**
	 * @returns {wp.media.view.MenuItem} returns itself to allow chaining
	 */
	render: function() {
		var options = this.options;

		if ( options.text ) {
			this.$el.text( options.text );
		} else if ( options.html ) {
			this.$el.html( options.html );
		}

		return this;
	}
});

module.exports = MenuItem;
