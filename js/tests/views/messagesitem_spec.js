/**
 * @author Luc Calaresu <dev@calaresu.com>
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


define(['views/messagesitem',
		'models/message',
		'models/folder',
		'models/account',
		'radio'],
	function(MessagesItem, Message, Account, Folder, Radio) {

	describe('MessagesItem', function () {

		var view;
		var model;

		beforeEach(function () {
			// on local attachment, we use the LocalAttachment model
			model = new Message({
				id: 22
			});
			view = new MessagesItem({
				model: model
			});
		});

		describe('On mobile phones ', function () {
			beforeEach(function () {
				// just make the screen small
				$('body').css('width', '600px');
				$(window).trigger('resize');
			});

			it ('should mark an email as read when selected', function() {
				var event = jasmine.createSpyObj('event', ['stopPropagation']);
				spyOn(Radio.ui, 'trigger');
				spyOn(Radio.message, 'trigger');

				view.openMessage(event);

				expect(event.stopPropagation).toHaveBeenCalled();
				// check message is marked as read
				expect(Radio.ui.trigger).toHaveBeenCalledWith(
					'messagesview:messageflag:set', model.id, 'unseen', false
				);
				// check message has been opened
				expect(Radio.message.trigger).toHaveBeenCalled();
				expect(Radio.message.trigger.calls.mostRecent().args[0]).toEqual('load');
			});
		});

		describe('On computers', function () {
			beforeEach(function () {
				// just make the screen "bigger" than a mobile phone
				$('body').css('width', '1024px');
				$(window).trigger('resize');
			});

			it ('should mark an email as read when selected', function() {
				var event = jasmine.createSpyObj('event', ['stopPropagation']);
				spyOn(Radio.ui, 'trigger');
				spyOn(Radio.message, 'trigger');

				view.openMessage(event);

				expect(event.stopPropagation).toHaveBeenCalled();
				// check message is marked as read
				expect(Radio.ui.trigger).toHaveBeenCalledWith(
					'messagesview:messageflag:set', model.id, 'unseen', false
				);
				// check message has been opened
				expect(Radio.message.trigger).toHaveBeenCalled();
				expect(Radio.message.trigger.calls.mostRecent().args[0]).toEqual('load');
			});
		});
	});
});
