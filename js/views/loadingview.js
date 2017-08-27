/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var Marionette = require('backbone.marionette');
	var LoadingTemplate = require('templates/loading.html');

	/**
	 * @class LoadingView
	 */
	var LoadingView = Marionette.View.extend({
		template: LoadingTemplate,
		templateContext: function() {
			return {
				hint: this.hint
			};
		},
		className: 'container',
		hint: '',
		initialize: function(options) {
			this.hint = options.text || '';
		}
	});

	return LoadingView;
});
