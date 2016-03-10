/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	require('views/helper');

	Radio.ui.on('folder:show', loadFolder);
	Radio.ui.on('message:load', function(account, folderId, messageId,
		options) {
		//FIXME: don't rely on global state vars
		loadMessage(account, messageId, options);
	});

	var composerVisible = false;

	function loadFolder(account, folderId, noSelect) {
		Radio.ui.trigger('composer:leave');
		Radio.ui.trigger('messagecontent:show');

		if (require('state').messagesLoading !== null) {
			require('state').messagesLoading.abort();
		}
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// Set folder active
		Radio.folder.trigger('setactive', account, folderId);
		Radio.ui.trigger('messagesview:messages:reset');
		$('#mail-messages')
			.addClass('icon-loading');
		$('#mail_new_message')
			.removeClass('hidden')
			.fadeIn();

		$('#load-new-mail-messages').hide();
		$('#load-more-mail-messages').hide();

		if (noSelect) {
			$('#emptycontent').show();
			$('#mail-message').removeClass('icon-loading');
			require('state').currentAccount = account;
			require('state').currentFolderId = folderId;
			Radio.ui.trigger('messagesview:message:setactive', null);
			$('#mail-messages').removeClass('icon-loading');
			require('state').currentlyLoading = null;
		} else {
			require('communication').fetchMessageList(account, folderId, {
				onSuccess: function(messages, cached) {
					require('state').currentlyLoading = null;
					require('state').currentAccount = account;
					require('state').currentFolderId = folderId;
					Radio.ui.trigger('messagesview:message:setactive', null);
					$('#mail-messages').removeClass('icon-loading');

					// Fade out the message composer
					$('#mail_new_message').prop('disabled', false);

					if (messages.length > 0) {
						Radio.ui.trigger('messagesview:messages:add', messages);

						// Fetch first 10 messages in background
						_.each(messages.slice(0, 10), function(
							message) {
							require('background').messageFetcher.push(message.id);
						});

						var messageId = messages[0].id;
						loadMessage(account, messageId);
						// Show 'Load More' button if there are
						// more messages than the pagination limit
						if (messages.length > 20) {
							$('#load-more-mail-messages')
								.fadeIn()
								.css('display', 'block');
						}
					} else {
						$('#emptycontent').show();
						$('#mail-message').removeClass('icon-loading');
					}
					$('#load-new-mail-messages')
						.fadeIn()
						.css('display', 'block')
						.prop('disabled', false);

					if (cached) {
						// Trigger folder update
						// TODO: replace with horde sync once it's implemented
						Radio.ui.trigger('messagesview:messages:update');
					}
				},
				onError: function(error, textStatus) {
					if (textStatus !== 'abort') {
						// Set the old folder as being active
						var folderId = require('state').currentFolderId;
						Radio.folder.trigger('setactive', account, folderId);
						Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
					}
				},
				cache: true
			});
		}
	}

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
							folderId: require('state').currentFolderId,
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

	function loadMessage(account, messageId, options) {
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

		if (!options.force && composerVisible) {
			return;
		}
		// Abort previous loading requests
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// check if message is a draft
		var draftsFolder = account.get('specialFolders').drafts;
		var draft = draftsFolder === require('state').currentFolderId;

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

		require('communication').fetchMessage(
			require('state').currentAccount,
			require('state').currentFolderId,
			messageId,
			{
				onSuccess: function(message) {
					if (draft) {
						Radio.ui.trigger('composer:show', message);
					} else {
						require('cache').addMessage(require('state').currentAccount,
							require('state').currentFolderId,
							message);
						Radio.ui.trigger('message:show', message);
					}
				},
				onError: function(jqXHR, textStatus) {
					if (textStatus !== 'abort') {
						Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected message.'));
					}
				}
			});
	}

	var view = {
		saveAttachment: saveAttachment,
		openForwardComposer: openForwardComposer,
		loadMessage: loadMessage
	};

	return view;
});
