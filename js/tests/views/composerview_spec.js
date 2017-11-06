/* global expect, spyOn */

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
	'views/composerview',
	'models/accountcollection',
	'models/attachment',
	'models/localattachment',
	'state'
], function(ComposerView, AccountCollection, Attachment, LocalAttachment, State) {
	describe('ComposerView', function() {
		var accounts;

		beforeEach(function() {
			accounts = new AccountCollection([
				{
					accountId: 13,
					name: 'Jane Nextcloud',
					emailAddress: 'jane@nextcloud.com',
					folders: [
						{
							id: 'inbox'
						}
					]
				},
				{
					accountId: 14,
					name: 'John Nextcloud',
					emailAddress: 'john@nextcloud.com',
					folders: [
						{
							id: 'inbox'
						}
					]
				}
			]);

			State.currentAccount = accounts.first();
		});

		it('creates a view to composer a new message', function() {
			var view = new ComposerView({
				accounts: accounts
			});
			spyOn(view, 'saveDraft');

			expect(view.type).toBe('new');
			expect(view.isReply()).toBe(false);
			expect(view.account).toBe(accounts.at(0));
			expect(view.repliedMessage).toBeNull();
		});

		it('creates a reply composer', function() {
			var account = accounts.at(1);
			var folder = account.folders.first();
			var view = new ComposerView({
				accounts: accounts,
				account: account,
				folder: folder,
				type: 'reply'
			});
			spyOn(view, 'saveDraft');

			expect(view.type).toBe('reply');
			expect(view.isReply()).toBe(true);
			expect(view.account).toBe(account);
			expect(view.folder).toBe(folder);
		});

		it('doesn\'t have draft UID at creation', function() {
			var view = new ComposerView({
				accounts: accounts
			});
			spyOn(view, 'saveDraft');

			expect(view.draftUID).toBeUndefined();
		});

		it('creates the correct list of selectable accounts withoug aliases', function() {
			var view = new ComposerView({
				accounts: accounts
			});
			spyOn(view, 'saveDraft');

			var expected = [
				{
					id: 1,
					accountId: 13,
					aliasId: null,
					emailAddress: 'jane@nextcloud.com',
					name: 'Jane Nextcloud'
				},
				{
					id: 2,
					accountId: 14,
					aliasId: null,
					emailAddress: 'john@nextcloud.com',
					name: 'John Nextcloud'
				}
			];
			expect(view.buildAliases()).toEqual(expected);
		});

		it('renders correctly', function() {
			var view = new ComposerView({
				accounts: accounts
			});
			spyOn(view, 'saveDraft');

			view.render();

			var $el = view.$el;

			// Two accounts should be selectable
			expect($el.find('select.mail-account').
				children().length).toBe(2);
		});

		describe('with attachments', function () {
			var view;
			var localAttachment;

			beforeEach(function() {
				var accounts = new AccountCollection([
					{
						accountId: 13,
						name: 'Jane Nextcloud',
						emailAddress: 'jane@nextcloud.com',
						folders: [
							{
								id: 'inbox'
							}
						]
					}
				]);
				State.currentAccount = accounts.first();

				view = new ComposerView({
					accounts: accounts
				});
				spyOn(view, 'saveDraft');

				view.render();
				view.bindUIElements();

				localAttachment = new LocalAttachment({
					fileName: 'test.zip'
				});
			});

			it('calls onInputChanged and checkAllAttachmentsValid when the attachment list changed', function() {
				spyOn(view, 'onInputChanged');
				spyOn(view, 'checkAllAttachmentsValid');
				view.bindAttachments();
				view.attachments.add(localAttachment);
				expect(view.onInputChanged).toHaveBeenCalled();
				expect(view.checkAllAttachmentsValid).toHaveBeenCalled();
			});
			it('disables the send button when a local attachment is not valid', function() {
				// we put something into the 'To:' input, so the submit button can be activated
				view.$('.to').val('test@mailserver');
				view.onInputChanged();

				// and we chack how the button reacts when checkAllAttachmentsValid return false
				spyOn(view, 'checkAllAttachmentsValid').and.returnValue(false);
				expect(view.$('.submit-message').attr('disabled')).toBe(undefined);
				view.attachments.add(localAttachment);
				expect(view.$('.submit-message').attr('disabled')).toBe('disabled');
			});

			it('enables the send button when all local attachment are valid', function() {
				// we put something into the 'To:' input, so the submit button can be activated
				view.$('.to').val('test@mailserver');
				view.onInputChanged();

				// and we chack how the button reacts when checkAllAttachmentsValid return false
				spyOn(view, 'checkAllAttachmentsValid').and.returnValue(true);
				expect(view.$('.submit-message').attr('disabled')).toBe(undefined);
				view.attachments.add(localAttachment);
				expect(view.$('.submit-message').attr('disabled')).toBe(undefined);
			});

			describe('from local source (upload)', function () {
				it('detects when all local attachments are valid', function() {
					localAttachmentA = new LocalAttachment({
						fileName: 'test.zip',
						uploadStatus: 3       // success
					});
					localAttachmentB = new LocalAttachment({
						fileName: 'test2.zip',
						uploadStatus: 3       // success
					});
					view.attachments.add(localAttachmentA);
					view.attachments.add(localAttachmentA);
					expect(view.checkAllAttachmentsValid()).toBe(true);
				});

				it('detects when at least one local attachment is pending', function() {
					localAttachmentA = new LocalAttachment({
						fileName: 'test.zip',
						uploadStatus: 0       // pending
					});
					localAttachmentB = new LocalAttachment({
						fileName: 'test2.zip',
						uploadStatus: 3       // success
					});
					view.attachments.add(localAttachmentA);
					view.attachments.add(localAttachmentA);
					expect(view.checkAllAttachmentsValid()).toBe(false);
				});

				it('detects when at least one local attachment is ongoing', function() {
					localAttachmentA = new LocalAttachment({
						fileName: 'test.zip',
						uploadStatus: 1       // ongoing
					});
					localAttachmentB = new LocalAttachment({
						fileName: 'test2.zip',
						uploadStatus: 3       // success
					});
					view.attachments.add(localAttachmentA);
					view.attachments.add(localAttachmentA);
					expect(view.checkAllAttachmentsValid()).toBe(false);
				});

				it('detects when at least one local attachment is broken', function() {
					localAttachmentA = new LocalAttachment({
						fileName: 'test.zip',
						uploadStatus: 2       // error
					});
					localAttachmentB = new LocalAttachment({
						fileName: 'test2.zip',
						uploadStatus: 3       // success
					});
					view.attachments.add(localAttachmentA);
					view.attachments.add(localAttachmentA);
					expect(view.checkAllAttachmentsValid()).toBe(false);
				});
			});

			describe('from Files', function () {
				it('always consider attachments from Files as valid', function() {
					localAttachmentA = new Attachment({
						fileName: 'test.zip'
					});
					localAttachmentB = new Attachment({
						fileName: 'test2.zip'
					});
					view.attachments.add(localAttachmentA);
					view.attachments.add(localAttachmentA);
					expect(view.checkAllAttachmentsValid()).toBe(true);
				});
			});

		});
	});
});
