/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
