/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

define(function(require) {
	'use strict';

	var Backbone = require('backbone');
	var Folder = require('models/folder');

	/**
	 * @class FolderCollection
	 */
	var FolderCollection = Backbone.Collection.extend({
		model: Folder
	});

	return FolderCollection;
});
