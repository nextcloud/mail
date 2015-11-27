/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');

	function loadFolder(accountId, activeId) {
		var fetchingFolders = require('app').request('folder:entities', accountId);
		var UI = require('app').UI;

		// TODO: create loading-view
		$('#mail_messages').removeClass('hidden').addClass('icon-loading');
		$('#mail-message').removeClass('hidden').addClass('icon-loading');
		$('#mail_new_message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#setup').addClass('hidden');

		UI.clearMessages();
		$('#app-navigation').addClass('icon-loading');

		$.when(fetchingFolders).done(function(accountFolders) {
			$('#app-navigation').removeClass('icon-loading');
			require('app').State.folderView.collection.add(accountFolders);

			if (accountId === activeId) {
				var folderId = accountFolders.folders[0].id;

				require('app').trigger('folder:load', accountId, folderId, false);

				// Open composer if 'mailto' url-param is set
				// TODO: implement

				// Save current folder
				UI.setFolderActive(accountId, folderId);
				require('app').State.currentAccountId = accountId;
				require('app').State.currentFolderId = folderId;

				// Start fetching messages in background
				require('app').BackGround.messageFetcher.start();
			}
		});
		$.when(fetchingFolders).fail(function() {
			UI.showError(t('mail', 'Error while loading the selected account.'));
		});
	}

	return {
		loadFolder: loadFolder
	};
});
