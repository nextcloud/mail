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

	var MessageCollection = require('models/messagecollection');

	/**
	 * @class UnifiedMessageCollection
	 */
	var UnifiedMessageCollection = MessageCollection.extend({

		modelId: function(attrs) {
			return attrs.unifiedId;
		},

		getUnifiedId: function(message) {
			return message.id + '-' + message.folder.id + '-' + message.folder.account.id;
		}

	});

	return UnifiedMessageCollection;
});
