/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var $ = require('jquery');
	var _ = require('underscore');
	var OC = require('OC');
	var Radio = require('radio');
	var Attachments = require('models/attachments');
	var AttachmentsView = require('views/attachmentsview');
	var ComposerTemplate = require('text!templates/composer.html');

	return Marionette.View.extend({
		template: Handlebars.compile(ComposerTemplate),
		templateContext: function() {
			var aliases = null;
			if (this.accounts) {
				aliases = this.buildAliases();
				aliases = _.filter(aliases, function(alias) {
					return alias.accountId !== -1;
				});
			}

			return {
				aliases: aliases,
				isReply: this.isReply(),
				to: this.data.to,
				cc: this.data.cc,
				subject: this.data.subject,
				message: this.data.body,
				submitButtonTitle: this.isReply() ? t('mail', 'Reply') : t('mail', 'Send'),
				// Reply data
				replyToList: this.data.replyToList,
				replyCc: this.data.replyCc,
				replyCcList: this.data.replyCcList
			};
		},
		type: 'new',
		data: null,
		attachments: null,
		accounts: null,
		aliases: null,
		account: null,
		folder: null,
		repliedMessage: null,
		draftInterval: 1500,
		draftTimer: null,
		draftUID: null,
		hasData: false,
		autosized: false,
		regions: {
			attachmentsRegion: '.new-message-attachments'
		},
		events: {
			'click .submit-message': 'submitMessage',
			'click .submit-message-wrapper-inside': 'submitMessageWrapperInside',
			'keypress .message-body': 'handleKeyPress',
			'input  .to': 'onInputChanged',
			'paste  .to': 'onInputChanged',
			'keyup  .to': 'onInputChanged',
			'input  .cc': 'onInputChanged',
			'paste  .cc': 'onInputChanged',
			'keyup  .cc': 'onInputChanged',
			'input  .bcc': 'onInputChanged',
			'paste  .bcc': 'onInputChanged',
			'keyup  .bcc': 'onInputChanged',
			'input  .subject': 'onInputChanged',
			'paste  .subject': 'onInputChanged',
			'keyup  .subject': 'onInputChanged',
			'input  .message-body': 'onInputChanged',
			'paste  .message-body': 'onInputChanged',
			'keyup  .message-body': 'onInputChanged',
			'focus  .recipient-autocomplete': 'onAutoComplete',
			// CC/BCC toggle
			'click .composer-cc-bcc-toggle': 'ccBccToggle'
		},
		initialize: function(options) {
			var defaultOptions = {
				type: 'new',
				account: null,
				repliedMessage: null,
				data: {
					to: '',
					cc: '',
					subject: '',
					body: ''
				}
			};
			_.defaults(options, defaultOptions);

			/**
			 * Composer type (new, reply)
			 */
			this.type = options.type;

			/**
			 * Containing element
			 */
			if (options.el) {
				this.el = options.el;
			}

			/**
			 * Attachments sub-view
			 */
			this.attachments = new Attachments();
			this.bindAttachments();

			/**
			 * Data for replies
			 */
			this.data = options.data;

			if (!this.isReply()) {
				this.accounts = options.accounts;
				this.account = options.account || this.accounts.at(0);
				this.draftUID = options.data.id;
			} else {
				this.account = options.account;
				this.accounts = options.accounts;
				this.folder = options.folder;
				this.repliedMessage = options.repliedMessage;
			}
		},
		onRender: function() {
			this.showChildView('attachmentsRegion', new AttachmentsView({
				collection: this.attachments
			}));

			$('.tooltip-mailto').tooltip({placement: 'bottom'});

			if (this.isReply()) {
				// Expand reply message body on click
				var _this = this;
				this.$('.message-body').click(function() {
					_this.setAutoSize(true);
				});
			} else {
				this.setAutoSize(true);
			}

			this.defaultMailSelect();
		},
		setAutoSize: function(state) {
			if (state === true) {
				if (!this.autosized) {
					this.$('textarea').autosize({append: '\n\n'});
					this.autosized = true;
				}
				this.$('.message-body').trigger('autosize.resize');
			} else {
				this.$('.message-body').trigger('autosize.destroy');

				// dirty workaround to set reply message body to the default size
				this.$('.message-body').css('height', '');
				this.autosized = false;
			}
		},
		bindAttachments: function() {
			// when the attachment list changed (add, remove, change), we make sure
			// to update the 'Send' button
			this.attachments.bind('all', this.onInputChanged.bind(this));
		},
		isReply: function() {
			return this.type === 'reply';
		},
		onInputChanged: function() {
			// Submit button state
			var to = this.$('.to').val();
			var subject = this.$('.subject').val();
			var body = this.$('.message-body').val();
			// if some attachments are not valid, we disable the 'send' button
			var attachmentsValid = this.checkAllAttachmentsValid();
			if ((to !== '' || subject !== '' || body !== '') && attachmentsValid) {
				this.$('.submit-message').removeAttr('disabled');
			} else {
				this.$('.submit-message').attr('disabled', true);
			}

			// Save draft
			this.hasData = true;
			clearTimeout(this.draftTimer);
			var _this = this;
			this.draftTimer = setTimeout(function() {
				_this.saveDraft();
			}, this.draftInterval);
		},
		handleKeyPress: function(event) {
			// Define which objects to check for the event properties.
			// (Window object provides fallback for IE8 and lower.)
			event = event || window.event;
			var key = event.keyCode || event.which;
			// If enter and control keys are pressed:
			// (Key 13 and 10 set for compatibility across different operating systems.)
			if ((key === 13 || key === 10) && event.ctrlKey) {
				// If the new message is completely filled, and ready to be sent:
				// Send the new message.
				var sendBtnState = this.$('.submit-message').attr('disabled');
				if (sendBtnState === undefined) {
					this.submitMessage();
				}
			}
			return true;
		},
		ccBccToggle: function(e) {
			e.preventDefault();
			this.$('.composer-cc-bcc').slideToggle();
			this.$('.composer-cc-bcc .cc').focus();
			this.$('.composer-cc-bcc-toggle').fadeOut();
		},
		getMessage: function() {
			var message = {};
			var newMessageBody = this.$('.message-body');
			var to = this.$('.to');
			var cc = this.$('.cc');
			var bcc = this.$('.bcc');
			var subject = this.$('.subject');

			message.body = newMessageBody.val();
			message.to = to.val();
			message.cc = cc.val();
			message.bcc = bcc.val();
			message.subject = subject.val();
			message.attachments = this.attachments.toJSON();

			return message;
		},
		submitMessageWrapperInside: function() {
			// http://stackoverflow.com/questions/487073/check-if-element-is-visible-after-scrolling
			if (this._isVisible()) {
				this.$('.submit-message').click();
			} else {
				$('#mail-message').animate({
					scrollTop: this.$el.offset().top
				}, 1000);
				this.$('.submit-message-wrapper-inside').hide();
				// This function is needed because $('.message-body').focus does not focus the first line
				this._setCaretToPos(this.$('.message-body')[0], 0);
			}
		},
		_setSelectionRange: function(input, selectionStart, selectionEnd) {
			if (input.setSelectionRange) {
				input.focus();
				input.setSelectionRange(selectionStart, selectionEnd);
			} else if (input.createTextRange) {
				var range = input.createTextRange();
				range.collapse(true);
				range.moveEnd('character', selectionEnd);
				range.moveStart('character', selectionStart);
				range.select();
			}
		},
		_setCaretToPos: function(input, pos) {
			this._setSelectionRange(input, pos, pos);
		},
		_isVisible: function() {
			var $elem = this.$el;
			var $window = $(window);
			var docViewTop = $window.scrollTop();
			var docViewBottom = docViewTop + $window.height();
			var elemTop = $elem.offset().top;

			return elemTop <= docViewBottom;
		},
		submitMessage: function() {
			clearTimeout(this.draftTimer);
			//
			// TODO:
			//  - input validation
			//  - feedback on success
			//  - undo lie - very important
			//

			// loading feedback: show spinner and disable elements
			var newMessageBody = this.$('.message-body');
			var newMessageSend = this.$('.submit-message');
			newMessageBody.addClass('icon-loading');
			var to = this.$('.to');
			var cc = this.$('.cc');
			var bcc = this.$('.bcc');
			var subject = this.$('.subject');
			this.$('.mail-account').prop('disabled', true);
			to.prop('disabled', true);
			cc.prop('disabled', true);
			bcc.prop('disabled', true);
			subject.prop('disabled', true);
			this.$('.new-message-attachments-action').css('display', 'none');
			this.$('#add-cloud-attachment').prop('disabled', true);
			this.$('#add-local-attachment').prop('disabled', true);
			newMessageBody.prop('disabled', true);
			newMessageSend.prop('disabled', true);
			newMessageSend.val(t('mail', 'Sending …'));
			var alias = null;

			// if available get account from drop-down list
			if (this.$('.mail-account').length > 0) {
				alias = this.findAliasById(this.$('.mail-account').
					find(':selected').val());
				this.account = this.accounts.get(alias.accountId);
			}

			// send the mail
			var _this = this;
			var options = {
				draftUID: this.draftUID,
				aliasId: alias.aliasId
			};

			if (this.isReply()) {
				options.repliedMessage = this.repliedMessage;
				options.folder = this.folder;
			}

			Radio.message.request('send', this.account, this.getMessage(), options).then(function() {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				if (!!options.repliedMessage) {
					// Reply -> flag message as replied
					Radio.ui.trigger('messagesview:messageflag:set',
						options.repliedMessage.get('id'),
						'answered',
						true);
				}

				_this.$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				newMessageBody.val('');
				newMessageBody.trigger('autosize.resize');
				_this.attachments.reset();
				if (_this.draftUID !== null) {
					// the sent message was a draft
					if (!_.isUndefined(Radio.ui.request('messagesview:collection'))) {
						Radio.ui.request('messagesview:collection').
							remove({id: _this.draftUID});
					}
					_this.draftUID = null;
				}
			}, function(jqXHR) {
				var error = '';
				if (jqXHR.status === 500) {
					error = t('mail', 'Server error');
				} else {
					var resp = JSON.parse(jqXHR.responseText);
					error = resp.message;
				}
				newMessageSend.prop('disabled', false);
				OC.Notification.showTemporary(error);
			}).then(function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				_this.$('.mail-account').prop('disabled', false);
				to.prop('disabled', false);
				cc.prop('disabled', false);
				bcc.prop('disabled', false);
				subject.prop('disabled', false);
				_this.$('.new-message-attachments-action').
					css('display', 'inline-block');
				_this.$('#add-cloud-attachment').prop('disabled', false);
				_this.$('#add-local-attachment').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			});
			return false;
		},
		saveDraft: function(onSuccess) {
			clearTimeout(this.draftTimer);
			//
			// TODO:
			//  - input validation
			//  - feedback on success
			//  - undo lie - very important
			//

			// if available get account from drop-down list
			if (this.$('.mail-account').length > 0) {
				var alias = this.findAliasById(this.$('.mail-account').
					find(':selected').val());
				this.account = this.accounts.get(alias.accountId);
			}

			// send the mail
			var _this = this;
			Radio.message.request('draft', this.account, this.getMessage(), {
				folder: this.folder,
				repliedMessage: this.repliedMessage,
				draftUID: this.draftUID
			}).then(function(data) {
				if (_.isFunction(onSuccess)) {
					onSuccess();
				}

				if (_this.draftUID !== null) {
					// update UID in message list
					var collection = Radio.ui.request('messagesview:collection');
					var message = collection.findWhere({id: this.draftUID});
					if (message) {
						message.set({id: data.uid});
						collection.set([message], {remove: false});
					}
				}
				_this.draftUID = data.uid;
			}, console.error.bind(this));
			return false;
		},
		setReplyBody: function(from, date, text) {
			var minutes = date.getMinutes();

			this.$('.message-body').first().text(
				'\n\n\n' +
				from + ' – ' +
				$.datepicker.formatDate('D, d. MM yy ', date) +
				date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
				text.replace(/\n/g, '\n> ')
				);

			this.setAutoSize(false);
			// Expand reply message body on click
			var _this = this;
			this.$('.message-body').click(function() {
				_this.setAutoSize(true);
			});
		},
		focusTo: function() {
			this.$el.find('input.to').focus();
		},
		setTo: function(value) {
			this.$el.find('input.to').val(value);
		},
		focusSubject: function() {
			this.$el.find('input.subject').focus();
		},
		onAutoComplete: function(e) {
			var elem = $(e.target);
			function split(val) {
				return val.split(/,\s*/);
			}

			function extractLast(term) {
				return split(term).pop();
			}
			if (!elem.data('autocomplete')) {
				// If the autocomplete wasn't called yet:
				// don't navigate away from the field on tab when selecting an item
				var prevUID = false;

				elem.bind('keydown', function(event) {
					if (event.keyCode === $.ui.keyCode.TAB &&
						typeof elem.data('autocomplete') !== 'undefined' &&
						elem.data('autocomplete').menu.active) {
						event.preventDefault();
					}
				}).autocomplete({
					source: function(request, response) {
						$.getJSON(
							OC.generateUrl('/apps/mail/autoComplete'),
							{
								term: extractLast(request.term)
							}, response);
					},
					search: function() {
						// custom minLength
						var term = extractLast(this.value);
						return term.length >= 2;
					},
					response: function() {
						// Reset prevUid
						prevUID = false;
					},
					focus: function() {
						// prevent value inserted on focus
						return false;
					},
					select: function(event, ui) {
						var terms = split(this.value);
						// remove the current input
						terms.pop();
						// add the selected item
						terms.push(ui.item.value);
						// add placeholder to get the comma-and-space at the end
						terms.push('');
						this.value = terms.join(', ');
						return false;
					}
				}).
					data('ui-autocomplete')._renderItem = function(
					$ul, item) {
					var $item = $('<li/>');
					var $row = $('<a/>');

					$row.addClass('mail-recipient-autocomplete');

					var $placeholder;
					if (prevUID === item.id) {
						$placeholder = $('<div/>');
						$placeholder.addClass('avatar');
						$row.append($placeholder);
					} else if (item.photo && item.photo !== null) {
						var $avatar = $('<img/>');
						$avatar.addClass('avatar');
						$avatar.height('32px');
						$avatar.width('32px');
						$avatar.attr('src', item.photo);
						$row.append($avatar);
					} else {
						$placeholder = $('<div/>');
						$placeholder.imageplaceholder(item.label || item.value);
						$placeholder.addClass('avatar');
						$row.append($placeholder);
					}

					prevUID = item.id;

					$row.append($('<span>').text(item.label || item.value));

					$item.append($row);
					$item.appendTo($ul);
					return $item;
				};
			}
		},
		buildAliases: function() {
			var aliases = [];
			var id = 1;

			this.accounts.forEach(function(account) {
				var json = account.toJSON();
				// add Primary email address
				aliases.push({
					id: id++,
					accountId: json.accountId,
					aliasId: null,
					emailAddress: json.emailAddress,
					name: json.name
				});
				// add Aliases email adresses
				for (var x in json.aliases) {
					aliases.push({
						id: id++,
						accountId: json.aliases[x].accountId,
						aliasId: json.aliases[x].id,
						emailAddress: json.aliases[x].alias,
						name: json.aliases[x].name
					});
				}
			});
			this.aliases = aliases;
			return aliases;
		},
		findAliasById: function(id) {
			return _.find(this.aliases, function(alias) {
				return parseInt(alias.id) === parseInt(id);
			});
		},
		defaultMailSelect: function() {
			var alias = null;
			if (!this.isReply()) {
				if (require('state').currentAccount.get('accountId') !== -1) {
					alias = _.find(this.aliases, function(alias) {
						return alias.emailAddress === require('state').currentAccount.get('email');
					});
				} else {
					var firstAccount = this.accounts.filter(function(
						account) {
						return account.get('accountId') !== -1;
					})[0];
					alias = _.find(this.aliases, function(alias) {
						return alias.emailAddress === firstAccount.get('emailAddress');
					});
				}
			} else {
				var toEmail = this.data.toEmail;
				alias = _.find(this.aliases, function(alias) {
					return alias.emailAddress === toEmail;
				});
			}
			if (alias) {
				this.$('.mail-account').val(alias.id);
			}
		},
		/**
		 * Checke that all attachments are valid.
		 * If there is some LocalAttachments stil pending, ongoing or that failed,
		 * This method will return false.
		 * If there is no LocalAttachments, or if they are are all sent,
		 * this method will return true.
		 * @return {boolean} all attachments valid
		 */
		checkAllAttachmentsValid: function() {
			var allAttachmentsValid = true;
			var len = this.attachments.length;
			for (var i = 0; i < len; i++) {
				/* We check all the attachments here */
				var attachment = this.attachments.models[i];
				var uploadStatus = attachment.get('uploadStatus');
				var isLocalUpload = (uploadStatus !== undefined);
				/* If at least one attachment is a local upload and */
				/* not a success (==3), we disable the send button */
				if (isLocalUpload && uploadStatus < 3) {
					allAttachmentsValid = false;
				}
			}
			return allAttachmentsValid;
		}
	});

});
