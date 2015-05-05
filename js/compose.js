/* global Handlebars, views */
var Mail = {
	State:{
		accounts: null
	},
	UI:{
		initializeInterface:function () {

			$.ajax(OC.generateUrl('apps/mail/accounts'), {
				data:{},
				type:'GET',
				success:function (jsondata) {
					Mail.State.accounts = jsondata;

					// don't try to load accounts if there are none
					if(jsondata.length === 0) {
						return;
					}
					// only show account switcher when there are multiple
					if(jsondata.length > 1) {
						var source   = $("#mail-account-manager").html();
						var template = Handlebars.compile(source);
						var html = template(jsondata);
						$('#accountManager').html(html);
					}

					// setup sendmail view
					var view = new views.SendMail({
						el: $('#app-content'),
						aliases: Mail.State.accounts
					});

					view.sentCallback = function() {
						$('#nav-buttons').removeClass('hidden');
						$('.mail_account').slideUp();
						$('#new-message-fields').slideUp();
						$('#new-message-attachments').slideUp();
					};
					// And render it
					view.render();


					$('textarea').autosize({append:'"\n\n"'});

				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading the accounts.'));
				}
			});
		},

		showError: function(message) {
			OC.Notification.show(message);
			$('#app-navigation')
				.removeClass('icon-loading');
			$('#app-content')
				.removeClass('icon-loading');
		},

		hideMenu:function () {
			var menu = $('#new-message');
			menu.addClass('hidden');
		}

	}
};

$(document).ready(function () {
	Mail.UI.initializeInterface();

	$(document).on('click', '#nav-to-mail', function(event) {
		event.stopPropagation();
		location.href = OC.generateUrl('/apps/mail/');
	});

	$(document).on('click', '#back-in-time', function(event) {
		event.stopPropagation();
		window.history.back();
	});

	if($('#cc').attr('value') || $('#bcc').attr('value')) {
		$('#new-message-cc-bcc').show();
		$('#new-message-cc-bcc-toggle').hide();
	}

});
