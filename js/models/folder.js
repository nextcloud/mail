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
			this.account = this.get('account');
			this.unset('account');
			this.folders = new FolderCollection(this.get('folders') || []);
			this.folders.forEach(_.bind(function(folder) {
				folder.account = this.account;
			}, this));
			this.unset('folders');
			this.messages = new MessageCollection();
		},
		toggleOpen: function() {
			this.set({open: !this.get('open')});
		},
		/**
		 * @param {Message} message
		 * @returns {undefined}
		 */
		addMessage: function(message) {
			message.folder = this;
			this.messages.add(message);
		},
		/**
		 * @param {Array<Message>} message
		 * @returns {undefined}
		 */
		addMessages: function(messages) {
			var _this = this;
			_.each(messages, function(message) {
				_this.addMessage(message);
			});
		},
		/**
		 * @param {Folder} folder
		 * @returns {undefined}
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
