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

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');
	var Radio = require('radio');
	var FolderService = require('service/folderservice');

	Radio.message.on('fetch:bodies', fetchBodies);

	/**
	 * @param {Account} account
	 * @returns {undefined}
	 */
	function loadFolders(account) {
		var fetchingFolders = FolderService.getFolderEntities(account);

		$.when(fetchingFolders).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected account.'));
		});

		return fetchingFolders.promise();
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {boolean} noSelect
	 * @returns {undefined}
	 */
	function loadFolderMessages(account, folder, noSelect, searchQuery) {
		Radio.ui.trigger('composer:leave');

		// Set folder active
		Radio.folder.trigger('setactive', account, folder);

		$('#load-more-mail-messages').hide();

		if (noSelect) {
			$('#emptycontent').show();
			require('state').currentAccount = account;
			require('state').currentFolder = folder;
			Radio.ui.trigger('messagesview:message:setactive', null);
			require('state').currentlyLoading = null;
		} else {
			var loadingMessages = Radio.message.request('entities', account, folder, {
				filter: searchQuery,
				cache: true
			});

			$.when(loadingMessages).done(function(messages, cached) {
				Radio.ui.trigger('foldercontent:show', account, folder, {
					searchQuery: searchQuery
				});
				require('state').currentlyLoading = null;
				require('state').currentAccount = account;
				require('state').currentFolder = folder;
				Radio.ui.trigger('messagesview:message:setactive', null);

				// Fade out the message composer
				$('#mail_new_message').prop('disabled', false);

				Radio.ui.trigger('messagesview:messages:add', messages);

				if (messages.length > 0) {
					// Fetch first 10 messages in background
					// Radio.message.trigger('fetch:bodies', account, folder, messages.slice(0, 10));

					Radio.message.trigger('load', account, folder, messages.first());

					$('#load-more-mail-messages')
						.fadeIn()
						.css('display', 'block');
				}

				if (cached) {
					// Trigger folder update
					// TODO: replace with horde sync once it's implemented
					Radio.ui.trigger('messagesview:messages:update');
				}
			});

			$.when(loadingMessages).fail(function() {
				// Set the old folder as being active
				var folder = require('state').currentFolder;
				Radio.folder.trigger('setactive', account, folder);
				Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
			});
		}
	}

	var loadFolderMessagesDebounced = _.debounce(loadFolderMessages, 1000);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function showFolder(account, folder) {
		Radio.ui.trigger('search:set', '');
		Radio.ui.trigger('content:loading');
		loadFolderMessages(account, folder, false);

		// Save current folder
		Radio.folder.trigger('setactive', account, folder);
		require('state').currentAccount = account;
		require('state').currentFolder = folder;
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {string} query
	 * @returns {Promise}
	 */
	function searchFolder(account, folder, query) {
		// If this was triggered by a URL change, we set the search input manually
		Radio.ui.trigger('search:set', query);

		Radio.ui.trigger('composer:leave');
		Radio.ui.trigger('content:loading', t('mail', 'Searching for {query}', {
			query: query
		}));
		loadFolderMessagesDebounced(account, folder, false, query);
	}

	/**
	 * Fetch and cache messages in the background
	 *
	 * The message is only fetched if it has not been cached already
	 *
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {Message[]} messages
	 * @returns {undefined}
	 */
	function fetchBodies(account, folder, messages) {
		if (messages.length > 0) {
			Radio.message.request('bodies', account, folder, messages);
		}
	}

	return {
		loadAccountFolders: loadFolders,
		showFolder: showFolder,
		searchFolder: searchFolder
	};
});
