/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );
	grunt.util = require( 'grunt-legacy-util' );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: ['Gruntfile.js']
			},
			all: ['Gruntfile.js', 'js/*.js', '!js/*.min.js']
		},
		checktextdomain: {
			options: {
				correct_domain: false,
				text_domain: ['communaute-blindee'],
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: ['**/*.php', '!**/node_modules/**'],
				expand: true
			}
		},
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: ['/node_modules'],
					mainFile: 'communaute-blindee.php',
					potFilename: 'communaute-blindee.pot',
					processPot: function( pot ) {
						pot.headers['last-translator']      = 'imath <contact@imathi.eu>';
						pot.headers['language-team']        = 'FRENCH <contact@imathi.eu>';
						pot.headers['report-msgid-bugs-to'] = 'https://github.com/imath/communaute-blindee/issues';
						return pot;
					},
					type: 'wp-plugin'
				}
			}
        },
		jsvalidate:{
			src: ['js/*.js', '!js/*.min.js'],
			options:{
				globals: {},
				esprimaOptions:{},
				verbose: false
			}
        },
        clean: {
			all: ['templates/css/*.min.css', 'js/*.min.js']
        },
        uglify: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.js',
				src: ['js/*.js', '!js/*.min.js']
			}
		},
		cssmin: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.css',
				src: ['templates/css/*.css', '!templates/css/*.min.css']
			}
		}
	} );

    grunt.registerTask( 'jstest', ['jsvalidate', 'jshint'] );

    grunt.registerTask( 'translate', ['checktextdomain', 'makepot'] );

    grunt.registerTask( 'shrink', ['clean', 'cssmin', 'uglify'] );

	grunt.registerTask( 'release', ['checktextdomain', 'makepot', 'jstest', 'shrink'] );

	// Default task.
	grunt.registerTask( 'default', ['checktextdomain'] );
};
