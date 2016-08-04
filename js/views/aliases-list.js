/**
 * ownCloud - Mail
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

	return Marionette.ItemView.extend({
		collection: null,
		model: null,
		tagName: 'tr',
		childViewContainer: 'tbody',
		template: Handlebars.compile(AliasesListTemplate),
		templateHelpers: function() {
			return {
				aliases: this.model.toJSON()
			};
		},
		ui: {
			'deleteButton' : 'button'
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
			var deletingAlias = Radio.aliases.request('delete:alias', currentAccount, this.model.get('id'));
			this.ui.deleteButton.prop('disabled', true);
			this.ui.deleteButton.attr('class', 'icon-loading-small');
			$.when(deletingAlias).done(function() {
				currentAccount.get('aliases').remove(_this.model);
			});
			$.when(deletingAlias).always(function() {
				var aliases = currentAccount.get('aliases');
				if (aliases.get(_this.model)) {
					_this.ui.deleteButton.attr('class', 'icon-delete');
					_this.ui.deleteButton.prop('disabled', false);
				}
			});
		}

	});
});
