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
	var _ = require('underscore');
	var OC = require('OC');
	var Radio = require('radio');
	var ErrorMessageFactory = require('util/errormessagefactory');

	Radio.message.on('load', load);
	Radio.message.on('forward', openForwardComposer);
	Radio.message.on('flag', flagMessage);
	Radio.message.on('move', moveMessage);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message} message
	 * @param {object} options
	 * @returns {undefined}
	 */
	function load(account, folder, message, options) {
		options = options || {};
		var defaultOptions = {
			force: false
		};
		_.defaults(options, defaultOptions);

		// Do not reload email when clicking same again
		if (require('state').currentMessage && require('state').currentMessage.get('id') === message.get('id')) {
			return;
		}

		Radio.ui.trigger('composer:leave');

		// TODO: expression is useless?
		if (!options.force && false) {
			return;
		}

		// check if message is a draft
		var draft = require('state').currentFolder.get('specialRole') === 'drafts';

		// close email first
		// Check if message is open
		if (require('state').currentMessage !== null) {
			var lastMessage = require('state').currentMessage;
			Radio.ui.trigger('messagesview:message:setactive', null);
			if (lastMessage.get('id') === message.get('id')) {
				return;
			}
		}

		Radio.ui.trigger('message:loading');

		// Set current Message as active
		Radio.ui.trigger('messagesview:message:setactive', message);
		require('state').currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		Radio.message.request('entity', account, folder, message.get('id')).then(function(messageBody) {
			if (draft) {
				Radio.ui.trigger('composer:show', messageBody);
			} else {
				// TODO: ideally this should be handled in messageservice.js
				require('cache').addMessage(account, folder, messageBody);
				Radio.ui.trigger('message:show', message, messageBody);
			}
		}, function() {
			Radio.ui.trigger('message:error', ErrorMessageFactory.getRandomMessageErrorMessage());
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
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @param {function} callback
	 * @returns {Promise}
	 */
	function saveAttachmentToFiles(account, folder, messageId, attachmentId, callback) {
		var saveAll = _.isUndefined(attachmentId);

		return new Promise(function(resolve, reject) {
			OC.dialogs.filepicker(
				t('mail', 'Choose a folder to store the attachment in'),
				function(path) {
					if (typeof callback === 'function') {
						callback();
					}
					Radio.message.request('save:cloud', account,
						folder, messageId, attachmentId, path).then(function() {
						if (saveAll) {
							Radio.ui.trigger('error:show', t('mail', 'Attachments saved to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Attachment saved to Files.'));
						}
						resolve();
					}, function() {
						if (saveAll) {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachments to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachment to Files.'));
						}
						reject();
					});
				}, false, 'httpd/unix-directory', true);
		});
	}

	function flagMessage(message, flag, value) {
		var folder = message.folder;
		var account = folder.account;
		var prevUnseen = folder.get('unseen');

		if (message.get('flags').get(flag) === value) {
			// Nothing to do
			return;
		}
		message.get('flags').set(flag, value);

		// Update folder counter
		if (flag === 'unseen') {
			var unseen = Math.max(0, prevUnseen + (value ? 1 : -1));
			folder.set('unseen', unseen);
		}

		// Update the folder to reflect the new unread count
		Radio.ui.trigger('title:update');

		Radio.message.request('flag', account, folder, message, flag, value).
			catch(function() {
				Radio.ui.trigger('error:show', t('mail', 'Message flag could not be set.'));

				// Restore previous state
				message.get('flags').set(flag, !value);
				folder.set('unseen', prevUnseen);
				Radio.ui.trigger('title:update');
			});
	}

	function moveMessage(sourceAccount, sourceFolder, message, destAccount,
		destFolder) {
		if (sourceAccount.get('accountId') === destAccount.get('accountId')
			&& sourceFolder.get('id') === destFolder.get('id')) {
			// Nothing to move
			return;
		}

		sourceFolder.messages.remove(message);
		destFolder.addMessage(message);

		Radio.message.request('move', sourceAccount, sourceFolder, message, destAccount, destFolder).
			then(function() {
				// TODO: update counters
			}, function() {
				Radio.ui.trigger('error:show', t('mail', 'Could not move message.'));
				sourceFolder.addMessage(message);
			});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {number} messageId
	 * @param {function} callback
	 * @returns {Promise}
	 */
	function saveAttachmentsToFiles(account, folder, messageId, callback) {
		return saveAttachmentToFiles(account, folder, messageId, null, callback);
	}

	return {
		saveAttachmentToFiles: saveAttachmentToFiles,
		saveAttachmentsToFiles: saveAttachmentsToFiles
	};
});
