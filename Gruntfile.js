/* global module */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
module.exports = function(grunt) {
	// Project configuration.
	grunt.initConfig({
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: ['Gruntfile.js', 'js/*.js', 'js/models/*.js', 'js/views/*.js',
				'!js/mail.min.js', '!js/webpack.*.js']
		},
		jscs: {
			src: '<%= jshint.all %>',
			options: {
				config: '.jscsrc',
				verbose: true
			}
		},
		karma: {
			unit: {
				configFile: 'karma.conf.js',
				autoWatch: true
			},
			continuous: {
				configFile: 'karma.conf.js',
				browsers: ['PhantomJS'],
				singleRun: true,
			}
		}
	});
	// jscs
	grunt.loadNpmTasks('grunt-jscs');

	// jshint
	grunt.loadNpmTasks('grunt-contrib-jshint');

	// Karma unit tests
	grunt.loadNpmTasks('grunt-karma');

	// Default task
	grunt.registerTask('default', ['jscs', 'jshint', 'karma:continuous']);
};
