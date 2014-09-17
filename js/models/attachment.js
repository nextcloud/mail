/* global Backbone */
var models = {};

models.Attachment = Backbone.Model.extend({

	initialize: function () {
		this.set('id', _.uniqueId());

		var s = this.get('fileName');
		if(s.charAt(0) === '/') {
			s = s.substr(1);
		}

		this.set('displayName',s);
	}
});

models.Attachments = Backbone.Collection.extend({
	model: models.Attachment
});

models.Message = Backbone.Model.extend();

models.MessageList = Backbone.Collection.extend({
	model: models.Message
});

models.Folder = Backbone.Model.extend();

models.FolderList = Backbone.Collection.extend({
	model: models.Folder,

	comparator: function(folder) {
		return folder.get("id");
	}
});
