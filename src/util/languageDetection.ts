/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage } from '@nextcloud/l10n'
import { html, toPlain } from './text.js'

const MIN_LENGTH = 60

/**
 * Load franc and the CLDR alias table, and derive the languages we can act on.
 */
async function load() {
	const [{ franc }, { data }, { expressions }, aliases] = await Promise.all([
		import(/* webpackChunkName: "language-detection" */ 'franc'),
		import(/* webpackChunkName: "language-detection" */ 'franc/data.js'),
		import(/* webpackChunkName: "language-detection" */ 'franc/expressions.js'),
		import(/* webpackChunkName: "language-detection" */ 'cldr-core/supplemental/aliases.json'),
	])

	// franc speaks ISO 639-3 and prefers individual codes over macrolanguages
	// (cmn, not zho), so the aliases double as a filter: a code without one has
	// no 639-1 equivalent and therefore no counterpart in the UI languages.
	const languageAlias = aliases.supplemental.metadata.alias.languageAlias
	const toIso6391 = (code: string) => languageAlias[code]?._replacement.split(/[\s_-]/)[0]
	const detectable = new Set([
		...Object.keys(expressions),
		...Object.values(data).flatMap((script) => Object.keys(script)),
	].map(toIso6391))

	return { detectable, franc, toIso6391 }
}

let detector: ReturnType<typeof load> | undefined

/**
 * Detect the language of a message body, if it differs from the user's own.
 *
 * @param body message body as rendered, i.e. HTML in both body components
 * @return ISO 639-1 code of the detected language, or null when the message is
 *         in the user's language, is too short, or when either language is one
 *         franc can not detect
 */
export async function detectForeignLanguage(body: string): Promise<string | null> {
	const text = toPlain(html(body)).value.replace(/\s+/g, ' ').trim()
	if (text.length < MIN_LENGTH) {
		return null
	}

	detector ??= load()
	const { detectable, franc, toIso6391 } = await detector

	const userLanguage = getLanguage().split(/[-_]/)[0]
	if (!detectable.has(userLanguage)) {
		return null
	}

	const detected = toIso6391(franc(text, { minLength: MIN_LENGTH }))
	return detected && detected !== userLanguage ? detected : null
}
