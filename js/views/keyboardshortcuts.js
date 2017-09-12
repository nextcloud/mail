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
	var Radio = require('radio');

	var KeyboardShortcutView = Marionette.View.extend({
		id: 'keyboardshortcut',
		template: KeyboardShortcutTemplate,
		options: undefined,
		initialize: function(options) {
			this.options = options;
			this.listenTo(Radio.ui, 'composer:show', this.onShowComposer);
			// enable the new message button (for navigation to composer)
			$('#mail_new_message').prop('disabled', false);
		},
		onShowComposer: function() {
			var accountId = this.options.account.get('id');
			var folderId = this.options.account.folders.first().get('id');
			Radio.navigation.trigger('folder', accountId, folderId, true, true);
		}
	});

	return KeyboardShortcutView;
});
