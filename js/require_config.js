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
			 * Libraries
			 */
			backbone: 'vendor/backbone/backbone',
			'backbone.radio': 'vendor/backbone.radio/build/backbone.radio',
			domready: 'vendor/domReady/domReady',
			handlebars: 'vendor/handlebars/handlebars',
			marionette: 'vendor/backbone.marionette/lib/backbone.marionette',
			underscore: 'vendor/underscore/underscore',
			text: 'vendor/text/text'
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
		'app',
		'notification'
	]);
})();
