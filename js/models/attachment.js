/* global Backbone */
var models = {};

models.Attachment = Backbone.Model.extend({

	initialize: function () {
		this.set('id', _.uniqueId());
	}
});

models.Attachments = Backbone.Collection.extend({
	model: models.Attachment
});
