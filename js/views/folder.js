/* global Backbone, Mail, models */

var views = views || {};

views.Folder = Backbone.Marionette.ItemView.extend({

	template: "#mail-folder-template",

	events: {
		"click .collapse" : "collapseFolder",
		"click li a" : "loadMessages"
	},

	initialize: function(options) {
		this.model = options.model;
	},

	collapseFolder: function(e) {
		e.preventDefault();
		this.model.toggleOpen();
	},

	loadMessages: function(e) {
		e.preventDefault();
		var accountId = this.model.get('accountId');
		var folderId = $(e.currentTarget).parent().data('folder_id');
		Mail.UI.loadMessages(accountId, folderId);
	}
});

views.Account = Backbone.Marionette.CompositeView.extend({

	collection: null,
	model: null,

	template: "#mail-account-template",

	childView: views.Folder,

	childViewContainer: '#mail_folders',

	initialize: function(options) {
		this.model = options.model;
		this.collection = new models.FolderList(this.model.get('folders'));
	}

});

views.Folders = Backbone.Marionette.CollectionView.extend({

	// The collection will be kept here
	collection: null,

	childView: views.Account,

	initialize: function() {
		this.collection = new models.AccountList();
	}

});
