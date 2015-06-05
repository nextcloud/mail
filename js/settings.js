/* global Mail */
$(document).ready(function() {

	// delete account in settings
	$(document).on('click', '.mailaccount-list .action.delete', function() {

		$(this).removeClass('icon-delete').addClass('icon-loading-small');

		var accountId = $(this).parent().parent().data('account-id');

		$.ajax(OC.generateUrl('/apps/mail/accounts/{accountId}'), {
			data: {accountId:accountId},
			type: 'DELETE',
			success: function() {
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
		Mail.UI.addAccount();
	});

});
