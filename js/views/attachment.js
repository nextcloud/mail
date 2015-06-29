/* global Backbone, OC */

var views = {};

views.Attachment = Backbone.Marionette.ItemView.extend({

	// Each attachment will be shown as a li row
	tagName: 'li',

	template: '#mail-attachment-template',

	events: {
		'click .icon-delete' : 'removeAttachment'
	},

	removeAttachment: function() {
		this.model.collection.remove(this.model);
	}

});

views.Attachments = Backbone.Marionette.CompositeView.extend({

	// The collection will be kept here
	collection: null,

	childView: views.Attachment,

	childViewContainer: 'ul',

	template: '#mail-attachments-template',

	events: {
		'click #mail_new_attachment' : 'addAttachment'
	},

	initialize: function(options) {
		this.collection = options.collection;
	},

	addAttachment: function() {
		var _this = this;
		OC.dialogs.filepicker(
			t('mail', 'Choose a file to add as attachment'),
			function(path) {
				_this.collection.add([
					{
						fileName: path
					}
				]);
			});
	}
});
