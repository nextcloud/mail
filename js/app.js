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
	var AccountController = require('controller/accountcontroller');
	var FolderController = require('controller/foldercontroller');
	var AccountService = require('service/accountservice');
	var FolderService = require('service/folderservice');
	var AppView = require('views/app');
	var SettingsView = require('views/settings');

	require('notification');

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

	/**
	 * Set up view
	 */
	Mail.view = new AppView();

	/*
	 * Set up event handler
	 */
	Mail.on('accounts:load', function() {
		Mail.Controller.accountController.loadAccounts();
	});
	Mail.on('folder:init', function(accountId, activeId) {
		Mail.Controller.folderController.loadFolder(accountId, activeId);
	});
	Mail.on('folder:load', function(accountId, folderId, noSelect) {
		Mail.UI.loadFolder(accountId, folderId, noSelect);
	});
	Mail.on('message:load', function(accountId, folderId, messageId, options) {
		//FIXME: don't rely on global state vars
		Mail.UI.loadMessage(messageId, options);
	});

	/**
	 * Set up controllers
	 */
	Mail.Controller = {};
	Mail.Controller.accountController = AccountController;
	Mail.Controller.folderController = FolderController;

	/**
	 * Set up services
	 */
	Mail.Service = {};
	Mail.Service.accountService = AccountService;
	Mail.Service.folderService = FolderService;

	/*
	 * Set up request/response handler
	 */
	Mail.reqres.setHandler('account:entities', function() {
		return Mail.Service.accountService.getAccountEntities();
	});
	Mail.reqres.setHandler('folder:entities', function(accountId) {
		return Mail.Service.folderService.getFolderEntities(accountId);
	});

	Mail.on('before:start', function() {
		// Render settings menu
		Mail.view.settings.show(new SettingsView({
			accounts: Mail.State.accounts
		}));
	});

	return Mail;
});
