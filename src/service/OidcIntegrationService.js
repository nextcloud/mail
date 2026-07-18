/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function getOidcProviders() {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers')
	return axios.get(url).then((resp) => resp.data)
}

export function createOidcProvider(provider) {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers')
	return axios.post(url, { data: provider }).then((resp) => resp.data)
}

export function updateOidcProvider(provider) {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers/{id}', {
		id: provider.id,
	})
	return axios.post(url, { data: provider }).then((resp) => resp.data)
}

export function deleteOidcProvider(id) {
	const url = generateUrl('/apps/mail/api/integration/oidc/providers/{id}', {
		id,
	})
	return axios.delete(url).then((resp) => resp.data)
}
