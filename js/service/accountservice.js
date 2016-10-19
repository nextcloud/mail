/**
 * Mail
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
	var Radio = require('radio');

	Radio.account.reply('create', createAccount);
	Radio.account.reply('update', editAccount);
	Radio.account.reply('entities', getAccountEntities);

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

	function editAccount(account, config) {
		var defer = $.Deferred();
		var url = OC.generateUrl('apps/mail/accounts/{id}', {
			id: account.get('accountId')
		});
		console.log(url);
		config.accountId=account.get('accountId');
		$.ajax(url, {
			data: config,
			type: 'PUT',
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
						defer.reject(t('mail', 'Error while updating an account: ' + error));
				}
			}
		});

		return defer.promise();
	}

	function getAccountEntities() {
		var defer = $.Deferred();
		var $serialized = $('#serialized-accounts');
		var accounts = require('state').accounts;

		if ($serialized.val() !== '') {
			var serialized = $serialized.val();
			var serialzedAccounts = JSON.parse(atob(serialized));

			accounts.reset();
			for (var i = 0; i < serialzedAccounts.length; i++) {
				accounts.add(serialzedAccounts[i]);
			}
			defer.resolve(accounts);

			$serialized.val('');
		} else {
			accounts.fetch({
				success: function(accounts) {
					require('cache').cleanUp(accounts);
					defer.resolve(accounts);
				},
				error: function() {
					defer.reject();
				}
			});
		}

		return defer.promise();
	}

	return {
		createAccount: createAccount,
		editAccount: editAccount,
		getAccountEntities: getAccountEntities
	};
});
