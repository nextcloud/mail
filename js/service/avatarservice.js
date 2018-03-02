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

	var _ = require('underscore');
	var fetch = require('nextcloud_fetch');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.avatar.reply('avatar', _.memoize(loadAvatar));

	/**
	 * @param {string} email
	 * @returns {Promise}
	 */
	function loadAvatar(email) {
		var url = OC.generateUrl('/apps/mail/api/avatars/url/{email}', {
			email: email
		});

		return fetch(url)
				.then(function(resp) {
					if (resp.ok) {
						return resp.json();
					}
					throw resp;
				})
				.then(function(avatar) {
					if (avatar.isExternal) {
						return OC.generateUrl('/apps/mail/api/avatars/image/{email}', {
							email: email
						});
					} else {
						return avatar.url;
					}
				}.bind(this))
				.catch(function(e) {
					if (e.status && e.status === 404) {
						return Promise.resolve(undefined);
					}
					return Promise.reject(e);
				});
	}

	return {
		loadAvatar: loadAvatar
	};

});
