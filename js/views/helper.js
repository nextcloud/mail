/* global relative_modified_date, formatDate, md5, humanFileSize, getScrollBarWidth */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Handlebars = require('handlebars');

	Handlebars.registerHelper('relativeModifiedDate', function(dateInt) {
		var lastModified = new Date(dateInt * 1000);
		var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
		// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
		return relative_modified_date(lastModifiedTime);
		// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
	});

	Handlebars.registerHelper('formatDate', function(dateInt) {
		var lastModified = new Date(dateInt * 1000);
		return formatDate(lastModified);
	});

	Handlebars.registerHelper('humanFileSize', function(size) {
		return humanFileSize(size);
	});

	Handlebars.registerHelper('accountColor', function(account) {
		var hash = md5(account);
		var maxRange = parseInt('ffffffffffffffffffffffffffffffff', 16);
		var hue = parseInt(hash, 16) / maxRange * 256;
		return new Handlebars.SafeString('hsl(' + hue + ', 90%, 65%)');
	});

	Handlebars.registerHelper('printAddressList', function(addressList) {
		var currentAddress = require('app').State.accounts.find(function(item) {
			return item.get('accountId') === require('app').State.currentAccountId;
		});

		var str = _.reduce(addressList, function(memo, value, index) {
			if (index !== 0) {
				memo += ', ';
			}
			var label = value.label
				.replace(/(^"|"$)/g, '')
				.replace(/(^'|'$)/g, '');
			label = Handlebars.Utils.escapeExpression(label);
			var email = Handlebars.Utils.escapeExpression(value.email);
			if (currentAddress && email === currentAddress.get('emailAddress')) {
				label = t('mail', 'you');
			}
			var title = t('mail', 'Send message to {email}', {email: email});
			memo += '<span class="tipsy-mailto" title="' + title + '">';
			memo += '<a class="link-mailto" data-email="" + email + "" data-label="" + label + "">';
			memo += label + '</a></span>';
			return memo;
		}, '');
		return new Handlebars.SafeString(str);
	});

	Handlebars.registerHelper('printAddressListPlain', function(addressList) {
		var str = _.reduce(addressList, function(memo, value, index) {
			if (index !== 0) {
				memo += ', ';
			}
			var label = value.label
				.replace(/(^"|"$)/g, '')
				.replace(/(^"|"$)/g, '');
			label = Handlebars.Utils.escapeExpression(label);
			var email = Handlebars.Utils.escapeExpression(value.email);
			if (label === email) {
				return memo + email;
			} else {
				return memo + '"' + label + '" <' + email + '>';
			}
		}, '');
		return str;
	});

	Handlebars.registerHelper('ifHasCC', function(cc, ccList, options) {
		if (!_.isUndefined(cc) || (!_.isUndefined(ccList) && ccList.length > 0)) {
			return options.fn(this);
		} else {
			return options.inverse(this);
		}
	});

	Handlebars.registerHelper('unlessHasCC', function(cc, ccList, options) {
		if (_.isUndefined(cc) && (_.isUndefined(ccList) || ccList.length === 0)) {
			return options.fn(this);
		} else {
			return options.inverse(this);
		}
	});

	Handlebars.registerHelper('t', function(text) {
		return t('mail', text);
	});

	//duplicate getScrollBarWidth function from core js.js
	//TODO: remove once OC 8.0 support has been dropped
	window.getScrollBarWidth = window.getScrollBarWidth || function() {
		var inner = document.createElement('p');
		inner.style.width = '100%';
		inner.style.height = '200px';

		var outer = document.createElement('div');
		outer.style.position = 'absolute';
		outer.style.top = '0px';
		outer.style.left = '0px';
		outer.style.visibility = 'hidden';
		outer.style.width = '200px';
		outer.style.height = '150px';
		outer.style.overflow = 'hidden';
		outer.appendChild(inner);

		document.body.appendChild(outer);
		var w1 = inner.offsetWidth;
		outer.style.overflow = 'scroll';
		var w2 = inner.offsetWidth;
		if (w1 === w2) {
			w2 = outer.clientWidth;
		}

		document.body.removeChild(outer);

		return (w1 - w2);
	};
	//END TODO

	// TODO: get rid of global functions
	// adjust controls/header bar width
	window.adjustControlsWidth = function() {
		if ($('#mail-message-header').length) {
			var controlsWidth;
			if ($(window).width() > 768) {
				controlsWidth =
					$('#content').width() -
					$('#app-navigation').width() -
					$('#mail_messages').width() -
					getScrollBarWidth();
			}
			$('#mail-message-header').css('width', controlsWidth);
			$('#mail-message-header').css('min-width', controlsWidth);
		}
	};

	$(window).resize(_.debounce(window.adjustControlsWidth, 250));
	// END TODO
});

