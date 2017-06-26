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

		});
	});
});
