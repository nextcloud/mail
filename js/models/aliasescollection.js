/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim  2016
 */

define(function(require) {
	'use strict';

	var Backbone = require('backbone');
	var Alias = require('models/alias');

	/**
	 * @class AliasesCollection
	 */
	var AliasesCollection = Backbone.Collection.extend({
		model: Alias
	});

	return AliasesCollection;
});
