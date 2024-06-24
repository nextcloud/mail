/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

const translateSpecial = (mailbox) => {
	if (mailbox.specialUse.includes('all')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'All')
	}
	if (mailbox.specialUse.includes('archive')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Archive')
	}
	if (mailbox.specialUse.includes('drafts')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Drafts')
	}
	if (mailbox.specialUse.includes('flagged')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Favorites')
	}
	if (mailbox.specialUse.includes('inbox')) {
		if (mailbox.isPriorityInbox) {
			// TRANSLATORS: translated mail box name
			return t('mail', 'Priority inbox')
		} else if (mailbox.isUnified) {
			// TRANSLATORS: translated mail box name
			return t('mail', 'All inboxes')
		} else {
			// TRANSLATORS: translated mail box name
			return t('mail', 'Inbox')
		}
	}
	if (mailbox.specialUse.includes('junk')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Junk')
	}
	if (mailbox.specialUse.includes('sent')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Sent')
	}
	if (mailbox.specialUse.includes('trash')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Trash')
	}
	throw new Error(`unknown special use ${mailbox.specialUse}`)
}

export const translate = (mailbox) => {
	if (mailbox.specialUse.length > 0) {
		try {
			return translateSpecial(mailbox)
		} catch (e) {
			console.error('could not translate special mailbox', e)
		}
	}
	return mailbox.displayName
}
