/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import { translate as t } from '@nextcloud/l10n'

import logger from '../logger'

export async function getUserConsent(redirectUrl) {
	const ssoWindow = window.open(
		redirectUrl,
		t('mail', 'Sign in with Google'),
		'toolbar=no, menubar=no, width=600, height=700'
	)
	ssoWindow.focus()
	await new Promise((res) => {
		window.addEventListener('message', (event) => {
			const { data } = event
			logger.debug('Child window message received', { event })

			if (data === 'DONE') {
				logger.info('Google user consent given')
				res()
			}
		})
	})
}
