/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var AppView = require('views/app');
	var Radio = require('radio');

	// Load controllers/services
	require('controller/accountcontroller');
	require('controller/foldercontroller');
	require('service/accountservice');
	require('service/folderservice');
	require('notification');

	var Mail = new Marionette.Application();

	Mail.on('start', function() {
		Radio.account.trigger('load');
	});

	Mail.view = new AppView();

	return Mail;
});
