/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { getUserConsent } from '../integration/oauth.js'
import { generateOauthState } from '../service/OauthStateService.js'

/**
 * The admin-configured OIDC providers passed to the page as initial state.
 *
 * @return {object[]}
 */
export function loadOidcProviders() {
	return loadState('mail', 'oidc_providers', [])
}

/**
 * Find the provider whose email domain matches the given address, if any.
 *
 * @param {string} email the email address to match
 * @param {object[]} providers the configured providers
 * @return {?object} the matching provider or null
 */
export function findProviderForEmail(email, providers) {
	const at = (email ?? '').lastIndexOf('@')
	if (at === -1) {
		return null
	}
	const domain = email.slice(at + 1).toLowerCase()
	return providers.find((provider) => (provider.emailDomain || '').toLowerCase() === domain) ?? null
}

/**
 * Open the provider's consent popup for an account, binding a fresh CSRF state.
 * Resolves when the popup reports success and rejects (with CONSENT_ABORTED) when
 * the user closes it.
 *
 * @param {object} provider the matched provider
 * @param {number} accountId the account being (re)connected
 * @return {Promise<void>}
 */
export async function openOidcConsent(provider, accountId) {
	const url = provider.authorizeUrl.replace('_state_', await generateOauthState(accountId))
	await getUserConsent(url)
}
