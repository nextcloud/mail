/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var Backbone = require('backbone');

	/**
	 * @class Folder
	 */
	var Folder = Backbone.Model.extend({
		messages: undefined,
		account: undefined,
		folder: undefined,
		folders: undefined,
		defaults: {
			open: false,
			folders: [],
			messagesLoaded: false
		},

		initialize: function() {
			var FolderCollection = require('models/foldercollection');
			var MessageCollection = require('models/messagecollection');
			var UnifiedMessageCollection = require('models/unifiedmessagecollection');
			this.account = this.get('account');
			this.unset('account');
			this.folders = new FolderCollection(this.get('folders') || []);
			this.folders.forEach(_.bind(function(folder) {
				folder.account = this.account;
			}, this));
			this.unset('folders');
			if (this.account && this.account.get('isUnified') === true) {
				this.messages = new UnifiedMessageCollection();
			} else {
				this.messages = new MessageCollection();
			}
		},

		toggleOpen: function() {
			this.set({open: !this.get('open')});
		},

		/**
		 * @param {Message} message
		 */
		addMessage: function(message) {
			if (this.account.id !== -1) {
				// Non-unified folder messages should keep their source folder
				message.folder = this;
			}
			message = this.messages.add(message);
			if (this.account.id === -1) {
				message.set('unifiedId', this.messages.getUnifiedId(message));
			}
			return message;
		},

		/**
		 * @param {Array<Message>} messages
		 */
		addMessages: function(messages) {
			var _this = this;
			return _.map(messages, _this.addMessage, this);
		},

		/**
		 * @param {Folder} folder
		 */

		addFolder: function(folder) {
			folder = this.folders.add(folder);
			folder.account = this.account;
		},

		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Folder;
});
