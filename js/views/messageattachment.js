/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
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
	var Marionette = require('backbone.marionette');
	var Radio = require('radio');
	var MessageController = require('controller/messagecontroller');
	var CalendarsPopoverView = require('views/calendarspopoverview');
	var MessageAttachmentTemplate = require('templates/message-attachment.html');

	/**
	 * @class MessageAttachmentView
	 */
	var MessageAttachmentView = Marionette.View.extend({
		template: MessageAttachmentTemplate,
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
				window.open(this.model.get('downloadUrl'));
				window.focus();
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

			var _this = this;
			MessageController.saveAttachmentToFiles(account, folder, messageId, attachmentId, function() {
				// Loading feedback
				_this.getUI('saveToCloudButton').removeClass('icon-folder')
				.addClass('icon-loading-small')
				.prop('disabled', true);
			}).catch(console.error.bind(this)).then(function() {
				// Remove loading feedback again
				_this.getUI('saveToCloudButton').addClass('icon-folder')
					.removeClass('icon-loading-small')
					.prop('disabled', false);
			});
		},
		_onImportCalendarEvent: function(e) {
			e.preventDefault();

			this.getUI('importCalendarEventButton')
				.removeClass('icon-add')
				.addClass('icon-loading-small');

			var _this = this;
			Radio.dav.request('calendars').then(function(calendars) {
				if (calendars.length > 0) {
					_this.getUI('attachmentImportPopover').addClass('open');
					var calendarsView = new CalendarsPopoverView({
						collection: calendars
					});
					calendarsView.render();
					_this.getUI('attachmentImportPopover').html(calendarsView.$el);
				} else {
					Radio.ui.trigger('error:show', t('mail', 'No writable calendars found'));
				}
			}).catch(console.error.bind(this)).then(function() {
				_this.getUI('importCalendarEventButton')
					.removeClass('icon-loading-small')
					.addClass('icon-add');
			});
		},
		_uploadToCalendar: function(url) {
			this._closeImportPopover();
			this.getUI('importCalendarEventButton')
				.removeClass('icon-add')
				.addClass('icon-loading-small');

			var downloadUrl = this.model.get('downloadUrl');
			var _this = this;
			Radio.message.request('attachment:download', downloadUrl).then(function(content) {
				return Radio.dav.request('calendar:import', url, content).catch(function() {
					Radio.ui.trigger('error:show', t('mail', 'Error while importing the calendar event'));
				});
			}).then(function() {
				_this.getUI('importCalendarEventButton')
					.removeClass('icon-loading-small')
					.addClass('icon-add');
			}).catch(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while downloading calendar event'));
				_this.getUI('importCalendarEventButton')
					.removeClass('icon-loading-small')
					.addClass('icon-add');
			});
		},
		_closeImportPopover: function(e) {
			if (_.isUndefined(e)) {
				this.getUI('attachmentImportPopover').removeClass('open');
				return;
			}
			var $target = $(e.target);
			if (this.$el.find($target).length === 0) {
				this.getUI('attachmentImportPopover').removeClass('open');
			}
		}
	});

	return MessageAttachmentView;
});
