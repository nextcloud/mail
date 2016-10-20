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


define(['views/settings', 'views/helper'], function(SettingsView) {

	describe('SettingsView', function () {

		var settingsview;

		beforeEach(function () {
			settingsview = new SettingsView({});
		});

		it('has the account and shortcut functions', function () {
			    expect(typeof settingsview.showKeyboardShortcuts).toBe("function");
			    expect(typeof settingsview.addAccount).toBe("function")
		});

		it('produces the correct HTML', function () {
			settingsview.render();

			html = settingsview.el.innerHTML.trim();
			expected_html = '<div id="mailsettings">\n\t<ul id="settings-accounts" class="mailaccount-list">\n\t</ul>\n' +
				'\t<a id="new_mail_account" class="button new-button" href="apps/mail/#setup">Add mail account</a>\n\n' +
				'\t<p><a id="keyboard-shortcuts" href="apps/mail/#keyboardShortcut">Keyboard shortcuts</a></p>\n' +
				'\t<p class="app-settings-hint">\n\t\tLooking to encrypt your emails? Install the <a href="https://www.mailvelope.com/" target="_blank">Mailvelope browser extension</a>!' +
				'\n\t</p>\n</div>';
			expect(html).toContain(expected_html);

		});
	});
});
