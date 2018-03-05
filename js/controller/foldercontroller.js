/* global Promise */

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
	var ErrorMessageFactory = require('util/errormessagefactory');

	Radio.folder.reply('message:delete', deleteMessage);

	/**
	 * @param {Account} account
	 * @returns {Promise}
	 */
	function loadFolders(account) {
		return Radio.folder.request('entities', account)
			.catch(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected account.'));
			});
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {string} searchQuery
	 * @param {bool} openFirstMessage
	 * @returns {Promise}
	 */
	function loadFolderMessages(account, folder, searchQuery, openFirstMessage) {
		openFirstMessage = openFirstMessage !== false;

		// Set folder active
		Radio.folder.trigger('setactive', account, folder);

		if (folder.get('noSelect')) {
			Radio.ui.trigger('content:error', t('mail', 'Can not load this folder.'));
			require('state').currentAccount = account;
			require('state').currentFolder = folder;
			Radio.ui.trigger('messagesview:message:setactive', null);
			require('state').currentlyLoading = null;
			return Promise.resolve();
		} else {
			return Radio.message.request('entities', account, folder, {
				cache: true,
				filter: searchQuery,
				replace: true
			}).then(function(messages) {
				Radio.ui.trigger('foldercontent:show', account, folder, {
					searchQuery: searchQuery
				});
				require('state').currentlyLoading = null;
				require('state').currentAccount = account;
				require('state').currentFolder = folder;
				Radio.ui.trigger('messagesview:message:setactive', null);

				// Fade out the message composer
				$('#mail_new_message').prop('disabled', false);

				if (messages.length > 0) {
					if (openFirstMessage) {
						var message = messages.first();
						Radio.message.trigger('load', message.folder.account, message.folder, message);
					}
				}
			}, function(error) {
				console.error('error while loading messages: ', error);
				var icon;
				if (folder.get('specialRole')) {
					icon = 'icon-' + folder.get('specialRole');
				}
				Radio.ui.trigger('content:error', ErrorMessageFactory.getRandomFolderErrorMessage(folder), icon);

				// Set the old folder as being active
				var oldFolder = require('state').currentFolder;
				Radio.folder.trigger('setactive', account, oldFolder);
			}).catch(console.error.bind(this));
		}
	}

	var loadFolderMessagesDebounced = _.debounce(loadFolderMessages, 1000);

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {bool} loadFirstMessage
	 * @returns {Promise}
	 */
	function showFolder(account, folder, loadFirstMessage) {
		loadFirstMessage = loadFirstMessage !== false;
		Radio.ui.trigger('search:set', '');
		Radio.ui.trigger('content:loading', folder.get('name'));

		return new Promise(function(resolve, reject) {
			_.defer(function() {
				var loading = loadFolderMessages(account, folder, undefined, loadFirstMessage);

				// Save current folder
				Radio.folder.trigger('setactive', account, folder);
				require('state').currentAccount = account;
				require('state').currentFolder = folder;

				loading.then(resolve).catch(reject);
			});
		});
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

		Radio.ui.trigger('content:loading', t('mail', 'Searching for {query}', {
			query: query
		}));
		_.defer(function() {
			loadFolderMessagesDebounced(account, folder, query);
		});
	}

	/**
	 * @param {Folder} folder
	 * @param {Folder} currentFolder
	 * @returns {Array} array of two folders, the first one is the individual
	 */
	function getSpecificAndUnifiedFolder(folder, currentFolder) {
		// Case 1: we're currently in a unified folder
		if (currentFolder.account.get('accountId') === -1) {
			return [folder, currentFolder];
		}

		// Locate unified folder if existent
		var unifiedAccount = require('state').accounts.get(-1);
		var unifiedFolder = unifiedAccount ? unifiedAccount.folders.first() : null;

		// Case 2: we're in a specific folder and a unified one is available too
		if (currentFolder.get('specialRole') === 'inbox' && unifiedFolder) {
			return [folder, unifiedFolder];
		}

		// Case 3: we're in a specific folder, but there's no unified one
		return [folder, null];
	}

	/**
	 * Call supplied function with folder as first parameter, if
	 * the folder is not undefined
	 *
	 * @param {Array<Folder>} folders
	 * @param {Function} fn
	 * @returns {mixed}
	 */
	function applyOnFolders(folders, fn) {
		folders.forEach(function(folder) {
			if (!folder) {
				// ignore
				return;
			}

			return fn(folder);
		});
	}

	/**
	 * @param {Message} message
	 * @param {Folder} currentFolder
	 * @returns {Promise}
	 */
	function deleteMessage(message, currentFolder) {
		var folders = getSpecificAndUnifiedFolder(message.folder, currentFolder);

		applyOnFolders(folders, function(folder) {
			// Update total counter and prevent negative values
			folder.set('total', Math.max(0, folder.get('total')-1));

			if(message.get('flags').get('unseen') === true) {
				folder.set('unseen', Math.max(0, folder.get('unseen')-1));
			}
		});
		
		var searchCollection = currentFolder.messages;
		var index = searchCollection.indexOf(message);
		// Select previous or first
		if (index === 0) {
			index = 1;
		} else {
			index = index - 1;
		}
		var nextMessage = searchCollection.at(index);

		// Remove message
		applyOnFolders(folders, function(folder) {
			folder.messages.remove(message);
		});

		if (require('state').currentMessage && require('state').currentMessage.get('id') === message.id) {
			if (nextMessage) {
				Radio.message.trigger('load', nextMessage.folder.account, nextMessage.folder, nextMessage);
			}
		}

		return Radio.message.request('delete', message)
			.catch(function(err) {
				console.error(err);

				Radio.ui.trigger('error:show', t('mail', 'Error while deleting message.'));

				applyOnFolders(folders, function(folder) {
					// Restore counter

					folder.set('total', folder.previousAttributes.total);

					// Add the message to the collection again
					folder.addMessage(message);
				});
			});
	}

	return {
		loadAccountFolders: loadFolders,
		showFolder: showFolder,
		searchFolder: searchFolder
	};
});
