/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import logger from '../logger.js'

let mailvelope

const loadMailvelopeStatically = () => window.mailvelope

const loadMailvelopeDynamically = () =>
	new Promise((resolve) => {
		window.addEventListener('mailvelope', () => resolve(window.mailvelope), false)
	})

export const getMailvelope = async () => {
	if (mailvelope) {
		return mailvelope
	}

	mailvelope = loadMailvelopeStatically()
	if (mailvelope) {
		logger.debug('mailvelope found statically')
		return mailvelope
	}

	logger.debug('loading mailvelope dynamically')
	mailvelope = await loadMailvelopeDynamically()
	logger.debug('mailvelope found dynamically')
	return mailvelope
}
