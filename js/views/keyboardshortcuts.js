/**
 * @author Steffen Lindner <mail@steffen-lindner.de>
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
	'strict';

	var Marionette = require('backbone.marionette');
	var KeyboardShortcutTemplate = require('templates/keyboard-shortcuts.html');

	var KeyboardShortcutView = Marionette.View.extend({
		id: 'keyboardshortcut',
		template: KeyboardShortcutTemplate
	});

	return KeyboardShortcutView;
});
