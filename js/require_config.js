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

'use strict';

requirejs.config({
	baseUrl: './../../../apps/mail/js',
	paths: {
		/**
		 * Application
		 */
		app: 'mail',
		marionette: 'backbone.marionette',
		handlebars: 'handlebars-v1.3.0',
		/**
		 * Libraries
		 */
		domReady: 'node_modules/domready/ready.min',
		//jquery: '../../../core/vendor/jquery/jquery.min',
		storage: 'jquery.storageapi',
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