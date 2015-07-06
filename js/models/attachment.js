/* global Backbone, _ */
var models = {};

models.Attachment = Backbone.Model.extend({

	initialize: function() {
		this.set('id', _.uniqueId());

		var s = this.get('fileName');
		if (s.charAt(0) === '/') {
			s = s.substr(1);
		}

		this.set('displayName', s);
	}
});

models.Attachments = Backbone.Collection.extend({
	model: models.Attachment
});

models.MessageFlags = Backbone.Model.extend({
	defaults: {
		answered: false
	}
});

models.Message = Backbone.Model.extend({
	defaults: {
		flags: [],
		active: false
	},

	initialize: function() {
		this.set('flags', new models.MessageFlags(this.get('flags')));
		this.listenTo(this.get('flags'), 'change', this._transformEvent);
	},

	_transformEvent: function() {
		this.trigger('change');
		this.trigger('change:flags', this);
	},

	toJSON: function() {
		var data = Backbone.Model.prototype.toJSON.call(this);
		if (data.flags && data.flags.toJSON) {
			data.flags = data.flags.toJSON();
		}
		if (!data.id) {
			data.id = this.cid;
		}
		return data;
	}
});

models.MessageList = Backbone.Collection.extend({
	model: models.Message,

	comparator: function(message) {
		return message.get('dateInt') * -1;
	}
});

models.Folder = Backbone.Model.extend({
	defaults: {
		open: false,
		folders: []
	},
	initialize: function() {
		this.set('folders', new models.FolderList(this.get('folders')));
	},

	toggleOpen: function() {
		this.set({open: !this.get('open')});
	},

	toJSON: function() {
		var data = Backbone.Model.prototype.toJSON.call(this);
		if (data.folders && data.folders.toJSON) {
			data.folders = data.folders.toJSON();
		}
		if (!data.id) {
			data.id = this.cid;
		}
		return data;
	}
});

models.FolderList = Backbone.Collection.extend({
	model: models.Folder
});

models.Account = Backbone.Model.extend({
	defaults: {
		folders: []
	},

	initialize: function() {
		this.set('folders', new models.FolderList(this.get('folders')));
	},

	toJSON: function() {
		var data = Backbone.Model.prototype.toJSON.call(this);
		if (data.folders && data.folders.toJSON) {
			data.folders = data.folders.toJSON();
		}
		if (!data.id) {
			data.id = this.cid;
		}
		return data;
	}
});

models.AccountList = Backbone.Collection.extend({
	model: models.Account,

	comparator: function(folder) {
		return folder.get('id');
	}
});
