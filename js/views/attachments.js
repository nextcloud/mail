/**
 * Mail
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
	var OC = require('OC');
	var Handlebars = require('handlebars');
	var AttachmentView = require('views/attachment');
	var AttachmentsTemplate = require('text!templates/attachments.html');

	return Marionette.CompositeView.extend({
		collection: null,
		childView: AttachmentView,
		childViewContainer: 'ul',
		template: Handlebars.compile(AttachmentsTemplate),
		events: {
			'click #mail_new_attachment': 'addAttachment'
		},
		modelEvents: {
			'change': 'render'
		},
		initialize: function(options) {
			this.collection = options.collection;
		},
		addAttachment: function() {
			var _this = this;
			OC.dialogs.filepicker(
				t('mail', 'Choose a file to add as attachment'),
				function(path) {
					_this.collection.add([
						{
							fileName: path
						}
					]);
				});
		}
	});
});
