/* global Backbone, Mail, models */

var views = views || {};

views.Folder = Backbone.Marionette.ItemView.extend({

	template: "#mail-folder-template",

	events: {
		"click .collapse" : "collapseFolder",
		"click li a" : "loadMessages"
	},

	collapseFolder: function(e) {
		e.preventDefault();
		$(e.currentTarget).parent().toggleClass('open');
	},

	loadMessages: function(e) {
		e.preventDefault();
		var accountId = this.model.get('id');
		var folderId = $(e.currentTarget).parent().data('folder_id');

		Mail.UI.loadMessages(accountId, folderId);
	}
});

views.Folders = Backbone.Marionette.CollectionView.extend({

	// The collection will be kept here
	collection: null,

	childView: views.Folder,

	events: {
	},

	initialize: function() {
		this.collection = new models.FolderList();
	}

});
