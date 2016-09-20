/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(['views/attachments', 'views/helper'], function(AttachmentView) {

	describe('AttachmentsView test', function() {

		beforeEach(function() {
			$('body').append('<div id="#mail-attachments-template"></div>');
			this.AttachmentView = new AttachmentView({});
		});

		afterEach(function() {
			this.AttachmentView.remove();
			$('#mail-attachments-template').remove();
		});

		describe('Rendering', function() {

			it('produces the correct HTML', function() {
				this.AttachmentView.render();

				expect(this.AttachmentView.el.innerHTML)
					.toContain('<ul></ul>\n<input type="button" id="mail_new_attachment" value="Add attachment from Files">');
			});

		});

	});
});

