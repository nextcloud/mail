/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import ICAL from 'ical.js'
import { loadState } from '@nextcloud/initial-state'

// Load the default profile (for example, home or work) configured by the user
const defaultProfileState = loadState('mail', 'defaultProfile', 'HOME')
const localesState = loadState('mail', 'locales', false)
const locales = localesState
	? localesState.map(({ code, name }) => ({
		id: code.toLowerCase().replace('_', '-'),
		name,
	}))
	: []

console.debug('Initial state loaded', 'defaultProfileState', defaultProfileState)
console.debug('Initial state loaded', 'localesState', localesState)

const properties = {
	n: {
		readableName: t('mail', 'Detailed name'),
		readableValues: [
			t('mail', 'Last name'),
			t('mail', 'First name'),
			t('mail', 'Additional names'),
			t('mail', 'Prefix'),
			t('mail', 'Suffix'),
		],
		displayOrder: [3, 1, 2, 0, 4],
		defaultValue: {
			value: ['', '', '', '', ''],
		},
		icon: 'icon-detailed-name',
		primary: false,
	},
	nickname: {
		readableName: t('mail', 'Nickname'),
		icon: 'icon-detailed-name',
		primary: false,
	},
	'x-phonetic-first-name': {
		readableName: t('mail', 'Phonetic first name'),
		icon: 'icon-detailed-name',
		force: 'text',
		primary: false,
	},
	'x-phonetic-last-name': {
		readableName: t('mail', 'Phonetic last name'),
		icon: 'icon-detailed-name',
		force: 'text',
		primary: false,
	},
	note: {
		readableName: t('mail', 'Notes'),
		icon: 'icon-note',
		primary: false,
	},
	url: {
		multiple: true,
		readableName: t('mail', 'Website'),
		icon: 'icon-public',
		primary: true,
	},
	geo: {
		multiple: true,
		readableName: t('mail', 'Location'),
		icon: 'icon-location',
		defaultjCal: {
			'3.0': [{}, 'FLOAT', '90.000;0.000'],
			'4.0': [{}, 'URI', 'geo:90.000,0.000'],
		},
		primary: false,
	},
	cloud: {
		multiple: true,
		icon: 'icon-federated-cloud-id',
		readableName: t('mail', 'Federated Cloud ID'),
		force: 'text',
		defaultValue: {
			value: [''],
			type: [defaultProfileState],
		},
		options: [
			{ id: 'HOME', name: t('mail', 'Home') },
			{ id: 'WORK', name: t('mail', 'Work') },
			{ id: 'OTHER', name: t('mail', 'Other') },
		],
		primary: false,
	},
	adr: {
		multiple: true,
		readableName: t('mail', 'Address'),
		readableValues: [
			t('mail', 'Post office box'),
			t('mail', 'Extended address'),
			t('mail', 'Address'),
			t('mail', 'City'),
			t('mail', 'State or province'),
			t('mail', 'Postal code'),
			t('mail', 'Country'),
		],
		displayOrder: [0, 2, 1, 5, 3, 4, 6],
		icon: 'icon-address',
		default: true,
		defaultValue: {
			value: ['', '', '', '', '', '', ''],
			type: [defaultProfileState],
		},
		options: [
			{ id: 'HOME', name: t('mail', 'Home') },
			{ id: 'WORK', name: t('mail', 'Work') },
			{ id: 'OTHER', name: t('mail', 'Other') },
		],
		primary: true,
	},
	bday: {
		readableName: t('mail', 'Birthday'),
		icon: 'icon-calendar-dark',
		force: 'date', // most ppl prefer date for birthdays, time is usually irrelevant
		defaultValue: {
			value: new ICAL.VCardTime(null, null, 'date').fromJSDate(new Date()),
		},
		primary: true,
	},
	anniversary: {
		readableName: t('mail', 'Anniversary'),
		icon: 'icon-anniversary',
		force: 'date', // most ppl prefer date for birthdays, time is usually irrelevant
		defaultValue: {
			value: new ICAL.VCardTime(null, null, 'date').fromJSDate(new Date()),
		},
		primary: false,
	},
	deathdate: {
		readableName: t('mail', 'Date of death'),
		icon: 'icon-death-day',
		force: 'date', // most ppl prefer date for birthdays, time is usually irrelevant
		defaultValue: {
			value: new ICAL.VCardTime(null, null, 'date').fromJSDate(new Date()),
		},
		primary: false,
	},
	email: {
		multiple: true,
		readableName: t('mail', 'Email'),
		icon: 'icon-mail',
		default: true,
		defaultValue: {
			value: '',
			type: [defaultProfileState],
		},
		options: [
			{ id: 'HOME', name: t('mail', 'Home') },
			{ id: 'WORK', name: t('mail', 'Work') },
			{ id: 'OTHER', name: t('mail', 'Other') },
		],
		primary: true,
	},
	impp: {
		multiple: true,
		readableName: t('mail', 'Instant messaging'),
		icon: 'icon-instant-message',
		defaultValue: {
			value: [''],
			type: ['SKYPE'],
		},
		options: [
			{ id: 'IRC', name: 'IRC' },
			{ id: 'KAKAOTALK', name: 'KakaoTalk' },
			{ id: 'KIK', name: 'KiK' },
			{ id: 'LINE', name: 'Line' },
			{ id: 'MATRIX', name: 'Matrix' },
			{ id: 'QQ', name: 'QQ' },
			{ id: 'SIGNAL', name: 'Signal' },
			{ id: 'SIP', name: 'SIP' },
			{ id: 'SKYPE', name: 'Skype' },
			{ id: 'TELEGRAM', name: 'Telegram' },
			{ id: 'THREEMA', name: 'Threema' },
			{ id: 'WECHAT', name: 'WeChat' },
			{ id: 'XMPP', name: 'XMPP' },
			{ id: 'ZOOM', name: 'Zoom' },
		],
		primary: false,
	},
	tel: {
		multiple: true,
		readableName: t('mail', 'Phone'),
		icon: 'icon-phone',
		default: true,
		defaultValue: {
			value: '',
			type: [defaultProfileState, 'VOICE'],
		},
		options: [
			{ id: 'HOME,VOICE', name: t('mail', 'Home') },
			{ id: 'HOME', name: t('mail', 'Home') },
			{ id: 'WORK,VOICE', name: t('mail', 'Work') },
			{ id: 'WORK', name: t('mail', 'Work') },
			{ id: 'CELL', name: t('mail', 'Mobile') },
			{ id: 'CELL,VOICE', name: t('mail', 'Mobile') },
			{ id: 'WORK,CELL', name: t('mail', 'Work mobile') },
			{ id: 'HOME,CELL', name: t('mail', 'Home mobile') },
			{ id: 'FAX', name: t('mail', 'Fax') },
			{ id: 'HOME,FAX', name: t('mail', 'Fax home') },
			{ id: 'WORK,FAX', name: t('mail', 'Fax work') },
			{ id: 'PAGER', name: t('mail', 'Pager') },
			{ id: 'VOICE', name: t('mail', 'Voice') },
			{ id: 'CAR', name: t('mail', 'Car') },
			{ id: 'WORK,PAGER', name: t('mail', 'Work pager') },
		],
		primary: true,
	},
	'x-managersname': {
		multiple: false,
		force: 'select',
		// TRANSLATORS The supervisor of an employee
		readableName: t('mail', 'Manager'),
		icon: 'icon-manager',
		default: false,
		options({ contact, $store, selectType }) {
			// Only allow contacts of the same address book
			const contacts = otherContacts({
				$store,
				self: contact,
			})

			// Reduce to an object to eliminate duplicates
			return Object.values(contacts.reduce((prev, { key }) => {
				const contact = $store.getters.getContact(key)
				return {
					...prev,
					[contact.uid]: {
						id: contact.key,
						name: contact.displayName,
					},
				}
			}, selectType ? { [selectType.value]: selectType } : {}))
		},
		primary: true,
	},
	'x-socialprofile': {
		multiple: true,
		force: 'text',
		icon: 'icon-social',
		readableName: t('mail', 'Social network'),
		defaultValue: {
			value: '',
			type: ['facebook'],
		},
		options: [
			{ id: 'FACEBOOK', name: 'Facebook', placeholder: 'https://facebook.com/…' },
			{ id: 'GITHUB', name: 'GitHub', placeholder: 'https://github.com/…' },
			{ id: 'GOOGLEPLUS', name: 'Google+', placeholder: 'https://plus.google.com/…' },
			{ id: 'INSTAGRAM', name: 'Instagram', placeholder: 'https://instagram.com/…' },
			{ id: 'LINKEDIN', name: 'LinkedIn', placeholder: 'https://linkedin.com/…' },
			{ id: 'XING', name: 'Xing', placeholder: 'https://www.xing.com/profile/…' },
			{ id: 'PINTEREST', name: 'Pinterest', placeholder: 'https://pinterest.com/…' },
			{ id: 'QZONE', name: 'QZone', placeholder: 'https://qzone.com/…' },
			{ id: 'TUMBLR', name: 'Tumblr', placeholder: 'https://tumblr.com/…' },
			{ id: 'TWITTER', name: 'Twitter', placeholder: 'https://twitter.com/…' },
			{ id: 'WECHAT', name: 'WeChat', placeholder: 'https://wechat.com/…' },
			{ id: 'YOUTUBE', name: 'YouTube', placeholder: 'https://youtube.com/…' },
			{ id: 'MASTODON', name: 'Mastodon', placeholder: 'https://mastodon.social/…' },
			{ id: 'DIASPORA', name: 'Diaspora', placeholder: 'https://joindiaspora.com/…' },
			{ id: 'NEXTCLOUD', name: 'Nextcloud', placeholder: 'Link to profile page (https://nextcloud.example.com/…)' },
			{ id: 'OTHER', name: 'Other', placeholder: 'https://example.com/…' },
		],
		primary: true,
	},
	relationship: {
		readableName: t('mail', 'Relationship to you'),
		force: 'select',
		icon: 'icon-relation-to-you',
		options: [
			{ id: 'SPOUSE', name: t('mail', 'Spouse') },
			{ id: 'CHILD', name: t('mail', 'Child') },
			{ id: 'MOTHER', name: t('mail', 'Mother') },
			{ id: 'FATHER', name: t('mail', 'Father') },
			{ id: 'PARENT', name: t('mail', 'Parent') },
			{ id: 'BROTHER', name: t('mail', 'Brother') },
			{ id: 'SISTER', name: t('mail', 'Sister') },
			{ id: 'RELATIVE', name: t('mail', 'Relative') },
			{ id: 'FRIEND', name: t('mail', 'Friend') },
			{ id: 'COLLEAGUE', name: t('mail', 'Colleague') },
			// TRANSLATORS The supervisor of an employee
			{ id: 'MANAGER', name: t('mail', 'Manager') },
			{ id: 'ASSISTANT', name: t('mail', 'Assistant') },
		],
		primary: false,
	},
	related: {
		multiple: true,
		readableName: t('mail', 'Related contacts'),
		icon: 'icon-related-contact',
		defaultValue: {
			value: [''],
			type: ['CONTACT'],
		},
		options: [
			{ id: 'CONTACT', name: t('mail', 'Contact') },
			{ id: 'AGENT', name: t('mail', 'Agent') },
			{ id: 'EMERGENCY', name: t('mail', 'Emergency') },
			{ id: 'FRIEND', name: t('mail', 'Friend') },
			{ id: 'COLLEAGUE', name: t('mail', 'Colleague') },
			{ id: 'COWORKER', name: t('mail', 'Co-worker') },
			// TRANSLATORS The supervisor of an employee
			{ id: 'MANAGER', name: t('mail', 'Manager') },
			{ id: 'ASSISTANT', name: t('mail', 'Assistant') },
			{ id: 'SPOUSE', name: t('mail', 'Spouse') },
			{ id: 'CHILD', name: t('mail', 'Child') },
			{ id: 'MOTHER', name: t('mail', 'Mother') },
			{ id: 'FATHER', name: t('mail', 'Father') },
			{ id: 'PARENT', name: t('mail', 'Parent') },
			{ id: 'BROTHER', name: t('mail', 'Brother') },
			{ id: 'SISTER', name: t('mail', 'Sister') },
			{ id: 'RELATIVE', name: t('mail', 'Relative') },
		],
		primary: false,
	},
	gender: {
		readableName: t('mail', 'Gender'),
		defaultValue: {
			// default to Female 🙋
			value: 'F',
		},
		icon: 'icon-gender',
		force: 'select',
		options: [
			{ id: 'F', name: t('mail', 'Female') },
			{ id: 'M', name: t('mail', 'Male') },
			{ id: 'O', name: t('mail', 'Other') },
			{ id: 'N', name: t('mail', 'None') },
			{ id: 'U', name: t('mail', 'Unknown') },
		],
		primary: false,
	},
	tz: {
		readableName: t('mail', 'Time zone'),
		force: 'select',
		icon: 'icon-timezone',
		primary: false,
	},
	lang: {
		readableName: t('mail', 'Spoken languages'),
		icon: 'icon-spoken-lang',
		defaultValue: {
			value: 'en',
		},
		multiple: true,
		primary: false,
	},
}

if (locales.length > 0) {
	properties.lang.force = 'select'
	properties.lang.options = locales
	properties.lang.greedyMatch = function(value, options) {
		// each locale already have the base code (e.g. fr in fr_ca)
		// in the list, meaning the only use case for this is a more
		// complete language tag than the short one we have
		// value: fr-ca-xxx... will be matched with option fr
		return options.find(({ id }) => {
			return id === value.split('-')[0]
		})
	}
}

const fieldOrder = [
	'title',
	'org',

	// primary fields
	'tel',
	'email',
	'adr',
	'bday',
	'url',
	'x-socialprofile',
	'x-managersname',

	// secondary fields
	'anniversary',
	'deathdate',
	'n',
	'nickname',
	'x-phonetic-first-name',
	'x-phonetic-last-name',
	'gender',
	'cloud',
	'impp',
	'geo',
	'note',
	'lang',
	'related',
	'relationship',
	'tz',

	'categories',
	'role',
]

export default { properties, fieldOrder }
