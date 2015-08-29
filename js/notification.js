/* global OC */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	var _ = require('underscore');

	if (_.isUndefined(OC.Notification.showTemporary)) {

		/**
		 * Shows a notification that disappears after x seconds, default is
		 * 7 seconds
		 * @param {string} text Message to show
		 * @param {array} [options] options array
		 * @param {int} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
		 * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
		 */
		OC.Notification.showTemporary = function(text, options) {
			var defaults = {
				isHTML: false,
				timeout: 7
			};
			options = options || {};
			// merge defaults with passed in options
			_.defaults(options, defaults);

			// clear previous notifications
			OC.Notification.hide();
			if (OC.Notification.notificationTimer) {
				clearTimeout(OC.Notification.notificationTimer);
			}

			if (options.isHTML) {
				OC.Notification.showHtml(text);
			} else {
				OC.Notification.show(text);
			}

			if (options.timeout > 0) {
				// register timeout to vanish notification
				OC.Notification.notificationTimer = setTimeout(OC.Notification.hide, (options.timeout * 1000));
			}
		};
	}
});
