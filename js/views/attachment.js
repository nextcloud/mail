/* global Backbone, Handlebars */

var views = {};

views.Attachment = Backbone.View.extend({
	// Each attachment will be shown as a li row
	tagName: 'li',

	initialize: function() {
		// Ensure our methods keep the `this` reference to the view itself
		_.bindAll(this, 'render');

		// If the model changes we need to re-render
		this.model.bind('change', this.render);
	},

	render: function() {
		// Clear existing row data if needed
		var tpl = Handlebars.compile($("#mail-attachment-template").html());
		$(this.el).html(tpl(this.model.toJSON()));
		return this;
	}
});

views.Attachments = Backbone.View.extend({

	// The collection will be kept here
	collection: null,

	events: {
		"click #mail_new_attachment" : "addAttachment",
		"click .new-message-attachments-action" : "removeAttachment"
	},

	initialize: function(options) {
		this.collection = options.collection;

		// Ensure our methods keep the `this` reference to the view itself
		_.bindAll(this, 'render');

		// Bind collection changes to re-rendering
		this.collection.bind('reset', this.render);
		this.collection.bind('add', this.render);
		this.collection.bind('remove', this.render);


	},

	addAttachment: function() {
		var self = this;
		OC.dialogs.filepicker(
			t('mail', 'Choose a folder to store the attachment in'),
			function (path) {
				self.collection.add([
					{
						fileName: path
					}
				]);
			});
	},

	removeAttachment: function(event) {
		var model = this.collection.get($(event.target).data('attachmentId'));
		this.collection.remove(model);

	},

	render: function() {
		var element = this.$el.find('ul');
		// Clear potential old entries first
		element.empty();

		// Go through the collection items
		this.collection.forEach(function(item) {

			// Instantiate a PeopleItem view for each
			var itemView = new views.Attachment({
				model: item
			});

			// Render the PeopleView, and append its element
			// to the table
			element.append(itemView.render().el);
		});

		return this;
	}
});
