/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
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

import dav from 'davclient.js'
import ical from 'ical.js'

const client = new dav.Client({
	baseUrl: OC.linkToRemote('dav/calendars'),
	xmlNamespaces: {
		'DAV:': 'd',
		'urn:ietf:params:xml:ns:caldav': 'c',
		'http://apple.com/ns/ical/': 'aapl',
		'http://owncloud.org/ns': 'oc',
		'http://calendarserver.org/ns/': 'cs'
	}
});
const props = [
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

const getResponseCodeFromHTTPResponse = (t) => {
	return parseInt(t.split(' ')[1]);
}

const getACLFromResponse = properties => {
	let canWrite = false;
	let acl = properties['{DAV:}acl'];
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


const getCalendarData = (properties) => {
	getACLFromResponse(properties);

	const data = {
		displayname: properties['{DAV:}displayname'],
		color: properties['{http://apple.com/ns/ical/}calendar-color'],
		order: properties['{http://apple.com/ns/ical/}calendar-order'],
		components: {
			vevent: false
		},
		writable: properties.canWrite
	};

	const components = properties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] || [];
	for (let i = 0; i < components.length; i++) {
		var name = components[i].attributes.getNamedItem('name').textContent.toLowerCase();
		if (data.components.hasOwnProperty(name)) {
			data.components[name] = true;
		}
	}

	return data;
}

/**
 * @returns {Promise}
 */
export const getUserCalendars = () => {
	var url = OC.linkToRemote('dav/calendars') + '/' + OC.currentUser + '/';

	return client.propFind(url, props, 1, {
		requesttoken: OC.requestToken
	}).then(function (data) {
		const calendars = [];

		data.body.forEach(cal => {
			if (cal.propStat.length < 1) {
				return;
			}
			if (getResponseCodeFromHTTPResponse(cal.propStat[0].status) === 200) {
				const properties = getCalendarData(cal.propStat[0].properties);
				if (properties && properties.components.vevent && properties.writable === true) {
					properties.url = cal.href;
					calendars.push(properties);
				}
			}
		});

		return calendars;
	});
}

const getRandomString = () => {
	let str = '';
	for (let i = 0; i < 7; i++) {
		str += Math.random().toString(36).substring(7);
	}
	return str;
}

const createICalElement = () => {
	const root = new ical.Component(['vcalendar', [], []]);

	root.updatePropertyWithValue('prodid', '-//' + OC.theme.name + ' Mail');

	return root;
}

const splitCalendar = (data) => {
	const timezones = [];
	const allObjects = {};
	const jCal = ical.parse(data);
	const components = new ical.Component(jCal);

	const vtimezones = components.getAllSubcomponents('vtimezone');
	vtimezones.forEach(vtimezone => timezones.push(vtimezone));

	const componentNames = ['vevent', 'vjournal', 'vtodo'];
	componentNames.forEach(componentName => {
		const vobjects = components.getAllSubcomponents(componentName);
		allObjects[componentName] = {};

		vobjects.forEach(vobject => {
			var uid = vobject.getFirstPropertyValue('uid');
			allObjects[componentName][uid] = allObjects[componentName][uid] || [];
			allObjects[componentName][uid].push(vobject);
		});
	});

	const split = [];
	componentNames.forEach(componentName => {
		split[componentName] = [];
		for (let objectsId in allObjects[componentName]) {
			const objects = allObjects[componentName][objectsId];
			const component = createICalElement();
			timezones.forEach(tz => component.addSubcomponent(tz));
			for (let objectId in objects) {
				component.addSubcomponent(objects[objectId]);
			}
			split[componentName].push(component.toString());
		}
	});

	return {
		name: components.getFirstPropertyValue('x-wr-calname'),
		color: components.getFirstPropertyValue('x-apple-calendar-color'),
		split: split
	};
}

/**
 * @param {String} url
 * @param {Object} data
 * @returns {Promise}
 */
export const importCalendarEvent = url => data => {
	console.debug('importing event into calendar', url, data);
	const promises = [];

	const file = splitCalendar(data);

	['vevent', 'vjournal', 'vtodo'].forEach(componentName => {
		for (let componentId in file.split[componentName]) {
			const component = file.split[componentName][componentId];
			promises.push(Promise.resolve($.ajax({
				url: url + getRandomString(),
				method: 'PUT',
				contentType: 'text/calendar; charset=utf-8',
				data: component
			})));
		}
	});

	return Promise.all(promises);
}
