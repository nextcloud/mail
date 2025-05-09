/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

export async function handleHttpAuthErrors(cb) {
	const mainStore = useMainStore()

	try {
		return await cb()
	} catch (error) {
		logger.debug('req err', { error, status: error.response?.status, message: error.response?.data?.message })
		if (error.response?.status === 401 && error.response?.data?.message === 'Current user is not logged in') {
			logger.warn('Request failed due to expired session')
			mainStore.setSessionExpiredMutation()
		}
		throw error
	}
}
