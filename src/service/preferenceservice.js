/* global Promise */

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.preference.reply('save', savePreference);
	Radio.preference.reply('get', savePreference);

	function savePreference(key, value) {
		var url = OC.generateUrl('/apps/mail/api/preferences/{key}', {
			key: key
		});

		return new Promise(function(resolve, reject) {
			return $.ajax(url, {
				method: 'PUT',
				data: {
					key: key,
					value: value
				},
				success: resolve,
				error: reject
			});
		}).then(function(data) {
			return data.value;
		});
	}

	function getPreference(key) {
		var url = OC.generateUrl('/apps/mail/api/preferences/{key}', {
			key: key
		});

		return new Promise(function(resolve, reject) {
			return $.ajax(url, {
				success: resolve,
				error: reject
			});
		}).then(function(data) {
			return data.value;
		});
	}

	return {
		savePreference: savePreference,
		getPreference: getPreference
	};
});
