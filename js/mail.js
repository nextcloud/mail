/* global Handlebars, Marionette, Notification, relative_modified_date, formatDate, humanFileSize, views, OC, _ */

if (_.isUndefined(OC.Notification.showTemporary)) {

	/**
	 * Shows a notification that disappears after x seconds, default is
	 * 7 seconds
	 * @param {string} text Message to show
	 * @param {array} [options] options array
	 * @param {int} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
	 * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
	 */
	OC.Notification.showTemporary = function(text, options) {
		var defaults = {
				isHTML: false,
				timeout: 7
			};
		options = options || {};
		// merge defaults with passed in options
		_.defaults(options, defaults);

		// clear previous notifications
		OC.Notification.hide();
		if (OC.Notification.notificationTimer) {
			clearTimeout(OC.Notification.notificationTimer);
		}

		if (options.isHTML) {
			OC.Notification.showHtml(text);
		} else {
			OC.Notification.show(text);
		}

		if (options.timeout > 0) {
			// register timeout to vanish notification
			OC.Notification.notificationTimer = setTimeout(OC.Notification.hide, (options.timeout * 1000));
		}
	};
}

var Mail = {
	State: (function() {
		var accounts = null;
		var currentFolderId = null;
		var currentAccountId = null;
		var currentMessageId = null;
		var messagesLoading = null;
		var messageLoading = null;

		Object.defineProperties(this, {
			accounts: {
				get: function() {
					return accounts;
				},
				set: function(acc) {
					accounts = acc;
				}
			},
			currentAccountId: {
				get: function() {
					return currentAccountId;
				},
				set: function(newId) {
					currentAccountId = newId;
				}
			},
			currentFolderId: {
				get: function() {
					return currentFolderId;
				},
				set: function(newId) {
					var oldId = currentFolderId;
					currentFolderId = newId;
					if (newId !== oldId) {
						Mail.UI.Events.onFolderChanged();
					}
				}
			},
			currentMessageId: {
				get: function() {
					return currentMessageId;
				},
				set: function(newId) {
					currentMessageId = newId;
				}
			},
			messagesLoading: {
				get: function() {
					return messagesLoading;
				},
				set: function(loading) {
					messagesLoading = loading;
				}
			},
			messageLoading: {
				get: function() {
					return messageLoading;
				},
				set: function(loading) {
					messageLoading = loading;
				}
			}
		});
		return this;
	})(),
	Cache: {
		getFolderPath: function(accountId, folderId) {
			return 'messages' +
				'.' +
				accountId.toString() +
				'.' +
				folderId.toString();
		},
		getMessagePath: function(accountId, folderId, messageId) {
			return Mail.Cache.getFolderPath(accountId, folderId) +
				'.' +
				messageId.toString();
		},
		cleanUp: function(accounts) {
			var activeAccounts = _.map(accounts, function(account) {
				return account.accountId;
			});
			var storage = $.localStorage;
			_.each(storage.get('messages'), function(account, accountId) {
				var isActive = _.any(activeAccounts, function(a) {
					return a === parseInt(accountId);
				});
				if (!isActive) {
					// Account does not exist anymore -> remove it
					storage.remove('messages.' + accountId);
				}
			});
		},
		getFolderMessages: function(accountId, folderId) {
			var path = Mail.Cache.getFolderPath(accountId, folderId);
			var storage = $.localStorage;
			return storage.isSet(path) ? storage.get(path) : null;
		},
		getMessage: function(accountId, folderId, messageId) {
			var path = Mail.Cache.getMessagePath(accountId, folderId, messageId);
			var storage = $.localStorage;
			if (storage.isSet(path)) {
				var message = storage.get(path);
				// Update the timestamp
				Mail.Cache.addMessage(accountId, folderId, message);
				return message;
			} else {
				return null;
			}
		},
		addMessage: function(accountId, folderId, message) {
			var path = Mail.Cache.getMessagePath(accountId, folderId, message.id);
			var storage = $.localStorage;

			// Add timestamp for later cleanup
			message.timestamp = Date.now();

			// Save the message to local storage
			storage.set(path, message);

			// Remove old messages (keep 20 most recently loaded)
			var messages = $.map(Mail.Cache.getFolderMessages(accountId, folderId), function(value) {
				return [value];
			});
			messages.sort(function(m1, m2) {
				return m2.timestamp - m1.timestamp;
			});
			var oldMessages = messages.slice(20, messages.length);
			_.each(oldMessages, function(message) {
				storage.remove(Mail.Cache.getMessagePath(accountId, folderId, message.id));
			});
		}
	},
	Search: {
		timeoutID: null,
		attach: function(search) {
			search.setFilter('mail', Mail.Search.filter);
		},
		filter: function(query) {
			window.clearTimeout(Mail.Search.timeoutID);
			Mail.Search.timeoutID = window.setTimeout(function() {
				Mail.UI.messageView.filterCurrentMailbox(query);
			}, 500);
			$('#searchresults').hide();
		}
	},
    /*jshint maxparams: 6 */
    /* Todo: Refactor if you are touching
       https://jslinterrors.com/this-function-has-too-many-parameters
     */
	BackGround: {
		showNotification: function(title, body, tag, icon, accountId, folderId) {
			// notifications not supported -> go away
			if (typeof Notification === 'undefined') {
				return;
			}
			// browser is active -> go away
			var isWindowFocused = document.querySelector(':focus') !== null;
			if (isWindowFocused) {
				return;
			}
			var notification = new Notification(
				title,
				{
					body: body,
					tag: tag,
					icon: icon
				}
			);
			notification.onclick = function() {
				Mail.UI.loadFolder(accountId, folderId, false);
				window.focus();
			};
			setTimeout(function() {
				notification.close();
			}, 5000);
		},

		showMailNotification: function(email, folder) {
			if (Notification.permission === "granted" && folder.messages.length > 0) {
				var from = _.map(folder.messages, function(m) {
					return m.from;
				});
				from = _.uniq(from);
				if (from.length > 2) {
					from = from.slice(0, 2);
					from.push('…');
				} else {
					from = from.slice(0, 2);
				}
				var body = n('mail',
					'%n new message in {folderName} \nfrom {from}',
					'%n new messages in {folderName} \nfrom {from}',
					folder.messages.length, {
						folderName: folder.name,
						from: from.join()
					});
				// If it's okay let's create a notification
				var tag = 'not-' + folder.accountId + '-' + folder.name;
				var icon = OC.filePath('mail', 'img', 'mail-notification.png');
				Mail.BackGround.showNotification(email, body, tag, icon, folder.accountId, folder.id);
			}
		},

		checkForNotifications: function() {
			_.each(Mail.State.accounts, function(a) {
				var localAccount = Mail.State.folderView.collection.get(a.accountId);
				var folders = localAccount.get('folders');

				$.ajax(
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/detectChanges', {accountId: a.accountId}), {
						data: JSON.stringify({folders: folders.toJSON()}),
						contentType: "application/json; charset=utf-8",
						dataType: "json",
						type: 'POST',
						success: function(jsondata) {
							_.each(jsondata, function(f) {
								// send notification
								if (f.newUnReadCounter > 0) {
									Mail.UI.changeFavicon(OC.filePath('mail', 'img', 'favicon-notification.png'));
									Mail.BackGround.showMailNotification(localAccount.get('email'), f);
								}

								// update folder status
								var localFolder = folders.get(f.id);
								localFolder.set('uidvalidity', f.uidvalidity);
								localFolder.set('uidnext', f.uidnext);
								localFolder.set('unseen', f.unseen);
								localFolder.set('total', f.total);

								// reload if current selected folder has changed
								if (Mail.State.currentAccountId === f.accountId &&
									Mail.State.currentFolderId === f.id) {
									Mail.UI.messageView.collection.add(f.messages);
								}

								Mail.State.folderView.updateTitle();
							});
						}

					}
				);
			});
		},

		/**
		 * Fetch message of the current account/folder in background
		 *
		 * Uses a queue where message IDs are stored and fetched periodically
		 * The message is only fetched if it's not already cached
		 */
		messageFetcher: (function() {
			var accountId = null;
			var folderId = null;
			var pollIntervall = 3 * 1000;
			var queue = [];
			var timer = null;

			function fetch () {
				if (queue.length > 0) {
					// Empty waiting queue
					var messages = queue;
					queue = [];

					_.each(messages, function(messageId) {
						if (!Mail.Cache.getMessage(accountId, folderId, messageId)) {
							Mail.Communication.fetchMessage(accountId, folderId, messageId, {
								backgroundMode: true,
								onSuccess: function(message) {
									// Add the fetched message to cache
									Mail.Cache.addMessage(accountId, folderId, message);
								}
							});
						}
					});
				}
			}

			return {
				start: function() {
					accountId = Mail.State.currentAccountId;
					folderId = Mail.State.currentFolderId;
					timer = setInterval(fetch, pollIntervall);
				},
				restart: function() {
					// Stop previous fetcher
					clearInterval(timer);

					// Clear waiting queue
					queue.length = 0;

					// Start again
					this.start();
				},
				push: function(message) {
					queue.push(message);
				}
			};
		}())
	},
	Communication: {
		get: function(url, options) {
			var defaultOptions = {
					ttl: 60000,
					cache: true,
					key: url
				},
				allOptions = options || {};
			_.defaults(allOptions, defaultOptions);

			// don't cache for the time being
			allOptions.cache = false;
			if (allOptions.cache) {
				var cache = $.initNamespaceStorage(allOptions.key).localStorage;
				var ttl = cache.get('ttl');
				if (ttl && ttl < Date.now()) {
					cache.removeAll();
				}
				var item = cache.get('data');
				if (item) {
					options.success(item);
					return;
				}
			}
			return $.ajax(url, {
				data: {},
				type: 'GET',
				error: function(xhr, textStatus) {
					options.error(textStatus);
				},
				success: function(data) {
					if (allOptions.cache) {
						cache.set('data', data);
						if (typeof allOptions.ttl === 'number') {
							cache.set('ttl', Date.now() + allOptions.ttl);
						}
					}
					options.success(data);
				}
			});
		},
		fetchMessage: function(accountId, folderId, messageId, options) {
			options = options || {};
			var defaults = {
				onSuccess: function() { },
				onError: function() { },
				backgroundMode: false
			};
			_.defaults(options, defaults);

			// Load cached version if available
			var message = Mail.Cache.getMessage(Mail.State.currentAccountId,
				Mail.State.currentFolderId,
				messageId);
			if (message) {
				options.onSuccess(message);
				return;
			}

			var xhr = $.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
				{
					accountId: accountId,
					folderId: folderId,
					messageId: messageId
				}), {
					data: {},
					type: 'GET',
					success: options.onSuccess,
					error: options.onError
				});
			if (!options.backgroundMode) {
				// Save xhr to allow aborting unneded requests
				Mail.State.messageLoading = xhr;
			}
		}
	},
	UI: (function() {
		var messageView = null;
		var composeView = null;
		var composerVisible = false;

		this.renderSettings = function() {
			var source   = $("#mail-settings-template").html();
			var template = Handlebars.compile(source);
			var html = template(Mail.State.accounts);
			$('#app-settings-content').html(html);
		};

		this.changeFavicon = function(src) {
			$('link[rel="shortcut icon"]').attr('href',src);
		};

		this.loadAccounts = function() {
			Mail.Communication.get(OC.generateUrl('apps/mail/accounts'), {
				success: function(accounts) {
					Mail.State.accounts = accounts;
					Mail.UI.renderSettings();
					if (accounts.length === 0) {
						Mail.UI.addAccount();
					} else {
						var firstAccountId = accounts[0].accountId;
						_.each(accounts, function(a) {
							Mail.UI.loadFoldersForAccount(a.accountId, firstAccountId);
						});
					}
					Mail.Cache.cleanUp(accounts);
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading the accounts.'));
				},
				ttl: 'no'
			});
		};

		this.initializeInterface = function() {
			Handlebars.registerHelper("relativeModifiedDate", function(dateInt) {
				var lastModified = new Date(dateInt * 1000);
				var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
				return relative_modified_date(lastModifiedTime);
			});

			Handlebars.registerHelper("formatDate", function(dateInt) {
				var lastModified = new Date(dateInt * 1000);
				return formatDate(lastModified);
			});

			Handlebars.registerHelper("humanFileSize", function(size) {
				return humanFileSize(size);
			});

			Handlebars.registerHelper("printAddressList", function(addressList) {
				var currentAddress = _.find(Mail.State.accounts, function(item) {
					return item.accountId === Mail.State.currentAccountId;
				});

				var str = _.reduce(addressList, function(memo, value, index) {
					if (index !== 0) {
						memo += ', ';
					}
					var label = value.label
						.replace(/(^"|"$)/g, '')
						.replace(/(^'|'$)/g, '');
					label = Handlebars.Utils.escapeExpression(label);
					var email = Handlebars.Utils.escapeExpression(value.email);
					if (currentAddress && email === currentAddress.emailAddress) {
						label = t('mail', 'you');
					}
					var title = t('mail', 'Send message to {email}', {email: email});
					memo += '<span class="tipsy-mailto" title="' + title + '">';
					memo += '<a class="link-mailto" data-email="' +	email + '" data-label="' +	label + '">';
					memo += label + '</a></span>';
					return memo;
				}, "");
				return new Handlebars.SafeString(str);
			});

			Handlebars.registerHelper("printAddressListPlain", function(addressList) {
				var str = _.reduce(addressList, function(memo, value, index) {
					if (index !== 0) {
						memo += ', ';
					}
					var label = value.label
						.replace(/(^"|"$)/g, '')
						.replace(/(^'|'$)/g, '');
					label = Handlebars.Utils.escapeExpression(label);
					var email = Handlebars.Utils.escapeExpression(value.email);
					if (label === email) {
						return memo + email;
					} else {
						return memo + '"' + label + '" <' + email + '>';
					}
				}, "");
				return str;
			});

			Handlebars.registerHelper("ifHasCC", function (cc, ccList, options) {
				if (!_.isUndefined(cc) || !_.isUndefined(ccList)) {
					return options.fn(this);
				} else {
					return options.inverse(this);
				}
			});

			Handlebars.registerHelper("unlessHasCC", function (cc, ccList, options) {
				if (_.isUndefined(cc) && _.isUndefined(ccList)) {
					return options.fn(this);
				} else {
					return options.inverse(this);
				}
			});

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
				window.navigator
					.registerProtocolHandler("mailto", url, "ownCloud Mail");
			}

			// setup messages view
			Mail.UI.messageView = new views.Messages({
				el: $('#mail_messages')
			});
			Mail.UI.messageView.render();

			// setup folder view
			Mail.State.folderView = new views.Folders({
				el: $('#folders')
			});
			Mail.State.folderView.render();

			Mail.State.folderView.listenTo(Mail.UI.messageView, 'change:unseen',
				Mail.State.folderView.changeUnseen);

			// request permissions
			if (typeof Notification !== 'undefined') {
				Notification.requestPermission();
			}

			if (!_.isUndefined(OC.Plugins)) {
				OC.Plugins.register('OCA.Search', Mail.Search);
			}

			setInterval(Mail.BackGround.checkForNotifications, 5*60*1000);
			this.loadAccounts();
		};

		this.loadFoldersForAccount = function(accountId, firstAccountId) {
			$('#mail_messages').removeClass('hidden').addClass('icon-loading');
			$('#mail-message').removeClass('hidden').addClass('icon-loading');
			$('#mail_new_message').removeClass('hidden');
			$('#folders').removeClass('hidden');
			$('#mail-setup').addClass('hidden');

			Mail.UI.clearMessages();
			$('#app-navigation').addClass('icon-loading');

			Mail.Communication.get(OC.generateUrl('apps/mail/accounts/{accountId}/folders', {accountId: accountId}), {
				success: function(jsondata) {
					$('#app-navigation').removeClass('icon-loading');
					Mail.State.folderView.collection.add(jsondata);

					if (jsondata.id === firstAccountId) {
						var folderId = jsondata.folders[0].id;

						Mail.UI.loadFolder(accountId, folderId, false);

						// Save current folder
						Mail.UI.setFolderActive(accountId, folderId);
						Mail.State.currentAccountId = accountId;
						Mail.State.currentFolderId = folderId;

						// Start fetching messages in background
						Mail.BackGround.messageFetcher.start();
					}
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading the selected account.'));
				},
				ttl: 'no'
			});
		};

		this.showError = function(message) {
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

		this.clearMessages = function() {
			Mail.UI.messageView.collection.reset();
			$('#messages-loading').fadeIn();

			$('#mail-message')
				.html('')
				.addClass('icon-loading');
		};

		this.hideMenu = function() {
			$('#new-message').addClass('hidden');
			if (Mail.State.accounts.length === 0) {
				$('#app-navigation').hide();
				$('#app-navigation-toggle').css('background-image', 'none');
			}
		};

		this.showMenu = function() {
			$('#new-message').removeClass('hidden');
			$('#app-navigation').show();
			$('#app-navigation-toggle').css('background-image', '');
		};

		this.addMessages = function(data) {
			Mail.UI.messageView.collection.add(data);
		};

		this.loadFolder = function(accountId, folderId, noSelect) {
			Mail.UI.Events.onComposerLeave();

			if (Mail.State.messagesLoading !== null) {
				Mail.State.messagesLoading.abort();
			}
			if (Mail.State.messageLoading !== null) {
				Mail.State.messageLoading.abort();
			}

			// Set folder active
			Mail.UI.setFolderActive(accountId, folderId);
			Mail.UI.clearMessages();
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
				Mail.State.currentAccountId = accountId;
				Mail.State.currentFolderId = folderId;
				Mail.UI.setMessageActive(null);
				$('#mail_messages').removeClass('icon-loading');
				Mail.State.currentlyLoading = null;
			} else {
				Mail.State.currentlyLoading = Mail.Communication.get(
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
						{'accountId': accountId, 'folderId': folderId}), {
						success: function(jsondata) {
							Mail.State.currentlyLoading = null;
							Mail.State.currentAccountId = accountId;
							Mail.State.currentFolderId = folderId;
							Mail.UI.setMessageActive(null);
							$('#mail_messages').removeClass('icon-loading');

							// Fade out the message composer
							$('#mail_new_message').prop('disabled', false);

							if (jsondata.length > 0) {
								Mail.UI.addMessages(jsondata);

								// Fetch first 10 messages in background
								_.each(jsondata.slice(0, 10), function(message) {
									Mail.BackGround.messageFetcher.push(message.id);
								});

								var messageId = jsondata[0].id;
								Mail.UI.loadMessage(messageId);
								// Show 'Load More' button if there are
								// more messages than the pagination limit
								if (jsondata.length > 20) {
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

						},
						error: function(textStatus) {
							if (textStatus !== 'abort') {
								// Set the old folder as being active
								Mail.UI.setFolderActive(Mail.State.currentAccountId, Mail.State.currentFolderId);
								Mail.UI.showError(t('mail', 'Error while loading messages.'));
							}
						},
						cache: false
					});
			}
		};

		this.saveAttachment = function(messageId, attachmentId) {
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
							accountId: Mail.State.currentAccountId,
							folderId: Mail.State.currentFolderId,
							messageId: messageId,
							attachmentId: attachmentId
						}), {
							data: {
								targetPath: path
							},
							type: 'POST',
							success: function() {
								if (typeof attachmentId === "undefined") {
									Mail.UI.showError(t('mail', 'Attachments saved to Files.'));
								} else {
									Mail.UI.showError(t('mail', 'Attachment saved to Files.'));
								}
							},
							error: function() {
								if (typeof attachmentId === "undefined") {
									Mail.UI.showError(t('mail', 'Error while saving attachments to Files.'));
								} else {
									Mail.UI.showError(t('mail', 'Error while saving attachment to Files.'));
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

		this.openComposer = function(data) {
			Mail.UI.composerVisible = true;
			$('.tipsy').remove();
			$('#mail_new_message').prop('disabled', true);
			$('#mail-message').removeClass('hidden-mobile');

			// Abort message loads
			if (Mail.State.messageLoading !== null) {
				Mail.State.messageLoading.abort();
				$('iframe').parent().removeClass('icon-loading');
				$('#mail-message').removeClass('icon-loading');
				$('#mail_message').removeClass('icon-loading');
			}

			if (Mail.State.composeView === null) {
				// setup sendmail view
				Mail.State.composeView = new views.SendMail({
					el: $('#mail-message'),
					aliases: Mail.State.accounts,
					data: data
				});

				Mail.State.composeView.sentCallback = function() {};
			} else {
				Mail.State.composeView.data = data;
				Mail.State.composeView.hasData = false;
				Mail.State.composeView.hasUnsavedChanges = false;
			}

			if (data && data.hasHtmlBody) {
				Mail.UI.showError(t('mail', 'Opening HTML drafts is not supported yet.'));
			}

			Mail.State.composeView.attachments.reset();
			Mail.State.composeView.render();

			// focus 'to' field automatically on clicking New message button
			$('#to').focus();
			if (!_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).data().email)) {
				var to = '"' + $(data.currentTarget).data().label + '" <' + $(data.currentTarget).data().email + '>';
				$('#to').val(to);
			}

			Mail.UI.setMessageActive(null);
		};

		this.loadDraft = function(messageId) {
			var storage = $.localStorage;
			var draftId = 'draft' +
				'.' + Mail.State.currentAccountId.toString() +
				'.' + Mail.State.currentFolderId.toString() +
				'.' + messageId.toString();
			if (storage.isSet(draftId)) {
				return storage.get(draftId);
			} else {
				return null;
			}
		};

		this.loadMessage = function(messageId, options) {
			options = options || {};
			var defaultOptions = {
				force: false
			};
			_.defaults(options, defaultOptions);

			// Do not reload email when clicking same again
			if (Mail.State.currentMessageId === messageId) {
				return;
			}

			Mail.UI.Events.onComposerLeave();

			if (!options.force && $('#new-message').length) {
				return;
			}
			// Abort previous loading requests
			if (Mail.State.messageLoading !== null) {
				Mail.State.messageLoading.abort();
			}

			// check if message is a draft
			var accountId = Mail.State.currentAccountId;
			var account = Mail.State.folderView.collection.findWhere({id: accountId});
			var draftsFolder = account.attributes.specialFolders.drafts;
			var draft = draftsFolder === Mail.State.currentFolderId;

			// close email first
			// Check if message is open
			if (Mail.State.currentMessageId !== null) {
				var lastMessageId = Mail.State.currentMessageId;
				Mail.UI.setMessageActive(null);
				if (lastMessageId === messageId) {
					return;
				}
			}

			var mailBody = $('#mail-message');
			mailBody.html('').addClass('icon-loading');

			// Set current Message as active
			Mail.UI.setMessageActive(messageId);

			// Fade out the message composer
			$('#mail_new_message').prop('disabled', false);

			var self = this;
			var loadMessageSuccess = function(message) {
				// load local storage draft
				var draft = self.loadDraft(messageId);
				if (draft) {
					message.replyCc = draft.cc;
					message.replyBcc = draft.bcc;
					message.replyBody = draft.body;
				} else {
					// Add body content to inline reply (text mails)
					if (!message.hasHtmlBody) {
						var date = new Date(message.dateIso);
						var minutes = date.getMinutes();

						message.replyBody = '\n\n\n\n' +
							message.from + ' – ' +
							$.datepicker.formatDate('D, d. MM yy ', date) +
							date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
							$(message.body).text().replace(/\n/g, '\n> ');
					}
				}

				// Render the message body
				var source = $("#mail-message-template").html();
				var template = Handlebars.compile(source);
				var html = template(message);
				mailBody
					.html(html)
					.removeClass('icon-loading');

				Mail.UI.messageView.setMessageFlag(messageId, 'unseen', false);

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
					// TODO better html handling, should be fine for the moment!
					var text = jQuery($(this).contents().find('body').html().replace(/<br>/g, '\n')).text();
					if (!draft) {
						var date = new Date(message.dateIso);
						var minutes = date.getMinutes();

						$('.reply-message-body').first().text(
							'\n\n\n' +
							message.from + ' – ' +
							$.datepicker.formatDate('D, d. MM yy ', date) +
							date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
							text.replace(/\n/g, '\n> ')
						);
					}

				});

				$('textarea').autosize({append: '"\n\n"'});
				// dirty workaround to set reply message body to the default size
				$('.reply-message-body').css('height', '');

				// Expand reply message body on click
				$('.reply-message-body').click(function() {
					$('.reply-message-body').trigger('autosize.resize');
				});
			};

			var loadDraftSuccess = function(data) {
				self.openComposer(data);
			};

			Mail.Communication.fetchMessage(
				Mail.State.currentAccountId,
				Mail.State.currentFolderId,
				messageId,
				{
					onSuccess: function(message) {
						if (draft) {
							loadDraftSuccess(message);
						} else {
							Mail.Cache.addMessage(Mail.State.currentAccountId,
								Mail.State.currentFolderId,
								message);
								loadMessageSuccess(message);
						}
					},
					onError: function(jqXHR, textStatus) {
						if (textStatus !== 'abort') {
							Mail.UI.showError(t('mail', 'Error while loading the selected message.'));
						}
					}
				});
		};

		this.setFolderActive = function(accountId, folderId) {
			Mail.UI.messageView.clearFilter();

			// disable all other folders for all accounts
			_.each(Mail.State.accounts, function(account) {
				var localAccount = Mail.State.folderView.collection.get(account.accountId);
				if (_.isUndefined(localAccount)) {
					return;
				}
				var folders = localAccount.get('folders');
				_.each(folders.models, function(folder) {
					folders.get(folder).set('active', false);
				});
			});

			Mail.State.folderView.getFolderById(accountId, folderId)
				.set('active', true);
		};

		this.setMessageActive = function(messageId) {
			Mail.UI.messageView.setActiveMessage(messageId);
			Mail.State.currentMessageId = messageId;
			Mail.State.folderView.updateTitle();
		};

		this.addAccount = function() {
			Mail.UI.Events.onComposerLeave();

			$('#mail_messages').addClass('hidden');
			$('#mail-message').addClass('hidden');
			$('#mail_new_message').addClass('hidden');
			$('#app-navigation').removeClass('icon-loading');

			Mail.UI.hideMenu();

			$('#mail-setup').removeClass('hidden');
			// don't show New Message button on Add account screen
			$('#mail_new_message').hide();
		};

		this.toggleSendButton = function() {
			if (($('#to').val() !== '') && (($('#subject').val() !== '') || ($('#new-message-body').val() !== ''))) {
				$('#new-message-send').removeAttr('disabled');
			} else {
				$('#new-message-send').attr('disabled', true);
			}
		};

		this.toggleReplyButton = function() {
			if (($('.reply-message-fields #to').val() !== '') && ($('.reply-message-body').val() !== '')) {
				$('.reply-message-send').removeAttr('disabled');
			} else {
				$('.reply-message-send').attr('disabled', true);
			}
		};

		this.toggleManualSetup = function() {
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

		this.showDraftSavedNotification = function() {
			OC.Notification.showTemporary(t('mail', 'Draft saved!'));
		};

		this.Events = {
			onComposerLeave: function() {
				// Trigger only once
				if (Mail.UI.composerVisible === true) {
					Mail.UI.composerVisible = false;

					var composer = Mail.State.composeView;
					if (composer && composer.hasData === true) {
						if (composer.hasUnsavedChanges === true) {
							composer.saveDraft(function() {
								Mail.UI.showDraftSavedNotification();
							});
						} else {
							Mail.UI.showDraftSavedNotification();
						}
					}
				}
			},

			onFolderChanged: function() {
				// Stop background message fetcher of previous folder
				Mail.BackGround.messageFetcher.restart();
				// hide message detail view on mobile
				$('#mail-message').addClass('hidden-mobile');
			}
		};

		Object.defineProperties(this, {
			messageView: {
				get: function() {
					return messageView;
				},
				set: function(mv) {
					messageView = mv;
				}
			},
			composeView: {
				get: function() {
					return composeView;
				},
				set: function(cv) {
					composeView = cv;
				}
			},
			composerVisible: {
				get: function() {
					return composerVisible;
				},
				set: function(state) {
					composerVisible = state;
				}
			}
		});

		return this;
	})()
};

$(document).ready(function() {
	Mail.UI.initializeInterface();

	// auto detect button handling
	$('#auto_detect_account').click(function(event) {
		event.preventDefault();
		$('#mail-account-name, #mail-address, #mail-password, #mail-setup-manual-toggle')
			.prop('disabled', true);
		$('#mail-imap-host, #mail-imap-port, #mail-imap-sslmode, #mail-imap-user, #mail-imap-password')
			.prop('disabled', true);
		$('#mail-smtp-host, #mail-smtp-port, #mail-smtp-sslmode, #mail-smtp-user, #mail-smtp-password')
			.prop('disabled', true);

		$('#auto_detect_account')
			.prop('disabled', true)
			.val(t('mail', 'Connecting …'));
		$('#connect-loading').fadeIn();
		var emailAddress = $('#mail-address').val();
		var accountName = $('#mail-account-name').val();
		var password = $('#mail-password').val();

		var dataArray = {
			accountName: accountName,
			emailAddress: emailAddress,
			password: password,
			autoDetect: true
		};

		// if manual setup is open, use manual values
		if ($('#mail-setup-manual').css('display') === 'block') {
			dataArray = {
				accountName: accountName,
				emailAddress: emailAddress,
				password: password,
				imapHost: $('#mail-imap-host').val(),
				imapPort: $('#mail-imap-port').val(),
				imapSslMode: $('#mail-imap-sslmode').val(),
				imapUser: $('#mail-imap-user').val(),
				imapPassword: $('#mail-imap-password').val(),
				smtpHost: $('#mail-smtp-host').val(),
				smtpPort: $('#mail-smtp-port').val(),
				smtpSslMode: $('#mail-smtp-sslmode').val(),
				smtpUser: $('#mail-smtp-user').val(),
				smtpPassword: $('#mail-smtp-password').val(),
				autoDetect: false
			};
		}

		$.ajax(OC.generateUrl('apps/mail/accounts'), {
			data: dataArray,
			type:'POST',
			success:function() {
				// reload accounts
				Mail.UI.loadAccounts();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				switch (jqXHR.status) {
				case 400:
					var response = JSON.parse(jqXHR.responseText);
					Mail.UI.showError(t('mail', response.message));
					break;
				default:
					var error = errorThrown || textStatus || t('mail', 'Unknown error');
					Mail.UI.showError(t('mail', 'Error while creating an account: ' + error));
				}
			},
			complete: function() {
				$('#mail-account-name, #mail-address, #mail-password, #mail-setup-manual-toggle')
					.prop('disabled', false);
				$('#mail-imap-host, #mail-imap-port, #mail-imap-sslmode, #mail-imap-user, #mail-imap-password')
					.prop('disabled', false);
				$('#mail-smtp-host, #mail-smtp-port, #mail-smtp-sslmode, #mail-smtp-user, #mail-smtp-password')
					.prop('disabled', false);
				$('#auto_detect_account')
					.prop('disabled', false)
					.val(t('mail', 'Connect'));
				$('#connect-loading').hide();

				Mail.UI.showMenu();
			}
		});
	});

	// set standard port for the selected IMAP & SMTP security
	$(document).on('change', '#mail-imap-sslmode', function() {
		var imapDefaultPort = 143;
		var imapDefaultSecurePort = 993;

		switch ($(this).val()) {
		case 'none':
		case 'tls':
			$('#mail-imap-port').val(imapDefaultPort);
			break;
		case 'ssl':
			$('#mail-imap-port').val(imapDefaultSecurePort);
			break;
		}
	});

	$(document).on('change', '#mail-smtp-sslmode', function() {
		var smtpDefaultPort = 587;
		var smtpDefaultSecurePort = 465;

		switch ($(this).val()) {
		case 'none':
		case 'tls':
			$('#mail-smtp-port').val(smtpDefaultPort);
			break;
		case 'ssl':
			$('#mail-smtp-port').val(smtpDefaultSecurePort);
			break;
		}
	});

	// toggle for advanced account configuration
	$(document).on('click', '#mail-setup-manual-toggle', function() {
		Mail.UI.toggleManualSetup();
	});

	// new mail message button handling
	$(document).on('click', '#mail_new_message', Mail.UI.openComposer);

	// disable send/reply buttons unless recipient and either subject or message body is filled
	$(document).on('change input paste keyup', '#to', Mail.UI.toggleSendButton);
	$(document).on('change input paste keyup', '#subject', Mail.UI.toggleSendButton);
	$(document).on('change input paste keyup', '#new-message-body', Mail.UI.toggleSendButton);
	$(document).on('change input paste keyup', '.reply-message-fields #to', Mail.UI.toggleReplyButton);
	$(document).on('change input paste keyup', '.reply-message-body', Mail.UI.toggleReplyButton);

	$(document).on('click', '#mail-message .attachment-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().data('messageId');
		var attachmentId = $(this).parent().data('attachmentId');
		Mail.UI.saveAttachment(messageId, attachmentId);
	});

	$(document).on('click', '#mail-message .attachments-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).data('messageId');
		Mail.UI.saveAttachment(messageId);
	});

	$(document).on('click', '.link-mailto', function(event){
		Mail.UI.openComposer(event);
	});

	// close message when close button is tapped on mobile
	$(document).on('click', '#mail-message-close', function(){
		$('#mail-message').addClass('hidden-mobile');
	});

	$(document).on('show', function() {
		Mail.UI.changeFavicon(OC.filePath('mail', 'img', 'favicon.png'));
	});

	// Listens to key strokes, and executes a function based on the key combinations.
	$(document).keyup(function(event){

		var key = event.keyCode || event.which;
		if (Mail.State.currentMessageId) {
			switch (key) {
			case 46:
				// Delete key
				if (!$('#to, #cc, .reply-message-body').is(':focus')) {
					$('.mail_message_summary.active .icon-delete.action.delete').click();
				}
				break;
			}
		}
	});

});
