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

import { Client } from 'davclient.js'
import ical from 'ical.js'
import { getCurrentUser, getRequestToken } from '@nextcloud/auth'
import { generateRemoteUrl } from '@nextcloud/router'
import Axios from '@nextcloud/axios'

import Logger from '../logger'

const client = new Client({
	baseUrl: generateRemoteUrl('dav/calendars'),
	xmlNamespaces: {
		'DAV:': 'd',
		'urn:ietf:params:xml:ns:caldav': 'c',
		'http://apple.com/ns/ical/': 'aapl',
		'http://owncloud.org/ns': 'oc',
		'http://calendarserver.org/ns/': 'cs',
	},
})
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
	'{http://owncloud.org/ns}invite',
]

const getResponseCodeFromHTTPResponse = (t) => {
	return parseInt(t.split(' ')[1])
}

const getACLFromResponse = (properties) => {
	let canWrite = false
	const acl = properties['{DAV:}acl']
	if (acl) {
		for (let k = 0; k < acl.length; k++) {
			let href = acl[k].getElementsByTagNameNS('DAV:', 'href')
			if (href.length === 0) {
				continue
			}
			href = href[0].textContent
			const writeNode = acl[k].getElementsByTagNameNS('DAV:', 'write')
			if (writeNode.length > 0) {
				canWrite = true
			}
		}
	}
	properties.canWrite = canWrite
}

const getCalendarData = (properties) => {
	getACLFromResponse(properties)

	const data = {
		displayname: properties['{DAV:}displayname'],
		color: properties['{http://apple.com/ns/ical/}calendar-color'],
		order: properties['{http://apple.com/ns/ical/}calendar-order'],
		components: {
			vevent: false,
		},
		writable: properties.canWrite,
	}

	const components = properties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] || []
	for (let i = 0; i < components.length; i++) {
		const name = components[i].attributes.getNamedItem('name').textContent.toLowerCase()
		if (Object.hasOwnProperty.call(data.components, name)) {
			data.components[name] = true
		}
	}

	return data
}

/**
 * @returns {Promise}
 */
export const getUserCalendars = () => {
	const url = generateRemoteUrl('dav/calendars') + '/' + getCurrentUser().uid + '/'

	return client
		.propFind(url, props, 1, {
			requesttoken: getRequestToken(),
		})
		.then((data) => {
			const calendars = []

			data.body.forEach((cal) => {
				if (cal.propStat.length < 1) {
					return
				}
				if (getResponseCodeFromHTTPResponse(cal.propStat[0].status) === 200) {
					const properties = getCalendarData(cal.propStat[0].properties)
					if (properties && properties.components.vevent && properties.writable === true) {
						properties.url = cal.href
						calendars.push(properties)
					}
				}
			})

			return calendars
		})
}

const getRandomString = () => {
	let str = ''
	for (let i = 0; i < 7; i++) {
		str += Math.random().toString(36).substring(7)
	}
	return str
}

const createICalElement = () => {
	const root = new ical.Component(['vcalendar', [], []])

	root.updatePropertyWithValue('prodid', '-//' + OC.theme.name + ' Mail')

	return root
}

const splitCalendar = (data) => {
	const timezones = []
	const allObjects = {}
	const jCal = ical.parse(data)
	const components = new ical.Component(jCal)

	const vtimezones = components.getAllSubcomponents('vtimezone')
	vtimezones.forEach((vtimezone) => timezones.push(vtimezone))

	const componentNames = ['vevent', 'vjournal', 'vtodo']
	componentNames.forEach((componentName) => {
		const vobjects = components.getAllSubcomponents(componentName)
		allObjects[componentName] = {}

		vobjects.forEach((vobject) => {
			const uid = vobject.getFirstPropertyValue('uid')
			allObjects[componentName][uid] = allObjects[componentName][uid] || []
			allObjects[componentName][uid].push(vobject)
		})
	})

	const split = []
	componentNames.forEach((componentName) => {
		split[componentName] = []
		for (const objectsId in allObjects[componentName]) {
			const objects = allObjects[componentName][objectsId]
			const component = createICalElement()
			timezones.forEach(component.addSubcomponent.bind(component))
			for (const objectId in objects) {
				component.addSubcomponent(objects[objectId])
			}
			split[componentName].push(component.toString())
		}
	})

	return {
		name: components.getFirstPropertyValue('x-wr-calname'),
		color: components.getFirstPropertyValue('x-apple-calendar-color'),
		split,
	}
}

/**
 * @param {String} url the url
 * @param {Object} data the data
 * @returns {Promise}
 */
export const importCalendarEvent = (url) => (data) => {
	Logger.debug('importing events into calendar', {
		url,
		data,
	})
	const promises = []

	const file = splitCalendar(data)
	const components = ['vevent', 'vjournal', 'vtodo']
	components.forEach((componentName) => {
		for (const componentId in file.split[componentName]) {
			const component = file.split[componentName][componentId]
			Logger.info('importing event component', { component })
			promises.push(
				Promise.resolve(
					Axios.put(url + getRandomString() + '.ics', component, {
						headers: {
							'Content-Type': 'text/calendar; charset=utf-8',
						},
					})
				)
			)
		}
	})

	return Promise.all(promises)
}
