/* global requirejs */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

(function() {
	'use strict';

	requirejs.config({
		baseUrl: './../../../apps/mail/js',
		paths: {
			/**
			 * Application
			 */
			app: 'mail',
			/**
			 * Libraries
			 */
			backbone: 'vendor/backbone/backbone',
			domready: 'vendor/domready/ready.min',
			handlebars: 'vendor/handlebars/handlebars',
			marionette: 'vendor/backbone.marionette/lib/backbone.marionette',
			underscore: '../../../core/vendor/underscore/underscore'
		},
		shim: {
			// TODO: remove once min-oc-version is 8.0
			handlebars: {
				exports: 'Handlebars'
			},
			// END TODO
			jquery: {
				exports: '$'
			}
		}
	});

	require([
		'init',
		'notification'
	]);
})();
