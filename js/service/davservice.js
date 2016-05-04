/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var _ = require('underscore');
	var $ = require('jquery');
	var Backbone = require('backbone');
	var dav = require('davclient');
	var OC = require('OC');
	var Radio = require('radio');
	var Calendar = require('models/dav/calendar');

	Radio.dav.reply('calendars', getUserCalendars);

	var client = new dav.Client({
		baseUrl: OC.linkToRemote('dav/calendars'),
		xmlNamespaces: {
			'DAV:': 'd',
			'urn:ietf:params:xml:ns:caldav': 'c',
			'http://apple.com/ns/ical/': 'aapl',
			'http://owncloud.org/ns': 'oc',
			'http://calendarserver.org/ns/': 'cs'
		}
	});
	var props = [
		'{DAV:}displayname',
		'{urn:ietf:params:xml:ns:caldav}calendar-description',
		'{urn:ietf:params:xml:ns:caldav}calendar-timezone',
		'{http://apple.com/ns/ical/}calendar-order',
		'{http://apple.com/ns/ical/}calendar-color',
		'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
		'{http://owncloud.org/ns}calendar-enabled',
		'{DAV:}acl',
		'{DAV:}owner',
		'{http://owncloud.org/ns}invite'
	];

	function getResponseCodeFromHTTPResponse(t) {
		return parseInt(t.split(' ')[1]);
	}

	function getCalendarData(properties) {
		var data = {
			displayname: properties['{DAV:}displayname'],
			color: properties['{http://apple.com/ns/ical/}calendar-color'],
			order: properties['{http://apple.com/ns/ical/}calendar-order'],
			components: {
				vevent: false
			}
		};

		var components = properties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] || [];
		for (var i = 0; i < components.length; i++) {
			var name = components[i].attributes.getNamedItem('name').textContent.toLowerCase();
			if (data.components.hasOwnProperty(name)) {
				data.components[name] = true;
			}
		}

		return data;
	}

	function getUserCalendars() {
		var defer = $.Deferred();
		var url = OC.linkToRemote('dav/calendars') + '/' + OC.currentUser + '/';

		client.propFind(url, props, 1, {
			'requesttoken': OC.requestToken
		}).then(function(data) {
			var calendars = new Backbone.Collection();

			_.each(data.body, function(cal) {
				if (cal.propStat.length < 1) {
					return;
				}
				if (getResponseCodeFromHTTPResponse(cal.propStat[0].status) === 200) {
					var properties = getCalendarData(cal.propStat[0].properties);
					if (properties && properties.components.vevent) {
						calendars.push(new Calendar(properties));
					}
				}
			});
			defer.resolve(calendars);
		}, function() {
			defer.reject();
		});

		return defer.promise();
	}
});
