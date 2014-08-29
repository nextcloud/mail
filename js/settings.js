$(document).ready(function(){

    // Add the settings page
    $.ajax(OC.generateUrl('apps/mail/settings'), {
        data:{},
        type:'GET',
        success:function (jsondata) {
                var source   = $("#mail-settings-template").html();
                var template = Handlebars.compile(source);
                var html = template(jsondata);
                $('#app-settings-content').html(html);
        }
    });

    // delete account in settings
	$(document).on('click', '.mailaccount-list .action.delete', function () {

        $(this).removeClass('icon-delete').addClass('icon-loading-small');

		var accountId = $(this).parent().parent().data('account-id');

		$.ajax(OC.generateUrl('/apps/mail/accounts/{accountId}'), {
			data:{accountId:accountId},
			type: 'DELETE',
			success:function (accountId) {
                //reload the complate page
                // TOOD should only reload the app nav/content
                window.location.reload();
			},
            error: function() {
			    OC.Notification.show(t('mail', 'Error while deleting account.'));
			}

		});


	});

    $(document).on('click', '#new_mail_account', function () {
        Mail.State.router.navigate('accounts/new', {trigger: true});
    });

});
