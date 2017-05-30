/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var AttachmentTemplate = require('text!templates/attachment.html');

	return Marionette.View.extend({
		tagName: 'li',
		template: Handlebars.compile(AttachmentTemplate),
		events: {
			'click .icon-delete': 'removeAttachment'
		},
		modelEvents: {
			'change:progress': 'onProgress'
		},
		removeAttachment: function() {
			this.model.collection.remove(this.model);
		},
		onProgress: function() {
		}
	});
});
