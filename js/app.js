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
	var AppView = require('views/app');

	// Load controllers/services
	require('controller/accountcontroller');
	require('controller/foldercontroller');
	require('service/accountservice');
	require('service/folderservice');
	require('notification');

	var Mail = new Marionette.Application();

	Mail.view = new AppView();

	return Mail;
});
