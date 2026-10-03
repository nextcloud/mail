/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function getOidcProviders() {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers')
	return (await axios.get(url)).data
}

export async function createOidcProvider(provider) {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers')
	return (await axios.post(url, { data: provider })).data
}

export async function updateOidcProvider(provider) {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers/{id}', {
		id: provider.id,
	})
	return (await axios.put(url, { data: provider })).data
}

export async function deleteOidcProvider(id) {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers/{id}', {
		id,
	})
	return (await axios.delete(url)).data
}
