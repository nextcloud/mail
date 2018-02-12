/* global spyOn, expect */

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


define(['jquery',
		'views/foldercontent',
		'models/folder',
		'models/account',
		'models/message',
		'radio'],
	function($, FolderContent, Folder, Account, Message, Radio) {

	var account;
	var folder;
	var message;
	var view;

	beforeEach(function () {
		account = new Account();
		folder = new Folder({
			account: account
		});
		message = new Message({
			from: [
				{
					label: 'Christoph Wurst',
					email: 'christoph@domain.tld'
				}
			]
		});
		folder.addMessage(message);
		view = new FolderContent({
			account: account,
			folder: folder,
			searchQuery: undefined
		});

		spyOn(Radio.avatar, 'request').and.callFake(function() {
			return Promise.resolve(undefined);
		});
	});

	describe('On mobile phones, FolderContent', function () {
		beforeEach(function () {
			// just make the screen small
			$('body').css('width', '600px');
			$(window).trigger('resize');
			view.render();
			view.bindUIElements();
		});

		it ('should exist', function () {
			expect(view).toBeDefined();
		});

		it ('should not mark first email as read on folder view', function() {
			spyOn(Radio.message, 'trigger');
			view.markMessageAsRead(message);
			expect(Radio.message.trigger).not.toHaveBeenCalled();
		});
	});

	describe('On computers, FolderContent', function () {
		beforeEach(function () {
			// just make the screen "bigger" than a mobile phone
			$('body').css('width', '1024px');
			$(window).trigger('resize');
			view.render();
			view.bindUIElements();
		});

		it ('should exist', function () {
			expect(view).toBeDefined();
		});

		it ('should mark first email as read on folder view', function() {
			spyOn(Radio.message, 'trigger');
			view.markMessageAsRead(message);
			expect(Radio.message.trigger).toHaveBeenCalledWith(
				'flag', message, 'unseen', false
			);
		});
	});
});
