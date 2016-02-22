/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');
	var Radio = require('radio');
	var FolderService = require('service/folderservice');

	Radio.folder.on('init', loadFolder);

	function urldecode(str) {
		return decodeURIComponent((str + '').replace(/\+/g, '%20'));
	}

	/**
	 * Handle mailto links
	 *
	 * @returns {undefined}
	 */
	function handleMailTo() {
		var hash = window.location.hash;
		if (hash === '' || hash === '#') {
			// Nothing to do
			return;
		}

		// Remove leading #
		hash = hash.substr(1);

		var composerOptions = {};
		var params = hash.split('&');

		_.each(params, function(param) {
			param = param.split('=');
			var key = param[0];
			var value = urldecode(param[1]);

			switch (key) {
				case 'mailto':
				case 'to':
					composerOptions.to = value;
					break;
				case 'cc':
					composerOptions.cc = value;
					break;
				case 'bcc':
					composerOptions.bcc = value;
					break;
				case 'subject':
					composerOptions.subject = value;
					break;
				case 'body':
					composerOptions.body = value;
					break;
			}
		});

		window.location.hash = '';
		Radio.ui.trigger('composer:show', composerOptions);
	}

	function loadFolder(account, active) {
		var fetchingFolders = FolderService.getFolderEntities(account);

		// TODO: create loading-view
		$('#mail-messages').addClass('icon-loading');
		$('#mail-message').addClass('icon-loading');
		$('#mail_new_message').removeClass('hidden');

		Radio.ui.trigger('messagesview:messages:reset');
		$('#app-navigation').addClass('icon-loading');

		$.when(fetchingFolders).done(function(accountFolders) {
			$('#app-navigation').removeClass('icon-loading');
			require('state').folderView.collection.add(accountFolders);

			if (account === active) {
				var folderId = accountFolders.folders[0].id;

				Radio.ui.trigger('folder:show', account, folderId, false);

				// Open composer if 'mailto' url-param is set
				handleMailTo();

				// Save current folder
				Radio.folder.trigger('setactive', account, folderId);
				require('state').currentAccount = account;
				require('state').currentFolderId = folderId;

				// Start fetching messages in background
				require('background').messageFetcher.start();
			}
		});
		$.when(fetchingFolders).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected account.'));
		});
	}

	return {
		loadFolder: loadFolder
	};
});
