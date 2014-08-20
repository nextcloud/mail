/* global Handlebars, relative_modified_date, formatDate, humanFileSize, views */
var Mail = {
	State:{
		currentAccountId: null
	},
	UI:{
		initializeInterface:function () {
			Handlebars.registerHelper("colorOfDate", function(dateInt) {
				var lastModified = new Date(dateInt*1000);
				var lastModifiedTime = Math.round(lastModified.getTime() / 1000);

				// date column
				var modifiedColor = Math.round((Math.round((new Date()).getTime() / 1000)-lastModifiedTime)/60/60/24*5);
				if (modifiedColor > 200) {
					modifiedColor = 200;
				}
				return 'rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')';
			});

			Handlebars.registerHelper("relativeModifiedDate", function(dateInt) {
				var lastModified = new Date(dateInt*1000);
				var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
				return relative_modified_date(lastModifiedTime);
			});

			Handlebars.registerHelper("formatDate", function(dateInt) {
				var lastModified = new Date(dateInt*1000);
				return formatDate(lastModified);
			});

			Handlebars.registerHelper("humanFileSize", function(size) {
				return humanFileSize(size);
			});

			$.ajax(OC.generateUrl('apps/mail/accounts'), {
				data:{},
				type:'GET',
				success:function (jsondata) {
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
						Mail.State.currentAccountId = jsondata[0].accountId;
				},
				error: function() {
//					OC.msg.finishedAction('', '');
				}
			});
		},

		hideMenu:function () {
			var menu = $('#new-message');

			menu.addClass('hidden');
		}

	}
};

$(document).ready(function () {
	Mail.UI.initializeInterface();

	$(document).on('change', '#app-navigation .mail_account', function(event) {
		event.stopPropagation();

		Mail.State.currentAccountId = $( this ).val();
	});

	if($('#cc').attr('value') || $('#bcc').attr('value')) {
		$('#new-message-cc-bcc').show();
		$('#new-message-cc-bcc-toggle').hide();
	}

	$('textarea').autosize();
});
