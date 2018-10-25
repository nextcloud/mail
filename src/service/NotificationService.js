/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import _ from 'lodash'
import {translate as t, translatePlural as n} from 'nextcloud-server/dist/l10n'
import {generateFilePath} from 'nextcloud-server/dist/router'

/**
 * @todo use Notification.requestPermission().then once all browsers support promise API
 *
 * @return {Promise}
 */
const request = () => {
	if (!("Notification" in window)) {
		console.info('browser does not support desktop notifications')
		return Promise.reject()
	} else if (Notification.permission === 'granted') {
		return Promise.resolve()
	} else if (Notification.permission === 'denied') {
		console.info('desktop notifications are denied')
		return Promise.reject()
	}

	console.info('requesting permissions to show desktop notifications')
	return Notification.requestPermission()
}

const showNotification = (title, body, icon) => {
	request()
		.then(() => {
			if (document.querySelector(':focus') !== null) {
				console.debug('browser is active. notification request is ignored')
				return
			}
		})

	const notification = new Notification(
		title,
		{
			body: body,
			icon: icon
		}
	)
	notification.onclick = () => {
		window.focus()
	}
}

const getNotificationBody = (messages) => {
	const labels = messages
		.filter(m => m.from.length > 0)
		.map(m => m.from[0].label)
	let from = _.uniq(labels)
	if (from.length > 2) {
		from = from.slice(0, 2)
		from.push('…')
	}

	// TODO: just use `n`?!
	if (messages.length === 1) {
		return t('mail', '{from}\n{subject}', {
			from: from.join(),
			subject: messages[0].subject
		})
	} else {
		return n('mail', '%n new message \nfrom {from}', '%n new messages \nfrom {from}', messages.length, {
			from: from.join()
		})
	}
}

export const showNewMessagesNotification = (messages) => {
	showNotification(
		t('mail', 'Nextcloud Mail'),
		getNotificationBody(messages),
		generateFilePath('mail', 'img', 'mail-notification.png')
	)
}
