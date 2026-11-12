/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { hiddenTags } from '../components/tags.js'
import { FOLLOW_UP_TAG_LABEL } from '../store/constants.js'

/**
 * Translate the display name of special tags or leave them as is if the user renamed them.
 *
 * @param {{displayName: string, imapLabel: string}} tag The original display name.
 * @return {string} The translated or original display name.
 */
export function translateTagDisplayName(tag) {
	if (tag.imapLabel === FOLLOW_UP_TAG_LABEL && tag.displayName === 'Follow up') {
		return t('mail', 'Follow up')
	}

	return tag.displayName
}

/**
 * @param {string} color The tag color as hex string.
 * @param {boolean} isDarkTheme Whether the dark theme is active.
 * @return {string} A text color readable on the current theme.
 */
export function tagTextColor(color, isDarkTheme) {
	return `color-mix(in srgb, ${color} 40%, ${isDarkTheme ? 'white' : 'black'})`
}

/**
 * @param {string} color The tag color as hex string.
 * @return {string} A background color for the tag text color.
 */
export function tagBackgroundColor(color) {
	return `color-mix(in srgb, ${color} 20%, var(--color-background-dark))`
}

export function validateTag(tagId, tagName, otherTags) {
	const testableDisplayName = tagName.toLowerCase().trim()

	if (testableDisplayName === '') {
		return t('mail', 'Tag name cannot be empty')
	}

	if (Object.keys(hiddenTags).some((tag) => tag.toLowerCase() === testableDisplayName)) {
		return t('mail', 'Tag name is a hidden system tag')
	}

	if (otherTags.some((tag) => tagId !== tag.id && tag.displayName.toLowerCase() === testableDisplayName)) {
		return t('mail', 'Tag already exists')
	}

	return true
}
