/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import MailboxLockedError from './MailboxLockedError.js'
import MailboxNotCachedError from './MailboxNotCachedError.js'
import NoDraftsMailboxConfiguredError from './NoDraftsMailboxConfiguredError.js'
import NoSentMailboxConfiguredError from './NoSentMailboxConfiguredError.js'
import NoTrashMailboxConfiguredError from './NoTrashMailboxConfiguredError.js'
import CouldNotConnectError from './CouldNotConnectError.js'
import ManageSieveError from './ManageSieveError.js'
import ManyRecipientsError from './ManyRecipientsError.js'

const map = {
	'OCA\\Mail\\Exception\\DraftsMailboxNotSetException': NoDraftsMailboxConfiguredError,
	'OCA\\Mail\\Exception\\MailboxLockedException': MailboxLockedError,
	'OCA\\Mail\\Exception\\MailboxNotCachedException': MailboxNotCachedError,
	'OCA\\Mail\\Exception\\SentMailboxNotSetException': NoSentMailboxConfiguredError,
	'OCA\\Mail\\Exception\\TrashMailboxNotSetException': NoTrashMailboxConfiguredError,
	'OCA\\Mail\\Exception\\CouldNotConnectException': CouldNotConnectError,
	'OCA\\Mail\\Exception\\ManyRecipientsException': ManyRecipientsError,
	'Horde\\ManageSieve\\Exception': ManageSieveError,
}

/**
 * @param {object} axiosError the axios Error
 * @return {Error}
 */
export const convertAxiosError = (axiosError) => {
	if (!('response' in axiosError)) {
		// No conversion
		return axiosError
	}

	if (!('x-mail-response' in axiosError.response.headers)) {
		// Not a structured response
		return axiosError
	}

	const response = axiosError.response
	if (!(response.data.data.type in map)) {
		// No conversion possible
		return axiosError
	}

	return new map[response.data.data.type](response.data.message)
}
