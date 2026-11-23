/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'

/**
 * Fetch all governance labels available for mails.
 *
 * @return {Promise<{id: string, type: string, name: string, priority: number, description: string, color: string, scopes: string[]}[]>}
 */
export async function fetchGovernanceLabels() {
	const url = generateUrl('/apps/mail/api/governance/labels')

	const response = await axios.get(url)
	return response.data.data
}

let labelsPromise = null

/**
 * Fetch governance labels once and cache them for the session.
 * Resolves to an empty list when the governance app is not available.
 *
 * @return {Promise<{id: string, type: string, name: string, priority: number, description: string, color: string, scopes: string[]}[]>}
 */
export function getGovernanceLabels() {
	if (!loadState('mail', 'governance-labels-available', false)) {
		return Promise.resolve([])
	}

	if (labelsPromise === null) {
		labelsPromise = fetchGovernanceLabels()
	}
	return labelsPromise
}
