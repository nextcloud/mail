/* global requirejs */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

(function(OC) {
	'use strict';

	requirejs.config({
		baseUrl: './../../../apps/mail/js',
		paths: {
			/**
			 * Libraries
			 */
			backbone: 'vendor/backbone/backbone',
			'backbone.radio': 'vendor/backbone.radio/build/backbone.radio',
			davclient: 'vendor/davclient.js/lib/client',
			domready: 'vendor/domReady/domReady',
			'es6-promise': 'vendor/es6-promise/es6-promise.min',
			handlebars: 'vendor/handlebars/handlebars',
			ical: 'vendor/ical.js/build/ical.min',
			marionette: 'vendor/backbone.marionette/lib/backbone.marionette',
			underscore: 'vendor/underscore/underscore',
			text: 'vendor/text/text'
		},
		shim: {
			davclient: {
				exports: 'dav'
			},
			ical: {
				exports: 'ICAL'
			}
		}
	});
	// avoid optimization errors
	requirejs.config({
		baseUrl: OC.linkTo('mail', 'js')
	});

	requirejs.createNode = function(config) {
		var node = config.xhtml ?
				document.createElementNS('http://www.w3.org/1999/xhtml', 'html:script') :
				document.createElement('script');
		node.type = config.scriptType || 'text/javascript';
		node.charset = 'utf-8';
		node.async = true;

		node.setAttribute('nonce', btoa(OC.requestToken));
		return node;
	};

	require([
		'init'
	]);
})(OC);
