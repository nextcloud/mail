/* global Handlebars, Marionette, relative_modified_date, formatDate, humanFileSize, views */
var Mail = {
	State:{
		currentFolderId: null,
		currentAccountId: null,
		currentMessageId: null,
		accounts: null,
		messageView: null,
		router: null
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

			Handlebars.registerHelper("printAddressList", function(addressList) {
				var currentAddress = _.find(Mail.State.accounts, function(item) {
					return item.accountId === Mail.State.currentAccountId;
				});

				var str = _.reduce(addressList, function(memo, value, index) {
					if (index !== 0) {
						memo += ', ';
					}
					var label = value.label
						.replace(/(^"|"$)/g, '')
						.replace(/(^'|'$)/g, '');
					label = Handlebars.Utils.escapeExpression(label);
					var email = Handlebars.Utils.escapeExpression(value.email);
					if (currentAddress && email === currentAddress.emailAddress) {
						label = t('mail', 'you');
					}
					return memo + '<span title="' + email + '">' + label + '</span>';
				}, "");
				return new Handlebars.SafeString(str);
			});

			Handlebars.registerHelper("printAddressListPlain", function(addressList) {
				var str = _.reduce(addressList, function(memo, value, index) {
					if (index !== 0) {
						memo += ', ';
					}
					var label = value.label
						.replace(/(^"|"$)/g, '')
						.replace(/(^'|'$)/g, '');
					label = Handlebars.Utils.escapeExpression(label);
					var email = Handlebars.Utils.escapeExpression(value.email);
					if(label === email) {
						return memo + email;
					} else {
						return memo + '"' + label + '" <' + email + '>';
					}
				}, "");
				return str;
			});

			Marionette.TemplateCache.prototype.compileTemplate = function(rawTemplate) {
				return Handlebars.compile(rawTemplate);
			};
			Marionette.ItemView.prototype.modelEvents = { "change" : "render"};
			Marionette.CompositeView.prototype.modelEvents = { "change" : "render"};

			// ask to handle all mailto: links
			if(window.navigator.registerProtocolHandler) {
				var url = window.location.protocol + '//' +
					window.location.host +
					OC.generateUrl('apps/mail/compose?uri=%s');
				window.navigator
					.registerProtocolHandler("mailto", url, "ownCloud Mail");
			}

			// setup messages view
			Mail.State.messageView = new views.Messages({
				el: $('#mail_messages')
			});
			Mail.State.messageView.render();

			// setup folder view
			Mail.State.folderView = new views.Folders({
				el: $('#folders')
			});
			Mail.State.folderView.render();

			Mail.State.folderView.listenTo(Mail.State.messageView, 'change:flags',
				Mail.State.folderView.changeMessageFlags);

			$.ajax(OC.generateUrl('apps/mail/accounts'), {
				data:{},
				type:'GET',
				success:function (jsondata) {
						Mail.State.accounts = jsondata;
						if (jsondata.length === 0) {
							Mail.UI.addAccount();
						} else {
							var firstAccountId = jsondata[0].accountId;
							_.each(Mail.State.accounts, function(a) {
								Mail.UI.loadFoldersForAccount(a.accountId, firstAccountId);
							});
						}
					},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading the accounts.'));
				}
			});

		},

		loadFoldersForAccount : function(accountId, firstAccountId) {

			$('#mail_messages').removeClass('hidden').addClass('icon-loading');
			$('#mail-message').removeClass('hidden').addClass('icon-loading');
			$('#mail_new_message').removeClass('hidden');
			$('#folders').removeClass('hidden');
			$('#mail-setup').addClass('hidden');

			Mail.UI.clearMessages();
			$('#app-navigation').addClass('icon-loading');

			$.ajax(OC.generateUrl('apps/mail/accounts/{accountId}/folders', {accountId: accountId}), {
				data:{},
				type:'GET',
				success:function (jsondata) {

					$('#app-navigation').removeClass('icon-loading');

					Mail.State.folderView.collection.add(jsondata);

					if (jsondata.id === firstAccountId) {
						var folderId = jsondata.folders[0].id;

						Mail.UI.loadMessages(accountId, folderId, false);

						// Save current folder
						Mail.UI.setFolderActive(accountId, folderId);
						Mail.State.currentAccountId = accountId;
						Mail.State.currentFolderId = folderId;
					}
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while loading the selected account.'));
				}
			});
		},

		showError: function(message) {
			OC.Notification.show(message);
			$('#app-navigation')
				.removeClass('icon-loading');
			$('#app-content')
				.removeClass('icon-loading');
			$('#mail-message')
				.removeClass('icon-loading');
			$('#mail_message')
				.removeClass('icon-loading');
			_.delay(function() {
				OC.Notification.hide();
			}, 4000);
		},

		clearMessages:function () {
			Mail.State.messageView.collection.reset();
			$('#messages-loading').fadeIn();

			$('#mail-message')
				.html('')
				.addClass('icon-loading');
		},

		hideMenu:function () {
			$('#new-message').addClass('hidden');
		},

		addMessages:function (data) {
			Mail.State.messageView.collection.add(data);
		},

		loadMessages:function (accountId, folderId, noSelect) {
			// Set folder active
			Mail.UI.setFolderInactive(Mail.State.currentAccountId, Mail.State.currentFolderId);
			Mail.UI.setFolderActive(accountId, folderId);
			Mail.UI.clearMessages();
			$('#mail_messages')
				.removeClass('hidden')
				.addClass('icon-loading');
			$('#mail-message').removeClass('hidden');
			$('#mail_new_message')
				.removeClass('hidden')
				.fadeIn();
			$('#folders').removeClass('hidden');
			$('#mail-setup').addClass('hidden');


			$('#load-new-mail-messages').hide();
			$('#load-more-mail-messages').hide();
			$('#emptycontent').hide();

			if (noSelect) {
				$('#emptycontent').show();
				$('#mail-message').removeClass('icon-loading');
				Mail.State.currentAccountId = accountId;
				Mail.State.currentFolderId = folderId;
				Mail.UI.setMessageActive(null);
				$('#mail_messages').removeClass('icon-loading');
			} else {
				$.ajax(
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
						{'accountId':accountId, 'folderId':folderId}), {
						data: {},
						type:'GET',
						success: function (jsondata) {
							Mail.State.currentAccountId = accountId;
							Mail.State.currentFolderId = folderId;
							Mail.UI.setMessageActive(null);
							$('#mail_messages').removeClass('icon-loading');
							
							// Fade out the message composer
							$('#mail_new_message').prop('disabled', false);
							$('#new-message').hide();
			
							if(jsondata.length > 0) {
								Mail.UI.addMessages(jsondata);
								var messageId = jsondata[0].id;
								Mail.UI.openMessage(messageId);
                                                                // Show 'Load More' button if there are
                                                                // more messages than the pagination limit
								if (jsondata.length > 20) {
                                                                        $('#load-more-mail-messages')
                                                                                .fadeIn()
                                                                                .css('display','block');
                                                                }
							} else {
								$('#emptycontent').show();
								$('#mail-message').removeClass('icon-loading');
							}
							$('#load-new-mail-messages')
								.fadeIn()
								.css('display','block')
								.prop('disabled', false);

						},
						error: function() {

							// Set the old folder as being active
							Mail.UI.setFolderInactive(accountId, folderId);
							Mail.UI.setFolderActive(Mail.State.currentAccountId, Mail.State.currentFolderId);

							Mail.UI.showError(t('mail', 'Error while loading messages.'));
						}
					});
			}
		},

		saveAttachment: function(messageId, attachmentId) {
			OC.dialogs.filepicker(
				t('mail', 'Choose a folder to store the attachment in'),
				function (path) {
					// Loading feedback
					var saveToFilesBtnSelector = '.attachment-save-to-cloud';
					if (typeof attachmentId !== "undefined") {
						saveToFilesBtnSelector = 'li[data-attachment-id="'+attachmentId+'"] '+saveToFilesBtnSelector;
					}
					$(saveToFilesBtnSelector)
						.removeClass('icon-upload')
						.addClass('icon-loading-small')
						.prop('disabled', true);

					$.ajax(
						OC.generateUrl(
							'apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
						{
							accountId: Mail.State.currentAccountId,
							folderId: Mail.State.currentFolderId,
							messageId: messageId,
							attachmentId: attachmentId
						}), {
							data: {
								targetPath: path
							},
							type:'POST',
							success: function () {
								if (typeof attachmentId === "undefined") {
									Mail.UI.showError(t('mail', 'Attachments saved to Files.'));
								} else {
									Mail.UI.showError(t('mail', 'Attachment saved to Files.'));
								}
							},
							error: function() {
								if (typeof attachmentId === "undefined") {
									Mail.UI.showError(t('mail', 'Error while saving attachments to Files.'));
								} else {
									Mail.UI.showError(t('mail', 'Error while saving attachment to Files.'));
								}
							},
							complete: function() {
								// Remove loading feedback again
								$('.attachment-save-to-cloud')
									.removeClass('icon-loading-small')
									.addClass('icon-upload')
									.prop('disabled', false);
							}
						});
				},
				false,
				'httpd/unix-directory',
				true
			);
		},

		openMessage:function (messageId) {
			// Do not reload email when clicking same again
			if(Mail.State.currentMessageId === messageId) {
				return;
			}

			// close email first
			// Check if message is open
			if (Mail.State.currentMessageId !== null) {
				$('#mail-message')
					.html('')
					.addClass('icon-loading');
				var lastMessageId = Mail.State.currentMessageId;
				Mail.UI.setMessageActive(null);
				if (lastMessageId === messageId) {
					return;
				}
			}

			// Set current Message as active
			Mail.UI.setMessageActive(messageId);

			var mailBody = $('#mail-message');

			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
					{
					accountId: Mail.State.currentAccountId,
					folderId: Mail.State.currentFolderId,
					messageId: messageId
				}), {
					data: {},
					type:'GET',
					success: function (data) {
						// Render the message body
						var source   = $("#mail-message-template").html();
						var template = Handlebars.compile(source);
						var html = template(data);
						mailBody
							.html(html)
							.removeClass('icon-loading');

						Mail.State.messageView.setMessageFlag(messageId, 'unseen', false);

						// HTML mail rendering
						$('iframe').load(function() {
							// Expand height to not have two scrollbars
							$(this).height( $(this).contents().find('html').height() + 20);
							// Fix styling
							$(this).contents().find('body').css({
								'margin': '0',
								'font-weight': 'normal',
								'font-size': '.8em',
								'line-height': '1.6em',
								'font-family': "'Open Sans', Frutiger, Calibri, 'Myriad Pro', Myriad, sans-serif",
								'color': '#000'
							});
							// Fix font when different font is forced
							$(this).contents().find('font').prop({
								'face': 'Open Sans',
								'color': '#000'
							});
							$(this).contents().find('.moz-text-flowed').css({
								'font-family': 'inherit',
								'font-size': 'inherit'
							});
							// Expand height again after rendering to account for new size
							$(this).height( $(this).contents().find('html').height() + 20);
							// Grey out previous replies
							$(this).contents().find('blockquote').css({
								'-ms-filter': '"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)"',
								'filter': 'alpha(opacity=50)',
								'opacity': '.5'
							});
							// Remove spinner when loading finished
							$('iframe').parent().removeClass('icon-loading');

						});

						$('textarea').autosize({append:'"\n\n"'});

					},
					error: function() {
						Mail.UI.showError(t('mail', 'Error while loading the selected message.'));
					}
				});
		},

		setFolderActive:function (accountId, folderId) {
			$('.mail_folders[data-account_id="' + accountId + '"] li[data-folder_id="' + folderId + '"]')
				.addClass('active');
		},

		setMessageActive:function (messageId) {
			Mail.State.messageView.setActiveMessage(messageId);
			Mail.State.currentMessageId = messageId;
		},

		addAccount:function () {
			$('#mail_messages').addClass('hidden');
			$('#mail-message').addClass('hidden');
			$('#mail_new_message').addClass('hidden');
			$('#app-navigation').removeClass('icon-loading');

			Mail.UI.hideMenu();

			$('#mail-setup').removeClass('hidden');
			// don't show New Message button on Add account screen
			$('#mail_new_message').hide();
		},

		setFolderInactive:function (accountId, folderId) {
			$('.mail_folders[data-account_id="' + accountId + '"] li[data-folder_id="' + folderId + '"]')
				.removeClass('active');
		}
	}
};

$(document).ready(function () {
	Mail.UI.initializeInterface();

	// auto detect button handling
	$('#auto_detect_account').click(function (event) {
		event.preventDefault();
		$('#mail-account-name, #mail-address, #mail-password, #mail-setup-manual-toggle')
			.prop('disabled', true);
		$('#mail-imap-host, #mail-imap-port, #mail-imap-sslmode, #mail-imap-user, #mail-imap-password')
			.prop('disabled', true);
		$('#mail-smtp-host, #mail-smtp-port, #mail-smtp-sslmode, #mail-smtp-user, #mail-smtp-password')
			.prop('disabled', true);

		$('#auto_detect_account')
			.prop('disabled', true)
			.val(t('mail', 'Connecting …'));
		$('#connect-loading').fadeIn();
		var emailAddress = $('#mail-address').val();
		var accountName = $('#mail-account-name').val();
		var password = $('#mail-password').val();

		var dataArray = {
			accountName: accountName,
			emailAddress: emailAddress,
			password: password,
			autoDetect: true
		};

		if($('#mail-setup-manual').css('display') === 'block') {
			dataArray = {
				accountName: accountName,
				emailAddress: emailAddress,
				password: password,
				imapHost: $('#mail-imap-host').val(),
				imapPort: $('#mail-imap-port').val(),
				imapSslMode: $('#mail-imap-sslmode').val(),
				imapUser: $('#mail-imap-user').val(),
				imapPassword: $('#mail-imap-password').val(),
				smtpHost: $('#mail-smtp-host').val(),
				smtpPort: $('#mail-smtp-port').val(),
				smtpSslMode: $('#mail-smtp-sslmode').val(),
				smtpUser: $('#mail-smtp-user').val(),
				smtpPassword: $('#mail-smtp-password').val(),
				autoDetect: false
			};
		}

		$.ajax(OC.generateUrl('apps/mail/accounts'), {
			data: dataArray,
			type:'POST',
			success:function (data) {
				var newAccountId = data.data.id;
				Mail.UI.loadFoldersForAccount(newAccountId, newAccountId);
			},
			error: function(jqXHR, textStatus, errorThrown){
				var error = errorThrown || textStatus || t('mail', 'Unknown error');
				Mail.UI.showError(t('mail', 'Error while creating an account: ' + error));
			},
			complete: function() {
				$('#mail-account-name, #mail-address, #mail-password, #mail-setup-manual-toggle')
					.prop('disabled', false);
				$('#mail-imap-host, #mail-imap-port, #mail-imap-sslmode, #mail-imap-user, #mail-imap-password')
					.prop('disabled', false);
				$('#mail-smtp-host, #mail-smtp-port, #mail-smtp-sslmode, #mail-smtp-user, #mail-smtp-password')
					.prop('disabled', false);
				$('#auto_detect_account')
					.prop('disabled', false)
					.val(t('mail', 'Connect'));
				$('#connect-loading').hide();
				$('#mail-setup-manual').hide();
			}
		});
	});

	// toggle for advanced account configuration
	$(document).on('click', '#mail-setup-manual-toggle', function () {
		$('#mail-setup-manual').slideToggle();
		$('#mail-imap-host').focus();
		if($('#mail-address').parent().prop('class') === 'groupmiddle') {
			$('#mail-password').slideToggle(function() {
				$('#mail-address').parent().removeClass('groupmiddle').addClass('groupbottom');
			});
		} else {
			$('#mail-password').slideToggle();
			$('#mail-address').parent().removeClass('groupbottom').addClass('groupmiddle');
		}
	});

	// new mail message button handling
	$(document).on('click', '#mail_new_message', function () {
		$('#mail_new_message').prop('disabled', true);

		// setup sendmail view
		var view = new views.SendMail({
			el: $('#mail-message'),
			aliases: Mail.State.accounts
		});

		view.sentCallback = function() {

		};

		// And render it
		view.render();

		// focus 'to' field automatically on clicking New message button
		$('#to').focus();

		Mail.UI.setMessageActive(null);
	});

	$(document).on('click', '#mail-message .attachment-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().data('messageId');
		var attachmentId = $(this).parent().data('attachmentId');
		Mail.UI.saveAttachment(messageId, attachmentId);
	});

	$(document).on('click', '#mail-message .attachments-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).data('messageId');
		Mail.UI.saveAttachment(messageId);
	});
});
