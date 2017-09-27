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

	function showNotification(title, body, icon) {
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
					icon: icon
				}
		);
		notification.onclick = function() {
			window.focus();
		};
	}

	/**
	 * @param {array<Message>} messages
	 * @returns {undefined}
	 */
	function showMailNotification(messages) {
		if (messages.length === 0) {
			// Ignore
			return;
		}

		// Update favicon to show red dot
		Radio.notification.trigger('favicon:change', OC.filePath('mail', 'img', 'favicon-notification.png'));

		if (Notification.permission !== 'granted') {
			// Don't show a notification
			return;
		}

		var from = _.map(messages, function(m) {
			return m.get('from')[0].label;
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
		if (messages.length === 1) {
			var subject = _.map(messages, function(m) {
				return m.get('subject');
			});
			body = t('mail',
					'{from}\n{subject}', {
						from: from.join(),
						subject: subject.join()
					});
		} else {
			body = n('mail',
					'%n new message \nfrom {from}',
					'%n new messages \nfrom {from}',
					messages.length, {
						from: from.join()
					});
		}
		// If it's okay let's create a notification
		var icon = OC.filePath('mail', 'img', 'mail-notification.png');
		showNotification(t('mail', 'Nextcloud Mail'), body, icon);
	}

	return {
		showNotification: showNotification,
		showMailNotification: showMailNotification
	};
});
