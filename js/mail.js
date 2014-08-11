/* global Handlebars, relative_modified_date, formatDate, humanFileSize, views */
var Mail = {
	State:{
		currentFolderId: null,
		currentAccountId: null,
		currentMessageId: null,
		messageView: null
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


			// setup sendmail view
			if ($('#mail_messages').length) {
				Mail.State.messageView = new views.Messages({
					el: $('#mail_messages')
				});

				// And render it
				Mail.State.messageView.render();
			}

			$.ajax(OC.generateUrl('apps/mail/accounts'), {
				data:{},
				type:'GET',
				success:function (jsondata) {
						// don't try to load accounts if there are none
						if(jsondata.length === 0) {
							return;
						}
						var source   = $("#mail-account-manager").html();
						var template = Handlebars.compile(source);
						var html = template(jsondata);
						$('#accountManager').html(html);
						Mail.UI.loadFoldersForAccount(jsondata[0].accountId);
					},
				error: function() {
//					OC.msg.finishedAction('', '');
				}
			});
		},

		loadFoldersForAccount : function(accountId) {
			var firstFolder, folderId;

			Mail.UI.clearFolders();
			Mail.UI.clearMessages();
			$('#app-navigation').addClass('icon-loading');
			$('#mail_messages').addClass('icon-loading');
			$('#mail-message').addClass('icon-loading');

			OC.msg.startAction('#app-navigation-msg', '');

			$.ajax(OC.generateUrl('apps/mail/accounts/{accountId}/folders', {accountId: accountId}), {
				data:{},
				type:'GET',
				success:function (jsondata) {
					var source   = $("#mail-folder-template").html();
					var template = Handlebars.compile(source);
					var html = template(jsondata);

					$('#app-navigation').removeClass('icon-loading');
					$('#folders').html(html);

					firstFolder = $('#app-navigation').find('.mail_folders li');

					if (firstFolder.length > 0) {
						$('#app-navigation').fadeIn(800);
						firstFolder = firstFolder.first();
						folderId = firstFolder.data('folder_id');
						accountId = firstFolder.parent().data('account_id');

						Mail.UI.loadMessages(accountId, folderId);

						// Save current folder
						Mail.UI.setFolderActive(accountId, folderId);
						Mail.State.currentAccountId = accountId;
						Mail.State.currentFolderId = folderId;
					} else {
						$('#app-navigation').fadeOut(800);
					}
				},
				error: function() {
					OC.msg.finishedAction('#app-navigation-msg', {
						status: 'error',
						data: {
							message: t('mail', 'Error while loading the selected account.')
						}
					});
					$('#app-navigation')
						.removeClass('icon-loading');
					$('#app-content')
						.removeClass('icon-loading');
				}
			});
		},

		clearMessages:function () {
			Mail.State.messageView.collection.reset();
			$('#messages-loading').fadeIn();

			$('#mail-message')
				.html('')
				.addClass('icon-loading');
		},

		clearFolders:function () {
			var list = $('.mail_folders');

			list.empty();
		},

		hideMenu:function () {
			var menu = $('#new-message');

			menu.addClass('hidden');
		},

		addMessages:function (data) {
//			var source   = $("#mail-messages-template").html();
//			var template = Handlebars.compile(source);
//			var html = template(data);
//			$('#mail_messages').append(html);
			Mail.State.messageView.collection.add(data);

			_.each($('.avatar'), function(a) {
				$(a).imageplaceholder($(a).data('user'), $(a).data('user'));
			}
			);
		},

		loadMessages:function (accountId, folderId) {
			// Set folder active
			Mail.UI.setFolderInactive(Mail.State.currentAccountId, Mail.State.currentFolderId);
			Mail.UI.setFolderActive(accountId, folderId);
			Mail.UI.clearMessages();

			$('#mail_new_message').fadeIn();
			$('#mail_messages').addClass('icon-loading');
			$('#load-more-mail-messages').hide();

			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages',
					{'accountId':accountId, 'folderId':encodeURIComponent(folderId)}), {
					data: {},
					type:'GET',
					success: function (jsondata) {
						// Add messages
						Mail.UI.addMessages(jsondata);
						$('#mail_messages').removeClass('icon-loading');
						$('#load-more-mail-messages').fadeIn();

						Mail.State.currentAccountId = accountId;
						Mail.State.currentFolderId = folderId;
						Mail.UI.setMessageActive(null);

						var messageId = jsondata[0].id;
						Mail.UI.openMessage(messageId);
					},
					error: function() {

						// Set the old folder as being active
						Mail.UI.setFolderInactive(accountId, folderId);
						Mail.UI.setFolderActive(Mail.State.currentAccountId, Mail.State.currentFolderId);

//						OC.dialogs.alert(jsondata.data.message, t('mail', 'Error'));
					}
				});
		},

		deleteMessage:function (messageId) {
			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
					{
					accountId: Mail.State.currentAccountId,
					folderId: encodeURIComponent(Mail.State.currentFolderId),
					messageId: messageId
				}), {
					data: {},
					type:'DELETE',
					success: function () {
						var nextMessage = $('#mail-message-summary-' + messageId).next();
						$('#mail-message-summary-' + messageId)
							.remove();

						// When currently open message is deleted, open next one
						if(messageId === Mail.State.currentMessageId) {
							var nextMessageId = nextMessage.data('messageId');
							Mail.UI.openMessage(nextMessageId);
						}
					},
					error: function() {
						OC.Notification.show(t('mail', 'Error while deleting mail.'));
					}
				});
		},

		toggleMessageStar: function(messageId, starred) {
			// Loading feedback
			$('#mail-message-summary-' + messageId)
				.find('.star')
				.removeClass('icon-starred')
				.removeClass('icon-star')
				.addClass('icon-loading-small');

			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/toggleStar',
					{
					accountId: Mail.State.currentAccountId,
					folderId: encodeURIComponent(Mail.State.currentFolderId),
					messageId: messageId
				}), {
					data: {
						starred: starred
					},
					type:'POST',
					success: function () {
						if (starred) {
							$('#mail-message-summary-' + messageId)
								.find('.star')
								.removeClass('icon-loading-small')
								.addClass('icon-star')
								.data('starred', false);
						} else {
							$('#mail-message-summary-' + messageId)
								.find('.star')
								.removeClass('icon-loading-small')
								.addClass('icon-starred')
								.data('starred', true);
						}
					},
					error: function() {
						OC.Notification.show(t('mail', 'Message could not be favorited. Please try again.'));
						if(starred) {
							$('#mail-message-summary-' + messageId)
								.find('.star')
								.removeClass('icon-loading-small')
								.addClass('icon-starred');
						} else {
							$('#mail-message-summary-' + messageId)
								.find('.star')
								.removeClass('icon-loading-small')
								.addClass('icon-star');
						}
					}
				});
		},

		saveAttachment: function(messageId, attachmentId) {
			OC.dialogs.filepicker(
				t('mail', 'Choose a folder to store the attachment in'),
				function (path) {
					// Loading feedback
					$('.attachment-save-to-cloud')
						.removeClass('icon-upload')
						.addClass('icon-loading-small')
						.html(t('mail', 'Saving to Files …'))
						.prop('disabled', true);

					$.ajax(
						OC.generateUrl(
							'apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/attachment/{attachmentId}',
						{
							accountId: Mail.State.currentAccountId,
							folderId: encodeURIComponent(Mail.State.currentFolderId),
							messageId: messageId,
							attachmentId: attachmentId
						}), {
							data: {
								targetPath: path
							},
							type:'POST',
							success: function () {
								if (typeof attachmentId === "undefined") {
									OC.Notification.show(t('mail', 'Attachments saved to Files.'));
								} else {
									OC.Notification.show(t('mail', 'Attachment saved to Files.'));
								}
							},
							error: function() {
								if (typeof attachmentId === "undefined") {
									OC.Notification.show(t('mail', 'Error while saving attachments to Files.'));
								} else {
									OC.Notification.show(t('mail', 'Error while saving attachment to Files.'));
								}
							},
							complete: function() {
								// Remove loading feedback again
								$('.attachment-save-to-cloud')
									.removeClass('icon-loading-small')
									.addClass('icon-upload')
									.html(t('mail', 'Save to Files'))
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
			// Fade out the message composer
			$('#mail_new_message').prop('disabled', false);
			$('#new-message').hide();

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

			var mailBody = $('#mail-message');

			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
					{
					accountId: Mail.State.currentAccountId,
					folderId: encodeURIComponent(Mail.State.currentFolderId),
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
						$('#mail-message-summary-' + messageId)
							.removeClass('unseen');

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

						// Set current Message as active
						Mail.UI.setMessageActive(messageId);
					},
					error: function() {
						OC.dialogs.alert(t('mail', 'Error while loading mail message.'), t('mail', 'Error'));
					}
				});
		},

		setFolderActive:function (accountId, folderId) {
			$('.mail_folders[data-account_id="' + accountId + '"]>li[data-folder_id="' + folderId + '"]')
				.addClass('active');
		},

		setMessageActive:function (messageId) {
			// Set active class for current message and remove it from old one

			if(Mail.State.currentMessageId !== null) {
				$('#mail-message-summary-'+Mail.State.currentMessageId)
					.removeClass('active');
			}

			Mail.State.currentMessageId = messageId;

			if(messageId !== null) {
				$('#mail-message-summary-'+messageId)
					.addClass('active');
			}
		},

		addAccount:function () {
			$('#mail_messages').addClass('hidden');
			$('#mail-message').addClass('hidden');
			$('#mail_new_message').addClass('hidden');
			$('#folders').addClass('hidden');

			Mail.UI.clearFolders();
			Mail.UI.hideMenu();

			var menu = $('#mail-setup');
			menu.removeClass('hidden');
			// don't show New Message button on Add account screen
			$('#mail_new_message').hide();
		},

		setFolderInactive:function (accountId, folderId) {
			$('.mail_folders[data-account_id="' + accountId + '"]>li[data-folder_id="' + folderId + '"]')
				.removeClass('active');
		}
	}
};

$(document).ready(function () {
	Mail.UI.initializeInterface();

	// auto detect button handling
	$('#auto_detect_account').click(function (event) {
		event.preventDefault();
		$('#mail-account-name').prop('disabled', true);
		$('#mail-address').prop('disabled', true);
		$('#mail-password').prop('disabled', true);
		$('#auto_detect_account')
			.prop('disabled', true)
			.val(t('mail', 'Connecting …'));
		$('#connect-loading').fadeIn();
		var emailAddress = $('#mail-address').val();
		var accountName = $('#mail-account-name').val();
		var password = $('#mail-password').val();
		$.ajax(OC.generateUrl('apps/mail/accounts'), {
			data:{
				accountName: accountName,
				emailAddress : emailAddress,
				password : password,
				autoDetect : true
			},
			type:'POST',
			success:function () {
				// reload on success
				window.location.reload();
			},
			error: function(jqXHR, textStatus, errorThrown){
				var error = errorThrown || textStatus || t('mail', 'Unknown error');
				OC.dialogs.alert(error, t('mail', 'Server Error'));
			},
			complete: function() {
				$('#mail-account-name').prop('disabled', false);
				$('#mail-address').prop('disabled', false);
				$('#mail-password').prop('disabled', false);
				$('#auto_detect_account')
					.prop('disabled', false)
					.val(t('mail', 'Connect'));
				$('#connect-loading').hide();
			}
		});
	});

	// new mail message button handling
	$(document).on('click', '#mail_new_message', function () {
		$('#mail_new_message').prop('disabled', true);
		$('#new-message').fadeIn();
		$('#mail-message').html('');
		$('#to').focus();
		Mail.UI.setMessageActive(null);
	});

	// Clicking on a folder loads the message list
	$(document).on('click', 'ul.mail_folders li', function () {
		var accountId = $(this).parent().data('account_id');
		var folderId = $(this).data('folder_id');

		Mail.UI.loadMessages(accountId, folderId);
	});

	// Clicking on a message loads the entire message
	$(document).on('click', '#mail_messages .mail-message-header', function () {
		var messageId = $(this).parent().data('messageId');
		Mail.UI.openMessage(messageId);
	});

	$(document).on('click', '#mail_messages .action.delete', function(event) {
		event.stopPropagation();
		$(this).removeClass('icon-delete').addClass('icon-loading');
		var messageElement = $(this).parent().parent()
			.addClass('transparency')
			.slideUp();

		var messageId = messageElement.data('messageId');
		Mail.UI.deleteMessage(messageId);
	});

	$(document).on('click', '#mail_messages .star', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().parent().data('messageId');
		Mail.UI.toggleMessageStar(messageId, $(this).data('starred'));
	});

	$(document).on('click', '#mail_messages .attachment-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().parent().parent().parent().parent().parent().data('messageId');
		var attachmentId = $(this).parent().data('attachmentId');
		Mail.UI.saveAttachment(messageId, attachmentId);
	});

	$(document).on('click', '#mail_messages .attachments-save-to-cloud', function(event) {
		event.stopPropagation();
		var messageId = $(this).parent().parent().parent().parent().parent().data('messageId');
		Mail.UI.saveAttachment(messageId);
	});

	$(document).on('change', '#app-navigation .mail_account', function(event) {
		event.stopPropagation();

		var accountId;

		accountId = $( this ).val();
		if(accountId === 'addAccount') {
			Mail.UI.addAccount();
		} else {
			Mail.UI.loadFoldersForAccount(accountId);
		}
	});

	$('textarea').autosize();
});
