/* global Notification */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var $ = require('jquery');
	var OC = require('OC');
	var Cache = require('cache');
	var Radio = require('radio');
	var State = require('state');
	var MessageCollection = require('models/messagecollection');

	/*jshint maxparams: 6 */
	function showNotification(title, body, tag, icon, account, folder) {
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
			Radio.navigation.trigger('folder', account.get('accountId'), folder.get('id'), false);
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
						var changedFolder = changedAccount.getFolderById(changes.id);
						var localFolder = folders.get(changes.id);
						localFolder.set('uidvalidity', changes.uidvalidity);
						localFolder.set('uidnext', changes.uidnext);
						localFolder.set('unseen', changes.unseen);
						localFolder.set('total', changes.total);

						// reload if current selected folder has changed
						if (State.currentAccount === changedAccount &&
							State.currentFolder.get('id') === changes.id) {
							Radio.ui.request('messagesview:collection').add(changes.messages);
							var messages = new MessageCollection(changes.messages).slice(0);
							Radio.message.trigger('fetch:bodies', changedAccount, changedFolder, messages);
						}

						// Save new messages to the cached message list
						var cachedList = Cache.getMessageList(changedAccount, localFolder);
						if (cachedList) {
							cachedList = cachedList.concat(changes.messages);
							Cache.addMessageList(changedAccount, localFolder, cachedList);
						}

						Radio.ui.trigger('title:update');
					});
				}
			});
		});
	}

	return {
		checkForNotifications: checkForNotifications,
		showNotification: showNotification,
		showMailNotification: showMailNotification
	};
});
