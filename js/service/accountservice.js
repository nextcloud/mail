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
	var OC = require('OC');

	function createAccount(config) {
		var defer = $.Deferred();

		$.ajax(OC.generateUrl('apps/mail/accounts'), {
			data: config,
			type: 'POST',
			success: function() {
				defer.resolve();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				switch (jqXHR.status) {
					case 400:
						var response = JSON.parse(jqXHR.responseText);
						defer.reject(response.message);
						break;
					default:
						var error = errorThrown || textStatus || t('mail', 'Unknown error');
						defer.reject(t('mail', 'Error while creating an account: ' + error));
				}
			}
		});

		return defer.promise();
	}

	function getAccountEntities() {
		var defer = $.Deferred();

		require('app').State.accounts.fetch({
			success: function(accounts) {
				require('app').Cache.cleanUp(accounts);
				defer.resolve(accounts);
			},
			error: function() {
				defer.reject();
			}
		});

		return defer.promise();
	}

	return {
		createAccount: createAccount,
		getAccountEntities: getAccountEntities
	};
});
