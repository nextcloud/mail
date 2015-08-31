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

	var Marionette = require('marionette');

	var Mail = new Marionette.Application();

	/*
	 * Set up modules
	 */
	Mail.BackGround = require('background');
	Mail.Communication = require('communication');
	Mail.Cache = require('cache');
	Mail.Search = require('search');
	Mail.State = require('state');
	Mail.UI = require('ui');

	/*
	 * Set up event handler
	 */
	// Account
	Mail.on('accounts:load', function() {
		Mail.UI.loadAccounts();
	});
	// Folder
	Mail.on('folder:load', function(accountId, folderId, noSelect) {
		Mail.UI.loadFolder(accountId, folderId, noSelect);
	});
	// Message
	Mail.on('message:load', function(accountId, folderId, messageId, options) {
		//FIXME: don't rely on global state vars
		Mail.UI.loadMessage(messageId, options);
	});

	return Mail;
});
