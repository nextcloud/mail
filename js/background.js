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

	var $ = require('jquery');
	var OC = require('OC');
	var Cache = require('cache');
	var Radio = require('radio');
	var State = require('state');

	/*jshint maxparams: 6 */
	function showNotification(title, body, tag, icon, account, folderId) {
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
			Radio.ui.trigger('folder:show', account, folderId, false);
			window.focus();
		};
		setTimeout(function() {
			notification.close();
		}, 5000);
	}

	function showMailNotification(email, folder) {
		if (Notification.permission === 'granted' && folder.messages.length > 0) {
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
			// special layout if there is only 1 new message
			var body = '';
			if (folder.messages.length === 1) {
				var subject = _.map(folder.messages, function(m) {
					return m.subject;
				});
				body = t('mail',
					'{from}\n{subject}', {
						from: from.join(),
						subject: subject.join()
					});
			} else {
				body = n('mail',
					'%n new message in {folderName} \nfrom {from}',
					'%n new messages in {folderName} \nfrom {from}',
					folder.messages.length, {
						folderName: folder.name,
						from: from.join()
					});
			}
			// If it's okay let's create a notification
			var tag = 'not-' + folder.accountId + '-' + folder.name;
			var icon = OC.filePath('mail', 'img', 'mail-notification.png');
			var account = State.accounts.get(folder.accountId);
			showNotification(email, body, tag, icon, account, folder.id);
		}
	}

	function checkForNotifications(accounts) {
		accounts.each(function(account) {
			var folders = account.get('folders');

			var url = OC.generateUrl('apps/mail/accounts/{id}/folders/detectChanges',
				{
					id: account.get('accountId')
				});
			$.ajax(url, {
				data: JSON.stringify({folders: folders.toJSON()}),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				type: 'POST',
				success: function(jsondata) {
					_.each(jsondata, function(changes) {
						// send notification
						if (changes.newUnReadCounter > 0) {
							Radio.notification.trigger(
								'favicon:change',
								OC.filePath(
									'mail',
									'img',
									'favicon-notification.png'));
							// only show one notification
							if (State.accounts.length === 1 || account.get('accountId') === -1) {
								showMailNotification(account.get('email'), changes);
							}
						}

						// update folder status
						var changedAccount = accounts.get(changes.accountId);
						var localFolder = folders.get(changes.id);
						localFolder.set('uidvalidity', changes.uidvalidity);
						localFolder.set('uidnext', changes.uidnext);
						localFolder.set('unseen', changes.unseen);
						localFolder.set('total', changes.total);

						// reload if current selected folder has changed
						if (State.currentAccount === changedAccount &&
							State.currentFolderId === changes.id) {
							Radio.ui.request('messagesview:collection').
								add(changes.messages);
						}

						// Save new messages to the cached message list
						var cachedList = Cache.getMessageList(changedAccount, changes.id);
						if (cachedList) {
							cachedList = cachedList.concat(changes.messages);
							Cache.addMessageList(changedAccount, changes.id, cachedList);
						}

						State.folderView.updateTitle();
					});
				}
			});
		});
	}
	/**
	 * Fetch message of the current account/folder in background
	 *
	 * Uses a queue where message IDs are stored and fetched periodically
	 * The message is only fetched if it's not already cached
	 */
	function MessageFetcher() {
		var account = null;
		var folderId = null;
		var pollIntervall = 3 * 1000;
		var queue = [];
		var timer = null;

		function fetch() {
			if (queue.length > 0) {
				// Empty waiting queue
				var messages = queue;
				queue = [];

				require('communication').fetchMessages(
					account, folderId, messages, {
						onSuccess: function(messages) {
							require('cache').addMessages(
								account,
								folderId, messages);
						}
					});
			}
		}

		return {
			start: function() {
				account = State.currentAccount;
				folderId = State.currentFolderId;
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
	}

	return {
		messageFetcher: new MessageFetcher(),
		checkForNotifications: checkForNotifications,
		showNotification: showNotification,
		showMailNotification: showMailNotification
	};
});
