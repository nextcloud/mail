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

	var Mail = {};

	Mail.BackGround = require('background');
	Mail.Communication = require('communication');
	Mail.Cache = require('cache');
	Mail.Search = require('search');
	Mail.State = require('state');
	Mail.UI = require('ui');

	return Mail;
});
