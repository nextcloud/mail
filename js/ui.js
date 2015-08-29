/* global Notification */

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

	var $ = require('jquery'),
		Handlebars = require('handlebars'),
		Marionette = require('marionette'),
		OC = require('OC'),
		MessagesView = require('views/messages'),
		FoldersView = require('views/folders'),
		ComposerView = require('views/composer');
	
	require('views/helper');
	require('settings');

	var view = {};

	var messageView = null;
	var composer = null;
	var composerVisible = false;

	view.renderSettings = function() {
		var accounts = _.filter(require('app').State.accounts, function(item) {
			return item.accountId !== -1;
		});
		var source = $("#mail-settings-template").html();
		var template = Handlebars.compile(source);
		var html = template(accounts);
		$('#app-settings-content').html(html);
	};

	view.changeFavicon = function(src) {
		$('link[rel="shortcut icon"]').attr('href', src);
	};

	view.loadAccounts = function() {
		require('app').Communication.get(OC.generateUrl('apps/mail/accounts'), {
			success: function(accounts) {
				require('app').State.accounts = accounts;
				require('app').UI.renderSettings();
				if (accounts.length === 0) {
					require('app').UI.addAccount();
				} else {
					var firstAccountId = accounts[0].accountId;
					_.each(accounts, function(a) {
						require('app').UI.loadFoldersForAccount(a.accountId, firstAccountId);
					});
				}
				require('app').Cache.cleanUp(accounts);
			},
			error: function() {
				require('app').UI.showError(t('mail', 'Error while loading the accounts.'));
			},
			ttl: 'no'
		});
	};

	view.initializeInterface = function() {
		// Register UI events
		window.addEventListener('resize', require('app').UI.Events.onWindowResize);

		Marionette.TemplateCache.prototype.compileTemplate = function(rawTemplate) {
			return Handlebars.compile(rawTemplate);
		};
		Marionette.ItemView.prototype.modelEvents = {"change": "render"};
		Marionette.CompositeView.prototype.modelEvents = {"change": "render"};

		// ask to handle all mailto: links
		if (window.navigator.registerProtocolHandler) {
			var url = window.location.protocol + '//' +
				window.location.host +
				OC.generateUrl('apps/mail/compose?uri=%s');
			try {
				window.navigator
					.registerProtocolHandler("mailto", url, "ownCloud Mail");
			} catch (e) {
			}
		}

		// setup messages view
		require('app').UI.messageView = new MessagesView({
			el: $('#mail_messages')
		});
		require('app').UI.messageView.render();

		// setup folder view
		require('app').State.folderView = new FoldersView({
			el: $('#folders')
		});
		require('app').State.folderView.render();

		require('app').State.folderView.listenTo(require('app').UI.messageView, 'change:unseen',
			require('app').State.folderView.changeUnseen);

		// request permissions
		if (typeof Notification !== 'undefined') {
			Notification.requestPermission();
		}

		if (!_.isUndefined(OC.Plugins)) {
			OC.Plugins.register('OCA.Search', require('app').Search);
		}

		setInterval(require('app').BackGround.checkForNotifications, 5 * 60 * 1000);
		view.loadAccounts();
	};

	view.loadFoldersForAccount = function(accountId, firstAccountId) {
		$('#mail_messages').removeClass('hidden').addClass('icon-loading');
		$('#mail-message').removeClass('hidden').addClass('icon-loading');
		$('#mail_new_message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#mail-setup').addClass('hidden');

		require('app').UI.clearMessages();
		$('#app-navigation').addClass('icon-loading');

		require('app').Communication.get(OC.generateUrl('apps/mail/accounts/{accountId}/folders', {accountId: accountId}), {
			success: function(jsondata) {
				$('#app-navigation').removeClass('icon-loading');
				require('app').State.folderView.collection.add(jsondata);

				if (jsondata.id === firstAccountId) {
					var folderId = jsondata.folders[0].id;

					require('app').UI.loadFolder(accountId, folderId, false);

					// Save current folder
					require('app').UI.setFolderActive(accountId, folderId);
					require('app').State.currentAccountId = accountId;
					require('app').State.currentFolderId = folderId;

					// Start fetching messages in background
					require('app').BackGround.messageFetcher.start();
				}
			},
			error: function() {
				require('app').UI.showError(t('mail', 'Error while loading the selected account.'));
			},
			ttl: 'no'
		});
	};

	view.showError = function(message) {
		OC.Notification.showTemporary(message);
		$('#app-navigation')
			.removeClass('icon-loading');
		$('#app-content')
			.removeClass('icon-loading');
		$('#mail-message')
			.removeClass('icon-loading');
		$('#mail_message')
			.removeClass('icon-loading');
	};

	view.clearMessages = function() {
		require('app').UI.messageView.collection.reset();
		$('#messages-loading').fadeIn();

		$('#mail-message')
			.html('')
			.addClass('icon-loading');
	};

	view.hideMenu = function() {
		$('.message-composer').addClass('hidden');
		if (require('app').State.accounts.length === 0) {
			$('#app-navigation').hide();
			$('#app-navigation-toggle').css('background-image', 'none');
		}
	};

	view.showMenu = function() {
		$('.message-composer').removeClass('hidden');
		$('#app-navigation').show();
		$('#app-navigation-toggle').css('background-image', '');
	};

	view.addMessages = function(data) {
		require('app').UI.messageView.collection.add(data);
	};

	view.loadFolder = function(accountId, folderId, noSelect) {
		require('app').UI.Events.onComposerLeave();

		if (require('app').State.messagesLoading !== null) {
			require('app').State.messagesLoading.abort();
		}
		if (require('app').State.messageLoading !== null) {
			require('app').State.messageLoading.abort();
		}

		// Set folder active
		require('app').UI.setFolderActive(accountId, folderId);
		require('app').UI.clearMessages();
		$('#mail_messages')
			.removeClass('hidden')
			.addClass('icon-loading')
			.removeClass('hidden');
		$('#mail_new_message')
			.removeClass('hidden')
			.fadeIn();
		$('#mail-message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#mail-setup').addClass('hidden');

		$('#load-new-mail-messages').hide();
		$('#load-more-mail-messages').hide();
		$('#emptycontent').hide();

		if (noSelect) {
			$('#emptycontent').show();
			$('#mail-message').removeClass('icon-loading');
			require('app').State.currentAccountId = accountId;
			require('app').State.currentFolderId = folderId;
			require('app').UI.setMessageActive(null);
			$('#mail_messages').removeClass('icon-loading');
			require('app').State.currentlyLoading = null;
		} else {
			require('app').Communication.fetchMessageList(accountId, folderId, {
				onSuccess: function(messages, cached) {
					require('app').State.currentlyLoading = null;
					require('app').State.currentAccountId = accountId;
					require('app').State.currentFolderId = folderId;
					require('app').UI.setMessageActive(null);
					$('#mail_messages').removeClass('icon-loading');

					// Fade out the message composer
					$('#mail_new_message').prop('disabled', false);

					if (messages.length > 0) {
						require('app').UI.addMessages(messages);

						// Fetch first 10 messages in background
						_.each(messages.slice(0, 10), function(message) {
							require('app').BackGround.messageFetcher.push(message.id);
						});

						var messageId = messages[0].id;
						require('app').UI.loadMessage(messageId);
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
						require('app').UI.messageView.loadNew();
					}
				},
				onError: function(error, textStatus) {
					if (textStatus !== 'abort') {
						// Set the old folder as being active
						require('app').UI.setFolderActive(require('app').State.currentAccountId, require('app').State.currentFolderId);
						require('app').UI.showError(t('mail', 'Error while loading messages.'));
					}
				},
				cache: true
			});
		}
	};

	view.saveAttachment = function(messageId, attachmentId) {
		OC.dialogs.filepicker(
			t('mail', 'Choose a folder to store the attachment in'),
			function(path) {
				// Loading feedback
				var saveToFilesBtnSelector = '.attachment-save-to-cloud';
				if (typeof attachmentId !== "undefined") {
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
							accountId: require('app').State.currentAccountId,
							folderId: require('app').State.currentFolderId,
							messageId: messageId,
							attachmentId: attachmentId
						}), {
					data: {
						targetPath: path
					},
					type: 'POST',
					success: function() {
						if (typeof attachmentId === "undefined") {
							require('app').UI.showError(t('mail', 'Attachments saved to Files.'));
						} else {
							require('app').UI.showError(t('mail', 'Attachment saved to Files.'));
						}
					},
					error: function() {
						if (typeof attachmentId === "undefined") {
							require('app').UI.showError(t('mail', 'Error while saving attachments to Files.'));
						} else {
							require('app').UI.showError(t('mail', 'Error while saving attachment to Files.'));
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
	};

	view.openComposer = function(data) {
		composerVisible = true;
		$('.tipsy').remove();
		$('#mail_new_message').prop('disabled', true);
		$('#mail-message').removeClass('hidden-mobile');

		// Abort message loads
		if (require('app').State.messageLoading !== null) {
			require('app').State.messageLoading.abort();
			$('iframe').parent().removeClass('icon-loading');
			$('#mail-message').removeClass('icon-loading');
			$('#mail_message').removeClass('icon-loading');
		}

		if (composer === null) {
			// setup composer view
			composer = new ComposerView({
				el: $('#mail-message'),
				onSubmit: require('app').Communication.sendMessage,
				onDraft: require('app').Communication.saveDraft,
				aliases: require('app').State.accounts
			});
		} else {
			composer.data = data;
			composer.hasData = false;
			composer.hasUnsavedChanges = false;
			composer.delegateEvents();
		}

		if (data && data.hasHtmlBody) {
			require('app').UI.showError(t('mail', 'Opening HTML drafts is not supported yet.'));
		}

		composer.render({
			data: data
		});

		// set 'from' dropdown to current account
		// TODO: fix selector conflicts
		if (require('app').State.currentAccountId !== -1) {
			$('.mail-account').val(require('app').State.currentAccountId);
		}

		// focus 'to' field automatically on clicking New message button
		var toInput = composer.el.find('input.to');
		toInput.focus();

		if (!_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).data().email)) {
			var to = '"' + $(data.currentTarget).data().label + '" <' + $(data.currentTarget).data().email + '>';
			toInput.val(to);
			composer.el.find('input.subject').focus();
		}

		require('app').UI.setMessageActive(null);
	};

	view.openForwardComposer = function() {
		var header = '\n\n\n\n-------- ' +
			t('mail', 'Forwarded message') +
			" --------\n";

		// TODO: find a better way to get the current message body
		var data = {
			subject: 'Fwd: ' + require('app').State.currentMessageSubject,
			body: header + require('app').State.currentMessageBody.replace(/<br \/>/g, "\n")
		};

		if (require('app').State.currentAccountId !== -1) {
			data.accountId = require('app').State.currentAccountId;
		}

		this.openComposer(data);
	};

	view.htmlToText = function(html) {
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
		text = text.replace(new RegExp(breakToken, 'g'), "\n");
		return text;
	};

	view.loadMessage = function(messageId, options) {
		options = options || {};
		var defaultOptions = {
			force: false
		};
		_.defaults(options, defaultOptions);

		// Do not reload email when clicking same again
		if (require('app').State.currentMessageId === messageId) {
			return;
		}

		require('app').UI.Events.onComposerLeave();

		if (!options.force && composerVisible) {
			return;
		}
		// Abort previous loading requests
		if (require('app').State.messageLoading !== null) {
			require('app').State.messageLoading.abort();
		}

		// check if message is a draft
		var accountId = require('app').State.currentAccountId;
		var account = require('app').State.folderView.collection.findWhere({id: accountId});
		var draftsFolder = account.attributes.specialFolders.drafts;
		var draft = draftsFolder === require('app').State.currentFolderId;

		// close email first
		// Check if message is open
		if (require('app').State.currentMessageId !== null) {
			var lastMessageId = require('app').State.currentMessageId;
			require('app').UI.setMessageActive(null);
			if (lastMessageId === messageId) {
				return;
			}
		}

		var mailBody = $('#mail-message');
		mailBody.html('').addClass('icon-loading');

		// Set current Message as active
		require('app').UI.setMessageActive(messageId);
		require('app').State.currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		var self = this;
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
				var text = require('app').UI.htmlToText(message.body);

				reply.body = '\n\n\n\n' +
					message.from + ' – ' +
					$.datepicker.formatDate('D, d. MM yy ', date) +
					date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
					text.replace(/\n/g, '\n> ');
			}

			// Save current messages's content for later use (forward)
			if (!message.hasHtmlBody) {
				require('app').State.currentMessageBody = message.body;
			}
			require('app').State.currentMessageSubject = message.subject;

			// Render the message body
			var source = $("#mail-message-template").html();
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
				onSubmit: require('app').Communication.sendMessage,
				onDraft: require('app').Communication.saveDraft,
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

			require('app').UI.messageView.setMessageFlag(messageId, 'unseen', false);

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
					'font-family': "'Open Sans', Frutiger, Calibri, 'Myriad Pro', Myriad, sans-serif",
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
					'-ms-filter': '"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)"',
					'filter': 'alpha(opacity=50)',
					'opacity': '.5'
				});
				// Remove spinner when loading finished
				$('iframe').parent().removeClass('icon-loading');

				// Add body content to inline reply (html mails)
				var text = $(this).contents().find('body').html();
				text = require('app').UI.htmlToText(text);
				if (!draft) {
					var date = new Date(message.dateIso);
					replyComposer.setReplyBody(message.from, date, text);
				}

				// Safe current mesages's content for later use (forward)
				require('app').State.currentMessageBody = text;

				// Show forward button
				$('#forward-button').show();
			});
		};

		var loadDraftSuccess = function(data) {
			self.openComposer(data);
		};

		require('app').Communication.fetchMessage(
			require('app').State.currentAccountId,
			require('app').State.currentFolderId,
			messageId,
			{
				onSuccess: function(message) {
					if (draft) {
						loadDraftSuccess(message);
					} else {
						require('app').Cache.addMessage(require('app').State.currentAccountId,
							require('app').State.currentFolderId,
							message);
						loadMessageSuccess(message);
					}
				},
				onError: function(jqXHR, textStatus) {
					if (textStatus !== 'abort') {
						require('app').UI.showError(t('mail', 'Error while loading the selected message.'));
					}
				}
			});
	};

	view.setFolderActive = function(accountId, folderId) {
		require('app').UI.messageView.clearFilter();

		// disable all other folders for all accounts
		_.each(require('app').State.accounts, function(account) {
			var localAccount = require('app').State.folderView.collection.get(account.accountId);
			if (_.isUndefined(localAccount)) {
				return;
			}
			var folders = localAccount.get('folders');
			_.each(folders.models, function(folder) {
				folders.get(folder).set('active', false);
			});
		});

		require('app').State.folderView.getFolderById(accountId, folderId)
			.set('active', true);
	};

	view.setMessageActive = function(messageId) {
		require('app').UI.messageView.setActiveMessage(messageId);
		require('app').State.currentMessageId = messageId;
		require('app').State.folderView.updateTitle();
	};

	view.addAccount = function() {
		require('app').UI.Events.onComposerLeave();

		$('#mail_messages').addClass('hidden');
		$('#mail-message').addClass('hidden');
		$('#mail_new_message').addClass('hidden');
		$('#app-navigation').removeClass('icon-loading');

		require('app').UI.hideMenu();

		$('#mail-setup').removeClass('hidden');
		// don't show New Message button on Add account screen
		$('#mail_new_message').hide();
	};

	view.toggleManualSetup = function() {
		$('#mail-setup-manual').slideToggle();
		$('#mail-imap-host').focus();
		if ($('#mail-address').parent().prop('class') === 'groupmiddle') {
			$('#mail-password').slideToggle(function() {
				$('#mail-address').parent()
					.removeClass('groupmiddle').addClass('groupbottom');
			});
		} else {
			$('#mail-password').slideToggle();
			$('#mail-address').parent()
				.removeClass('groupbottom').addClass('groupmiddle');
		}
	};

	view.showDraftSavedNotification = function() {
		OC.Notification.showTemporary(t('mail', 'Draft saved!'));
	};

	view.Events = {
		onComposerLeave: function() {
			// Trigger only once
			if (composerVisible === true) {
				composerVisible = false;

				if (composer && composer.hasData === true) {
					if (composer.hasUnsavedChanges === true) {
						composer.saveDraft(function() {
							require('app').UI.showDraftSavedNotification();
						});
					} else {
						require('app').UI.showDraftSavedNotification();
					}
				}
			}
		},
		onFolderChanged: function() {
			// Stop background message fetcher of previous folder
			require('app').BackGround.messageFetcher.restart();
			// hide message detail view on mobile
			$('#mail-message').addClass('hidden-mobile');
		},
		onWindowResize: function() {
			// Resize iframe
			var iframe = $('#mail-content iframe');
			iframe.height(iframe.contents().find('html').height() + 20);
		}
	};

	Object.defineProperties(view, {
		messageView: {
			get: function() {
				return messageView;
			},
			set: function(mv) {
				messageView = mv;
			}
		}
	});

	return view;
});
