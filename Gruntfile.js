/* jshint node:true */
<<<<<<< HEAD
/* jshint es3:false */
/* jshint esversion:6 */
/* jshint quotmark:false */

const buildTools = require( './tools/build' );
const fs = require( 'fs' );
const path = require( 'path' );
const installChanged = require( 'install-changed' );
const webpackConfig = require( './webpack.config' );

const SOURCE_DIR = 'src/';
const BUILD_DIR = 'build/';
const BANNER_TEXT = '/*! This file is auto-generated */';
const autoprefixer = require( 'autoprefixer' );
=======
/* jshint esversion: 6 */
/* eslint-env es6 */
/* globals Set */
var webpackConfig = require( './webpack.config' );
var installChanged = require( 'install-changed' );
>>>>>>> 438132fd36 (External Libraries: Upgrade CodeMirror to latest v5 in addition to updating CSSLint, Esprima, HTMLHint, and JSONLint.)

module.exports = function(grunt) {
	// First do `npm install` if package.json has changed.
	installChanged.watchPackage();

	buildTools.setGruntReference( grunt );

	const puppeteerOptions = {
		headless: 'new',
		args: [
			'--site-per-process',
			'--disable-web-security',
			'--no-sandbox',
			'--disable-setuid-sandbox'
		]
	};

	// Load tasks.
	for ( const devDep in require( './package.json' ).devDependencies ) {
		// Match: grunt-abc, @author/grunt-xyz
		// Skip: grunt-legacy-util
		if ( /^(@[^\/]+\/)?grunt-(?!legacy-util$)/.test( devDep ) ) {
			grunt.loadNpmTasks( devDep );
		}
	}

	// Load legacy utils.
	grunt.util = require( 'grunt-legacy-util' );

	// Load terser task.
	require( './tools/build/grunt-terser' )( grunt );

	// Project configuration.
	grunt.initConfig({
		postcss: {
			options: {
				processors: [
					autoprefixer({
						cascade: false
					})
				]
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: SOURCE_DIR,
				src: [
					'wp-admin/css/*.css',
					'wp-includes/css/*.css',
					'wp-includes/js/mediaelement/wp-*.css'
				]
			},
			colors: {
				expand: true,
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				src: [
					'wp-admin/css/colors/*/colors.css'
				]
			}
		},
 		usebanner: {
 			css: {
				options: {
					position: 'top',
					banner: BANNER_TEXT,
					linebreak: true
				},
			codemirror: {
				options: {
					linebreak: false,
					banner: require( './tools/webpack/codemirror-banner' )
				},
				files: {
					src: [
						WORKING_DIR + 'wp-includes/js/codemirror/codemirror.min.css'
					]
				}
			},
				files: {
					src: [
						`${BUILD_DIR}wp-admin/css/*.min.css`,
						`${BUILD_DIR}wp-includes/css/*.min.css`,
						`${BUILD_DIR}wp-admin/css/colors/*/*.css`
					]
				}
			},
			js: {
				usebanner: {
					options: {
						position: 'top',
						banner: '/*! This file is auto-generated */',
						linebreak: true
					},
					files: {
						src: [
							SOURCE_DIR + 'wp-includes/js/dist/*.min.js'
						]
					}
				}
			}
		},
		clean: {
			all: [BUILD_DIR],
			'vendor-js': {
				expand: true,
				cwd: SOURCE_DIR,
				src: [
					'wp-includes/js/dist/vendor/*.js',
					'wp-includes/js/clipboard.js',
					'wp-includes/js/clipboard.min.js',
					'wp-includes/js/sortable.min.js'
				]
			},
			'package-js': {
				expand: true,
				cwd: SOURCE_DIR,
				src: [
					'wp-includes/js/dist/*.js',
					'!wp-includes/js/dist/vendor/**'
				]
			},
			'mediaelement-js': {
				expand: true,
				cwd: SOURCE_DIR,
				src: [
					'wp-includes/js/mediaelement/mediaelement-and-player.js',
					'wp-includes/js/mediaelement/mediaelement-and-player.min.js',
					'wp-includes/js/mediaelement/mediaelement.js',
					'wp-includes/js/mediaelement/mediaelement.min.js',
					'wp-includes/js/mediaelement/mediaelementplayer-legacy.css',
					'wp-includes/js/mediaelement/mediaelementplayer-legacy.min.css',
					'wp-includes/js/mediaelement/mediaelementplayer.css',
					'wp-includes/js/mediaelement/mediaelementplayer.min.css',
					'wp-includes/js/mediaelement/mejs-controls.svg'
				]
			},
			dynamic: {
				dot: true,
				expand: true,
				cwd: BUILD_DIR,
				src: []
			},
			qunit: ['tests/qunit/compiled.html']
		},
		copy: {
			files: {
				files: [
					{
						dot: true,
						expand: true,
						cwd: SOURCE_DIR,
						src: [
							'**',
							'!wp-includes/js/media/**',
							'!**/.{svn,git}/**', // Ignore version control directories.
							// Exclude raw root certificate files that are combined into ca-bundle.crt.
							'!wp-includes/certificates/legacy-1024bit.pem',
							// Exclude plugins unless specifically listed
							'!wp-content/plugins/**',
							'wp-content/plugins/index.php',
							// ClassicPress Pepper plugin
							'wp-content/plugins/cp-pepper/**',
							// but not the ClassicPress pepper key file
							'!wp-content/plugins/cp-pepper/pepper.php',
							// Ignore unminified versions of external libs we don't ship:
							'!wp-includes/js/backbone.js',
							'!wp-includes/js/underscore.js',
							'!wp-includes/js/jquery/jquery.masonry.js',
							// Exclude some things present in a configured install
							'!wp-config.php',
							'!wp-content/uploads/**',
							// Exclude script-loader.php (handled in `copy:script-loader` task)
							'!wp-includes/script-loader.php',
							// Exclude version.php (handled in `copy:version` task)
							'!wp-includes/version.php'
						],
						dest: BUILD_DIR
					},
					{
						src: 'wp-config-sample.php',
						dest: BUILD_DIR
					}
				]
			},
<<<<<<< HEAD
=======
			'npm-packages': {
				files: [
					{
						[ WORKING_DIR + 'wp-includes/js/backbone.js' ]: [ './node_modules/backbone/backbone.js' ],
						[ WORKING_DIR + 'wp-includes/js/clipboard.js' ]: [ './node_modules/clipboard/dist/clipboard.js' ],
						[ WORKING_DIR + 'wp-includes/js/hoverIntent.js' ]: [ './node_modules/jquery-hoverintent/jquery.hoverIntent.js' ],

						// Renamed to avoid conflict with jQuery hoverIntent.min.js (after minifying).
						[ WORKING_DIR + 'wp-includes/js/hoverintent-js.min.js' ]: [ './node_modules/hoverintent/dist/hoverintent.min.js' ],

						[ WORKING_DIR + 'wp-includes/js/imagesloaded.min.js' ]: [ './node_modules/imagesloaded/imagesloaded.pkgd.min.js' ],
						[ WORKING_DIR + 'wp-includes/js/jquery/jquery.js' ]: [ './node_modules/jquery/dist/jquery.js' ],
						[ WORKING_DIR + 'wp-includes/js/jquery/jquery.min.js' ]: [ './node_modules/jquery/dist/jquery.min.js' ],
						[ WORKING_DIR + 'wp-includes/js/jquery/jquery.form.js' ]: [ './node_modules/jquery-form/src/jquery.form.js' ],
						[ WORKING_DIR + 'wp-includes/js/jquery/jquery.color.min.js' ]: [ './node_modules/jquery-color/dist/jquery.color.min.js' ],
						[ WORKING_DIR + 'wp-includes/js/masonry.min.js' ]: [ './node_modules/masonry-layout/dist/masonry.pkgd.min.js' ],
						[ WORKING_DIR + 'wp-includes/js/underscore.js' ]: [ './node_modules/underscore/underscore.js' ],
					}
				]
			},
			'codemirror': {
				options: {
					process: function( content, srcpath ) {
						if ( srcpath.includes( 'htmlhint.min.js' ) ) {
							return content + '\nif ( window.HTMLHint && window.HTMLHint.HTMLHint ) { window.HTMLHint = window.HTMLHint.HTMLHint; }';
						}
						return content;
					}
				},
				files: [
					{
						[ WORKING_DIR + 'wp-includes/js/codemirror/csslint.js' ]: [ './node_modules/csslint/dist/csslint.js' ],
						[ WORKING_DIR + 'wp-includes/js/codemirror/esprima.js' ]: [ './node_modules/esprima/dist/esprima.js' ],
						[ WORKING_DIR + 'wp-includes/js/codemirror/htmlhint.js' ]: [ './node_modules/htmlhint/dist/htmlhint.min.js' ],
						[ WORKING_DIR + 'wp-includes/js/codemirror/jsonlint.js' ]: [ './node_modules/jsonlint/web/jsonlint.js' ],
					},
					{
						expand: true,
						cwd: SOURCE_DIR + 'js/_enqueues/vendor/codemirror/',
						src: [
							'fakejshint.js',
							'htmlhint-kses.js',
						],
						dest: WORKING_DIR + 'wp-includes/js/codemirror/'
					}
				]
			},
			'vendor-js': {
				files: [
					{
						expand: true,
						cwd: SOURCE_DIR + 'js/_enqueues/vendor/',
						src: [
							'**/*',
							'!farbtastic.js',
							'!iris.min.js',
							'!deprecated/**',
							'!README.md',
							// Ignore unminified version of vendor lib we don't ship.
							'!jquery/jquery.masonry.js',
							'!tinymce/tinymce.js'
						],
						dest: WORKING_DIR + 'wp-includes/js/'
					},
					{
						expand: true,
						cwd: SOURCE_DIR + 'js/_enqueues/vendor/',
						src: [
							'farbtastic.js',
							'iris.min.js'
						],
						dest: WORKING_DIR + 'wp-admin/js/'
					},
					{
						expand: true,
						cwd: SOURCE_DIR + 'js/_enqueues/vendor/deprecated',
						src: [
							'suggest*'
						],
						dest: WORKING_DIR + 'wp-includes/js/jquery/'
					}
				].concat(
					// Copy tinymce.js only when building to /src.
					WORKING_DIR === SOURCE_DIR ? {
						expand: true,
						cwd: SOURCE_DIR + 'js/_enqueues/vendor/tinymce/',
						src: 'tinymce.js',
						dest: SOURCE_DIR + 'wp-includes/js/tinymce/'
					} : []
				)
			},
			'admin-js': {
				files: {
					[ WORKING_DIR + 'wp-admin/js/accordion.js' ]: [ './src/js/_enqueues/lib/accordion.js' ],
					[ WORKING_DIR + 'wp-admin/js/application-passwords.js' ]: [ './src/js/_enqueues/admin/application-passwords.js' ],
					[ WORKING_DIR + 'wp-admin/js/auth-app.js' ]: [ './src/js/_enqueues/admin/auth-app.js' ],
					[ WORKING_DIR + 'wp-admin/js/code-editor.js' ]: [ './src/js/_enqueues/wp/code-editor.js' ],
					[ WORKING_DIR + 'wp-admin/js/color-picker.js' ]: [ './src/js/_enqueues/lib/color-picker.js' ],
					[ WORKING_DIR + 'wp-admin/js/comment.js' ]: [ './src/js/_enqueues/admin/comment.js' ],
					[ WORKING_DIR + 'wp-admin/js/common.js' ]: [ './src/js/_enqueues/admin/common.js' ],
					[ WORKING_DIR + 'wp-admin/js/custom-background.js' ]: [ './src/js/_enqueues/admin/custom-background.js' ],
					[ WORKING_DIR + 'wp-admin/js/custom-header.js' ]: [ './src/js/_enqueues/admin/custom-header.js' ],
					[ WORKING_DIR + 'wp-admin/js/customize-controls.js' ]: [ './src/js/_enqueues/wp/customize/controls.js' ],
					[ WORKING_DIR + 'wp-admin/js/customize-nav-menus.js' ]: [ './src/js/_enqueues/wp/customize/nav-menus.js' ],
					[ WORKING_DIR + 'wp-admin/js/customize-widgets.js' ]: [ './src/js/_enqueues/wp/customize/widgets.js' ],
					[ WORKING_DIR + 'wp-admin/js/dashboard.js' ]: [ './src/js/_enqueues/wp/dashboard.js' ],
					[ WORKING_DIR + 'wp-admin/js/edit-comments.js' ]: [ './src/js/_enqueues/admin/edit-comments.js' ],
					[ WORKING_DIR + 'wp-admin/js/editor-expand.js' ]: [ './src/js/_enqueues/wp/editor/dfw.js' ],
					[ WORKING_DIR + 'wp-admin/js/editor.js' ]: [ './src/js/_enqueues/wp/editor/base.js' ],
					[ WORKING_DIR + 'wp-admin/js/gallery.js' ]: [ './src/js/_enqueues/lib/gallery.js' ],
					[ WORKING_DIR + 'wp-admin/js/image-edit.js' ]: [ './src/js/_enqueues/lib/image-edit.js' ],
					[ WORKING_DIR + 'wp-admin/js/inline-edit-post.js' ]: [ './src/js/_enqueues/admin/inline-edit-post.js' ],
					[ WORKING_DIR + 'wp-admin/js/inline-edit-tax.js' ]: [ './src/js/_enqueues/admin/inline-edit-tax.js' ],
					[ WORKING_DIR + 'wp-admin/js/language-chooser.js' ]: [ './src/js/_enqueues/lib/language-chooser.js' ],
					[ WORKING_DIR + 'wp-admin/js/link.js' ]: [ './src/js/_enqueues/admin/link.js' ],
					[ WORKING_DIR + 'wp-admin/js/media-gallery.js' ]: [ './src/js/_enqueues/deprecated/media-gallery.js' ],
					[ WORKING_DIR + 'wp-admin/js/media-upload.js' ]: [ './src/js/_enqueues/admin/media-upload.js' ],
					[ WORKING_DIR + 'wp-admin/js/media.js' ]: [ './src/js/_enqueues/admin/media.js' ],
					[ WORKING_DIR + 'wp-admin/js/nav-menu.js' ]: [ './src/js/_enqueues/lib/nav-menu.js' ],
					[ WORKING_DIR + 'wp-admin/js/password-strength-meter.js' ]: [ './src/js/_enqueues/wp/password-strength-meter.js' ],
					[ WORKING_DIR + 'wp-admin/js/password-toggle.js' ]: [ './src/js/_enqueues/admin/password-toggle.js' ],
					[ WORKING_DIR + 'wp-admin/js/plugin-install.js' ]: [ './src/js/_enqueues/admin/plugin-install.js' ],
					[ WORKING_DIR + 'wp-admin/js/post.js' ]: [ './src/js/_enqueues/admin/post.js' ],
					[ WORKING_DIR + 'wp-admin/js/postbox.js' ]: [ './src/js/_enqueues/admin/postbox.js' ],
					[ WORKING_DIR + 'wp-admin/js/revisions.js' ]: [ './src/js/_enqueues/wp/revisions.js' ],
					[ WORKING_DIR + 'wp-admin/js/set-post-thumbnail.js' ]: [ './src/js/_enqueues/admin/set-post-thumbnail.js' ],
					[ WORKING_DIR + 'wp-admin/js/svg-painter.js' ]: [ './src/js/_enqueues/wp/svg-painter.js' ],
					[ WORKING_DIR + 'wp-admin/js/tags-box.js' ]: [ './src/js/_enqueues/admin/tags-box.js' ],
					[ WORKING_DIR + 'wp-admin/js/tags-suggest.js' ]: [ './src/js/_enqueues/admin/tags-suggest.js' ],
					[ WORKING_DIR + 'wp-admin/js/tags.js' ]: [ './src/js/_enqueues/admin/tags.js' ],
					[ WORKING_DIR + 'wp-admin/js/site-health.js' ]: [ './src/js/_enqueues/admin/site-health.js' ],
					[ WORKING_DIR + 'wp-admin/js/site-icon.js' ]: [ './src/js/_enqueues/admin/site-icon.js' ],
					[ WORKING_DIR + 'wp-admin/js/privacy-tools.js' ]: [ './src/js/_enqueues/admin/privacy-tools.js' ],
					[ WORKING_DIR + 'wp-admin/js/theme-plugin-editor.js' ]: [ './src/js/_enqueues/wp/theme-plugin-editor.js' ],
					[ WORKING_DIR + 'wp-admin/js/theme.js' ]: [ './src/js/_enqueues/wp/theme.js' ],
					[ WORKING_DIR + 'wp-admin/js/updates.js' ]: [ './src/js/_enqueues/wp/updates.js' ],
					[ WORKING_DIR + 'wp-admin/js/user-profile.js' ]: [ './src/js/_enqueues/admin/user-profile.js' ],
					[ WORKING_DIR + 'wp-admin/js/user-suggest.js' ]: [ './src/js/_enqueues/lib/user-suggest.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/custom-html-widgets.js' ]: [ './src/js/_enqueues/wp/widgets/custom-html.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/media-audio-widget.js' ]: [ './src/js/_enqueues/wp/widgets/media-audio.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/media-gallery-widget.js' ]: [ './src/js/_enqueues/wp/widgets/media-gallery.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/media-image-widget.js' ]: [ './src/js/_enqueues/wp/widgets/media-image.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/media-video-widget.js' ]: [ './src/js/_enqueues/wp/widgets/media-video.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/media-widgets.js' ]: [ './src/js/_enqueues/wp/widgets/media.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets/text-widgets.js' ]: [ './src/js/_enqueues/wp/widgets/text.js' ],
					[ WORKING_DIR + 'wp-admin/js/widgets.js' ]: [ './src/js/_enqueues/admin/widgets.js' ],
					[ WORKING_DIR + 'wp-admin/js/word-count.js' ]: [ './src/js/_enqueues/wp/utils/word-count.js' ],
					[ WORKING_DIR + 'wp-admin/js/wp-fullscreen-stub.js' ]: [ './src/js/_enqueues/deprecated/fullscreen-stub.js' ],
					[ WORKING_DIR + 'wp-admin/js/xfn.js' ]: [ './src/js/_enqueues/admin/xfn.js' ]
				}
			},
			'includes-js': {
				files: {
					[ WORKING_DIR + 'wp-includes/js/admin-bar.js' ]: [ './src/js/_enqueues/lib/admin-bar.js' ],
					[ WORKING_DIR + 'wp-includes/js/api-request.js' ]: [ './src/js/_enqueues/wp/api-request.js' ],
					[ WORKING_DIR + 'wp-includes/js/autosave.js' ]: [ './src/js/_enqueues/wp/autosave.js' ],
					[ WORKING_DIR + 'wp-includes/js/comment-reply.js' ]: [ './src/js/_enqueues/lib/comment-reply.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-base.js' ]: [ './src/js/_enqueues/wp/customize/base.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-loader.js' ]: [ './src/js/_enqueues/wp/customize/loader.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-models.js' ]: [ './src/js/_enqueues/wp/customize/models.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-preview-nav-menus.js' ]: [ './src/js/_enqueues/wp/customize/preview-nav-menus.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-preview-widgets.js' ]: [ './src/js/_enqueues/wp/customize/preview-widgets.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-preview.js' ]: [ './src/js/_enqueues/wp/customize/preview.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-selective-refresh.js' ]: [ './src/js/_enqueues/wp/customize/selective-refresh.js' ],
					[ WORKING_DIR + 'wp-includes/js/customize-views.js' ]: [ './src/js/_enqueues/wp/customize/views.js' ],
					[ WORKING_DIR + 'wp-includes/js/heartbeat.js' ]: [ './src/js/_enqueues/wp/heartbeat.js' ],
					[ WORKING_DIR + 'wp-includes/js/mce-view.js' ]: [ './src/js/_enqueues/wp/mce-view.js' ],
					[ WORKING_DIR + 'wp-includes/js/media-editor.js' ]: [ './src/js/_enqueues/wp/media/editor.js' ],
					[ WORKING_DIR + 'wp-includes/js/quicktags.js' ]: [ './src/js/_enqueues/lib/quicktags.js' ],
					[ WORKING_DIR + 'wp-includes/js/shortcode.js' ]: [ './src/js/_enqueues/wp/shortcode.js' ],
					[ WORKING_DIR + 'wp-includes/js/utils.js' ]: [ './src/js/_enqueues/lib/cookies.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-ajax-response.js' ]: [ './src/js/_enqueues/lib/ajax-response.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-api.js' ]: [ './src/js/_enqueues/wp/api.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-auth-check.js' ]: [ './src/js/_enqueues/lib/auth-check.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-backbone.js' ]: [ './src/js/_enqueues/wp/backbone.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-custom-header.js' ]: [ './src/js/_enqueues/wp/custom-header.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-embed-template.js' ]: [ './src/js/_enqueues/lib/embed-template.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-embed.js' ]: [ './src/js/_enqueues/wp/embed.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-emoji-loader.js' ]: [ './src/js/_enqueues/lib/emoji-loader.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-emoji.js' ]: [ './src/js/_enqueues/wp/emoji.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-list-revisions.js' ]: [ './src/js/_enqueues/lib/list-revisions.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-lists.js' ]: [ './src/js/_enqueues/lib/lists.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-pointer.js' ]: [ './src/js/_enqueues/lib/pointer.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-sanitize.js' ]: [ './src/js/_enqueues/wp/sanitize.js' ],
					[ WORKING_DIR + 'wp-includes/js/wp-util.js' ]: [ './src/js/_enqueues/wp/util.js' ],
					[ WORKING_DIR + 'wp-includes/js/wpdialog.js' ]: [ './src/js/_enqueues/lib/dialog.js' ],
					[ WORKING_DIR + 'wp-includes/js/wplink.js' ]: [ './src/js/_enqueues/lib/link.js' ],
					[ WORKING_DIR + 'wp-includes/js/zxcvbn-async.js' ]: [ './src/js/_enqueues/lib/zxcvbn-async.js' ]
				}
			},
>>>>>>> 438132fd36 (External Libraries: Upgrade CodeMirror to latest v5 in addition to updating CSSLint, Esprima, HTMLHint, and JSONLint.)
			'wp-admin-css-compat-rtl': {
				options: {
					processContent(src) {
						return src.replace( /\.css/g, '-rtl.css' );
					}
				},
				src: `${SOURCE_DIR}wp-admin/css/wp-admin.css`,
				dest: `${BUILD_DIR}wp-admin/css/wp-admin-rtl.css`
			},
			'wp-admin-css-compat-min': {
				options: {
					processContent(src) {
						return src.replace( /\.css/g, '.min.css' );
					}
				},
				files: [
					{
						src: `${SOURCE_DIR}wp-admin/css/wp-admin.css`,
						dest: `${BUILD_DIR}wp-admin/css/wp-admin.min.css`
					},
					{
						src:  `${BUILD_DIR}wp-admin/css/wp-admin-rtl.css`,
						dest: `${BUILD_DIR}wp-admin/css/wp-admin-rtl.min.css`
					}
				]
			},
			'vendor-js': {
				files: [
					{
						src: `./node_modules/lodash/lodash.js`,
						dest: `${SOURCE_DIR}wp-includes/js/dist/vendor/lodash.js`
					},
					{
						src: `./node_modules/lodash/lodash.min.js`,
						dest: `${SOURCE_DIR}wp-includes/js/dist/vendor/lodash.min.js`
					},
					{
						src:  `./node_modules/moment/moment.js`,
						dest: `${SOURCE_DIR}wp-includes/js/dist/vendor/moment.js`
					},
					{
						src:  `./node_modules/moment/min/moment.min.js`,
						dest: `${SOURCE_DIR}wp-includes/js/dist/vendor/moment.min.js`
					},
					{
						src:  `./node_modules/clipboard/dist/clipboard.js`,
						dest: `${SOURCE_DIR}wp-includes/js/clipboard.js`
					},
					{
						src:  `./node_modules/clipboard/dist/clipboard.min.js`,
						dest: `${SOURCE_DIR}wp-includes/js/clipboard.min.js`
					},
					{
						src:  `./node_modules/sortablejs/Sortable.min.js`,
						dest: `${SOURCE_DIR}wp-includes/js/sortable.min.js`
					}
				]
			},
			'mediaelement-js': {
				files: [
					{
						src:  `./node_modules/mediaelement/build/mediaelement-and-player.js`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelement-and-player.js`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelement-and-player.min.js`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelement-and-player.min.js`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelement.js`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelement.js`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelement.min.js`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelement.min.js`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelementplayer-legacy.css`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelementplayer-legacy.css`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelementplayer-legacy.min.css`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelementplayer-legacy.min.css`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelementplayer.css`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelementplayer.css`
					},
					{
						src:  `./node_modules/mediaelement/build/mediaelementplayer.min.css`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mediaelementplayer.min.css`
					},
					{
						src:  `./node_modules/mediaelement/build/mejs-controls.svg`,
						dest: `${SOURCE_DIR}wp-includes/js/mediaelement/mejs-controls.svg`
					}
				]
			},
			'script-loader-impl': {
				options: {
					processContent(src) {
						return src.replace( /\$default_version = 'cp_' \. .*;/m, () => {
							const hash = grunt.config.get( 'dev.git-version' );
							if ( ! hash ) {
								grunt.log.fail(
									'Do not run the copy:script-loader-impl task directly'
								);
								grunt.fatal( 'grunt.config dev.git-version not set' );
							}
							return `$default_version = 'cp_${hash.substr( 0, 8 )}';`;
						} );
					}
				},
				src: `${SOURCE_DIR}wp-includes/script-loader.php`,
				dest: `${BUILD_DIR}wp-includes/script-loader.php`
			},
			version: {
				options: {
					processContent(src) {
						return src.replace( /^\$cp_version = '(.+?)';/m, (str, version) => {
							if ( process.env.CLASSICPRESS_RELEASE ) {
								// This is an official build that will receive auto-updates to newer
								// official builds.  Remove the '+dev' suffix from the source tree.
								version = version.replace( /\+dev$/, '' );
							} else if ( process.env.CLASSICPRESS_NIGHTLY ) {
								// This is an official nightly build that will receive auto-updates
								// to newer official nightly builds.  Replace the '+dev' suffix from
								// the source tree with e.g.  '+nightly.20181019'.  Use yesterday's
								// date - nightly builds are baked at midnight UTC.
								const d = new Date();
								d.setDate( d.getDate() - 1 );
								version = version.replace(
									/\+dev$/,
									`+nightly.${grunt.template.date( d, 'yyyymmdd' )}`
								);
							} else {
								// This is another type of build, probably someone generating one
								// for their own purposes.  Use e.g. '+build.20181020'.
								version = version.replace(
									/\+dev$/,
									`+build.${grunt.template.today( 'yyyymmdd' )}`
								);
							}

							return `$cp_version = '${version}';`;
						});
					}
				},
				src: `${SOURCE_DIR}wp-includes/version.php`,
				dest: `${BUILD_DIR}wp-includes/version.php`
			},
			dynamic: {
				dot: true,
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				src: []
			},
			qunit: {
				src: 'tests/qunit/index.html',
				dest: 'tests/qunit/compiled.html',
				options: {
					processContent(src) {
						return src.replace( /(\".+?\/)src(\/.+?)(?:.min)?(.js\")/g , (match, $1, $2, $3) => // Don't add `.min` to files that don't have it.
						`${$1}build${$2}${/jquery$/.test( $2 ) ? '' : '.min'}${$3}` );
					}
				}
			}
		},
		webpack: {
			min: webpackConfig( { environment: 'production', buildTarget: SOURCE_DIR, minify: true } ),
			dev: webpackConfig( { environment: 'production', buildTarget: SOURCE_DIR, minify: false } )
		},
		sass: {
			colors: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.css',
				src: ['wp-admin/css/colors/*/colors.scss'],
				options: {
					api: 'modern',
					implementation: require( 'sass' )
				}
			}
		},
		cssmin: {
			options: {
				compatibility: 'ie7'
			},
			codemirror: {
				files: {
					[ WORKING_DIR + 'wp-includes/js/codemirror/codemirror.min.css' ]: [
						'node_modules/codemirror/lib/codemirror.css',
						'node_modules/codemirror/addon/hint/show-hint.css',
						'node_modules/codemirror/addon/lint/lint.css',
						'node_modules/codemirror/addon/dialog/dialog.css',
						'node_modules/codemirror/addon/display/fullscreen.css',
						'node_modules/codemirror/addon/fold/foldgutter.css',
						'node_modules/codemirror/addon/merge/merge.css',
						'node_modules/codemirror/addon/scroll/simplescrollbars.css',
						'node_modules/codemirror/addon/search/matchesonscrollbar.css',
						'node_modules/codemirror/addon/tern/tern.css'
					]
				}
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.css',
				src: [
					'wp-admin/css/*.css',
					'!wp-admin/css/wp-admin*.css',
					'wp-includes/css/*.css',
					'wp-includes/js/mediaelement/wp-mediaelement.css',
					'wp-includes/js/filepond/*.css'
				]
			},
			rtl: {
				expand: true,
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				ext: '.min.css',
				src: [
					'wp-admin/css/*-rtl.css',
					'!wp-admin/css/wp-admin*.css',
					'wp-includes/css/*-rtl.css'
				]
			},
			colors: {
				expand: true,
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				ext: '.min.css',
				src: [
					'wp-admin/css/colors/*/*.css'
				]
			}
		},
		rtlcss: {
			options: {
				// rtlcss options
				opts: {
					clean: false,
					processUrls: { atrule: true, decl: false },
					stringMap: [
						{
							name: 'import-rtl-stylesheet',
							priority: 10,
							exclusive: true,
							search: [ '.css' ],
							replace: [ '-rtl.css' ],
							options: {
								scope: 'url',
								ignoreCase: false
							}
						}
					]
				},
				saveUnmodified: false,
				plugins: [
					{
						name: 'swap-dashicons-left-right-arrows',
						priority: 10,
						directives: {
							control: {},
							value: []
						},
						processors: [
							{
								expr: /content/im,
								action(prop, value) {
									if ( value === '"\\f141"' ) { // dashicons-arrow-left
										value = '"\\f139"';
									} else if ( value === '"\\f340"' ) { // dashicons-arrow-left-alt
										value = '"\\f344"';
									} else if ( value === '"\\f341"' ) { // dashicons-arrow-left-alt2
										value = '"\\f345"';
									} else if ( value === '"\\f139"' ) { // dashicons-arrow-right
										value = '"\\f141"';
									} else if ( value === '"\\f344"' ) { // dashicons-arrow-right-alt
										value = '"\\f340"';
									} else if ( value === '"\\f345"' ) { // dashicons-arrow-right-alt2
										value = '"\\f341"';
									}
									return { prop, value };
								}
							}
						]
					}
				]
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '-rtl.css',
				src: [
					'wp-admin/css/*.css',
					'wp-includes/css/*.css',

					// Exceptions
					'!wp-includes/css/dashicons.css',
					'!wp-includes/css/wp-embed-template.css',
					'!wp-includes/css/wp-embed-template-ie.css'
				]
			},
			colors: {
				expand: true,
				cwd: BUILD_DIR,
				dest: BUILD_DIR,
				ext: '-rtl.css',
				src: [
					'wp-admin/css/colors/*/colors.css'
				]
			},
			dynamic: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '-rtl.css',
				src: []
			}
		},
		jshint: {
			options: grunt.file.readJSON('.jshintrc'),
			grunt: {
				src: ['Gruntfile.js', 'tools/**/*.js', '!tools/build/grunt-terser.js']
			},
			tests: {
				src: [
					'tests/qunit/**/*.js',
					'!tests/qunit/vendor/*',
					'!tests/qunit/editor/**'
				],
				options: grunt.file.readJSON('tests/qunit/.jshintrc')
			},
			themes: {
				expand: true,
				cwd: `${SOURCE_DIR}wp-content/themes`,
				src: [
					'twenty*/**/*.js',
					'!twenty{eleven,twelve,thirteen}/**',
					// Third party scripts
					'!twenty{fourteen,fifteen,sixteen}/js/html5.js',
					'!twentyseventeen/assets/js/html5.js',
					'!twentyseventeen/assets/js/jquery.scrollTo.js'
				]
			},
			media: {
				src: [
					`${SOURCE_DIR}wp-includes/js/media/**/*.js`
				]
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: [
					'wp-admin/js/**/*.js',
					'wp-includes/js/*.js',
					// Built scripts.
					'!wp-includes/js/media-views.js',
					// ClassicPress scripts inside directories
					'wp-includes/js/jquery/jquery.table-hotkeys.js',
					'wp-includes/js/mediaelement/mediaelement-migrate.js',
					'wp-includes/js/mediaelement/wp-mediaelement.js',
					'wp-includes/js/mediaelement/wp-playlist.js',
					'wp-includes/js/plupload/handlers.js',
					'wp-includes/js/plupload/wp-plupload.js',
					'wp-includes/js/tinymce/plugins/wordpress/plugin.js',
					'wp-includes/js/tinymce/plugins/wp*/plugin.js',
					// Third party scripts
					'!wp-includes/js/codemirror/*.js',
					'!wp-includes/js/tinymce/plugins/**/*.js',
					'!wp-admin/js/farbtastic.js',
					'!wp-admin/js/iris.js',
					'!wp-includes/js/backbone*.js',
					'!wp-includes/js/clipboard.js',
					'!wp-includes/js/swfobject.js',
					'!wp-includes/js/underscore*.js',
					'!wp-includes/js/colorpicker.js',
					'!wp-includes/js/hoverIntent.js',
					'!wp-includes/js/json2.js',
					'!wp-includes/js/tw-sack.js',
					'!wp-includes/js/twemoji.js',
					'!wp-includes/js/plupload/*.js',
					'!**/*.min.js'
				],
				// Remove once other JSHint errors are resolved
				options: {
					curly: false,
					eqeqeq: false
				},
				// Limit JSHint's run to a single specified file:
				//
				//	grunt jshint:core --file=filename.js
				//
				// Optionally, include the file path:
				//
				//	grunt jshint:core --file=path/to/filename.js
				//
				filter(filepath) {
					let index;
					const file = grunt.option( 'file' );

					// Don't filter when no target file is specified
					if ( ! file ) {
						return true;
					}

					// Normalize filepath for Windows
					filepath = filepath.replace( /\\/g, '/' );
					index = filepath.lastIndexOf( `/${file}` );

					// Match only the filename passed from cli
					if ( filepath === file || ( -1 !== index && index === filepath.length - ( file.length + 1 ) ) ) {
						return true;
					}

					return false;
				}
			},
			plugins: {
				expand: true,
				cwd: `${SOURCE_DIR}wp-content/plugins`,
				src: [
					'**/*.js',
					'!**/*.min.js'
				],
				// Limit JSHint's run to a single specified plugin directory:
				//
				//	grunt jshint:plugins --dir=foldername
				//
				filter(dirpath) {
					let index;
					const dir = grunt.option( 'dir' );

					// Don't filter when no target folder is specified
					if ( ! dir ) {
						return true;
					}

					dirpath = dirpath.replace( /\\/g, '/' );
					index = dirpath.lastIndexOf( `/${dir}` );

					// Match only the folder name passed from cli
					if ( -1 !== index ) {
						return true;
					}

					return false;
				}
			}
		},
		eslint: {
			options: {
				overrideConfigFile: '.eslint.config.js'
			},
			grunt: {
				src: [
					'Gruntfile.js'
				]
			},
			core: {
				options: {
					fix: grunt.option( 'fix' )
				},
				src: [
					'src/wp-admin/js/**/*.js',
					'src/wp-includes/js/*.js',
					// Built scripts.
					'!src/wp-includes/js/media-views.js',
					// ClassicPress scripts inside directories
					'src/wp-includes/js/jquery/jquery.table-hotkeys.js',
					'src/wp-includes/js/mediaelement/mediaelement-migrate.js',
					'src/wp-includes/js/mediaelement/wp-mediaelement.js',
					'src/wp-includes/js/mediaelement/wp-playlist.js',
					'src/wp-includes/js/plupload/handlers.js',
					'src/wp-includes/js/plupload/wp-plupload.js',
					'src/wp-includes/js/tinymce/plugins/wordpress/plugin.js',
					'src/wp-includes/js/tinymce/plugins/wp*/plugin.js',
					// Third party scripts
					'!src/wp-includes/js/codemirror/*.js',
					'!src/wp-includes/js/jquery/*.js',
					'!src/wp-includes/js/tinymce/plugins/**/*.js',
					'!src/wp-admin/js/farbtastic.js',
					'!src/wp-admin/js/iris.js',
					'!src/wp-includes/js/backbone*.js',
					'!src/wp-includes/js/clipboard.js',
					'!src/wp-includes/js/swfobject.js',
					'!src/wp-includes/js/underscore*.js',
					'!src/wp-includes/js/colorpicker.js',
					'!src/wp-includes/js/hoverIntent.js',
					'!src/wp-includes/js/json2.js',
					'!src/wp-includes/js/tw-sack.js',
					'!src/wp-includes/js/twemoji.js',
					'!src/wp-includes/js/plupload/*.js',
					'!src/wp-includes/js/zxcvbn-async.js',
					'!src/**/*.min.js'
				]
			}
		},
		jsdoc : {
			dist : {
				dest: 'jsdoc',
				options: {
					configure : 'jsdoc.conf.json'
				}
			}
		},
		qunit: {
			all: {
				src: [
					'tests/qunit/*.html'
				],
				options: {
					timeout: 7500,
					httpBase: 'http://localhost:8008',
					puppeteer: puppeteerOptions
				}
			}
		},
		connect: {
			server: {
				options: {
					port: 8008,
					base: '.',
					middleware(connect, options, middlewares) {
						middlewares.unshift( ({method}, res, next) => {
							if (method === 'POST') { // admin-ajax.php
								// The 'connect' web server will return HTTP
								// 405 (Method Not Allowed) here.
								return res.end('');
							}
							return next();
						} );
						return middlewares;
					}
				}
			}
		},
		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'phpunit.xml.dist']
			},
			ajax: {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'phpunit.xml.dist', '--group', 'ajax']
			},
			multisite: {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'tests/phpunit/multisite.xml']
			},
			'ms-files': {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'tests/phpunit/multisite.xml', '--group', 'ms-files']
			},
			'external-http': {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'phpunit.xml.dist', '--group', 'external-http']
			},
			'restapi-jsclient': {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'phpunit.xml.dist', '--group', 'restapi-jsclient']
			},
			'wp-api-client-fixtures': {
				cmd: 'phpunit',
				args: ['--verbose', '-c', 'phpunit.xml.dist', '--filter', 'WP_Test_REST_Schema_Initialization::test_build_wp_api_client_fixtures']
			}
		},
		terser: {
			// Settings for all subtasks
			options: {
				output: {
					ascii_only: true
				},
				ie8: true
			},
			// Subtasks
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: [
					'wp-admin/js/**/*.js',
					'wp-includes/js/*.js',
					'wp-includes/js/plupload/*.js',
					'wp-includes/js/mediaelement/wp-mediaelement.js',
					'wp-includes/js/mediaelement/wp-playlist.js',
					'wp-includes/js/mediaelement/mediaelement-migrate.js',
					'wp-includes/js/tinymce/plugins/wordpress/plugin.js',
					'wp-includes/js/tinymce/plugins/wp*/plugin.js',
					'wp-includes/js/filepond/*.js',

					// Exceptions
					'!wp-admin/js/custom-header.js', // Why? We should minify this.
					'!wp-admin/js/farbtastic.js',
					'!wp-admin/js/iris.min.js',
					'!wp-includes/js/backbone.*',
					'!wp-includes/js/masonry.min.js',
					'!wp-includes/js/swfobject.js',
					'!wp-includes/js/underscore.*',
					'!wp-includes/js/zxcvbn.min.js',
					'!wp-includes/js/wp-embed.js' // We have extra options for this, see terser:embed
				]
			},
			embed: {
				options: {
					compress: {
						conditionals: false
					}
				},
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: ['wp-includes/js/wp-embed.js']
			},
			media: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: [
					'wp-includes/js/media-audiovideo.js',
					'wp-includes/js/media-grid.js',
					'wp-includes/js/media-models.js',
					'wp-includes/js/media-views.js'
				]
			},
			jqueryui: {
				output: {
					// Preserve comments that start with a bang.
					comments: /^!/
				},
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: ['wp-includes/js/jquery/ui/*.js']
			},
			masonry: {
				output: {
					// Preserve comments that start with a bang.
					comments: /^!/
				},
				src: `${SOURCE_DIR}wp-includes/js/jquery/jquery.masonry.js`,
				dest: `${SOURCE_DIR}wp-includes/js/jquery/jquery.masonry.min.js`
			},
			imgareaselect: {
				src: `${SOURCE_DIR}wp-includes/js/imgareaselect/jquery.imgareaselect.js`,
				dest: `${SOURCE_DIR}wp-includes/js/imgareaselect/jquery.imgareaselect.min.js`
			}
		},
<<<<<<< HEAD
=======
		webpack: {
			prod: webpackConfig( { environment: 'production', buildTarget: WORKING_DIR } ),
			dev: webpackConfig( { environment: 'development', buildTarget: WORKING_DIR } ),
			watch: webpackConfig( { environment: 'development', watch: true } ),
			codemirror: require( './tools/webpack/codemirror.config.js' )( { buildTarget: WORKING_DIR } ),
		},
>>>>>>> 438132fd36 (External Libraries: Upgrade CodeMirror to latest v5 in addition to updating CSSLint, Esprima, HTMLHint, and JSONLint.)
		concat: {
			tinymce: {
				options: {
					separator: '\n',
					process(src, filepath) {
						return `// Source: ${filepath.replace( BUILD_DIR, '' )}\n${src}`;
					}
				},
				src: [
					`${BUILD_DIR}wp-includes/js/tinymce/tinymce.min.js`,
					`${BUILD_DIR}wp-includes/js/tinymce/themes/modern/theme.min.js`,
					`${BUILD_DIR}wp-includes/js/tinymce/plugins/*/plugin.min.js`
				],
				dest: `${BUILD_DIR}wp-includes/js/tinymce/wp-tinymce.min.js`
			},
			emoji: {
				options: {
					separator: '\n',
					process(src, filepath) {
						return `// Source: ${filepath.replace( BUILD_DIR, '' )}\n${src}`;
					}
				},
				src: [
					`${BUILD_DIR}wp-includes/js/twemoji.min.js`,
					`${BUILD_DIR}wp-includes/js/wp-emoji.min.js`
				],
				dest: `${BUILD_DIR}wp-includes/js/wp-emoji-release.min.js`
			},
			certificates: {
				options: {
					separator: '\n\n'
				},
				src: [
					SOURCE_DIR + 'wp-includes/certificates/legacy-1024bit.pem',
					'vendor/composer/ca-bundle/res/cacert.pem'
				],
				dest: SOURCE_DIR + 'wp-includes/certificates/ca-bundle.crt'
			}
		},
		imagemin: {
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: [
					'wp-{admin,includes}/images/**/*.{png,jpg,gif,jpeg}',
					'wp-includes/js/tinymce/skins/wordpress/images/*.{png,jpg,gif,jpeg}'
				],
				dest: SOURCE_DIR
			}
		},
		includes: {
			emoji: {
				src: `${BUILD_DIR}wp-includes/formatting.php`,
				dest: '.'
			},
			embed: {
				src: `${BUILD_DIR}wp-includes/embed.php`,
				dest: '.'
			}
		}
	});

	// Allow builds to be minimal
	if( grunt.option( 'minimal-copy' ) ) {
		const copyFilesOptions = grunt.config.get( 'copy.files.files' );
		copyFilesOptions[0].src.push( '!wp-content/plugins/**' );
		copyFilesOptions[0].src.push( '!wp-content/themes/!(twenty*)/**' );
		grunt.config.set( 'copy.files.files', copyFilesOptions );
	}

	// RTL task.
	grunt.registerTask(
		'rtl',
		[
			'rtlcss:core',
			'rtlcss:colors'
		]
	);

	// Color schemes task.
	grunt.registerTask(
		'colors',
		[
			'sass:colors',
			'postcss:colors'
		]
	);

	// JSHint task.
	grunt.registerTask(
		'jshint:corejs',
		[
			'jshint:grunt',
			'jshint:tests',
			'jshint:themes',
			'jshint:core',
			'jshint:media'
		]
	);

	// ESLint task.
	grunt.registerTask(
		'eslint:corejs',
		[
			'eslint:grunt',
			'eslint:core'
		]
	);

	grunt.registerTask(
		'restapi-jsclient',
		[
			'phpunit:restapi-jsclient',
			'qunit:compiled'
		]
	);

	grunt.registerTask(
		'precommit:image',
		'Detect OS and only run on linux',
		function() {
			if ( /linux/.test( process.platform ) ) {
				grunt.task.run( [ 'imagemin:core' ] );
			} else {
				grunt.log.writeln( 'Image minification should only run on Linux, `precommit:image` skipped.' );
			}
		}
	);

	grunt.registerTask(
		'precommit:js',
		[
			'rollup',
			'jshint:corejs',
			'eslint:corejs',
			'terser:masonry',
			'terser:imgareaselect'
		]
	);

	grunt.registerTask(
		'precommit:css',
		[
			'postcss:core'
		]
	);

	grunt.registerTask(
		'precommit:php',
		[
			'phpunit:wp-api-client-fixtures'
		]
	);

	grunt.registerTask(
		'precommit:emoji',
		[
			'emojiReplace'
		]
	);

	grunt.registerTask(
		'precommit',
		[
			'precommit:js',
			'precommit:css',
			'precommit:image',
			'precommit:emoji',
			'precommit:php',
			'precommit:git-conflicts',
			'precommit:npm-bug'
		]
	);

	grunt.registerTask(
		'precommit:verify',
		'Run precommit checks and verify no changed files.  Commit everything before running!',
		[
			'precommit',
			'precommit:check-for-changes'
		]
	);

	grunt.registerTask(
		'dev:git-version',
		function() {
			const done = this.async();

			if (
				process.env.CLASSICPRESS_GIT_VERSION &&
				/^[a-f0-9]{8}/.test( process.env.CLASSICPRESS_GIT_VERSION )
			) {
				grunt.log.ok(
					`Using git version from env var: ${process.env.CLASSICPRESS_GIT_VERSION.substr( 0, 8 )}`
				);
				grunt.config.set( 'dev.git-version', process.env.CLASSICPRESS_GIT_VERSION );
				done();
				return;
			}

			grunt.util.spawn( {
				cmd: 'git',
				args: [ 'rev-parse', 'HEAD' ]
			}, ( error, { stdout, stderr }, code ) => {
				if ( code !== 0 ) {
					grunt.fatal( `git rev-parse failed: code ${code}:\n${stdout}\n${stderr}` );
				}
				const hash = stdout.trim();
				if ( ! hash || hash.length !== 40 ) {
					grunt.fatal( `git rev-parse returned invalid value: ${hash}` );
				}
				grunt.config.set( 'dev.git-version', hash );
				grunt.log.ok(
					`Using git version from \`git rev-parse\`: ${hash.substr( 0, 8 )}`
				);
				done();
			} );
		}
	);

	grunt.registerTask(
		'precommit:check-for-changes',
		function() {
			grunt.task.requires( 'precommit' );

			const done = this.async();

			grunt.util.spawn( {
				cmd: 'git',
				args: [ 'ls-files', '-m' ]
			}, ( error, { stdout }, code ) => {
				if ( error ) {
					throw error;
				}
				if ( code !== 0 ) {
					throw new Error( `git ls-files failed: code ${code}` );
				}
				const files = stdout.split( '\n' )
					.map( f => f.trim() )
					.filter( f => f !== '' )
					.filter( f => f !== 'package-lock.json' );
				if ( files.length ) {
					grunt.log.writeln(
						'One or more files were modified when running the precommit checks:'
						.red
					);
					grunt.log.writeln();
					files.forEach( ( { yellow } ) => grunt.log.writeln( yellow ) );
					grunt.log.writeln();
					grunt.log.writeln(
						'Please run `grunt precommit` and commit the results.'
						.red.bold
					);
					grunt.log.writeln();
					throw new Error(
						'Modified files detected during precommit checks!'
					);
				} else {
					grunt.log.ok( 'No modified files detected.' );
				}
				done();
			} );
		}
	);

	grunt.registerTask(
		'precommit:git-conflicts',
		function() {
			const done = this.async();

			grunt.util.spawn( {
				cmd: 'bash',
				args: [ '-c', "git ls-files -z | xargs -0 grep -E -C3 -n --binary-files=without-match '(<<" + "<<<<<|^=======(\\s|$)|>>>>>" + ">>)'" ]
			}, ( error, { stdout, stderr }, code ) => {
				// Ignore error because it is populated for non-zero exit codes:
				// https://gruntjs.com/api/grunt.util#grunt.util.spawn
				// An exit code of 1 from `grep` means "no match" which is fine.
				// `xargs` reports this as exit code 123 (Linux) or 1 (OS X).
				if ( ( code !== 0 && code !== 1 && code !== 123 ) || stderr.length ) {
					grunt.fatal(
						`checking for changes failed: code ${code}:\n${stderr + stdout}`
					);
				}
				if ( stdout.trim().length ) {
					stdout.trim().split( '\n' ).forEach( line => {
						grunt.log.writeln(
							/^[^:]+:\d+:/.test( line ) ? line.red : line
						);
					} );
					grunt.fatal(
						'git conflict markers detected in the above files!'
					);
				}

				done();
			} );
		}
	);

	grunt.registerTask(
		'precommit:npm-bug',
		function() {
			// Check for and prevent https://github.com/npm/cli/issues/1685
			// This just adds needless noise to the npm files which already have a
			// very large commit history.
			const lockfileLines = fs.readFileSync(
				path.join( __dirname, 'package-lock.json' ),
				'utf8'
			).split( '\n' );
			const maxLinesToPrint = 9;
			let badLines = 0;
			lockfileLines.forEach( ( line, i ) => {
				const lineNumber = i + 1;
				if ( /"http:\/\//.test( line ) ) {
					if ( badLines < maxLinesToPrint ) {
						grunt.log.writeln(
							`package-lock.json line ${lineNumber}: ${line}`.yellow
						);
					}
					badLines++;
				}
			} );
			if ( badLines > 0 ) {
				if ( badLines > maxLinesToPrint ) {
					grunt.log.writeln(
						`... and ${badLines - maxLinesToPrint} more lines`.yellow
					);
				}
				grunt.log.writeln( 'The above lines may need to be fixed to https:// manually.' );
				grunt.log.writeln( 'See: https://github.com/ClassicPress/ClassicPress/pull/711' );
				grunt.fatal( 'package-lock.json contains invalid lines' );
			}
		}
	);

	grunt.registerTask(
		'rollup',
		function() {
			const done = this.async();
			grunt.util.spawn( {
				cmd: 'node',
				args: [ './node_modules/.bin/rollup', '--config' ]
			}, ( error, { stdout, stderr } ) => {
				if ( error ) {
					throw error;
				}
				console.log( stdout );
				console.error( stderr );
				done();
			} );
		}
	);

	grunt.registerTask(
		'emojiReplace',
		function() {
			const done = this.async();

			buildTools.replaceEmojiRegex()
			.then ( data => {
				let content = grunt.file.read( `${SOURCE_DIR}wp-includes/formatting.php` );
				content = content.replace( /\/\/ START: emoji arrays[\S\s]*\/\/ END: emoji arrays/g, data );
				grunt.file.write( `${SOURCE_DIR}wp-includes/formatting.php`, content );
				grunt.log.writeln( 'Emoji data replaced.' );
				done();
			} )
			.catch( error => {
				grunt.log.error( error );
				done( false );
			} );
		}
	);

<<<<<<< HEAD
	grunt.registerTask(
		'certificates:update',
		'Updates the Composer package responsible for root certificate updates.',
		function() {
			var done = this.async();
			var flags = this.flags;
			var args = [ 'update' ];

			grunt.util.spawn( {
				cmd: 'composer',
				args: args,
				opts: { stdio: 'inherit' }
			}, function( error ) {
				if ( flags.error && error ) {
					done( false );
				} else {
					done( true );
				}
			} );
		}
	);

	grunt.registerTask(
		'certificates:upgrade',
		[
			'certificates:update',
			'concat:certificates'
		]
	);

	grunt.registerTask(
		'copy:script-loader',
		[
			'dev:git-version',
			'copy:script-loader-impl'
		]
	);

	grunt.registerTask(
		'copy:all',
		[
			'copy:files',
=======
	grunt.registerTask( 'copy:js', [
		'copy:npm-packages',
		'copy:vendor-js',
		'copy:admin-js',
		'copy:includes-js'
	] );

	grunt.registerTask( 'uglify:all', [
		'uglify:core',
		'uglify:emoji-loader',
		'uglify:jquery-ui',
		'uglify:imgareaselect',
		'uglify:jqueryform',
		'uglify:moment'
	] );

	grunt.registerTask( 'build:codemirror', [
		'webpack:codemirror',
		'cssmin:codemirror',
		'usebanner:codemirror',
		'copy:codemirror'
	] );

	grunt.registerTask( 'build:webpack', [
		'clean:webpack-assets',
		'webpack:prod',
		'webpack:dev',
		'clean:interactivity-assets',
	] );

	grunt.registerTask( 'build:js', [
		'clean:js',
		'build:webpack',
		'copy:js',
		'file_append',
		'uglify:all',
		'concat:tinymce',
		'concat:emoji'
	] );

	grunt.registerTask( 'build:css', [
		'clean:css',
>>>>>>> 438132fd36 (External Libraries: Upgrade CodeMirror to latest v5 in addition to updating CSSLint, Esprima, HTMLHint, and JSONLint.)
			'copy:wp-admin-css-compat-rtl',
			'copy:wp-admin-css-compat-min',
			'copy:script-loader',
			'copy:version'
		]
	);

	grunt.registerTask(
		'js-dependencies',
		[
			'clean:vendor-js',
			'clean:package-js',
			'clean:mediaelement-js',
			'copy:vendor-js',
			'webpack:dev',
			'webpack:min',
			'usebanner:js',
			'copy:mediaelement-js'
		]
	);

	grunt.registerTask(
		'build',
		[
			'clean:all',
			'js-dependencies',
			'copy:all',
			'cssmin:core',
			'colors',
			'rtl',
			'cssmin:rtl',
			'cssmin:colors',
<<<<<<< HEAD
			'terser:core',
			'terser:embed',
			'terser:jqueryui',
			'concat:tinymce',
			'concat:emoji',
			'includes:emoji',
			'includes:embed',
			'usebanner:css'
		]
	);

	grunt.registerTask(
		'prerelease',
		[
=======
		'cssmin:themes',
		'usebanner:files'
	] );

	grunt.registerTask( 'certificates:upgrade-package', 'Upgrades the package responsible for supplying the certificate authority certificate store bundled with WordPress.', function() {
		var done = this.async();
		var flags = this.flags;
		var spawn = require( 'child_process' ).spawnSync;
		var fs = require( 'fs' );

		// Ensure that `composer update` has been run and the dependency is installed.
		if ( ! fs.existsSync( 'vendor' ) || ! fs.existsSync( 'vendor/composer' ) || ! fs.existsSync( 'vendor/composer/ca-bundle' ) ) {
			grunt.log.error( 'composer/ca-bundle dependency is missing. Please run `composer update` before attempting to upgrade the certificate bundle.' );
			done( false );
			return;
		}

		/*
		 * Because the `composer/ca-bundle` is pinned to an exact version to ensure upgrades are applied intentionally,
		 * the `composer update` command will not upgrade the dependency. Instead, `composer require` must be called,
		 * but the specific version being upgraded to must be known and passed to the command.
		 */
		var outdatedResult = spawn( 'composer', [ 'outdated', 'composer/ca-bundle', '--format=json' ] );

		if ( outdatedResult.status !== 0 ) {
			grunt.log.error( 'Failed to get the package information for composer/ca-bundle.' );
			done( false );
			return;
		}

		var packageInfo;
		try {
			var stdout = outdatedResult.stdout.toString().trim();
			if ( ! stdout ) {
				grunt.log.writeln( 'The latest version is already installed.' );
				done( true );
				return;
			}
			packageInfo = JSON.parse( stdout );
		} catch ( e ) {
			grunt.log.error( 'Failed to parse the package information for composer/ca-bundle.' );
			done( false );
			return;
		}

		// Check for the version information needed to perform the necessary comparisons.
		if ( ! packageInfo.versions || ! packageInfo.versions[0] || ! packageInfo.latest ) {
			grunt.log.error( 'Could not determine version information for composer/ca-bundle.' );
			done( false );
			return;
		}

		var currentVersion = packageInfo.versions[0];
		var latestVersion = packageInfo.latest;

		// Compare versions to ensure we actually need to update
		if ( currentVersion === latestVersion ) {
			grunt.log.writeln( 'The latest version is already installed: ' + latestVersion + '.' );
			done( true );
			return;
		}

		grunt.log.writeln( 'Installed version: ' + currentVersion );
		grunt.log.writeln( 'New version found: ' + latestVersion );

		// Upgrade to the latest version and change the pinned version in composer.json.
		var args = [ 'require', 'composer/ca-bundle:' + latestVersion, '--dev' ];

		grunt.util.spawn( {
			cmd: 'composer',
			args: args,
			opts: { stdio: 'inherit' }
		}, function( error ) {
			if ( flags.error && error ) {
				done( false );
			} else {
				grunt.log.writeln( 'Successfully updated composer/ca-bundle to ' + latestVersion );
				done( true );
			}
		} );
	} );

	grunt.registerTask( 'build:certificates', [
		'concat:certificates'
	] );

	grunt.registerTask( 'certificates:upgrade', [
		'certificates:upgrade-package',
		'copy:certificates',
		'build:certificates'
	] );

	grunt.registerTask( 'build:files', [
		'clean:files',
		'copy:files',
		'copy:version',
	] );

	grunt.registerTask( 'replace:workflow-references-local-to-remote', [
		'copy:workflow-references-local-to-remote',
	]);

	grunt.registerTask( 'replace:workflow-references-remote-to-local', [
		'copy:workflow-references-remote-to-local',
	]);

	grunt.registerTask( 'post-branching', [
		'clean:workflows',
		'replace:workflow-references-local-to-remote'
	]);

	/**
	 * Build verification tasks.
	 */
	grunt.registerTask( 'verify:build', [
		'verify:old-files',
		'verify:source-maps',
	] );

	/**
	 * Build assertions to ensure no project files are inside `$_old_files` in the build directory.
	 *
	 * @ticket 36083
	 */
	grunt.registerTask( 'verify:old-files', function() {
		const file = `${ BUILD_DIR }wp-admin/includes/update-core.php`;

		assert(
			fs.existsSync( file ),
			'The build/wp-admin/includes/update-core.php file does not exist.'
		);

		const contents = fs.readFileSync( file, {
			encoding: 'utf8',
		} );

		assert(
			contents.length > 0,
			'The build/wp-admin/includes/update-core.php file must not be empty.'
		);

		const match = contents.match( /\$_old_files = array\(([^\)]+)\);/ );

		assert(
			match.length > 0,
			'The build/wp-admin/includes/update-core.php file does not include an `$_old_files` array.'
		);

		const files = match[1].split( '\n\t' ).filter( function( file ) {
			// Filter out empty lines.
			if ( '' === file ) {
				return false;
			}

			// Filter out commented out lines.
			if ( 0 === file.indexOf( '/' ) ) {
				return false;
			}

			return true;
		} ).map( function( file ) {
			// Strip leading and trailing single quotes and commas.
			return file.replace( /^\'|\',$/g, '' );
		} );

		files.forEach(function( file ){
			const search = `${ BUILD_DIR }${ file }`;
			assert(
				false === fs.existsSync( search ),
				`${ search } should not be present in the $_old_files array.`
			);
		});
	} );

	/**
	 * Compiled JavaScript files may link to sourcemaps. In some cases,
	 * the source map may not be available, which can cause 404 errors when
	 * browsers try to download the sourcemap from the referenced URLs.
	 * Ensure that sourcemap links are not included in JavaScript files.
	 *
	 * @ticket 24994
	 * @ticket 46218
	 * @ticket 60348
	 */
	grunt.registerTask( 'verify:source-maps', function() {
		const ignoredFiles = [
			'build/wp-includes/js/dist/components.js',
			'build/wp-includes/js/dist/data.js',
		];
		const files = buildFiles.reduce( ( acc, path ) => {
			// Skip excluded paths and any path that isn't a file.
			if ( '!' === path[0] || '**' !== path.substr( -2 ) ) {
				return acc;
			}
			acc.push( ...glob.sync( `${ BUILD_DIR }/${ path }/*.js` ) );
			return acc;
		}, [] );

		assert(
			files.length > 0,
			'No JavaScript files found in the build directory.'
		);

		files
			.filter(file => ! ignoredFiles.includes( file) )
			.forEach( function( file ) {
				const contents = fs.readFileSync( file, {
					encoding: 'utf8',
				} );
				// `data:` URLs are allowed:
				const doesNotHaveSourceMap = ! /^\/\/# sourceMappingURL=((?!data:).)/m.test(contents);

				assert(
					doesNotHaveSourceMap,
					`The ${ file } file must not contain a sourceMappingURL.`
				);
			} );
	} );

	grunt.registerTask( 'build', function() {
		if ( grunt.option( 'dev' ) ) {
			grunt.task.run( [
				'build:js',
				'build:css',
				'build:codemirror',
				'gutenberg-sync',
				'gutenberg-copy',
				'copy-vendor-scripts',
				'build:certificates'
			] );
		} else {
			grunt.task.run( [
				'build:certificates',
				'build:files',
				'build:js',
				'build:css',
				'build:codemirror',
				'gutenberg-sync',
				'gutenberg-copy',
				'copy-vendor-scripts',
				'replace:source-maps',
				'verify:build'
			] );
		}
	} );

	grunt.registerTask( 'prerelease', [
		'format:php:error',
>>>>>>> 438132fd36 (External Libraries: Upgrade CodeMirror to latest v5 in addition to updating CSSLint, Esprima, HTMLHint, and JSONLint.)
			'precommit:php',
			'precommit:js',
			'precommit:css',
			'precommit:image'
		]
	);

	// Testing tasks.
	grunt.registerMultiTask(
		'phpunit',
		'Runs PHPUnit tests, including the ajax, external-http, and multisite tests.',
		function() {
			grunt.util.spawn( {
				cmd: this.data.cmd,
				args: this.data.args,
				opts: { stdio: 'inherit' }
			}, this.async() );
		}
	);

	grunt.registerTask(
		'qunit:local',
		'Runs QUnit tests with a local server.',
		[
			'connect',
			'qunit'
		]
	);

	grunt.registerTask(
		'qunit:compiled',
		'Runs QUnit tests on compiled as well as uncompiled scripts.',
		[
			'build',
			'copy:qunit',
			'qunit:local'
		]
	);

	grunt.registerTask(
		'test',
		'Runs all QUnit and PHPUnit tasks.',
		[
			'qunit:compiled',
			'phpunit'
		]
	);

	// Default task.
	grunt.registerTask(
		'default',
		[
			'build'
		]
	);
};
