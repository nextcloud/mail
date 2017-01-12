/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var AliasesListTemplate = require('text!templates/aliases-list.html');
	var Radio = require('radio');

	return Marionette.View.extend({
		collection: null,
		model: null,
		tagName: 'tr',
		childViewContainer: 'tbody',
		template: Handlebars.compile(AliasesListTemplate),
		templateContext: function() {
			return {
				aliases: this.model.toJSON()
			};
		},
		ui: {
			deleteButton: 'button'
		},
		events: {
			'click @ui.deleteButton': 'deleteAlias'
		},
		initialize: function(options) {
			this.model = options.model;
		},
		deleteAlias: function(event) {
			event.stopPropagation();
			var currentAccount = require('state').accounts.get(this.model.get('accountId'));
			var _this = this;
			this.getUI('deleteButton').prop('disabled', true);
			this.getUI('deleteButton').attr('class', 'icon-loading-small');
			Radio.aliases.request('delete', currentAccount, this.model.get('id'))
				.then(function() {
					currentAccount.get('aliases').remove(_this.model);
				}, console.error.bind(this))
				.then(function() {
					_this.getUI('deleteButton').attr('class', 'icon-delete');
					_this.getUI('deleteButton').prop('disabled', false);
				});
		}

	});
});
