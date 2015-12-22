/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var $ = require('jquery');
	var OC = require('OC');

	return Marionette.LayoutView.extend({
		el: $('#app'),
		regions: {
			navigation: '#app-navigation',
			content: '#app-content',
			setup: '#setup'
		},
                events: {
			'click #mail_new_message': 'onNewMessageClick'
                },
		initialize: function() {
			this.bindUIElements();

			// Hide notification favicon when switching back from
			// another browser tab
			$(document).on('show', this.onDocumentShow);
		},
		onDocumentShow: function(e) {
			e.preventDefault();
			require('app').UI.changeFavicon(OC.filePath('mail', 'img', 'favicon.png'));
		},
                onNewMessageClick: function(e) {
			e.preventDefault();
			require('app').UI.openComposer();
                },
		render: function() {
			// This view doesn't need rendering
		}
	});
});
