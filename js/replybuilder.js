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

define(function(require) {
	'use strict';

	var _ = require('underscore');

	var RecipientType = Object.seal({
		None: 0,
		To: 1,
		Cc: 2
	});

	var buildReply = function(message, messageBody) {
		var recipientType = RecipientType.None;
		var ownAddress = message.folder.account.get('emailAddress');
		var isOwnAddress = function(a) {
			return a.email === ownAddress;
		};
		var isNotOwnAddress = _.negate(isOwnAddress);

		// Locate why we received this message
		// Can be in 'to', 'cc' or unknown
		var replyingAddress = _.find(messageBody.get('to'), isOwnAddress);
		if (!_.isUndefined(replyingAddress)) {
			recipientType = RecipientType.To;
		} else {
			replyingAddress = _.find(messageBody.get('cc'), isOwnAddress);
			if (!_.isUndefined(replyingAddress)) {
				recipientType = RecipientType.Cc;
			} else {
				replyingAddress = {
					label: ownAddress,
					email: ownAddress
				};
			}
		}

		var to = [];
		var cc = [];
		if (recipientType === RecipientType.To) {
			// Send to everyone except yourself plus the original sender
			to = messageBody.get('to').filter(isNotOwnAddress);
			to = to.concat(messageBody.get('from'));

			// CC remains the same
			cc = messageBody.get('cc');
		} else if (recipientType === RecipientType.Cc) {
			// Send to the same people plus the sender
			to = messageBody.get('to').concat(messageBody.get('from'));

			// All CC values are being kept except the replying address
			cc = messageBody.get('cc').filter(isNotOwnAddress);
		} else {
			// Send to the same recipient and the sender -> answer all
			to = messageBody.get('to');
			to = to.concat(messageBody.get('from'));

			// Keep CC values
			cc = messageBody.get('cc');
		}

		return {
			to: to,
			from: replyingAddress ? [replyingAddress] : [],
			fromEmail: message.folder.account.get('emailAddress'), // TODO: alias?
			cc: cc,
			body: ''
		};
	};

	return {
		buildReply: buildReply
	};

});
