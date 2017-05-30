/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(['views/attachments', 'views/helper'], function (AttachmentView) {

	describe('AttachmentsView', function () {

		beforeEach(function () {
			$('body').append('<div id="#mail-attachments-template"></div>');
			this.AttachmentView = new AttachmentView({});
		});

		afterEach(function () {
			this.AttachmentView.remove();
			$('#mail-attachments-template').remove();
		});

		it('produces the correct HTML', function () {
			this.AttachmentView.render();

			expect(this.AttachmentView.el.innerHTML)
				.toContain('<ul></ul>\n\
<button type="button" id="add-local-attachment" style="display: inline-block;">\n\
  <span class="icon-upload"></span> Add attachment\n\
</button>\n\
<button type="button" id="add-cloud-attachment" style="display: inline-block;">\n\
  <span class="icon-edit"></span> Add from Files\n\
</button>\n\
<input type="file" multiple="" id="local-attachments" style="display: none;">\n');
		});

	});
});
