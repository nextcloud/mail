/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var Radio = require('backbone.radio');

	var channelNames = [
		'account',
		'aliases',
		'attachment',
		'folder',
		'dav',
		'keyboard',
		'message',
		'navigation',
		'notification',
		'preference',
		'state',
		'sync',
		'ui'
	];

	var channels = {};
	_.each(channelNames, function(channelName) {
		channels[channelName] = Radio.channel(channelName);
		// Uncomment the following line for debugging
		// Radio.tuneIn(channelName);
	});

	return channels;
});
