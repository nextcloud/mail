/* global Backbone, Handlebars, Mail, models */

var views = views || {};

views.Folder = Backbone.Marionette.ItemView.extend({

	template: "#mail-folder-template"

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
