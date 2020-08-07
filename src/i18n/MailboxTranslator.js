/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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

const translateSpecial = (folder) => {
	if (folder.specialUse.includes('all')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'All')
	}
	if (folder.specialUse.includes('archive')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Archive')
	}
	if (folder.specialUse.includes('drafts')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Drafts')
	}
	if (folder.specialUse.includes('flagged')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Favorites')
	}
	if (folder.specialUse.includes('inbox')) {
		if (folder.isPriorityInbox) {
			// TRANSLATORS: translated mail box name
			return t('mail', 'Priority inbox')
		} else if (folder.isUnified) {
			// TRANSLATORS: translated mail box name
			return t('mail', 'All inboxes')
		} else {
			// TRANSLATORS: translated mail box name
			return t('mail', 'Inbox')
		}
	}
	if (folder.specialUse.includes('junk')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Junk')
	}
	if (folder.specialUse.includes('sent')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Sent')
	}
	if (folder.specialUse.includes('trash')) {
		// TRANSLATORS: translated mail box name
		return t('mail', 'Trash')
	}
	throw new Error(`unknown special use ${folder.specialUse}`)
}

export const translate = (folder) => {
	if (folder.specialUse.length > 0) {
		try {
			return translateSpecial(folder)
		} catch (e) {
			console.error('could not translate special folder', e)
		}
	}
	return folder.displayName
}
