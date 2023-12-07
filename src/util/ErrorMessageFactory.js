/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

import { translate as t } from '@nextcloud/l10n'

const smileys = [':-(', ':-/', ':-\\', ':-|', ":'-(", ":'-/", ":'-\\", ":'-|"]

const getRandomSmiley = () => {
	return smileys[Math.floor(Math.random() * smileys.length)]
}

/**
 * @param {object} folder a folder
 * @return {string}
 */
export const getRandomFolderErrorMessage = (folder) => {
	const folderName = folder.get('name')
	const rawTexts = [
		t('mail', 'Could not load {tag}{name}{endtag}', {
			name: folderName,
		}),
		t('mail', 'Could not load {tag}{name}{endtag}', {
			name: folderName,
		}),
		t('mail', 'There was a problem loading {tag}{name}{endtag}', {
			name: folderName,
		}),
	]
	const texts = rawTexts.map((text) => text.replace('{tag}', '<strong>').replace('{endtag}', '</strong>'))
	const text = texts[Math.floor(Math.random() * texts.length)]
	return text + ' ' + getRandomSmiley()
}

/**
 * @return {string}
 */
export const getRandomMessageErrorMessage = () => {
	const texts = [
		t('mail', 'Could not load your message'),
		t('mail', 'Could not load the desired message'),
		t('mail', 'Could not load the message'),
	]
	const text = texts[Math.floor(Math.random() * texts.length)]
	return text + ' ' + getRandomSmiley()
}
