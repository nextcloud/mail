/* global Backbone, Mail, models, oc_defaults */

var views = views || {};

views.Folder = Backbone.Marionette.ItemView.extend({

	template: "#mail-folder-template",

	events: {
		"click .collapse" : "collapseFolder",
		"click .folder" : "loadMessages"
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
		var noSelect = $(e.currentTarget).parent().data('no_select');
		Mail.UI.loadMessages(accountId, folderId, noSelect);
	},

	onRender: function() {
		// Get rid of that pesky wrapping-div.
		// Assumes 1 child element present in template.
		this.$el = this.$el.children();
		// Unwrap the element to prevent infinitely
		// nesting elements during re-render.
		this.$el.unwrap();
		this.setElement(this.$el);
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
		this.collection = this.model.get('folders');
	}

});

views.Folders = Backbone.Marionette.CollectionView.extend({

	// The collection will be kept here
	collection: null,

	childView: views.Account,

	initialize: function() {
		this.collection = new models.AccountList();
	},

	getFolderById: function () {
		var activeAccount = Mail.State.currentAccountId;
		activeAccount = this.collection.get(activeAccount);
		activeFolder = activeAccount.get('folders').get(Mail.State.currentFolderId);

		if (!_.isUndefined(activeFolder)) {
			return activeFolder;
		}

		// bad hack to navigate down the tree ...
		var folderId = atob(Mail.State.currentFolderId);
		var parts = folderId.split('/');
		var activeFolder = activeAccount;
		var k = '';
		_.each(parts, function(p){
			if (k.length > 0) {
				k += '/';
			}
			k += p;

			if (!_.isUndefined(activeFolder)) {
				return;
			}

			var folders = activeFolder.folders || activeFolder.get('folders');
			activeFolder = folders.filter(function(f) {
				return f.id === btoa(k);
			}).shift();
		});
		return activeFolder;
	},

	changeUnseen: function(model, unseen) {
		// TODO: currentFolderId and currentAccountId should be an attribute of this view
		var activeFolder = this.getFolderById();
		if (unseen) {
			activeFolder.set('unseen', activeFolder.get('unseen') + 1);
		} else {
			if (activeFolder.get('unseen') > 0) {
				activeFolder.set('unseen', activeFolder.get('unseen') - 1);
			}
		}
		this.updateTitle();
	},

	updateTitle: function() {

		var activeAccount = Mail.State.currentAccountId;
		activeAccount = this.collection.get(activeAccount);
		var activeFolder = this.getFolderById();
		var unread = activeFolder.unseen;
		var name = activeFolder.name || activeFolder.get('name');

		if ( unread > 0) {
			window.document.title = name + ' (' + unread + ') - ' +
			activeAccount.get('email') + ' - Mail - ' + oc_defaults.title;
		} else {
			window.document.title = name + ' - ' + activeAccount.get('email') +
			' - Mail - ' + oc_defaults.title;
		}
	}

});
