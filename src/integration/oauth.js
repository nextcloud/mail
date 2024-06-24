/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

import logger from '../logger.js'

export const CONSENT_ABORTED = 'OAUTH_CONSENT_ABORTED'

export async function getUserConsent(redirectUrl) {
	const ssoWindow = window.open(
		redirectUrl,
		t('mail', 'Connect OAUTH2 account'),
		'toolbar=no, menubar=no, width=600, height=700',
	)
	ssoWindow.focus()
	await new Promise((resolve, reject) => {
		window.addEventListener('message', (event) => {
			const { data } = event
			logger.debug('Child window message received', { event })

			if (data === 'DONE') {
				logger.info('OAUTH2 user consent given')
				resolve()
			}
		})
		const windowClosedTimer = setInterval(() => {
			if (ssoWindow.closed) {
				clearInterval(windowClosedTimer)
				reject(new Error(CONSENT_ABORTED))
			}
		}, 200)
	})
}
