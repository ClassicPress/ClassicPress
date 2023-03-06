/* globals wp */
/* jshint qunit: true */
/* eslint-env qunit */
/* eslint-disable no-magic-numbers */

( function() {
	'use strict';

	QUnit.module( 'Image Media Widget' );

	QUnit.test( 'image widget control', function( assert ) {
		var ImageWidgetControl, imageWidgetControlInstance, imageWidgetModelInstance, mappedProps, testImageUrl, templateProps;
		testImageUrl = 'http://s.w.org/style/images/wp-header-logo.png';
		assert.equal( typeof wp.mediaWidgets.controlConstructors.media_image, 'function', 'wp.mediaWidgets.controlConstructors.media_image is a function' );
		ImageWidgetControl = wp.mediaWidgets.controlConstructors.media_image;
		assert.ok( ImageWidgetControl.prototype instanceof wp.mediaWidgets.MediaWidgetControl, 'wp.mediaWidgets.controlConstructors.media_image subclasses wp.mediaWidgets.MediaWidgetControl' );

		imageWidgetModelInstance = new wp.mediaWidgets.modelConstructors.media_image();
		imageWidgetControlInstance = new ImageWidgetControl({
			el: jQuery( '<div></div>' ),
			syncContainer: jQuery( '<div></div>' ),
			model: imageWidgetModelInstance
		});

		// Test mapModelToPreviewTemplateProps() when no data is set.
		templateProps = imageWidgetControlInstance.mapModelToPreviewTemplateProps();
		assert.equal( templateProps.caption, undefined, 'mapModelToPreviewTemplateProps should not return attributes that are should_preview_update false' );
		assert.equal( templateProps.attachment_id, 0, 'mapModelToPreviewTemplateProps should return default values' );
		assert.equal( templateProps.currentFilename, '', 'mapModelToPreviewTemplateProps should return a currentFilename' );

		// Test mapModelToPreviewTemplateProps() when data is set on model.
		imageWidgetControlInstance.model.set( { url: testImageUrl, alt: 'some alt text', link_type: 'none' } );
		templateProps = imageWidgetControlInstance.mapModelToPreviewTemplateProps();
		assert.equal( templateProps.currentFilename, 'wp-header-logo.png', 'mapModelToPreviewTemplateProps should set currentFilename based off of url' );
		assert.equal( templateProps.url, testImageUrl, 'mapModelToPreviewTemplateProps should return the proper url' );
		assert.equal( templateProps.alt, 'some alt text', 'mapModelToPreviewTemplateProps should return the proper alt text' );
		assert.equal( templateProps.link_type, undefined, 'mapModelToPreviewTemplateProps should ignore attributes that are not needed in the preview' );
		assert.equal( templateProps.error, false, 'mapModelToPreviewTemplateProps should return error state' );

		// Test mapModelToPreviewTemplateProps() when error is set on model.
		imageWidgetControlInstance.model.set( 'error', 'missing_attachment' );
		templateProps = imageWidgetControlInstance.mapModelToPreviewTemplateProps();
		assert.equal( templateProps.error, 'missing_attachment', 'mapModelToPreviewTemplateProps should return error string' );

		// Reset model.
		imageWidgetControlInstance.model.set({ error: false, attachment_id: 0, url: null });

		// Test isSelected().
		assert.equal( imageWidgetControlInstance.isSelected(), false, 'media_image.isSelected() should return false when no media is selected' );
		imageWidgetControlInstance.model.set({ error: 'missing_attachment', attachment_id: 777 });
		assert.equal( imageWidgetControlInstance.isSelected(), false, 'media_image.isSelected() should return false when media is selected and error is set' );
		imageWidgetControlInstance.model.set({ error: false, attachment_id: 777 });
		assert.equal( imageWidgetControlInstance.isSelected(), true, 'media_image.isSelected() should return true when media is selected and no error exists' );
		imageWidgetControlInstance.model.set({ error: false, attachment_id: 0, url: testImageUrl });
		assert.equal( imageWidgetControlInstance.isSelected(), true, 'media_image.isSelected() should return true when url is set and no error exists' );

		// Reset model.
		imageWidgetControlInstance.model.set({ error: false, attachment_id: 0, url: null });

		// Test editing of widget title.
		imageWidgetControlInstance.render();
		imageWidgetControlInstance.$el.find( '.title' ).val( 'Chicken and Ribs' ).trigger( 'input' );
		assert.equal( imageWidgetModelInstance.get( 'title' ), 'Chicken and Ribs', 'Changing title should update model title attribute' );

		// Test mapMediaToModelProps.
		mappedProps = imageWidgetControlInstance.mapMediaToModelProps( { link: 'file', url: testImageUrl } );
		assert.equal( mappedProps.link_url, testImageUrl, 'mapMediaToModelProps should set file link_url according to mediaFrameProps.link' );
		mappedProps = imageWidgetControlInstance.mapMediaToModelProps( { link: 'post', postUrl: 'https://wordpress.org/image-2/' } );
		assert.equal( mappedProps.link_url, 'https://wordpress.org/image-2/', 'mapMediaToModelProps should set file link_url according to mediaFrameProps.link' );
		mappedProps = imageWidgetControlInstance.mapMediaToModelProps( { link: 'custom', linkUrl: 'https://wordpress.org' } );
		assert.equal( mappedProps.link_url, 'https://wordpress.org', 'mapMediaToModelProps should set custom link_url according to mediaFrameProps.linkUrl' );

		// Test mapModelToMediaFrameProps().
		imageWidgetControlInstance.model.set({ error: false, url: testImageUrl, 'link_type': 'custom', 'link_url': 'https://wordpress.org', 'size': 'custom', 'width': 100, 'height': 150, 'title': 'widget title', 'image_title': 'title of image' });
		mappedProps = imageWidgetControlInstance.mapModelToMediaFrameProps( imageWidgetControlInstance.model.toJSON() );
		assert.equal( mappedProps.linkUrl, 'https://wordpress.org', 'mapModelToMediaFrameProps should set linkUrl from model.link_url' );
		assert.equal( mappedProps.link, 'custom', 'mapModelToMediaFrameProps should set link from model.link_type' );
		assert.equal( mappedProps.width, 100, 'mapModelToMediaFrameProps should set width when model.size is custom' );
		assert.equal( mappedProps.height, 150, 'mapModelToMediaFrameProps should set height when model.size is custom' );
		assert.equal( mappedProps.title, 'title of image', 'mapModelToMediaFrameProps should set title from model.image_title' );
	});

	QUnit.test( 'image widget control renderPreview', function( assert ) {
		var imageWidgetControlInstance, imageWidgetModelInstance, done;
		done = assert.async();

		imageWidgetModelInstance = new wp.mediaWidgets.modelConstructors.media_image();
		imageWidgetControlInstance = new wp.mediaWidgets.controlConstructors.media_image({
			el: jQuery( '<div></div>' ),
			syncContainer: jQuery( '<div></div>' ),
			model: imageWidgetModelInstance
		});
		assert.equal( imageWidgetControlInstance.$el.find( 'img' ).length, 0, 'No images should be rendered' );
		imageWidgetControlInstance.model.set({ error: false, url: 'http://s.w.org/style/images/wp-header-logo.png' });

		// Due to renderPreview being deferred.
		setTimeout( function() {
			assert.equal( imageWidgetControlInstance.$el.find( 'img[src="http://s.w.org/style/images/wp-header-logo.png"]' ).length, 1, 'One image should be rendered' );
			done();
		}, 50 );

		done();
	});

	QUnit.test( 'image media model', function( assert ) {
		var ImageWidgetModel, imageWidgetModelInstance;
		assert.equal( typeof wp.mediaWidgets.modelConstructors.media_image, 'function', 'wp.mediaWidgets.modelConstructors.media_image is a function' );
		ImageWidgetModel = wp.mediaWidgets.modelConstructors.media_image;
		assert.ok( ImageWidgetModel.prototype instanceof wp.mediaWidgets.MediaWidgetModel, 'wp.mediaWidgets.modelConstructors.media_image subclasses wp.mediaWidgets.MediaWidgetModel' );

		imageWidgetModelInstance = new ImageWidgetModel();
		_.each( imageWidgetModelInstance.attributes, function( value, key ) {
			assert.equal( value, ImageWidgetModel.prototype.schema[ key ][ 'default' ], 'Should properly set default for ' + key );
		});
	});

})();
