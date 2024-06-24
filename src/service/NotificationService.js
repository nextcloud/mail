/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import uniq from 'lodash/fp/uniq.js'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'

import Logger from '../logger.js'

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
		// Close the notification when clicked
		notification.close()
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
		}, undefined, {
			escape: false,
			sanitize: false,
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
		generateFilePath('mail', 'img', 'mail-notification.png'),
	)
}
