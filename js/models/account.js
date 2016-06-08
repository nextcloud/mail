/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Backbone = require('backbone');
	var FolderCollection = require('models/foldercollection');
	var AliasesCollection = require('models/aliasescollection');
	var OC = require('OC');

	/**
	 * @class Account
	 */
	var Account = Backbone.Model.extend({
		defaults: {
			folders: [],
			aliases: []
		},
		idAttribute: 'accountId',
		url: OC.generateUrl('apps/mail/accounts'),
		initialize: function() {
			this.set('folders', new FolderCollection(this.get('folders')));
			this.set('aliases', new AliasesCollection(this.get('aliases')));
		},
		_getFolderByIdRecursively: function(folder, folderId) {
			if (!folder) {
				return null;
			}

			if (folder.get('id') === folderId) {
				return folder;
			}

			var subFolders = folder.get('folders');
			if (!subFolders) {
				return null;
			}
			for (var i = 0; i < subFolders.length; i++) {
				var subFolder = this._getFolderByIdRecursively(subFolders.at(i), folderId);
				if (subFolder) {
					return subFolder;
				}
			}

			return null;
		},
		getFolderById: function(folderId) {
			var folders = this.get('folders');
			if (!folders) {
				return undefined;
			}
			for (var i = 0; i < folders.length; i++) {
				var result = this._getFolderByIdRecursively(folders.at(i), folderId);
				if (result) {
					return result;
				}
			}
			return undefined;
		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.folders && data.folders.toJSON) {
				data.folders = data.folders.toJSON();
			}
			if (data.aliases && data.aliases.toJSON) {
				data.aliases = data.aliases.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});

	return Account;
});
