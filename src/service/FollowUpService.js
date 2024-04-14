/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * Check wheter the given message database ids have been replied to.
 *
 * @param {number[]} messageIds The message database ids to check.
 * @return {Promise<{wasFollowedUp: number[]}>} The ids that have been replied to and no longer need to be tracked as a follow-up reminder.
 */
export async function checkMessageIds(messageIds) {
	const url = generateUrl('/apps/mail/api/follow-up/check-message-ids')

	const response = await axios.post(url, { messageIds })
	return response.data.data
}
