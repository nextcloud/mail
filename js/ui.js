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

	var $ = require('jquery');
	var Handlebars = require('handlebars');
	var OC = require('OC');
	var Radio = require('radio');
	var ComposerView = require('views/composer');

	require('views/helper');

	Radio.ui.on('menu:show', showMenu);
	Radio.ui.on('folder:load', loadFolder);
	Radio.ui.on('message:load', function(accountId, folderId, messageId, options) {
		//FIXME: don't rely on global state vars
		loadMessage(messageId, options);
	});
	Radio.ui.on('composer:leave', onComposerLeave);
	Radio.ui.on('folder:changed', onFolderChanged);

	var composer = null;
	var composerVisible = false;

	function onComposerLeave() {
		// Trigger only once
		if (composerVisible === true) {
			composerVisible = false;

			if (composer && composer.hasData === true) {
				if (composer.hasUnsavedChanges === true) {
					composer.saveDraft(function() {
						showDraftSavedNotification();
					});
				} else {
					showDraftSavedNotification();
				}
			}
		}
	}

	function onFolderChanged() {
		// Stop background message fetcher of previous folder
		require('background').messageFetcher.restart();
		// hide message detail view on mobile
		$('#mail-message').addClass('hidden-mobile');
	}

	function initializeInterface() {
		setInterval(require('background').checkForNotifications, 5 * 60 * 1000);
		Radio.account.trigger('load');
	}

	function clearMessages() {
		Radio.ui.trigger('messagesview:messages:reset');
		$('#messages-loading').fadeIn();

		$('#mail-message')
			.html('')
			.addClass('icon-loading');
	}

	function showMenu() {
		$('#app-navigation').show();
		$('#app-navigation-toggle').css('background-image', '');
	}

	function addMessages(data) {
		Radio.ui.trigger('messagesview:messages:add', data);
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
		setFolderActive(accountId, folderId);
		clearMessages();
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
			setMessageActive(null);
			$('#mail-messages').removeClass('icon-loading');
			require('state').currentlyLoading = null;
		} else {
			require('communication').fetchMessageList(accountId, folderId, {
				onSuccess: function(messages, cached) {
					require('state').currentlyLoading = null;
					require('state').currentAccountId = accountId;
					require('state').currentFolderId = folderId;
					setMessageActive(null);
					$('#mail-messages').removeClass('icon-loading');

					// Fade out the message composer
					$('#mail_new_message').prop('disabled', false);

					if (messages.length > 0) {
						addMessages(messages);

						// Fetch first 10 messages in background
						_.each(messages.slice(0, 10), function(message) {
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
						var state = require('state');
						// Set the old folder as being active
						setFolderActive(state.currentAccountId, state.currentFolderId);
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
							showError(t('mail', 'Attachments saved to Files.'));
						} else {
							showError(t('mail', 'Attachment saved to Files.'));
						}
					},
					error: function() {
						if (typeof attachmentId === 'undefined') {
							showError(t('mail', 'Error while saving attachments to Files.'));
						} else {
							showError(t('mail', 'Error while saving attachment to Files.'));
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
			showError(t('mail', 'Opening HTML drafts is not supported yet.'));
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

		if (!_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).data().email)) {
			var to = '"' + $(data.currentTarget).data().label + '" <' + $(data.currentTarget).data().email + '>';
			toInput.val(to);
			composer.el.find('input.subject').focus();
		}

		setMessageActive(null);
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

	function htmlToText(html) {
		var breakToken = '__break_token__';
		// Preserve line breaks
		html = html.replace(/<br>/g, breakToken);
		html = html.replace(/<br\/>/g, breakToken);

		// Add <br> break after each closing div, p, li to preserve visual
		// line breaks for replies
		html = html.replace(/<\/div>/g, '</div>' + breakToken);
		html = html.replace(/<\/p>/g, '</p>' + breakToken);
		html = html.replace(/<\/li>/g, '</li>' + breakToken);

		var tmp = $('<div>');
		tmp.html(html);
		var text = tmp.text();

		// Finally, replace tokens with line breaks
		text = text.replace(new RegExp(breakToken, 'g'), '\n');
		return text;
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
			setMessageActive(null);
			if (lastMessageId === messageId) {
				return;
			}
		}

		var mailBody = $('#mail-message');
		mailBody.html('').addClass('icon-loading');

		// Set current Message as active
		setMessageActive(messageId);
		require('state').currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		var loadMessageSuccess = function(message) {
			var reply = {
				replyToList: message.replyToList,
				replyCc: message.ReplyCc,
				replyCcList: message.replyCcList,
				body: ''
			};

			// Add body content to inline reply (text mails)
			if (!message.hasHtmlBody) {
				var date = new Date(message.dateIso);
				var minutes = date.getMinutes();
				var text = htmlToText(message.body);

				reply.body = '\n\n\n\n' +
					message.from + ' – ' +
					$.datepicker.formatDate('D, d. MM yy ', date) +
					date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
					text.replace(/\n/g, '\n> ');
			}

			// Save current messages's content for later use (forward)
			if (!message.hasHtmlBody) {
				require('state').currentMessageBody = message.body;
			}
			require('state').currentMessageSubject = message.subject;

			// Render the message body
			var source = require('text!templates/message.html');
			var template = Handlebars.compile(source);
			var html = template(message);
			mailBody
				.html(html)
				.removeClass('icon-loading');
			adjustControlsWidth();

			// Temporarily disable new-message composer events
			if (composer) {
				composer.undelegateEvents();
			}

			// setup reply composer view
			var replyComposer = new ComposerView({
				el: $('#reply-composer'),
				type: 'reply',
				onSubmit: require('communication').sendMessage,
				onDraft: require('communication').saveDraft,
				accountId: message.accountId,
				folderId: message.folderId,
				messageId: message.messageId
			});
			replyComposer.render({
				data: reply
			});

			// Hide forward button until the message has finished loading
			if (message.hasHtmlBody) {
				$('#forward-button').hide();
			}

			// Set max width for attached images
			$('.mail-message-attachments img.mail-attached-image').each(function() {
				$(this).css({
					'max-width': $('.mail-message-body').width(),
					'height': 'auto'
				});
			});

			Radio.ui.trigger('messagesview:messageflag:set', messageId, 'unseen', false);

			// HTML mail rendering
			$('iframe').load(function() {
				// Expand height to not have two scrollbars
				$(this).height($(this).contents().find('html').height() + 20);
				// Fix styling
				$(this).contents().find('body').css({
					'margin': '0',
					'font-weight': 'normal',
					'font-size': '.8em',
					'line-height': '1.6em',
					'font-family': '"Open Sans", Frutiger, Calibri, "Myriad Pro", Myriad, sans-serif',
					'color': '#000'
				});
				// Fix font when different font is forced
				$(this).contents().find('font').prop({
					'face': 'Open Sans',
					'color': '#000'
				});
				$(this).contents().find('.moz-text-flowed').css({
					'font-family': 'inherit',
					'font-size': 'inherit'
				});
				// Expand height again after rendering to account for new size
				$(this).height($(this).contents().find('html').height() + 20);
				// Grey out previous replies
				$(this).contents().find('blockquote').css({
					'color': '#888'
				});
				// Remove spinner when loading finished
				$('iframe').parent().removeClass('icon-loading');

				// Does the html mail have blocked images?
				var hasBlockedImages = false;
				if ($(this).contents().find('[data-original-src],[data-original-style]').length) {
					hasBlockedImages = true;
				}

				// Show/hide button to load images
				if (hasBlockedImages) {
					$('#show-images-text').show();
				} else {
					$('#show-images-text').hide();
				}

				// Add body content to inline reply (html mails)
				var text = $(this).contents().find('body').html();
				text = htmlToText(text);
				if (!draft) {
					var date = new Date(message.dateIso);
					replyComposer.setReplyBody(message.from, date, text);
				}

				// Safe current mesages's content for later use (forward)
				require('state').currentMessageBody = text;

				// Show forward button
				$('#forward-button').show();
			});
		};

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
						loadMessageSuccess(message);
					}
				},
				onError: function(jqXHR, textStatus) {
					if (textStatus !== 'abort') {
						showError(t('mail', 'Error while loading the selected message.'));
					}
				}
			});
	}

	function setFolderActive(accountId, folderId) {
		Radio.ui.trigger('messagesview:filter:clear');

		// disable all other folders for all accounts
		require('state').accounts.each(function(account) {
			var localAccount = require('state').folderView.collection.get(account.get('accountId'));
			if (_.isUndefined(localAccount)) {
				return;
			}
			var folders = localAccount.get('folders');
			_.each(folders.models, function(folder) {
				folders.get(folder).set('active', false);
			});
		});

		require('state').folderView.getFolderById(accountId, folderId)
			.set('active', true);
	}

	function setMessageActive(messageId) {
		Radio.ui.trigger('messagesview:message:setactive', messageId);
		require('state').currentMessageId = messageId;
		require('state').folderView.updateTitle();
	}

	function showDraftSavedNotification() {
		OC.Notification.showTemporary(t('mail', 'Draft saved!'));
	}

	var view = {
		initializeInterface: initializeInterface,
		clearMessages: clearMessages,
		addMessages: addMessages,
		loadFolder: loadFolder,
		saveAttachment: saveAttachment,
		openComposer: openComposer,
		openForwardComposer: openForwardComposer,
		loadMessage: loadMessage,
		setFolderActive: setFolderActive,
		setMessageActive: setMessageActive
	};

	return view;
});
