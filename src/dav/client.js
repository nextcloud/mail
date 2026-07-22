/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import memoize from 'lodash/fp/memoize.js'
import * as webdav from 'webdav'

export const getClient = memoize((service) => {
	// Add this so the server knows it is an request from the browser
	axios.defaults.headers['X-Requested-With'] = 'XMLHttpRequest'

	// force our axios
	const patcher = webdav.getPatcher()
	patcher.patch('request', axios)

	return webdav.createClient(generateRemoteUrl(`dav/${service}/${getCurrentUser().uid}`))
})
