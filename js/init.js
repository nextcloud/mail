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
	var $ = require('jquery');
	var OC = require('OC');
	var App = require('app');

	$(function() {
		// Conigure CSRF token
		$.ajaxSetup({
			headers: {
				requesttoken: OC.requestToken
			}
		});

		// Start app when the page is ready
		console.log('Starting Mail …');
		App.start();
	});
});
