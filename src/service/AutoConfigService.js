/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function queryIspdb(host, email) {
	return (await axios.get(generateUrl('/apps/mail/api/autoconfig/ispdb/{host}/{email}', { host, email }))).data.data
}

export async function queryMx(email) {
	return (await axios.get(generateUrl('/apps/mail/api/autoconfig/mx/{email}', { email }))).data.data
}

export async function testConnectivity(host, port) {
	return (await axios.get(generateUrl('/apps/mail/api/autoconfig/test?host={host}&port={port}', { host, port }))).data.data
}
