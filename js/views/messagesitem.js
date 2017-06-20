/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');
	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var Radio = require('radio');
	var MessageTemplate = require('text!templates/message-list-item.html');

	return Marionette.View.extend({
		template: Handlebars.compile(MessageTemplate),
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
		serializeModel: function() {
			var json = this.model.toJSON();
			json.isUnified = require('state').currentAccount.get('isUnified');
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

			var displayName = this.model.get('from');
			// Don't show any placeholder if 'from' isn't set
			if (displayName) {
				_.each(this.$el.find('.avatar'), function(a) {
					$(a).height('32px');
					$(a).imageplaceholder(displayName, displayName);
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

			$('.action.delete').tooltip({placement: 'left'});
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
			var folder = this.model.folder;
			var account = folder.account;
			Radio.message.trigger('load', account, folder, this.model, {
				force: true
			});
		},
		deleteMessage: function(event) {
			event.stopPropagation();
			var thisModel = this.model;
			this.getUI('iconDelete').removeClass('icon-delete').addClass('icon-loading-small');
			$('.tooltip').remove();

			var folder = this.model.folder;
			var account = folder.account;
			var count = folder.get('total');
			folder.set('total', count - 1);

			var thisModelCollection = thisModel.collection;
			var index = thisModelCollection.indexOf(thisModel);
			// Select previous or first
			if (index === 0) {
				index = 1;
			} else {
				index = index - 1;
			}
			var nextMessage = thisModelCollection.at(index);
			if (require('state').currentMessage && require('state').currentMessage.get('id') === thisModel.id) {
				if (nextMessage) {
					Radio.message.trigger('load', account, folder, nextMessage);
				}
			}

			this.$el.addClass('transparency').slideUp(function() {
				$('.tooltip').remove();
				thisModelCollection.remove(thisModel);

				// manually trigger mouseover event for current mouse position
				// in order to create a tooltip for the next message if needed
				if (event.clientX) {
					$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
				}
			});

			// really delete the message
			Radio.message.request('delete', account, folder, this.model).catch(function() {
				// TODO: move to controller
				Radio.ui.trigger('error:show', t('mail', 'Error while deleting message.'));

				// Restore counter
				count = folder.get('total');
				folder.set('total', count + 1);

				// Add the message to the collection again
				folder.addMessage(this.model);
			});
		}
	});
});
