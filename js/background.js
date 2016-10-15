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
	var Radio = require('radio');
	var State = require('state');
	var MessageCollection = require('models/messagecollection');

	function checkForNotifications(accounts) {
		accounts.each(function(account) {
			var folders = account.folders;

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
								Radio.ui.trigger('notification:mail:show', account.get('email'), changes);
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
							_.each(changes.messages, function(msg) {
								State.currentFolder.addMessage(msg);
							});
							var messages = new MessageCollection(changes.messages).slice(0);
							Radio.message.trigger('fetch:bodies', changedAccount, changedFolder, messages);
						}

						Radio.ui.trigger('title:update');
					});
				}
			});
		});
	}

	return {
		checkForNotifications: checkForNotifications
	};
});
