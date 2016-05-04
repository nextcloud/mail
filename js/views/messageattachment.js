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

	var $ = require('jquery');
	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var Radio = require('radio');
	var MessageController = require('controller/messagecontroller');
	var CalendarsPopoverView = require('views/calendarspopoverview');
	var MessageAttachmentTemplate = require('text!templates/message-attachment.html');

	/**
	 * @class MessageAttachmentView
	 */
	var MessageAttachmentView = Marionette.ItemView.extend({
		template: Handlebars.compile(MessageAttachmentTemplate),
		ui: {
			'downloadButton': '.attachment-download',
			'saveToCloudButton': '.attachment-save-to-cloud',
			'importCalendarEventButton': '.attachment-import.calendar',
			'attachmentImportPopover': '.attachment-import-popover'
		},
		events: {
			'click': '_onClick',
			'click @ui.saveToCloudButton': '_onSaveToCloud',
			'click @ui.importCalendarEventButton': '_onImportCalendarEvent'
		},
		initialize: function() {
			this.listenTo(Radio.ui, 'document:click', this._closeImportPopover);
		},
		_onClick: function(e) {
			if (!e.isDefaultPrevented()) {
				var $target = $(e.target);
				if ($target.hasClass('select-calendar')) {
					var url = $target.data('calendar-url');
					this._uploadToCalendar(url);
					return;
				}

				e.preventDefault();
				window.location = this.model.get('downloadUrl');
			}
		},
		_onSaveToCloud: function(e) {
			e.preventDefault();
			// TODO: 'message' should be a property of this attachment model
			// TODO: 'folder' should be a property of the message model and so on
			var account = require('state').currentAccount;
			var folder = require('state').currentFolder;
			var messageId = this.model.get('messageId');
			var attachmentId = this.model.get('id');
			var saving = MessageController.saveAttachmentToFiles(account, folder, messageId, attachmentId);

			// Loading feedback
			this.ui.saveToCloudButton.removeClass('icon-folder')
					.addClass('icon-loading-small')
					.prop('disabled', true);

			var _this = this;
			$.when(saving).always(function() {
				// Remove loading feedback again
				_this.ui.saveToCloudButton.addClass('icon-folder')
						.removeClass('icon-loading-small')
						.prop('disabled', false);
			});
		},
		_onImportCalendarEvent: function(e) {
			e.preventDefault();

			this.ui.importCalendarEventButton
					.removeClass('icon-add')
					.addClass('icon-loading-small');

			var fetchingCalendars = Radio.dav.request('calendars');

			var _this = this;
			$.when(fetchingCalendars).done(function(calendars) {
				_this.ui.attachmentImportPopover.removeClass('hidden');
				var calendarsView = new CalendarsPopoverView({
					collection: calendars
				});
				calendarsView.render();
				_this.ui.attachmentImportPopover.html(calendarsView.$el);
			});
			$.when(fetchingCalendars).always(function() {
				_this.ui.importCalendarEventButton
						.removeClass('icon-loading-small')
						.addClass('icon-add');
			});
		},
		_uploadToCalendar: function(url) {
			this._closeImportPopover();
			this.ui.importCalendarEventButton
					.removeClass('icon-add')
					.addClass('icon-loading-small');

			var downloadUrl = this.model.get('downloadUrl');
			var downloadingAttachment = Radio.message.request('attachment:download', downloadUrl);

			var _this = this;
			$.when(downloadingAttachment).done(function(content) {

				var importingCalendarEvent = Radio.dav.request('calendar:import', url, content);

				$.when(importingCalendarEvent).fail(function() {
					Radio.ui.trigger('error:show', t('mail', 'Error while importing the calendar event'));
				});
				$.when(importingCalendarEvent).always(function() {
					_this.ui.importCalendarEventButton
							.removeClass('icon-loading-small')
							.addClass('icon-add');
				});
			});
			$.when(downloadingAttachment.fail(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while downloading calendar event'));
				_this.ui.importCalendarEventButton
						.removeClass('icon-loading-small')
						.addClass('icon-add');
			}));
		},
		_closeImportPopover: function(e) {
			if (_.isUndefined(e)) {
				this.ui.attachmentImportPopover.addClass('hidden');
				return;
			}
			var $target = $(e.target);
			if (this.$el.find($target).length === 0) {
				this.ui.attachmentImportPopover.addClass('hidden');
			}
		}
	});

	return MessageAttachmentView;
});
