/* global expect */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2017
 */

define([
	'views/attachmentsview',
	'jquery'
], function(AttachmentView, $) {

	describe('AttachmentsView', function() {

		var view;

		beforeEach(function() {
			$('body').append('<div id="#mail-attachments-template"></div>');
			view = new AttachmentView({});
		});

		afterEach(function() {
			view.remove();
			$('#mail-attachments-template').remove();
		});

		it('produces the correct HTML', function() {
			view.render();

			expect(view.el.innerHTML)
				.toContain('<ul></ul>\n\
<button type="button" id="add-local-attachment" style="display: inline-block;">\n\
  <span class="icon-upload"></span> Upload attachment\n\
</button>\n\
<button type="button" id="add-cloud-attachment" style="display: inline-block;">\n\
  <span class="icon-folder"></span> Add attachment from Files\n\
</button>\n\
<input type="file" multiple="" id="local-attachments" style="display: none;">\n');
		});
	});
});
