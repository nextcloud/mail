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
module.exports = function (grunt) {
	// Project configuration.
	grunt.initConfig({
		jscs: {
        		src: 'js/*.js',
			options: {
				config: '.jscsrc',
				verbose: true
			}
		}
	});

	// jscs
	grunt.loadNpmTasks('grunt-jscs');

	// Default task
	grunt.registerTask('default', ['grunt-jscs']);
};
