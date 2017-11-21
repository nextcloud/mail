/* global expect */

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
	'replybuilder',
	'models/message',
	'models/folder',
	'models/account',
	'backbone'
], function(ReplyBuilder, Message, Folder, Account, Backbone) {

	describe('ReplyBuilder', function() {

		var message, messageBody, folder, account;

		beforeEach(function() {
			message = new Message();
			messageBody = new Backbone.Model();
			folder = new Folder();
			account = new Account();
			account.addFolder(folder);
			folder.addMessage(message);
		});

		var createAddress = function(addr) {
			return {
				label: addr,
				email: addr
			};
		};

		var setEmail = function(message, address) {
			message.folder.account.set('emailAddress', address.email);
		};

		var assertSameAddressList = function(l1, l2) {
			var rawL1 = l1.map(function(a) {
				return a.email;
			});
			var rawL2 = l2.map(function(a) {
				return a.email;
			});
			rawL1.sort();
			rawL2.sort();
			expect(rawL1).toEqual(rawL2);
		};

		// b -> a to a -as b
		it('handles a one-on-one reply', function() {
			var a = createAddress('a@domain.tld');
			var b = createAddress('b@domain.tld');
			messageBody.set('from', [b]);
			messageBody.set('to', [a]);
			messageBody.set('cc', []);
			setEmail(message, a);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [a]);
			assertSameAddressList(reply.to, [b]);
			assertSameAddressList(reply.cc, []);
		});

		it('handles simple group reply', function() {
			var a = createAddress('a@domain.tld');
			var b = createAddress('b@domain.tld');
			var c = createAddress('c@domain.tld');
			messageBody.set('from', [a]);
			messageBody.set('to', [b, c]);
			messageBody.set('cc', []);
			setEmail(message, b);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [b]);
			assertSameAddressList(reply.to, [a, c]);
			assertSameAddressList(reply.cc, []);
		});


		it('handles group reply with CC', function() {
			var a = createAddress('a@domain.tld');
			var b = createAddress('b@domain.tld');
			var c = createAddress('c@domain.tld');
			var d = createAddress('d@domain.tld');
			messageBody.set('from', [a]);
			messageBody.set('to', [b, c]);
			messageBody.set('cc', [d]);
			setEmail(message, b);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [b]);
			assertSameAddressList(reply.to, [a, c]);
			assertSameAddressList(reply.cc, [d]);
		});

		it('handles group reply of CC address', function() {
			var a = createAddress('a@domain.tld');
			var b = createAddress('b@domain.tld');
			var c = createAddress('c@domain.tld');
			var d = createAddress('d@domain.tld');
			messageBody.set('from', [a]);
			messageBody.set('to', [b, c]);
			messageBody.set('cc', [d]);
			setEmail(message, d);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [d]);
			assertSameAddressList(reply.to, [a, b, c]);
			assertSameAddressList(reply.cc, []);
		});

		it('handles group reply of CC address with many CCs', function() {
			var a = createAddress('a@domain.tld');
			var b = createAddress('b@domain.tld');
			var c = createAddress('c@domain.tld');
			var d = createAddress('d@domain.tld');
			var e = createAddress('e@domain.tld');
			messageBody.set('from', [a]);
			messageBody.set('to', [b, c]);
			messageBody.set('cc', [d, e]);
			setEmail(message, e);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [e]);
			assertSameAddressList(reply.to, [a, b, c]);
			assertSameAddressList(reply.cc, [d]);
		});

		it('handles reply of message where the recipient is in the CC', function() {
			var ali = createAddress('ali@domain.tld');
			var bob = createAddress('bob@domain.tld');
			var me = createAddress('c@domain.tld');
			var dani = createAddress('d@domain.tld');

			messageBody.set('from', [ali]);
			messageBody.set('to', [bob]);
			messageBody.set('cc', [me, dani]);
			setEmail(message, me);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [me]);
			assertSameAddressList(reply.to, [ali, bob]);
			assertSameAddressList(reply.cc, [dani]);
		});

		it('handles jan\'s reply to nina\'s mesage to a mailing list', function() {
			var nina = createAddress('nina@nc.com');
			var list = createAddress('list@nc.com');
			var jan = createAddress('jan@nc.com');

			messageBody.set('from', [nina]);
			messageBody.set('to', [list]);
			messageBody.set('cc', []);
			setEmail(message, jan);

			var reply = ReplyBuilder.buildReply(message, messageBody);

			assertSameAddressList(reply.from, [jan]);
			assertSameAddressList(reply.to, [nina, list]);
			assertSameAddressList(reply.cc, []);
		});

	});

});
