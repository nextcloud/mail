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

	var Radio = require('backbone.radio');

	var uiChannel = Radio.channel('ui');
	var notificationChannel = Radio.channel('notification');
	var stateChannel = Radio.channel('state');
	var accountChannel = Radio.channel('account');
	var folderChannel = Radio.channel('folder');
	var messageChannel = Radio.channel('message');
	var navigationChannel = Radio.channel('navigation');

	var channels = {
		ui: uiChannel,
		notification: notificationChannel,
		state: stateChannel,
		account: accountChannel,
		folder: folderChannel,
		message: messageChannel,
		navigation: navigationChannel
	};

	// Log all events to the console
	for (var channelName in channels) {
		Radio.tuneIn(channelName);
	}

	return channels;
});

