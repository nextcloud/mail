/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* global module */
module.exports = function(grunt) {
	// Project configuration.
	grunt.initConfig({
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: ['Gruntfile.js', 'js/*.js', 'js/models/*.js', 'js/views/*.js',
				'!js/build/build.js', '!js/webpack.*.js']
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

	// jshint
	grunt.loadNpmTasks('grunt-contrib-jshint');

	// Karma unit tests
	grunt.loadNpmTasks('grunt-karma');

	// Default task
	grunt.registerTask('default', ['jshint', 'karma:continuous']);
};
