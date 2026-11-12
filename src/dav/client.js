/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { getClient as getDavClient } from '@nextcloud/files/dav'
import { generateRemoteUrl } from '@nextcloud/router'
import memoize from 'lodash/fp/memoize.js'

export const getClient = memoize((service) => getDavClient(generateRemoteUrl(`dav/${service}/${getCurrentUser().uid}`)))
