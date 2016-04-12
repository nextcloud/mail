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

	/**
	 * @param {Account} account
	 * @param {Account} active
	 * @returns {undefined}
	 */
	function loadFolder(account, active) {
		var fetchingFolders = FolderService.getFolderEntities(account);

		Radio.ui.trigger('messagesview:messages:reset');
		$('#app-navigation').addClass('icon-loading');

		$.when(fetchingFolders).done(function(accountFolders) {
			if (account === active) {
				var folder = accountFolders.at(0);

				Radio.ui.trigger('folder:show', account, folder, false);

				// Open composer if 'mailto' url-param is set
				handleMailTo();

				// Save current folder
				Radio.folder.trigger('setactive', account, folder);
				require('state').currentAccount = account;
				require('state').currentFolder = folder;
			}
		});
		$.when(fetchingFolders).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected account.'));
		});

		return fetchingFolders.promise();
	}

	return {
		loadFolder: loadFolder
	};
});
