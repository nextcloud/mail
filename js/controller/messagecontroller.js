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
	var _ = require('underscore');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.message.on('load', function(account, folder, messageId, options) {
		//FIXME: don't rely on global state vars
		load(account, messageId, options);
	});
	Radio.message.on('forward', openForwardComposer);

	/**
	 * @param {Account} account
	 * @param {number} messageId
	 * @param {object} options
	 * @returns {undefined}
	 */
	function load(account, messageId, options) {
		options = options || {};
		var defaultOptions = {
			force: false
		};
		_.defaults(options, defaultOptions);

		// Do not reload email when clicking same again
		if (require('state').currentMessageId === messageId) {
			return;
		}

		Radio.ui.trigger('composer:leave');

		// TODO: expression is useless?
		if (!options.force && false) {
			return;
		}
		// Abort previous loading requests
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// check if message is a draft
		var draftsFolder = account.get('specialFolders').drafts;
		var draft = draftsFolder === require('state').currentFolder.get('id');

		// close email first
		// Check if message is open
		if (require('state').currentMessageId !== null) {
			var lastMessageId = require('state').currentMessageId;
			Radio.ui.trigger('messagesview:message:setactive', null);
			if (lastMessageId === messageId) {
				return;
			}
		}

		Radio.ui.trigger('message:loading');

		// Set current Message as active
		Radio.ui.trigger('messagesview:message:setactive', messageId);
		require('state').currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		var fetchingMessage = Radio.message.request('entity',
			require('state').currentAccount,
			require('state').currentFolder,
			messageId);

		$.when(fetchingMessage).done(function(message) {
			if (draft) {
				Radio.ui.trigger('composer:show', message);
			} else {
				// TODO: ideally this should be handled in messageservice.js
				require('cache').addMessage(require('state').currentAccount,
					require('state').currentFolder,
					message);
				Radio.ui.trigger('message:show', message);
			}
		});
		$.when(fetchingMessage).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected message.'));
		});
	}

	/**
	 * @returns {undefined}
	 */
	function openForwardComposer() {
		var header = '\n\n\n\n-------- ' +
			t('mail', 'Forwarded message') +
			' --------\n';

		// TODO: find a better way to get the current message body
		var data = {
			subject: 'Fwd: ' + require('state').currentMessageSubject,
			body: header + require('state').currentMessageBody.replace(/<br \/>/g, '\n')
		};

		if (require('state').currentAccount.get('accountId') !== -1) {
			data.accountId = require('state').currentAccount.get('accountId');
		}

		Radio.ui.trigger('composer:show', data);
	}

	/**
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @returns {undefined}
	 */
	function saveAttachment(messageId, attachmentId) {
		OC.dialogs.filepicker(
			t('mail', 'Choose a folder to store the attachment in'),
			function(path) {
				// Loading feedback
				var saveToFilesBtnSelector = '.attachment-save-to-cloud';
				if (typeof attachmentId !== 'undefined') {
					saveToFilesBtnSelector = 'li[data-attachment-id="' +
						attachmentId + '"] ' + saveToFilesBtnSelector;
				}
				$(saveToFilesBtnSelector)
					.removeClass('icon-folder')
					.addClass('icon-loading-small')
					.prop('disabled', true);

				$.ajax(
					OC.generateUrl(
						'apps/mail/accounts/{accountId}/' +
						'folders/{folderId}/messages/{messageId}/' +
						'attachment/{attachmentId}',
						{
							accountId: require('state').currentAccount.get('accountId'),
							folderId: require('state').currentFolder.get('id'),
							messageId: messageId,
							attachmentId: attachmentId
						}), {
					data: {
						targetPath: path
					},
					type: 'POST',
					success: function() {
						if (typeof attachmentId === 'undefined') {
							Radio.ui.trigger('error:show', t('mail', 'Attachments saved to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Attachment saved to Files.'));
						}
					},
					error: function() {
						if (typeof attachmentId === 'undefined') {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachments to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachment to Files.'));
						}
					},
					complete: function() {
						// Remove loading feedback again
						$('.attachment-save-to-cloud')
							.removeClass('icon-loading-small')
							.addClass('icon-folder')
							.prop('disabled', false);
					}
				});
			},
			false,
			'httpd/unix-directory',
			true
			);
	}
});