/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2017
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var Backbone = require('backbone');
	var FolderCollection = require('models/foldercollection');
	var AliasesCollection = require('models/aliasescollection');
	var OC = require('OC');

	/**
	 * @class Account
	 */
	var Account = Backbone.Model.extend({
		defaults: {
			aliases: [],
			specialFolders: [],
			isUnified: false
		},
		idAttribute: 'accountId',
		url: function() {
			return OC.generateUrl('apps/mail/api/accounts');
		},
		initialize: function() {
			this.folders = new FolderCollection();
			this.set('aliases', new AliasesCollection(this.get('aliases')));
		},
		_getFolderByIdRecursively: function(folder, folderId) {
			if (!folder) {
				return null;
			}

			if (folder.get('id') === folderId) {
				return folder;
			}

			var subFolders = folder.folders;
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
		/**
		 * @param {Folder} folder
		 * @returns {undefined}
		 */
		addFolder: function(folder) {
			folder.account = this;
			this.folders.add(folder);
		},
		getFolderById: function(folderId) {
			if (!this.folders) {
				return undefined;
			}
			for (var i = 0; i < this.folders.length; i++) {
				var result = this._getFolderByIdRecursively(this.folders.at(i), folderId);
				if (result) {
					return result;
				}
			}
			return undefined;
		},
		getSpecialFolder: function() {
			if (!this.folders) {
				return undefined;
			}
			return _.find(this.folders, function(folder) {
				// TODO: handle special folders in subfolder properly
				if (folder.get('specialRole') === 'draft') {
					return true;
				}
			});
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
