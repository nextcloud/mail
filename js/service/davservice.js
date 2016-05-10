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
	var ical = require('ical');
	var OC = require('OC');
	var Radio = require('radio');
	var Calendar = require('models/dav/calendar');

	Radio.dav.reply('calendars', getUserCalendars);
	Radio.dav.reply('calendar:import', importCalendarEvent);

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

	function getACLFromResponse(properties) {
		var canWrite = false;
		var acl = properties['{DAV:}acl'];
		if (acl) {
			for (var k = 0; k < acl.length; k++) {
				var href = acl[k].getElementsByTagNameNS('DAV:', 'href');
				if (href.length === 0) {
					continue;
				}
				href = href[0].textContent;
				var writeNode = acl[k].getElementsByTagNameNS('DAV:', 'write');
				if (writeNode.length > 0) {
					canWrite = true;
				}
			}
		}
		properties.canWrite = canWrite;
	}
	;

	function getCalendarData(properties) {
		getACLFromResponse(properties);

		var data = {
			displayname: properties['{DAV:}displayname'],
			color: properties['{http://apple.com/ns/ical/}calendar-color'],
			order: properties['{http://apple.com/ns/ical/}calendar-order'],
			components: {
				vevent: false
			},
			writable: properties.canWrite
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
					if (properties && properties.components.vevent && properties.writable === true) {
						properties.url = cal.href;
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

	function getRandomString() {
		var str = '';
		for (var i = 0; i < 7; i++) {
			str += Math.random().toString(36).substring(7);
		}
		return str;
	}

	function createICalElement() {
		var root = new ical.Component(['vcalendar', [], []]);

		root.updatePropertyWithValue('prodid', '-//ownCloud Mail');

		return root;
	}

	function splitCalendar(data) {
		var timezones = [];
		var allObjects = {};
		var jCal = ical.parse(data);
		var components = new ical.Component(jCal);

		var vtimezones = components.getAllSubcomponents('vtimezone');
		_.each(vtimezones, function(vtimezone) {
			timezones.push(vtimezone);
		});

		var componentNames = ['vevent', 'vjournal', 'vtodo'];
		_.each(componentNames, function(componentName) {
			var vobjects = components.getAllSubcomponents(componentName);
			allObjects[componentName] = {};

			_.each(vobjects, function(vobject) {
				var uid = vobject.getFirstPropertyValue('uid');
				allObjects[componentName][uid] = allObjects[componentName][uid] || [];
				allObjects[componentName][uid].push(vobject);
			});
		});

		var split = [];
		_.each(componentNames, function(componentName) {
			split[componentName] = [];
			_.each(allObjects[componentName], function(objects) {
				var component = createICalElement();
				_.each(timezones, function(timezone) {
					component.addSubcomponent(timezone);
				});
				_.each(objects, function(object) {
					component.addSubcomponent(object);
				});
				split[componentName].push(component.toString());
			});
		});

		return {
			name: components.getFirstPropertyValue('x-wr-calname'),
			color: components.getFirstPropertyValue('x-apple-calendar-color'),
			split: split
		};
	}

	function importCalendarEvent(url, data) {
		var defer = $.Deferred();
		var xhrs = [];

		var file = splitCalendar(data);

		var componentNames = ['vevent', 'vjournal', 'vtodo'];
		_.each(componentNames, function(componentName) {
			_.each(file.split[componentName], function(component) {
				xhrs.push($.ajax({
					url: url + getRandomString(),
					method: 'PUT',
					contentType: 'text/calendar; charset=utf-8',
					data: component,
					error: function() {
						defer.reject();
					}
				}));
			});
		});

		$.when.apply($, xhrs).done(function() {
			defer.resolve();
		});

		return defer.promise();
	}
});
