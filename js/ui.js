/* global adjustControlsWidth */

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

	var _ = require('underscore');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');
	var ComposerView = require('views/composer');

	require('views/helper');

	Radio.ui.on('folder:load', loadFolder);
	Radio.ui.on('message:load', function(accountId, folderId, messageId,
		options) {
		//FIXME: don't rely on global state vars
		loadMessage(messageId, options);
	});
	Radio.ui.on('composer:leave', onComposerLeave);
	Radio.ui.on('composer:events:undelegate', function() {
		if (composer) {
			composer.undelegateEvents();
		}
	});

	var composer = null;
	var composerVisible = false;

	function onComposerLeave() {
		// Trigger only once
		if (composerVisible === true) {
			composerVisible = false;

			if (composer && composer.hasData === true) {
				if (composer.hasUnsavedChanges === true) {
					composer.saveDraft(function() {
						Radio.ui.trigger('notification:show', t('mail', 'Draft saved!'));
					});
				} else {
					Radio.ui.trigger('notification:show', t('mail', 'Draft saved!'));
				}
			}
		}
	}

	function initializeInterface() {
		setInterval(require('background').checkForNotifications, 5 * 60 * 1000);
		Radio.account.trigger('load');
	}

	function loadFolder(accountId, folderId, noSelect) {
		Radio.ui.trigger('composer:leave');

		if (require('state').messagesLoading !== null) {
			require('state').messagesLoading.abort();
		}
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// Set folder active
		Radio.folder.trigger('setactive', accountId, folderId);
		Radio.ui.trigger('messagesview:messages:reset');
		$('#mail-messages')
			.removeClass('hidden')
			.addClass('icon-loading')
			.removeClass('hidden');
		$('#mail_new_message')
			.removeClass('hidden')
			.fadeIn();
		$('#mail-message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#setup').addClass('hidden');

		$('#load-new-mail-messages').hide();
		$('#load-more-mail-messages').hide();
		$('#emptycontent').hide();

		if (noSelect) {
			$('#emptycontent').show();
			$('#mail-message').removeClass('icon-loading');
			require('state').currentAccountId = accountId;
			require('state').currentFolderId = folderId;
			Radio.ui.trigger('messagesview:message:setactive', null);
			$('#mail-messages').removeClass('icon-loading');
			require('state').currentlyLoading = null;
		} else {
			require('communication').fetchMessageList(accountId, folderId, {
				onSuccess: function(messages, cached) {
					require('state').currentlyLoading = null;
					require('state').currentAccountId = accountId;
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
						loadMessage(messageId);
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
						Radio.folder.trigger('setactive', accountId, folderId);
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
							accountId: require('state').currentAccountId,
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

	function openComposer(data) {
		composerVisible = true;
		$('.tipsy').remove();
		$('#mail_new_message').prop('disabled', true);
		$('#mail-message').removeClass('hidden-mobile');

		// Abort message loads
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
			$('iframe').parent().removeClass('icon-loading');
			$('#mail-message').removeClass('icon-loading');
			$('#mail_message').removeClass('icon-loading');
		}

		if (composer === null) {
			// setup composer view
			composer = new ComposerView({
				el: $('#mail-message'),
				onSubmit: require('communication').sendMessage,
				onDraft: require('communication').saveDraft,
				accounts: require('state').accounts
			});
		} else {
			composer.data = data;
			composer.hasData = false;
			composer.hasUnsavedChanges = false;
			composer.delegateEvents();
		}

		if (data && data.hasHtmlBody) {
			Radio.ui.trigger('error:show', t('mail', 'Opening HTML drafts is not supported yet.'));
		}

		composer.render({
			data: data
		});

		// set 'from' dropdown to current account
		// TODO: fix selector conflicts
		if (require('state').currentAccountId !== -1) {
			$('.mail-account').val(require('state').currentAccountId);
		}

		// focus 'to' field automatically on clicking New message button
		var toInput = composer.el.find('input.to');
		toInput.focus();

		if (!_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).
			data().email)) {
			var to = '"' + $(data.currentTarget).
				data().label + '" <' + $(data.currentTarget).data().email + '>';
			toInput.val(to);
			composer.el.find('input.subject').focus();
		}

		Radio.ui.trigger('messagesview:message:setactive', null);
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

		if (require('state').currentAccountId !== -1) {
			data.accountId = require('state').currentAccountId;
		}

		openComposer(data);
	}

	function loadMessage(messageId, options) {
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
		var accountId = require('state').currentAccountId;
		var account = require('state').folderView.collection.findWhere({id: accountId});
		var draftsFolder = account.attributes.specialFolders.drafts;
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

		var loadDraftSuccess = function(data) {
			openComposer(data);
		};

		require('communication').fetchMessage(
			require('state').currentAccountId,
			require('state').currentFolderId,
			messageId,
			{
				onSuccess: function(message) {
					if (draft) {
						loadDraftSuccess(message);
					} else {
						require('cache').addMessage(require('state').currentAccountId,
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
		initializeInterface: initializeInterface,
		loadFolder: loadFolder,
		saveAttachment: saveAttachment,
		openComposer: openComposer,
		openForwardComposer: openForwardComposer,
		loadMessage: loadMessage
	};

	return view;
});
