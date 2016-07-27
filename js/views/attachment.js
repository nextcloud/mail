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

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var AttachmentTemplate = require('text!templates/attachment.html');

	return Marionette.ItemView.extend({
		tagName: 'li',
		template: Handlebars.compile(AttachmentTemplate),
		events: {
			'click .icon-delete': 'removeAttachment',
			'click #mail-new-attachment-local': 'addAttachmentLocal'
		},
		removeAttachment: function() {
			this.model.collection.remove(this.model);
		}
	});
});
