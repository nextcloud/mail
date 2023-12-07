/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
