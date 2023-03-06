/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
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
 *
 */

import uniq from 'lodash/fp/uniq'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'

import Logger from '../logger'

/**
 * @todo use Notification.requestPermission().then once all browsers support promise API
 * @return {Promise}
 */
const request = () => {
	if (!('Notification' in window)) {
		Logger.info('browser does not support desktop notifications')
		return Promise.reject(new Error('browser does not support desktop notifications'))
	} else if (Notification.permission === 'granted') {
		return Promise.resolve()
	} else if (Notification.permission === 'denied') {
		Logger.info('desktop notifications are denied')
		return Promise.reject(new Error('desktop notifications are denied'))
	}

	Logger.info('requesting permissions to show desktop notifications')
	return Notification.requestPermission()
}

const showNotification = async (title, body, icon) => {
	try {
		await request()
	} catch (error) {
		// User denied permission
		return
	}

	if (document.querySelector(':focus') !== null) {
		Logger.debug('browser is active. notification request is ignored')
	}

	const notification = new Notification(title, {
		body,
		icon,
	})
	notification.onclick = () => {
		window.focus()
	}
}

const getNotificationBody = (messages) => {
	const labels = messages.filter((m) => m.from.length > 0).map((m) => m.from[0].label)
	let from = uniq(labels)
	if (from.length > 2) {
		from = from.slice(0, 2)
		from.push('…')
	}

	// TODO: just use `n`?!
	if (messages.length === 1) {
		return t('mail', '{from}\n{subject}', {
			from: from.join(),
			subject: messages[0].subject,
		})
	} else {
		return n('mail', '%n new message \nfrom {from}', '%n new messages \nfrom {from}', messages.length, {
			from: from.join(),
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
