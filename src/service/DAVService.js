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

import curry from 'lodash/fp/curry'
import ical from 'ical.js'
import { getClient } from '../dav/client'
import Axios from '@nextcloud/axios'

import Logger from '../logger'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { uidToHexColor } from '../util/calendarColor'

const canWrite = (properties) => {
	let acls = properties?.acl?.ace

	if (!acls) {
		return false
	}
	// There might only be one ACL, not a list
	if (!Array.isArray(acls)) {
		acls = [acls]
	}

	for (const acl of acls) {
		if (acl.grant?.privilege?.write !== undefined) {
			return true
		}
	}

	return false
}

const getCalendarData = (calendar) => {
	return {
		displayname: calendar.props.displayname,
		order: calendar.props['calendar-order'],
		components: {
			vevent: true, // check if VEVENT exists in props['supported-calendar-component-set'].comps
		},
		writable: canWrite(calendar.props),
		url: generateRemoteUrl(`dav/calendars/${getCurrentUser().uid}${calendar.filename}/`),
		color: calendar.props['calendar-color'] ?? uidToHexColor(calendar.props.displayname ?? ''),

	}
}

/**
 * @return {Promise}
 */
export const getUserCalendars = async () => {
	const response = await getClient('calendars')
		.getDirectoryContents('/', {
			data: `<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:aapl="http://apple.com/ns/ical/" xmlns:oc="http://owncloud.org/ns" xmlns:cs="http://calendarserver.org/ns/">
  <d:prop>
    <d:displayname />
    <c:calendar-description />
    <c:calendar-timezone />
    <aapl:calendar-order />
    <aapl:calendar-color />
    <c:supported-calendar-component-set />
    <oc:calendar-enabled />
    <d:acl />
    <d:owner />
    <oc:invite />
  </d:prop>
</d:propfind>`,
			details: true,
		})

	return response.data
		.map(getCalendarData)
		.filter(props => props.components.vevent && props.writable === true)
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
 * @param {string} url the url
 * @param {object} data the data
 * @return {Promise}
 */
export const importCalendarEvent = curry((url, data) => {
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
})
