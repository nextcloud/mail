/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
 *
 * ownCloud - Mail
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

	Radio.aliases.reply('entities', getAliasesEntities);
	Radio.aliases.reply('save', saveAlias);
	Radio.aliases.reply('delete', deleteAlias);

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function getAliasesEntities(account) {
		var defer = $.Deferred();

		var url = OC.generateUrl('/apps/mail/accounts/{id}/aliases', {
			id: account.get('accountId')
		});
		var data = {
			type: 'GET',
			success: function(data) {
			},
			error: function(xhr) {

			},
			data: {
				accountId: account.get('accountId')
			}
		};
		var promise =  $.ajax(url, data);

		promise.done(function(data) {
			defer.resolve(data);
		});

		promise.fail(function() {
			defer.reject();
		});

		return defer.promise();
	}

	/**
	 * @param {Account} account
	 * @param alias
	 * @returns {undefined}
	 */
	function saveAlias(account, alias) {
		var defer = $.Deferred();

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
		var promise =  $.ajax(url, data);

		promise.done(function(data) {
			defer.resolve(data);
		});

		promise.fail(function() {
			defer.reject();
		});

		return defer.promise();
	}

	/**
	 * @param {Account} account
	 * @param aliasId
	 * @returns {undefined}
	 */
	function deleteAlias(account, aliasId) {
		var defer = $.Deferred();

		var url = OC.generateUrl('/apps/mail/accounts/{id}/aliases/{aliasId}', {
			id: account.get('accountId'),
			aliasId: aliasId
		});
		var data = {
			type: 'DELETE'
		};
		var promise =  $.ajax(url, data);

		promise.done(function(data) {
			defer.resolve();
		});

		promise.fail(function() {
			defer.reject();
		});

		return defer.promise();
	}

});