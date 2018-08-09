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
	var ErrorTemplate = require('templates/error.html');

	var ErrorView = Marionette.View.extend({

		id: 'emptycontent',

		className: 'app-content-details',

		template: ErrorTemplate,

		_text: undefined,

		_icon: undefined,

		_canRetry: undefined,

		events: {
			'click .retry': '_onRetry'
		},

		templateContext: function() {
			return {
				text: this._text,
				icon: this._icon,
				canRetry: this._canRetry
			};
		},

		initialize: function(options) {
			this._text = options.text || t('mail', 'An unknown error occurred');
			this._icon = options.icon || 'icon-mail';
			this._canRetry = options.canRetry || false;
		},

		_onRetry: function() {
			this.triggerMethod('retry');
		}
	});

	return ErrorView;
});
