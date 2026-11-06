/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
import { getCurrentUser } from '@nextcloud/auth'
import Axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import ical from 'ical.js'
import curry from 'lodash/fp/curry.js'
import { getClient } from '../dav/client.js'
import Logger from '../logger.js'
import { uidToHexColor } from '../util/calendarColor.js'

function canWrite(properties) {
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

const CALENDAR_COMPONENTS = ['VEVENT', 'VJOURNAL', 'VTODO']

/**
 * @param {object} properties the DAV properties of a calendar collection
 * @return {string[]} the upper-case component names the calendar accepts
 */
function getSupportedComponents(properties) {
	let comps = properties?.['supported-calendar-component-set']?.comp

	// RFC 4791 section 5.2.3: if the property is absent, all components are supported
	if (!comps) {
		return CALENDAR_COMPONENTS
	}
	// There might only be one component, not a list
	if (!Array.isArray(comps)) {
		comps = [comps]
	}

	return comps
		.map((comp) => comp?.['@name']?.toUpperCase())
		.filter(Boolean)
}

function getCalendarData(calendar) {
	return {
		displayname: calendar.props.displayname,
		order: calendar.props['calendar-order'],
		components: getSupportedComponents(calendar.props),
		writable: canWrite(calendar.props),
		url: generateRemoteUrl(`dav/calendars/${getCurrentUser().uid}${calendar.filename}/`),
		color: calendar.props['calendar-color'] ?? uidToHexColor(calendar.props.displayname ?? ''),

	}
}

/**
 * @param {string[]} requiredComponents only return calendars that accept all of these components
 * @return {Promise}
 */
export async function getUserCalendars(requiredComponents = ['VEVENT']) {
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
		.filter((props) => props.writable === true
			&& requiredComponents.every((component) => props.components.includes(component)))
}

/**
 * @param {string} data the iCalendar data
 * @return {string[]} the upper-case names of the calendar components contained in the data
 */
export function getCalendarComponents(data) {
	const vcalendar = new ical.Component(ical.parse(data))

	return CALENDAR_COMPONENTS
		.filter((name) => vcalendar.getAllSubcomponents(name.toLowerCase()).length > 0)
}

function getRandomString() {
	let str = ''
	for (let i = 0; i < 7; i++) {
		str += Math.random().toString(36).substring(7)
	}
	return str
}

function createICalElement() {
	const root = new ical.Component(['vcalendar', [], []])

	root.updatePropertyWithValue('prodid', '-//' + OC.theme.name + ' Mail')

	return root
}

function splitCalendar(data) {
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
			promises.push(Promise.resolve(Axios.put(url + getRandomString() + '.ics', component, {
				headers: {
					'Content-Type': 'text/calendar; charset=utf-8',
				},
			})))
		}
	})

	return Promise.all(promises)
})
