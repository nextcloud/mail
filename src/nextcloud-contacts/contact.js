/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { v4 as uuid } from 'uuid'
import ICAL from 'ical.js'
import b64toBlob from 'b64-to-blob'

import updateDesignSet from './updateDesignSet.js'
import sanitizeSVG from '@mattkrick/sanitize-svg'

/**
 * Check if the given value is an empty array or an empty string
 *
 * @param {string|Array} value the value to check
 * @return {boolean}
 */
const isEmpty = value => {
	return (Array.isArray(value) && value.join('') === '') || (!Array.isArray(value) && value === '')
}

export const ContactKindProperties = ['KIND', 'X-ADDRESSBOOKSERVER-KIND']

export const MinimalContactProperties = [
	'EMAIL', 'UID', 'CATEGORIES', 'FN', 'ORG', 'N', 'X-PHONETIC-FIRST-NAME', 'X-PHONETIC-LAST-NAME', 'X-MANAGERSNAME', 'TITLE', 'NOTES', 'RELATED',
].concat(ContactKindProperties)

export default class Contact {

	/**
	 * Creates an instance of Contact
	 *
	 * @param {string} vcard the vcard data as string with proper new lines
	 * @param {object} addressbook the addressbook which the contat belongs to
	 * @memberof Contact
	 */
	constructor(vcard, addressbook) {
		if (typeof vcard !== 'string' || vcard.length === 0) {
			throw new Error('Invalid vCard')
		}

		let jCal = ICAL.parse(vcard)
		if (jCal[0] !== 'vcard') {
			throw new Error('Only one contact is allowed in the vcard data')
		}

		if (updateDesignSet(jCal)) {
			jCal = ICAL.parse(vcard)
		}

		this.jCal = jCal
		this.addressbook = addressbook
		this.vCard = new ICAL.Component(this.jCal)

		// used to state a contact is not up to date with
		// the server and cannot be pushed (etag)
		this.conflict = false

		// if no uid set, create one
		if (!this.vCard.hasProperty('uid')) {
			console.info('This contact did not have a proper uid. Setting a new one for ', this)
			this.vCard.addPropertyWithValue('uid', uuid())
		}

		// if no rev set, init one
		if (!this.vCard.hasProperty('rev')) {
			const rev = new ICAL.VCardTime(null, null, 'date-time')
			rev.fromUnixTime(Date.now() / 1000)
			this.vCard.addPropertyWithValue('rev', rev)
		}
	}

	/**
	 * Update internal data of this contact
	 *
	 * @param {jCal} jCal jCal object from ICAL.js
	 * @memberof Contact
	 */
	updateContact(jCal) {
		this.jCal = jCal
		this.vCard = new ICAL.Component(this.jCal)
	}

	/**
	 * Update linked addressbook of this contact
	 *
	 * @param {object} addressbook the addressbook
	 * @memberof Contact
	 */
	updateAddressbook(addressbook) {
		this.addressbook = addressbook
	}

	/**
	 * Ensure we're normalizing the possible arrays
	 * into a string by taking the first element
	 * e.g. ORG:ABC\, Inc.; will output an array because of the semi-colon
	 *
	 * @param {Array|string} data the data to normalize
	 * @return {string}
	 * @memberof Contact
	 */
	firstIfArray(data) {
		return Array.isArray(data) ? data[0] : data
	}

	/**
	 * Return the url
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get url() {
		if (this.dav) {
			return this.dav.url
		}
		return ''
	}

	/**
	 * Return the version
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get version() {
		return this.vCard.getFirstPropertyValue('version')
	}

	/**
	 * Set the version
	 *
	 * @param {string} version the version to set
	 * @memberof Contact
	 */
	set version(version) {
		this.vCard.updatePropertyWithValue('version', version)
	}

	/**
	 * Return the uid
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get uid() {
		return this.vCard.getFirstPropertyValue('uid')
	}

	/**
	 * Set the uid
	 *
	 * @param {string} uid the uid to set
	 * @memberof Contact
	 */
	set uid(uid) {
		this.vCard.updatePropertyWithValue('uid', uid)
	}

	/**
	 * Return the rev
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get rev() {
		return this.vCard.getFirstPropertyValue('rev')
	}

	/**
	 * Set the rev
	 *
	 * @param {string} rev the rev to set
	 * @memberof Contact
	 */
	set rev(rev) {
		this.vCard.updatePropertyWithValue('rev', rev)
	}

	/**
	 * Return the key
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get key() {
		return this.uid + '~' + this.addressbook.id
	}

	/**
	 * Return the photo
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get photo() {
		return this.vCard.getFirstPropertyValue('photo')
	}

	/**
	 * Set the photo
	 *
	 * @param {string} photo the photo to set
	 * @memberof Contact
	 */
	set photo(photo) {
		this.vCard.updatePropertyWithValue('photo', photo)
	}

	/**
	 * Return the photo usable url
	 * We cannot fetch external url because of csp policies
	 *
	 * @memberof Contact
	 */
	async getPhotoUrl() {
		const photo = this.vCard.getFirstProperty('photo')
		if (!photo) {
			return false
		}
		const encoding = photo.getFirstParameter('encoding')
		let photoType = photo.getFirstParameter('type')
		const photoB64 = this.photo

		const isBinary = photo.type === 'binary' || encoding === 'b'

		let photoB64Data = photoB64
		if (photo && photoB64.startsWith('data') && !isBinary) {
			// get the last part = base64
			photoB64Data = photoB64.split(',').pop()
			// 'data:image/png;base64' => 'png'
			photoType = photoB64.split(';')[0].split('/').pop()
		}

		// Verify if SVG is valid
		if (photoType.toLowerCase().startsWith('svg')) {
			const imageSvg = atob(photoB64Data)
			const cleanSvg = await sanitizeSVG(imageSvg)

			if (!cleanSvg) {
				console.error('Invalid SVG for the following contact. Ignoring...', this.contact, { photoB64, photoType })
				return false
			}
		}

		try {
			// Create blob from url
			const blob = b64toBlob(photoB64Data, `image/${photoType}`)
			return URL.createObjectURL(blob)
		} catch {
			console.error('Invalid photo for the following contact. Ignoring...', this.contact, { photoB64, photoType })
			return false
		}
	}

	/**
	 * Return the groups
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get groups() {
		const groupsProp = this.vCard.getFirstProperty('categories')
		if (groupsProp) {
			return groupsProp.getValues()
				.filter(group => typeof group === 'string')
				.filter(group => group.trim() !== '')
		}
		return []
	}

	/**
	 * Set the groups
	 *
	 * @param {Array} groups the groups to set
	 * @memberof Contact
	 */
	set groups(groups) {
		// delete the title if empty
		if (isEmpty(groups)) {
			this.vCard.removeProperty('categories')
			return
		}

		if (Array.isArray(groups)) {
			let property = this.vCard.getFirstProperty('categories')
			if (!property) {
				// Init with empty group since we set everything afterwise
				property = this.vCard.addPropertyWithValue('categories', '')
			}
			property.setValues(groups)
		} else {
			throw new Error('groups data is not an Array')
		}
	}

	/**
	 * Return the groups
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get kind() {
		return this.firstIfArray(
			ContactKindProperties
				.map(s => s.toLowerCase())
				.map(s => this.vCard.getFirstPropertyValue(s))
				.flat()
				.filter(k => k),
		)
	}

	/**
	 * Return the first email
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get email() {
		return this.firstIfArray(this.vCard.getFirstPropertyValue('email'))
	}

	/**
	 * Return the first org
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get org() {
		return this.firstIfArray(this.vCard.getFirstPropertyValue('org'))
	}

	/**
	 * Set the org
	 *
	 * @param {string} org the org data
	 * @memberof Contact
	 */
	set org(org) {
		// delete the org if empty
		if (isEmpty(org)) {
			this.vCard.removeProperty('org')
			return
		}
		this.vCard.updatePropertyWithValue('org', org)
	}

	/**
	 * Return the first x-managersname
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get managersName() {
		const prop = this.vCard.getFirstProperty('x-managersname')
		if (!prop) {
			return null
		}
		return prop.getFirstParameter('uid') ?? null
	}

	/**
	 * Return the first title
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get title() {
		return this.firstIfArray(this.vCard.getFirstPropertyValue('title'))
	}

	/**
	 * Set the title
	 *
	 * @param {string} title the title
	 * @memberof Contact
	 */
	set title(title) {
		// delete the title if empty
		if (isEmpty(title)) {
			this.vCard.removeProperty('title')
			return
		}
		this.vCard.updatePropertyWithValue('title', title)
	}

	/**
	 * Return the full name
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get fullName() {
		return this.vCard.getFirstPropertyValue('fn')
	}

	/**
	 * Set the full name
	 *
	 * @param {string} name the fn data
	 * @memberof Contact
	 */
	set fullName(name) {
		this.vCard.updatePropertyWithValue('fn', name)
	}

	/**
	 * Formatted display name based on the order key
	 *
	 * @readonly
	 * @memberof Contact
	 */
	get displayName() {
		const n = this.vCard.getFirstPropertyValue('n')
		const fn = this.vCard.getFirstPropertyValue('fn')
		const org = this.vCard.getFirstPropertyValue('org')

		// if ordered by last or first name we need the N property
		// ! by checking the property we check for null AND empty string
		// ! that means we can then check for empty array and be safe not to have
		// ! 'xxxx'.join('') !== ''
		// otherwise the FN is enough
		if (fn) {
			return fn
		}
		// BUT if no FN property use the N anyway
		if (n && !isEmpty(n)) {
			// Stevenson;John;Philip,Paul;Dr.;Jr.,M.D.,A.C.P.
			// -> John Stevenson
			if (isEmpty(n[0])) {
				return n[1]
			}
			return n.slice(0, 2).reverse().join(' ')
		}
		// LAST chance, use the org ir that's the only thing we have
		if (org && !isEmpty(org)) {
			// org is supposed to be an array but is also used as plain string
			return Array.isArray(org) ? org[0] : org
		}
		return ''

	}

	/**
	 * Return the first name if exists
	 * Returns the displayName otherwise
	 *
	 * @readonly
	 * @memberof Contact
	 * @return {string} firstName|displayName
	 */
	get firstName() {
		if (this.vCard.hasProperty('n')) {
			// reverse and join
			return this.vCard.getFirstPropertyValue('n')[1]
		}
		return this.displayName
	}

	/**
	 * Return the last name if exists
	 * Returns the displayName otherwise
	 *
	 * @readonly
	 * @memberof Contact
	 * @return {string} lastName|displayName
	 */
	get lastName() {
		if (this.vCard.hasProperty('n')) {
			// reverse and join
			return this.vCard.getFirstPropertyValue('n')[0]
		}
		return this.displayName
	}

	/**
	 * Return the phonetic first name if exists
	 * Returns the first name or displayName otherwise
	 *
	 * @readonly
	 * @memberof Contact
	 * @return {string} phoneticFirstName|firstName|displayName
	 */
	get phoneticFirstName() {
		if (this.vCard.hasProperty('x-phonetic-first-name')) {
			return this.vCard.getFirstPropertyValue('x-phonetic-first-name')
		}
		return this.firstName
	}

	/**
	 * Return first matching link for provided type
	 * Returns empty string otherwise
	 *
	 * @param {string} type of social
	 * @readonly
	 * @memberof Contact
	 * @return {string} firstMatchingLink|''
	 */
	socialLink(type) {
		if (this.vCard.hasProperty('x-socialprofile')) {
			const x = this.vCard.getAllProperties('x-socialprofile').filter(a => a.jCal[1].type.toString() === type)

			if (x.length > 0) {
				return x[0].jCal[3].toString()
			}
		}
		return ''
	}

	/**
	 * Return the phonetic last name if exists
	 * Returns the displayName otherwise
	 *
	 * @readonly
	 * @memberof Contact
	 * @return {string} lastName|displayName
	 */
	get phoneticLastName() {
		if (this.vCard.hasProperty('x-phonetic-last-name')) {
			return this.vCard.getFirstPropertyValue('x-phonetic-last-name')
		}
		return this.lastName
	}

	/**
	 * Return all the properties as Property objects
	 *
	 * @readonly
	 * @memberof Contact
	 * @return {Property[]} http://mozilla-comm.github.io/ical.js/api/ICAL.Property.html
	 */
	get properties() {
		return this.vCard.getAllProperties()
	}

	/**
	 * Return an array of formatted properties for the search
	 *
	 * @readonly
	 * @memberof Contact
	 * @return {string[]}
	 */
	get searchData() {
		return this.jCal[1].map(x => x[0] + ':' + x[3])
	}

	/**
	 * Add the contact to the group
	 *
	 * @param {string} group the group to add the contact to
	 * @memberof Contact
	 */
	addToGroup(group) {
		if (this.groups.indexOf(group) === -1) {
			if (this.groups.length > 0) {
				this.vCard.getFirstProperty('categories').setValues(this.groups.concat(group))
			} else {
				this.vCard.updatePropertyWithValue('categories', [group])
			}
		}
	}

	toStringStripQuotes() {
		const regexp = /TYPE="([a-zA-Z-,]+)"/gmi
		const card = this.vCard.toString()
		return card.replace(regexp, 'TYPE=$1')
	}

}
