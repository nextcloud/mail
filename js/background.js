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

	var OC = require('OC');
	var Radio = require('radio');

	/*jshint maxparams: 6 */
	function showNotification(title, body, tag, icon, accountId, folderId) {
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
			Radio.ui.trigger('folder:load', accountId, folderId, false);
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
			showNotification(email, body, tag, icon,
				folder.accountId, folder.id);
		}
	}

	function checkForNotifications() {
		require('state').accounts.each(function(account) {
			var localAccount = require(
				'state').folderView.collection.get(account.get('accountId'));
			var folders = localAccount.get('folders');

			$.ajax(
				OC.generateUrl(
					'apps/mail/accounts/{accountId}/folders/detectChanges',
					{
						accountId: account.get(
					'accountId')}), {
					data: JSON.stringify({folders: folders.toJSON()}),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					type: 'POST',
					success: function(jsondata) {
						_.each(jsondata, function(f) {
							// send notification
							if (f.newUnReadCounter > 0) {
								Radio.notification.trigger(
									'favicon:change',
									OC.filePath(
										'mail',
										'img',
										'favicon-notification.png'));
								// only show one notification
								if (require(
									'state').accounts.length === 1 || account.get(
									'accountId') === -1) {
									showMailNotification(
										localAccount.get(
											'email'),
										f);
								}
							}

							// update folder status
							var localFolder = folders.get(f.id);
							localFolder.set('uidvalidity', f.uidvalidity);
							localFolder.set('uidnext', f.uidnext);
							localFolder.set('unseen', f.unseen);
							localFolder.set('total', f.total);

							// reload if current selected folder has changed
							if (require('state').currentAccountId === f.accountId &&
								require('state').currentFolderId === f.id) {
								require(
									'ui').messageView.collection.add(
									f.messages);
							}

							// Save new messages to the cached message list
							var cachedList = require(
								'cache').
								getMessageList(f.accountId, f.id);
							if (cachedList) {
								cachedList = cachedList.concat(
									f.messages);
								require('cache').
									addMessageList(
										f.accountId,
										f.id,
										cachedList);
							}

							require('state').folderView.updateTitle();
						});
					}
				}
			);
		});
	}
	/**
	 * Fetch message of the current account/folder in background
	 *
	 * Uses a queue where message IDs are stored and fetched periodically
	 * The message is only fetched if it's not already cached
	 */
	function MessageFetcher() {
		var accountId = null;
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
					accountId, folderId, messages, {
						onSuccess: function(messages) {
							require('cache').addMessages(
								accountId,
								folderId, messages);
						}
					});
			}
		}

		return {
			start: function() {
				accountId = require('state').currentAccountId;
				folderId = require('state').currentFolderId;
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
