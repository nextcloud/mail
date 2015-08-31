/* global module */

/**
 * ownCloud - Mail
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
			all: ['Gruntfile.js', 'js/*.js', 'js/models/*.js', 'js/views/*.js']
		},
		jscs: {
			src: '<%= jshint.all %>',
			options: {
				config: '.jscsrc',
				verbose: true
			}
		}

	});

	// jscs
	grunt.loadNpmTasks('grunt-jscs');

	// jshint
	grunt.loadNpmTasks('grunt-contrib-jshint');

	// Default task
	grunt.registerTask('default', ['jscs', 'jshint']);
};
