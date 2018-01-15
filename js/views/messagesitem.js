/* global Promise */

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

	var $ = require('jquery');
	require('jquery-ui/ui/widgets/draggable');
	var _ = require('underscore');
	var OC = require('OC');
	var Marionette = require('backbone.marionette');
	var Radio = require('radio');
	var MessageTemplate = require('templates/message-list-item.html');
	var imageplaceholder = require('views/imageplaceholder');
	var tooltip = require('views/tooltip');

	return Marionette.View.extend({
		template: MessageTemplate,
		ui: {
			iconDelete: '.action.delete',
			star: '.star'
		},
		events: {
			'click .action.delete': 'deleteMessage',
			'click .mail-message-header': 'openMessage',
			'click .star': 'toggleMessageStar'
		},
		modelEvents: {
			change: 'render'
		},

		/**
		 * Get the sender/recipient label as string
		 *
		 * @returns {String}
		 */
		_getMessageLabel: function() {
			var sendRec = [];
			if (this.model.folder.get('specialRole') === 'sent') {
				sendRec = this.model.get('to');
			} else {
				sendRec = this.model.get('from');
			}

			switch (sendRec.length) {
				case 0:
					return '-';
				case 1:
					return sendRec[0].label;
				default:
					return sendRec[0].label + ' ' + t('mail', '& others');
			}
		},

		serializeModel: function() {
			var json = this.model.toJSON();
			json.isUnified = require('state').currentAccount && require('state').currentAccount.get('isUnified');
			json.sender = this.model.get('from')[0];
			json.label = this._getMessageLabel();
			return json;
		},
		onRender: function() {
			// Get rid of that pesky wrapping-div.
			// Assumes 1 child element present in template.
			this.$el = this.$el.children();
			// Unwrap the element to prevent infinitely
			// nesting elements during re-render.
			this.$el.unwrap();
			this.setElement(this.$el);

			var displayName = this.model.get('from')[0].label;
			// Don't show any placeholder if 'from' isn't set
			if (displayName) {
				_.each(this.$el.find('.avatar'), function(a) {
					$(a).height('32px');
					imageplaceholder(a, displayName, displayName);
				});
			}

			var _this = this;
			var dragScope = 'folder-' + this.model.folder.account.get('accountId');
			this.$el.draggable({
				appendTo: '#content-wrapper',
				scope: dragScope,
				helper: function() {
					var el = $('<div class="icon-mail"></div>');
					el.data('folderId', _this.model.folder.get('id'));
					el.data('messageId', _this.model.get('id'));
					return el;
				},
				cursorAt: {
					top: -5,
					left: -5
				},
				revert: 'invalid'
			});

			tooltip('.action.delete', {placement: 'left'});

			this._fetchAvatar();
		},

		/**
		 * @private
		 */
		_fetchAvatar: function() {
			var url = OC.generateUrl('/apps/mail/api/avatars/url/{email}', {
				email: this.model.get('fromEmail')
			});

			Promise.resolve($.ajax(url)).then(function(avatar) {
				if (avatar.isExternal) {
					this.model.set('senderImage', OC.generateUrl('/apps/mail/api/avatars/image/{email}', {
						email: this.model.get('fromEmail')
					}));
				} else {
					this.model.set('senderImage', avatar.url);
				}
			}.bind(this));
		},

		toggleMessageStar: function(event) {
			event.stopPropagation();

			var starred = this.model.get('flags').get('flagged');

			// directly change star state in the interface for quick feedback
			if (starred) {
				this.getUI('star')
						.removeClass('icon-starred')
						.addClass('icon-star');
			} else {
				this.getUI('star')
						.removeClass('icon-star')
						.addClass('icon-starred');
			}

			Radio.message.trigger('flag', this.model, 'flagged', !starred);
		},
		openMessage: function(event) {
			event.stopPropagation();
			$('#mail-message').removeClass('hidden-mobile');
			// make sure message is marked as read when clicked on it
			Radio.message.trigger('flag', this.model, 'unseen', false);
			Radio.message.trigger('load', this.model.folder.account, this.model.folder, this.model, {
				force: true
			});
		},
		deleteMessage: function(event) {
			event.stopPropagation();
			var message = this.model;

			this.getUI('iconDelete').removeClass('icon-delete').addClass('icon-loading-small');
			$('.tooltip').remove();

			this.$el.addClass('transparency').slideUp(function() {
				$('.tooltip').remove();

				// really delete the message
				Radio.folder.request('message:delete', message, require('state').currentFolder);

				// manually trigger mouseover event for current mouse position
				// in order to create a tooltip for the next message if needed
				if (event.clientX) {
					$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
				}
			});
		}
	});
});
