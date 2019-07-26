/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */
/* globals Set */
const webpackConfig = require( './webpack.config.prod' );
const webpackDevConfig = require( './webpack.config.dev' );
const path = require('path');

const spawn = require( 'child_process' ).spawnSync;
const SOURCE_DIR = 'src/';
const BUILD_DIR = 'build/';
const BANNER_TEXT = '/*! This file is auto-generated */';
const autoprefixer = require( 'autoprefixer' );

module.exports = function(grunt) {
    const puppeteerOptions = {};
    if (process.env.TRAVIS) {
		// Avoid error: Failed to launch chrome! No usable sandbox!
		puppeteerOptions.args = [ '--no-sandbox' ];
	}

    // Load tasks.
    require('matchdep').filterDev(['grunt-*', '!grunt-legacy-util']).forEach( grunt.loadNpmTasks );
    // Load legacy utils
    grunt.util = require('grunt-legacy-util');

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
					'wp-includes/css/*.css'
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
			options: {
				position: 'top',
				banner: BANNER_TEXT,
				linebreak: true
			},
			files: {
				src: [
					`${BUILD_DIR}wp-admin/css/*.min.css`,
					`${BUILD_DIR}wp-includes/css/*.min.css`,
					`${BUILD_DIR}wp-admin/css/colors/*/*.css`
				]
			}
		},
		clean: {
			all: [BUILD_DIR],
			dynamic: {
				dot: true,
				expand: true,
				cwd: BUILD_DIR,
				src: []
			},
			tinymce: ['<%= concat.tinymce.dest %>'],
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
							// Exclude Hello Dolly (it's used in the source tree for tests)
							'!wp-content/plugins/hello.php',
							// Ignore unminified versions of external libs we don't ship:
							'!wp-includes/js/backbone.js',
							'!wp-includes/js/underscore.js',
							'!wp-includes/js/jquery/jquery.masonry.js',
							'!wp-includes/js/jquery/ui/*.js',
							'!wp-includes/js/tinymce/tinymce.js',
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
							/* jshint quotmark: true */
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

							/* jshint quotmark: true */
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
		sass: {
			colors: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.css',
				src: ['wp-admin/css/colors/*/colors.scss'],
				options: {
					implementation: require( 'node-sass' ),
					outputStyle: 'expanded'
				}
			}
		},
		cssmin: {
			options: {
				compatibility: 'ie7'
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
					'wp-includes/js/mediaelement/wp-mediaelement.css'
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
				src: ['Gruntfile.js']
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
					'!wp-includes/js/media-*',
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
					'!wp-admin/js/farbtastic.js',
					'!wp-includes/js/backbone*.js',
					'!wp-includes/js/swfobject.js',
					'!wp-includes/js/underscore*.js',
					'!wp-includes/js/colorpicker.js',
					'!wp-includes/js/hoverIntent.js',
					'!wp-includes/js/json2.js',
					'!wp-includes/js/tw-sack.js',
					'!wp-includes/js/twemoji.js',
					'!**/*.min.js'
				],
				// Remove once other JSHint errors are resolved
				options: {
					curly: false,
					eqeqeq: false
				},
				// Limit JSHint's run to a single specified file:
				//
				//    grunt jshint:core --file=filename.js
				//
				// Optionally, include the file path:
				//
				//    grunt jshint:core --file=path/to/filename.js
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
				//    grunt jshint:plugins --dir=foldername
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
				options: {
					urls: [
						'http://localhost:8008/tests/qunit/compiled.html',
						'http://localhost:8008/tests/qunit/index.html'
					],
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
		uglify: {
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

					// Exceptions
					'!wp-admin/js/custom-header.js', // Why? We should minify this.
					'!wp-admin/js/farbtastic.js',
					'!wp-admin/js/iris.min.js',
					'!wp-includes/js/backbone.*',
					'!wp-includes/js/masonry.min.js',
					'!wp-includes/js/swfobject.js',
					'!wp-includes/js/underscore.*',
					'!wp-includes/js/zxcvbn.min.js',
					'!wp-includes/js/wp-embed.js' // We have extra options for this, see uglify:embed
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
				options: {
					// Preserve comments that start with a bang.
					preserveComments: /^!/
				},
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: ['wp-includes/js/jquery/ui/*.js']
			},
			masonry: {
				options: {
					// Preserve comments that start with a bang.
					preserveComments: /^!/
				},
				src: `${SOURCE_DIR}wp-includes/js/jquery/jquery.masonry.js`,
				dest: `${SOURCE_DIR}wp-includes/js/jquery/jquery.masonry.min.js`
			},
			imgareaselect: {
				src: `${SOURCE_DIR}wp-includes/js/imgareaselect/jquery.imgareaselect.js`,
				dest: `${SOURCE_DIR}wp-includes/js/imgareaselect/jquery.imgareaselect.min.js`
			}
		},
		webpack: {
			prod: webpackConfig,
			dev: webpackDevConfig
		},
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
				dest: `${BUILD_DIR}wp-includes/js/tinymce/wp-tinymce.js`
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
			}
		},
		compress: {
			tinymce: {
				options: {
					mode: 'gzip',
					level: 9
				},
				src: '<%= concat.tinymce.dest %>',
				dest: `${BUILD_DIR}wp-includes/js/tinymce/wp-tinymce.js.gz`
			}
		},
		jsvalidate:{
			options: {
				globals: {},
				esprimaOptions:{},
				verbose: false
			},
			build: {
				files: {
					src: [
						`${BUILD_DIR}wp-{admin,includes}/**/*.js`,
						`${BUILD_DIR}wp-content/themes/twenty*/**/*.js`
					]
				}
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
		},
		replace: {
			emojiRegex: {
				options: {
					patterns: [
						{
							match: /\/\/ START: emoji arrays[\S\s]*\/\/ END: emoji arrays/g,
							replacement() {
                                let regex;
                                let files;
                                let partials;
                                let partialsSet;
                                let entities;
                                let emojiArray;

                                grunt.log.writeln( 'Fetching list of Twemoji files...' );

                                // Fetch a list of the files that Twemoji supplies
                                files = spawn( 'svn', [ 'ls', 'https://github.com/twitter/twemoji/branches/gh-pages/2/svg' ] );
                                if ( 0 !== files.status ) {
									grunt.fatal( 'Unable to fetch Twemoji file list' );
								}

                                entities = files.stdout.toString();

                                // Tidy up the file list
                                entities = entities.replace( /\.svg/g, '' );
                                entities = entities.replace( /^$/g, '' );

                                // Convert the emoji entities to HTML entities
                                partials = entities = entities.replace( /([a-z0-9]+)/g, '&#x$1;' );

                                // Remove the hyphens between the HTML entities
                                entities = entities.replace( /-/g, '' );

                                // Sort the entities list by length, so the longest emoji will be found first
                                emojiArray = entities.split( '\n' ).sort( ( a, b ) => {
                                    return b.length - a.length;
                                } );

                                // Convert the entities list to PHP array syntax
                                entities = `'${emojiArray.filter( val => val.length >= 8 ? val : false ).join( '\', \'' )}'`;

                                // Create a list of all characters used by the emoji list
                                partials = partials.replace( /-/g, '\n' );

                                // Set automatically removes duplicates
                                partialsSet = new Set( partials.split( '\n' ) );

                                // Convert the partials list to PHP array syntax
                                partials = `'${Array.from( partialsSet ).filter( val => val.length >= 8 ? val : false ).join( '\', \'' )}'`;

                                regex = '// START: emoji arrays\n';
                                regex += `\t$entities = array( ${entities} );\n`;
                                regex += `\t$partials = array( ${partials} );\n`;
                                regex += '\t// END: emoji arrays';

                                return regex;
                            }
						}
					]
				},
				files: [
					{
						expand: true,
						flatten: true,
						src: [
							`${SOURCE_DIR}wp-includes/formatting.php`
						],
						dest: `${SOURCE_DIR}wp-includes/`
					}
				]
			}
		},
		_watch: {
			all: {
				files: [
					`${SOURCE_DIR}**`,
					`!${SOURCE_DIR}wp-includes/js/media/**`,
					// Ignore version control directories.
					`!${SOURCE_DIR}**/.{svn,git}/**`
				],
				tasks: ['clean:dynamic', 'copy:dynamic'],
				options: {
					dot: true,
					spawn: false,
					interval: 2000
				}
			},
			config: {
				files: [
					'Gruntfile.js',
					'webpack-dev.config.js',
					'webpack.config.js'
				]
			},
			colors: {
				files: [`${SOURCE_DIR}wp-admin/css/colors/**`],
				tasks: ['sass:colors']
			},
			rtl: {
				files: [
					`${SOURCE_DIR}wp-admin/css/*.css`,
					`${SOURCE_DIR}wp-includes/css/*.css`
				],
				tasks: ['rtlcss:dynamic'],
				options: {
					spawn: false,
					interval: 2000
				}
			},
			test: {
				files: [
					'tests/qunit/**',
					'!tests/qunit/editor/**'
				],
				tasks: ['connect', 'qunit']
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


    // Register tasks.
    grunt.loadNpmTasks('@lodder/grunt-postcss');

    // Webpack task.
    grunt.loadNpmTasks( 'grunt-webpack' );

    // Connect task (local server for QUnit tests).
    grunt.loadNpmTasks( 'grunt-contrib-connect' );

    // RTL task.
    grunt.registerTask('rtl', ['rtlcss:core', 'rtlcss:colors']);

    // Color schemes task.
    grunt.registerTask('colors', ['sass:colors', 'postcss:colors']);

    // JSHint task.
    grunt.registerTask( 'jshint:corejs', [
		'jshint:grunt',
		'jshint:tests',
		'jshint:themes',
		'jshint:core',
		'jshint:media'
	] );

    grunt.registerTask( 'restapi-jsclient', [
		'phpunit:restapi-jsclient',
		'qunit:compiled'
	] );

    grunt.renameTask( 'watch', '_watch' );

    grunt.registerTask( 'watch', function() {
		if ( ! this.args.length || this.args.includes('webpack') ) {

			grunt.task.run( 'webpack:dev' );
		}

		grunt.task.run( `_${this.nameArgs}` );
	} );

    grunt.registerTask( 'precommit:image', [
		'imagemin:core'
	] );

    grunt.registerTask( 'precommit:js', [
		'webpack:prod',
		'jshint:corejs',
		'uglify:masonry',
		'uglify:imgareaselect'
	] );

    grunt.registerTask( 'precommit:css', [
		'postcss:core'
	] );

    grunt.registerTask( 'precommit:php', [
		'phpunit:wp-api-client-fixtures'
	] );

    grunt.registerTask( 'precommit:emoji', [
		'replace:emojiRegex'
	] );

    grunt.registerTask( 'precommit', [
		'precommit:js',
		'precommit:css',
		'precommit:image',
		'precommit:emoji',
		'precommit:php'
	] );

    grunt.registerTask(
		'precommit:verify',
		'Run precommit checks and verify no changed files.  Commit everything before running!',
		[
			'precommit',
			'precommit:check-for-changes'
		]
	);

    grunt.registerTask( 'dev:git-version', function() {
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
		}, (error, {stdout}, code) => {
			if ( error ) {
				throw error;
			}
			if ( code !== 0 ) {
				throw new Error( `git rev-parse failed: code ${code}` );
			}
			const hash = stdout.trim();
			if ( ! hash || hash.length !== 40 ) {
				throw new Error( `git rev-parse returned invalid value: ${hash}` );
			}
			grunt.config.set( 'dev.git-version', hash );
			grunt.log.ok(
				`Using git version from \`git rev-parse\`: ${hash.substr( 0, 8 )}`
			);
			done();
		} );
	} );

    grunt.registerTask( 'precommit:check-for-changes', function() {
		grunt.task.requires( 'precommit' );

		const done = this.async();

		grunt.util.spawn( {
			cmd: 'git',
			args: [ 'ls-files', '-m' ]
		}, (error, {stdout}, code) => {
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
				files.forEach( ({yellow}) => grunt.log.writeln( yellow ) );
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
	} );

    grunt.registerTask( 'copy:script-loader', [
		'dev:git-version',
		'copy:script-loader-impl'
	] );

    grunt.registerTask( 'copy:all', [
		'copy:files',
		'copy:wp-admin-css-compat-rtl',
		'copy:wp-admin-css-compat-min',
		'copy:script-loader',
		'copy:version'
	] );

    grunt.registerTask( 'build', [
		'clean:all',
		'copy:all',
		'cssmin:core',
		'colors',
		'rtl',
		'cssmin:rtl',
		'cssmin:colors',
		'uglify:core',
		'uglify:embed',
		'uglify:jqueryui',
		'concat:tinymce',
		'compress:tinymce',
		'clean:tinymce',
		'concat:emoji',
		'includes:emoji',
		'includes:embed',
		'usebanner',
		'jsvalidate:build'
	] );

    grunt.registerTask( 'prerelease', [
		'precommit:php',
		'precommit:js',
		'precommit:css',
		'precommit:image'
	] );

    // Testing tasks.
    grunt.registerMultiTask('phpunit', 'Runs PHPUnit tests, including the ajax, external-http, and multisite tests.', function() {
		grunt.util.spawn({
			cmd: this.data.cmd,
			args: this.data.args,
			opts: {stdio: 'inherit'}
		}, this.async());
	});

    grunt.registerTask('qunit:local', 'Runs QUnit tests with a local server.',
		['connect', 'qunit']);

    grunt.registerTask('qunit:compiled', 'Runs QUnit tests on compiled as well as uncompiled scripts.',
		['build', 'copy:qunit', 'qunit:local']);

    grunt.registerTask('test', 'Runs all QUnit and PHPUnit tasks.', ['qunit:compiled', 'phpunit']);

    // Travis CI tasks.
    grunt.registerTask(
		'travis:precommit-and-js',
		'Runs precommit checks and JavaScript tests on Travis CI.',
		[
			'precommit:verify', // -> precommit:js -> jshint:corejs
			'qunit:compiled'
		]
	);
    grunt.registerTask(
		'travis:phpunit',
		'Runs PHPUnit tests on Travis CI.',
		'phpunit'
	);

    // Patch task.
    grunt.renameTask('patch_wordpress', 'patch');

    // Add an alias `apply` of the `patch` task name.
    grunt.registerTask('apply', 'patch');

    // Default task.
    grunt.registerTask('default', ['build']);

    /*
	 * Automatically updates the `:dynamic` configurations
	 * so that only the changed files are updated.
	 */
    grunt.event.on('watch', (action, filepath, target) => {
		let src;

		if ( ![ 'all', 'rtl', 'webpack' ].includes(target) ) {
			return;
		}

		src = [ path.relative( SOURCE_DIR, filepath ) ];

		if ( action === 'deleted' ) {
			grunt.config( [ 'clean', 'dynamic', 'src' ], src );
		} else {
			grunt.config( [ 'copy', 'dynamic', 'src' ], src );

			if ( target === 'rtl' ) {
				grunt.config( [ 'rtlcss', 'dynamic', 'src' ], src );
			}
		}
	});
};
