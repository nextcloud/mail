/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'
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
