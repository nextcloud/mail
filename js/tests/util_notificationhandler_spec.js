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
	'util/notificationhandler',
	'radio'
], function(NotificationHandler, Radio) {

	describe('NotificationHandler', function() {
		var notification;
		var originalNotification;

		function getNotificationMock() {
			var mock = function(title, options) {
				this.title = title;
				this.options = options;
				// Hack to get ref to notification object
				notification = this;
			};
			mock.prototype = {
				getTitle: function() {
					return this.title;
				},
				getOptions: function() {
					return this.options;
				},
				close: function() {

				}
			};
			return mock;
		}


		beforeEach(function() {
			originalNotification = window.Notification;
			jasmine.clock().install();
		});

		afterEach(function() {
			window.Notification = originalNotification;
			jasmine.clock().uninstall();
		});

		it('should do nothing if notifications are not supported by the browser', function() {
			window.Notification = undefined;

			NotificationHandler.showNotification('a', 'b');

			// A new notification should have been created
			expect(notification).toBe(undefined);
		});

		it('should construct and show a new notification', function() {
			window.Notification = getNotificationMock();
			spyOn(Radio.navigation, 'trigger');

			NotificationHandler.showNotification('a', 'b');

			// A new notification should have been created
			expect(notification).not.toBe(undefined);

			// Check click handler
			expect(Radio.navigation.trigger).not.toHaveBeenCalled();
			expect(notification.onclick).not.toBe(undefined);
		});

		it('should close the notification after 5 seconds', function() {
			window.Notification = getNotificationMock();
			spyOn(Radio.navigation, 'trigger');

			NotificationHandler.showNotification('a', 'b');

			// A new notification should have been created
			expect(notification).not.toBe(undefined);

			spyOn(notification, 'close');
			expect(notification.close).not.toHaveBeenCalled();
			jasmine.clock().tick(2000);
			expect(notification.close).not.toHaveBeenCalled();
			jasmine.clock().tick(3100);
			expect(notification.close).toHaveBeenCalled();
		});
	});
});
