/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var AttachmentView = require('views/messageattachment');
	var AttachmentsTemplate = require('text!templates/message-attachments.html');

	/**
	 * @type MessageAttachmentsView
	 */
	var MessageAttachmentsView = Marionette.CompositeView.extend({
		/**
		 * @lends Marionette.CompositeView
		 */
		template: Handlebars.compile(AttachmentsTemplate),
		childView: AttachmentView,
		childViewContainer: '.attachments'
	});

	return MessageAttachmentsView;
});
