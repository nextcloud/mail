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

	var Handlebars = require('handlebars');
	var Marionette = require('marionette');
	var ErrorTemplate = require('text!templates/error.html');

	var ErrorView = Marionette.View.extend({
		id: 'emptycontent',
		className: 'container',
		template: Handlebars.compile(ErrorTemplate),
		templateContext: function() {
			return {
				text: this.text,
				icon: this.icon
			};
		},
		initialize: function(options) {
			this.text = options.text || t('mail', 'An unknown error occurred');
			this.icon = options.icon || 'icon-mail';
		}
	});

	return ErrorView;
});
