/* global Notification */

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

	var _ = require('underscore');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.ui.on('notification:mail:show', showMailNotification);
	Radio.ui.on('notification:request', requestNotification);

	function requestNotification() {
		if (typeof Notification !== 'undefined') {
			Notification.requestPermission();
		}
	}

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
			var State = require('state');
			var tag = 'not-' + folder.accountId + '-' + folder.name;
			var icon = OC.filePath('mail', 'img', 'mail-notification.png');
			var account = State.accounts.get(folder.accountId);
			showNotification(email, body, tag, icon, account, folder.id);
		}
	}

	return {
		showNotification: showNotification,
		showMailNotification: showMailNotification
	};
});
