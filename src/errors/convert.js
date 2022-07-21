/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import MailboxLockedError from './MailboxLockedError'
import MailboxNotCachedError from './MailboxNotCachedError'
import NoDraftsMailboxConfiguredError from './NoDraftsMailboxConfiguredError'
import NoSentMailboxConfiguredError from './NoSentMailboxConfiguredError'
import NoTrashMailboxConfiguredError from './NoTrashMailboxConfiguredError'
import CouldNotConnectError from './CouldNotConnectError'
import ManageSieveError from './ManageSieveError'
import ManyRecipientsError from './ManyRecipientsError'

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
