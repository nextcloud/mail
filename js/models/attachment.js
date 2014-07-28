/* global Backbone */
var models = {};

models.Attachment = Backbone.Model.extend({

});

models.Attachments = Backbone.Collection.extend({
	model: models.Attachment
});
