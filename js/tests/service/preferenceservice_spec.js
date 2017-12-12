/* global sinon, expect */

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define([
	'service/preferenceservice'
], function(PreferenceService) {
	'use strict';

	describe('PreferenceService', function() {
		var server;

		beforeEach(function() {
			server = sinon.fakeServer.create();
		});

		afterEach(function() {
			server.restore();
		});

		it('retrieves a preference from the back-end', function(done) {
			var retrieving = PreferenceService.getPreference('test');

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
					200,
					{
						'Content-Type': 'application/json'
					},
					JSON.stringify({
						value: '123'
					})
					);

			retrieving.then(function(value) {
				expect(value).toBe('123');

				done();
			}).catch(done.fail);
		});

		it('retrieves an error from the back-end', function(done) {
			var retrieving = PreferenceService.getPreference('test');

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(500, null, null);

			retrieving.then(done.fail).catch(done);
		});

		it('stores a preference on the back-end', function(done) {
			var storing = PreferenceService.savePreference('test', '123');

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
					200,
					{
						'Content-Type': 'application/json'
					},
					JSON.stringify({
						value: '123'
					})
					);

			storing.then(function(value) {
				expect(value).toBe('123');

				done();
			}).catch(done.fail);
		});
	});
});
