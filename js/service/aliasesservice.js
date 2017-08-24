/* global Promise */

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
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

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.aliases.reply('save', saveAlias);
	Radio.aliases.reply('delete', deleteAlias);

	/**
	 * @param {Account} account
	 * @param alias
	 * @returns {Promise}
	 */
	function saveAlias(account, alias) {
		var url = OC.generateUrl('/apps/mail/accounts/{id}/aliases', {
			id: account.get('accountId')
		});
		var data = {
			type: 'POST',
			data: {
				accountId: account.get('accountId'),
				alias: alias.alias,
				aliasName: alias.name
			}
		};
		return Promise.resolve($.ajax(url, data));
	}

	/**
	 * @param {Account} account
	 * @param aliasId
	 * @returns {Promise}
	 */
	function deleteAlias(account, aliasId) {
		var url = OC.generateUrl('/apps/mail/accounts/{id}/aliases/{aliasId}', {
			id: account.get('accountId'),
			aliasId: aliasId
		});
		var data = {
			type: 'DELETE'
		};
		return Promise.resolve($.ajax(url, data));
	}

});