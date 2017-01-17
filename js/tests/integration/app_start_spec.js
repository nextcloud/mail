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

define([
	'app',
	'cache',
	'radio',
	'backbone',
	'controller/accountcontroller',
	'models/accountcollection'
], function(Mail, Cache, Radio, Backbone, AccountController,
	AccountCollection) {
	describe('App', function() {

		beforeEach(function() {
			jasmine.Ajax.install();
			$('testcontainer').remove();
			$('body')
				.append('testcontainer')
				.append(
					'<input type="hidden" id="config-installed-version" value="0.6.1">'
					+ '<input type="hidden" id="serialized-accounts" value="">'
					+ '<div id="user-displayname">Jane Doe</div>'
					+ '<div id="user-email">jane@doe.cz</div>'
					+ '<div id="app">'
					+ '	<div id="app-navigation" class="icon-loading">'
					+ '		<div id="mail-new-message-fixed"></div>'
					+ '		<ul>'
					+ '			<li id="app-navigation-accounts"></li>'
					+ '		</ul>'
					+ '		<div id="app-settings">'
					+ '			<div id="app-settings-header">'
					+ '				<button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>'
					+ '			</div>'
					+ '			<div id="app-settings-content"></div>'
					+ '		</div>'
					+ '	</div>'
					+ '	<div id="app-content">'
					+ '		<div class="mail-content container">'
					+ '			<div class="container icon-loading"></div>'
					+ '		</div>'
					+ '	</div>'
					+ '</div>');
		});

		afterEach(function() {
			jasmine.Ajax.uninstall();
		});

		it('starts', function() {
			var resolve;
			var accountsPromise = new Promise(function(res) {
				resolve = res;
			});

			spyOn(Cache, 'init');
			spyOn(Radio.ui, 'trigger');
			spyOn(Backbone.history, 'start');
			spyOn(AccountController, 'loadAccounts').and.callFake(function() {
				return accountsPromise;
			});

			// No ajax calls so far
			expect(jasmine.Ajax.requests.count()).toBe(0);

			// Let's go…
			Mail.start();

			expect(Cache.init).toHaveBeenCalled();
			expect(Radio.ui.trigger).toHaveBeenCalledWith('content:loading');

			var accounts = new AccountCollection([
				{
					accountId: 44,
					name: 'Jane Doe',
					email: 'jane@doe.se'
				}
			]);
			resolve(accounts);

			accountsPromise.then(function() {
				// The promise is resolved asynchronously, so we have to use the
				// promise here too
				expect(Backbone.history.start).toHaveBeenCalled();
			});
		});
	});
});
