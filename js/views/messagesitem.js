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
	var Marionette = require('backbone.marionette');
	var CrashReport = require('crashreport');
	var Radio = require('radio');
	var MessageTemplate = require('templates/message-list-item.html');
	var imageplaceholder = require('views/imageplaceholder');

	return Marionette.View.extend({
		template: MessageTemplate,
		ui: {
			self: '.app-content-list-item',
			iconDelete: '.action.delete',
			star: '.star',
			menu: '.popovermenu',
			toggleMenu: '.toggle-menu'
		},
		events: {
			'click @ui.self': 'openMessage',
			'click @ui.star': 'toggleMessageStar',
			'click @ui.toggleMenu': 'toggleActionsMenu',
			'click .action.delete': 'deleteMessage',
			'click .action.toggle-read': 'toggleMessageRead'
		},
		modelEvents: {
			change: 'render'
		},

		/** @type {bool} */
		actionsMenuShown: false,

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
			json.isUnified =
				require('state').currentAccount &&
				require('state').currentAccount.get('isUnified');
			json.sender = this.model.get('from')[0];
			json.label = this._getMessageLabel();
			return json;
		},
		onRender: function() {
			var from = this.model.get('from')[0];
			// Don't show any placeholder if 'from' isn't set
			if (from) {
				_.each(this.$el.find('.avatar'), function(a) {
					$(a).height('32px');
					imageplaceholder(a, from.label, from.label);
				});
			} else {
				var $avatar = this.$('.avatar');
				$avatar.height('32px');
				imageplaceholder($avatar, '?', '?');
			}

			var _this = this;
			var dragScope =
				'folder-' + this.model.folder.account.get('accountId');
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

			this.listenTo(Radio.ui, 'document:click', function(event) {
				var target = $(event.target);
				var toggleDropdown = this.getUI('toggleMenu');
				if (!toggleDropdown.is(target)) {
					// Click was not triggered by toggle menu -> close menu
					this.actionsMenuShown = false;
					this.toggleMenuClass();
				}
			});

			this._fetchAvatar();
		},

		/**
		 * @private
		 */
		_fetchAvatar: function() {
			Radio.avatar
				.request('avatar', this.model.get('fromEmail'))
				.then(
					function(url) {
						if (url) {
							this.model.set('senderImage', url);
						}
					}.bind(this)
				)
				.catch(CrashReport.report);
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
			// Ignore the event if the actions menu was targeted
			if ($(event.target).hasClass('toggle-menu')) {
				return;
			}
			event.stopPropagation();
			$('.app-content-list').addClass('showdetails');
			// make sure message is marked as read when clicked on it
			Radio.message.trigger('flag', this.model, 'unseen', false);
			Radio.message.trigger(
				'load',
				this.model.folder.account,
				this.model.folder,
				this.model,
				{
					force: true
				}
			);
		},

		toggleActionsMenu: function() {
			this.actionsMenuShown = !this.actionsMenuShown;
			this.toggleMenuClass();
		},

		toggleMenuClass: function() {
			this.getUI('menu').toggleClass('open', this.actionsMenuShown);
		},

		toggleMessageRead: function(event) {
			event.stopPropagation();
			var message = this.model;
			if (message) {
				var state = message.get('flags').get('unseen');
				Radio.message.trigger('flag', message, 'unseen', !state);
			}
			this.toggleActionsMenu();
		},

		deleteMessage: function(event) {
			event.stopPropagation();
			var message = this.model;

			this.$el.addClass('transparency').slideUp(function() {
				$('.tooltip').remove();

				// really delete the message
				Radio.folder.request(
					'message:delete',
					message,
					require('state').currentFolder
				);

				// manually trigger mouseover event for current mouse position
				// in order to create a tooltip for the next message if needed
				if (event.clientX) {
					$(
						document.elementFromPoint(event.clientX, event.clientY)
					).trigger('mouseover');
				}
			});

			this.toggleActionsMenu();
		}
	});
});
