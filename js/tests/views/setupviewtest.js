/* global spyOn */

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
	'radio',
	'views/setupview'
], function(Radio, SetupView) {

	describe('Setup view', function() {
		var view,
			loadingPromise,
			resolvePromise,
			rejectPromise;

		/**
		 * @param {Object} config
		 * @returns {Promise}
		 */
		function triggerFormSubmit(config) {
			return view.onChildviewFormSubmit(config);
		}

		beforeEach(function() {
			view = new SetupView();
			loadingPromise = new Promise(function(resolve, reject) {
				resolvePromise = resolve;
				rejectPromise = reject;
			});
		});

		it('shows the setup form', function() {
			view.render();
			expect(view.$el.html()).toContain('Connect your mail account');
		});

		it('shows a loading view while the account is being created', function() {
			spyOn(Radio.account, 'request').and.returnValue(loadingPromise);
			spyOn(Radio.ui, 'trigger');

			// Submit -> show loading view
			triggerFormSubmit({});
			expect(Radio.account.request).toHaveBeenCalledWith('create', {});
			expect(view.$el.html()).toContain('Setting up your account');
		});

		it('show the form again after an error occurred', function(done) {
			spyOn(Radio.account, 'request').and.returnValue(loadingPromise);
			spyOn(Radio.ui, 'trigger');
			var config = {
				accountName: 'User',
				emailAddress: 'user@example.com',
				password: '123456',
				imapHost: 'imap.example.com',
				imapUser: 'iuser@example.com',
				imapPassword: 'i1234',
				smtpHost: 'smtp.example.com',
				smtpUser: 'suser@example.com',
				smtpPassword: 's1234',
				autoDetect: false
			};

			// Reject loading promise -> show error view
			rejectPromise('Some error');
			triggerFormSubmit(config).then(function() {
				expect(Radio.account.request).toHaveBeenCalledWith('create', config);
				expect(view.$el.html()).toContain('Connect your mail account');
				expect(view.$el.html()).toContain('User');
				expect(view.$el.html()).toContain('user@example.com');
				expect(view.$el.html()).toContain('123456');
				
				expect(view.$el.html()).toContain('imap.example.com');
				expect(view.$el.html()).toContain('iuser@example.com');
				expect(view.$el.html()).toContain('i1234');
				expect(view.$el.html()).toContain('smtp.example.com');
				expect(view.$el.html()).toContain('suser@example.com');
				expect(view.$el.html()).toContain('s1234');
				done();
			});
		});
	});

});