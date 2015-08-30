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

	// delete account in settings
	$(document).on('click', '.mailaccount-list .action.delete', function() {

		$(this).removeClass('icon-delete').addClass('icon-loading-small');

		var accountId = $(this).parent().parent().data('account-id');

		$.ajax(OC.generateUrl('/apps/mail/accounts/{accountId}'), {
			data: {accountId: accountId},
			type: 'DELETE',
			success: function() {
				// Delete cached message lists
				require('app').Cache.removeAccount(accountId);

				// reload the complete page
				// TODO should only reload the app nav/content
				window.location.reload();
			},
			error: function() {
				OC.Notification.show(t('mail', 'Error while deleting account.'));
			}

		});
	});
	$(document).on('click', '#new_mail_account', function() {
		require('app').UI.addAccount();
	});

});
