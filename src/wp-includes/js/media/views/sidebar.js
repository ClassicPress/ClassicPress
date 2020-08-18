module.exports = function() { // ClassicPress: defer loading via require()

/**
 * wp.media.view.Sidebar
 *
 * @memberOf wp.media.view
 *
 * @class
 * @augments wp.media.view.PriorityList
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 */
var Sidebar = wp.media.view.PriorityList.extend(/** @lends wp.media.view.Sidebar.prototype */{
	className: 'media-sidebar'
});

return Sidebar;

};
