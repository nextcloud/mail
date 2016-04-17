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

	Radio.message.on('load', function(account, folder, messageId, options) {
		//FIXME: don't rely on global state vars
		load(account, messageId, options);
	});

	/**
	 * @param {Account} account
	 * @param {number} messageId
	 * @param {object} options
	 * @returns {undefined}
	 */
	function load(account, messageId, options) {
		options = options || {};
		var defaultOptions = {
			force: false
		};
		_.defaults(options, defaultOptions);

		// Do not reload email when clicking same again
		if (require('state').currentMessageId === messageId) {
			return;
		}

		Radio.ui.trigger('composer:leave');

		if (!options.force && require('ui').isComposerVisible()) {
			return;
		}
		// Abort previous loading requests
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// check if message is a draft
		var draftsFolder = account.get('specialFolders').drafts;
		var draft = draftsFolder === require('state').currentFolder.get('id');

		// close email first
		// Check if message is open
		if (require('state').currentMessageId !== null) {
			var lastMessageId = require('state').currentMessageId;
			Radio.ui.trigger('messagesview:message:setactive', null);
			if (lastMessageId === messageId) {
				return;
			}
		}

		Radio.ui.trigger('message:loading');

		// Set current Message as active
		Radio.ui.trigger('messagesview:message:setactive', messageId);
		require('state').currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		var fetchingMessage = Radio.message.request('entity',
			require('state').currentAccount,
			require('state').currentFolder,
			messageId);

		$.when(fetchingMessage).done(function(message) {
			if (draft) {
				Radio.ui.trigger('composer:show', message);
			} else {
				// TODO: ideally this should be handled in messageservice.js
				require('cache').addMessage(require('state').currentAccount,
					require('state').currentFolder,
					message);
				Radio.ui.trigger('message:show', message);
			}
		});
		$.when(fetchingMessage).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Error while loading the selected message.'));
		});
	}
});