var View = wp.media.View,
	UploaderInline;

/**
 * wp.media.view.UploaderInline
 *
 * The inline uploader that shows up in the 'Upload Files' tab.
 *
 * @memberOf wp.media.view
 *
 * @class
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
UploaderInline = View.extend(/** @lends wp.media.view.UploaderInline.prototype */{
	tagName:   'div',
	className: 'uploader-inline',
	template:  wp.template('uploader-inline'),

	events: {
		'click .close': 'hide'
	},

	initialize: function() {
		_.defaults( this.options, {
			message: '',
			status:  true,
			canClose: false
		});

		if ( ! this.options.$browser && this.controller.uploader ) {
			this.options.$browser = this.controller.uploader.$browser;
		}

		if ( _.isUndefined( this.options.postId ) ) {
			this.options.postId = wp.media.view.settings.post.id;
		}

		if ( this.options.status ) {
			this.views.set( '.upload-inline-status', new wp.media.view.UploaderStatus({
				controller: this.controller
			}) );
		}
	},

	prepare: function() {
		var suggestedWidth = this.controller.state().get('suggestedWidth'),
			suggestedHeight = this.controller.state().get('suggestedHeight'),
			data = {};

		data.message = this.options.message;
		data.canClose = this.options.canClose;

		if ( suggestedWidth && suggestedHeight ) {
			data.suggestedWidth = suggestedWidth;
			data.suggestedHeight = suggestedHeight;
		}

		return data;
	},
	/**
	 * @returns {wp.media.view.UploaderInline} Returns itself to allow chaining
	 */
	dispose: function() {
		if ( this.disposing ) {
			/**
			 * call 'dispose' directly on the parent class
			 */
			return View.prototype.dispose.apply( this, arguments );
		}

		// Run remove on `dispose`, so we can be sure to refresh the
		// uploader with a view-less DOM. Track whether we're disposing
		// so we don't trigger an infinite loop.
		this.disposing = true;
		return this.remove();
	},
	/**
	 * @returns {wp.media.view.UploaderInline} Returns itself to allow chaining
	 */
	remove: function() {
		/**
		 * call 'remove' directly on the parent class
		 */
		var result = View.prototype.remove.apply( this, arguments );

		_.defer( _.bind( this.refresh, this ) );
		return result;
	},

	refresh: function() {
		var uploader = this.controller.uploader;

		if ( uploader ) {
			uploader.refresh();
		}
	},
	/**
	 * @returns {wp.media.view.UploaderInline}
	 */
	ready: function() {
		var $browser = this.options.$browser,
			$placeholder;

		if ( this.controller.uploader ) {
			$placeholder = this.$('.browser');

			// Check if we've already replaced the placeholder.
			if ( $placeholder[0] === $browser[0] ) {
				return;
			}

			$browser.detach().text( $placeholder.text() );
			$browser[0].className = $placeholder[0].className;
			$placeholder.replaceWith( $browser.show() );
		}

		this.refresh();
		return this;
	},
	show: function() {
		this.$el.removeClass( 'hidden' );
		if ( this.controller.$uploaderToggler && this.controller.$uploaderToggler.length ) {
			this.controller.$uploaderToggler.attr( 'aria-expanded', 'true' );
		}
	},
	hide: function() {
		this.$el.addClass( 'hidden' );
		if ( this.controller.$uploaderToggler && this.controller.$uploaderToggler.length ) {
			this.controller.$uploaderToggler
				.attr( 'aria-expanded', 'false' )
				// Move focus back to the toggle button when closing the uploader.
				.focus();
		}
	}

});

module.exports = UploaderInline;
