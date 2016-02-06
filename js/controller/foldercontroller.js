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
	var _ = require('underscore');
	var Radio = require('radio');

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
		require('ui').openComposer(composerOptions);
	}

	function loadFolder(accountId, activeId) {
		var fetchingFolders = Radio.folder.request('entities', accountId);
		var UI = require('ui');

		// TODO: create loading-view
		$('#mail-messages').removeClass('hidden').addClass('icon-loading');
		$('#mail-message').removeClass('hidden').addClass('icon-loading');
		$('#mail_new_message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#setup').addClass('hidden');

		UI.clearMessages();
		$('#app-navigation').addClass('icon-loading');

		$.when(fetchingFolders).done(function(accountFolders) {
			$('#app-navigation').removeClass('icon-loading');
			require('state').folderView.collection.add(accountFolders);

			if (accountId === activeId) {
				var folderId = accountFolders.folders[0].id;

				Radio.ui.trigger('folder:load', accountId, folderId, false);

				// Open composer if 'mailto' url-param is set
				handleMailTo();

				// Save current folder
				UI.setFolderActive(accountId, folderId);
				require('state').currentAccountId = accountId;
				require('state').currentFolderId = folderId;

				// Start fetching messages in background
				require('background').messageFetcher.start();
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
