/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
	var _ = require('underscore');
	var Radio = require('radio');
	var FolderService = require('service/folderservice');

	/**
	 * @param {Account} account
	 * @returns {undefined}
	 */
	function loadFolders(account) {
		var fetchingFolders = FolderService.getFolderEntities(account);

		Radio.ui.trigger('messagesview:messages:reset');
		$('#app-navigation').addClass('icon-loading');

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
	function loadFolderMessages(account, folder, noSelect) {
		Radio.ui.trigger('composer:leave');

		if (require('state').messagesLoading !== null) {
			require('state').messagesLoading.abort();
		}
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// Set folder active
		Radio.folder.trigger('setactive', account, folder);
		Radio.ui.trigger('content:loading');
		Radio.ui.trigger('messagesview:messages:reset');

		$('#load-new-mail-messages').hide();
		$('#load-more-mail-messages').hide();

		if (noSelect) {
			$('#emptycontent').show();
			require('state').currentAccount = account;
			require('state').currentFolder = folder;
			Radio.ui.trigger('messagesview:message:setactive', null);
			require('state').currentlyLoading = null;
		} else {
			var loadingMessages = Radio.message.request('entities', account, folder, {
				cache: true
			});

			$.when(loadingMessages).done(function(messages, cached) {
				Radio.ui.trigger('foldercontent:show', account, folder);
				require('state').currentlyLoading = null;
				require('state').currentAccount = account;
				require('state').currentFolder = folder;
				Radio.ui.trigger('messagesview:message:setactive', null);

				// Fade out the message composer
				$('#mail_new_message').prop('disabled', false);

				if (messages.length > 0) {
					Radio.ui.trigger('messagesview:messages:add', messages);

					// Fetch first 10 messages in background
					_.each(messages.slice(0, 10), function(
						message) {
						require('background').messageFetcher.push(message.get('id'));
					});

					Radio.message.trigger('load', account, folder, messages.first());
					// Show 'Load More' button if there are
					// more messages than the pagination limit
					if (messages.length > 20) {
						$('#load-more-mail-messages')
							.fadeIn()
							.css('display', 'block');
					}
				} else {
					$('#emptycontent').show();
				}
				$('#load-new-mail-messages')
					.fadeIn()
					.css('display', 'block')
					.prop('disabled', false);

				if (cached) {
					// Trigger folder update
					// TODO: replace with horde sync once it's implemented
					Radio.ui.trigger('messagesview:messages:update');
				}
			});

			$.when(loadingMessages).fail(function(error) {
				// Set the old folder as being active
				var folder = require('state').currentFolder;
				Radio.folder.trigger('setactive', account, folder);
				Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
			});
		}
	}

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @returns {Promise}
	 */
	function showFolder(account, folder) {
		loadFolderMessages(account, folder, false);

		// Save current folder
		Radio.folder.trigger('setactive', account, folder);
		require('state').currentAccount = account;
		require('state').currentFolder = folder;
	}

	return {
		loadFolder: loadFolders,
		showFolder: showFolder
	};
});
