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

define(function() {
	'use strict';

	var smileys = [
		':-(',
		':-/',
		':-\\',
		':-|',
		':\'-(',
		':\'-/',
		':\'-\\',
		':\'-|'
	];

	function getRandomSmiley() {
		return smileys[Math.floor(Math.random() * smileys.length)]
	}

	/**
	 * @param {Folder} folder
	 * @returns {string}
	 */
	function getRandomFolderErrorMessage(folder) {
		var folderName = folder.get('name');
		var rawTexts = [
			t('mail', 'Could not load {tag}{name}{endtag}', {
				name: folderName
			}),
			t('mail', 'Couldn\x27t load {tag}{name}{endtag}', {
				name: folderName
			}),
			t('mail', 'There was a problem loading {tag}{name}{endtag}', {
				name: folderName
			})
		];
		var texts = _.map(rawTexts, function(text) {
			return text.replace('{tag}', '<strong>').replace('{endtag}', '</strong>');
		});
		var text = texts[Math.floor(Math.random() * texts.length)]
		return text + ' ' + getRandomSmiley();
	}

	/**
	 * @returns {string}
	 */
	function getRandomMessageErrorMessage() {
		var texts = [
			t('mail', 'Couldn\x27t load your message'),
			t('mail', 'Could not load the desired message'),
			t('mail', 'Could not load the message')
		];
		var text = texts[Math.floor(Math.random() * texts.length)]
		return text + ' ' + getRandomSmiley();
	}

	return {
		getRandomFolderErrorMessage: getRandomFolderErrorMessage,
		getRandomMessageErrorMessage: getRandomMessageErrorMessage
	};
});
