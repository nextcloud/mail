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
	var _ = require('underscore');
	var Radio = require('radio');

	Radio.aliases.reply('load:alias', loadAliases);
	Radio.aliases.reply('save:alias', saveAlias);
	Radio.aliases.reply('delete:alias', deleteAlias);

	/**
	 * @param {Account} account
	 * @returns {undefined}
	 */
	function loadAliases(account) {
		var fetchingAliases = Radio.aliases.request('entities');

		$.when(fetchingAliases).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Fetching Aliases Failed.'));
		});

		return fetchingAliases;
	}

	/**
	 * @param {Account} account
	 * @param alias
	 * @returns {undefined}
	 */
	function saveAlias(account, alias) {
		var savingAliases = Radio.aliases.request('save', account, alias);

		$.when(savingAliases).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Saving Aliases Failed.'));
		});

		return savingAliases;
	}

	/**
	 * @param {Account} account
	 * @param aliasId
	 * @returns {undefined}
	 */
	function deleteAlias(account, aliasId) {
		var deletingAliases = Radio.aliases.request('delete', account, aliasId);

		$.when(deletingAliases).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Deleting Aliases Failed.'));
		});

		return deletingAliases;
	}

});
