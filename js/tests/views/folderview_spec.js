/* global expect */

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
	'views/folderview',
	'models/account',
	'models/folder',
], function(FolderView, Account, Folder) {
	describe('FolderView', function() {
		var folder;
		var folderView;

		beforeEach(function() {
			folder = new Folder({
				account: new Account(),
				id: 'folder1'
			});
			folderView = new FolderView({
				model: folder
			});
		});

		it('shows subfolders on clicking the expand triangle', function() {
			var subFolder1 = new Folder({
				id: 'sub1',
				name: 'sub1'
			});
			folder.addFolder(subFolder1);
			var subFolder2 = new Folder({
				id: 'sub2',
				name: 'sub2'
			});
			folder.addFolder(subFolder2);

			folderView.render();
			expect(folderView.$el.attr('class').split(' ')).toContain('collapsible');
			expect(folderView.$el.attr('class').split(' ')).not.toContain('open');

			folderView.$el.find('button.collapse').click();

			expect(folderView.$el.attr('class').split(' ')).toContain('open');
		});
	});
});
