/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var Radio = require('radio');
	var lastQuery = '';

	function filter(query) {
		if (query !== lastQuery) {
			lastQuery = query;

			if (require('state').currentAccount && require('state').currentFolder) {
				var accountId = require('state').currentAccount.get('accountId');
				var folderId = require('state').currentFolder.get('id');
				Radio.navigation.trigger('search', accountId, folderId, query);
			}
		}
	}

	return {
		filter: filter
	};
});
