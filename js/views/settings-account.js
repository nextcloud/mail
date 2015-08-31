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
	'use strict';

	var Marionette = require('marionette');
	var $ = require('jquery');
	var OC = require('OC');

	return Marionette.ItemView.extend({
		tagName: 'li',
		id: function() {
			return 'mail-account-' + this.model.get('accountId');
		},
		template: '#mail-settings-account',
		events: {
			'click .delete.action': 'onDelete'
		},
		onDelete: function(e) {
			e.stopPropagation();
			this.$el.removeClass('icon-delete').addClass('icon-loading-small');

			var accountId = this.model.get('accountId');

			$.ajax(OC.generateUrl('/apps/mail/accounts/{accountId}'), {
				data: {accountId: accountId},
				type: 'DELETE',
				success: function() {
					// Delete cached message lists
					require('app').Cache.removeAccount(accountId);

					// reload the complete page
					// TODO should only reload the app nav/content
					window.location.reload();
				},
				error: function() {
					OC.Notification.show(t('mail', 'Error while deleting account.'));
				}
			});
		}
	});
});
