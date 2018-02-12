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
	'service/avatarservice'
], function(AvatarService) {
	'use strict';

	describe('AvatarService', function() {
		var server;

		beforeEach(function() {
			server = sinon.fakeServer.create();
		});

		afterEach(function() {
			server.restore();
		});

		it('does not load the image if no avatar is available', function(done) {
			var loading = AvatarService.loadAvatar('user@domain.com');

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
					404,
					{
						'Content-Type': 'application/json'
					});

			loading.then(function(url) {
				expect(url).toBe(undefined);
				done();
			}).catch(done.fail);
		});

		it('does load the image if an avatar is available', function(done) {
			var loading = AvatarService.loadAvatar('user@domain.com');

			expect(server.requests.length).toBe(1);
			server.requests[0].respond(
					200,
					{
						'Content-Type': 'application/json'
					},
					JSON.stringify({
						isExternal: true,
						mime: 'image/jpeg',
						url: 'https://domain.com/favicon.ico'
					}));

			loading.then(function(url) {
				expect(url).not.toBe(undefined);
				done();
			}).catch(done.fail);
		});
	});
});
