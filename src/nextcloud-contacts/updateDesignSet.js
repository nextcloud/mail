/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import ICAL from 'ical.js'

/**
 * Prevents ical.js from adding 'VALUE=PHONE-NUMBER' in vCard 3.0.
 * While not wrong according to the RFC, there's a bug in sabreio/vobject (used
 * by Nextcloud Server) that prevents saving vCards with this parameters.
 *
 * @link https://github.com/nextcloud/contacts/pull/1393#issuecomment-570945735
 * @return {boolean} Whether or not the design set has been altered.
 */
const removePhoneNumberValueType = () => {
	if (ICAL.design.vcard3.property.tel) {
		delete ICAL.design.vcard3.property.tel
		return true
	}

	return false
}

/**
 * Some clients group properties by naming them something like 'ITEM1.URL'.
 * These should be treated the same as their original (i.e. 'URL' in this
 * example), so we iterate through the vCard to find these properties and
 * add them to the ical.js design set.
 *
 * @link https://github.com/nextcloud/contacts/issues/42
 * @param {Array} vCard The ical.js vCard
 * @return {boolean} Whether or not the design set has been altered.
 */
const addGroupedProperties = vCard => {
	let madeChanges = false
	vCard[1].forEach(prop => {
		const propGroup = prop[0].split('.')

		// if this is a grouped property, update the designSet
		if (propGroup.length === 2) {
			madeChanges = setPropertyAlias(propGroup[1], prop[0])
		}
	})
	return madeChanges
}

/**
 * Fixes misbehaviour with TYPE quotes and separated commas
 * Seems to have been introduced with https://github.com/mozilla-comm/ical.js/pull/387
 *
 * @return {boolean} Whether or not the design set has been altered.
 */
const setTypeMultiValueSeparateDQuote = () => {
	if (
		!ICAL.design.vcard.param.type
		|| ICAL.design.vcard.param.type.multiValueSeparateDQuote !== false
		|| !ICAL.design.vcard3.param.type
		|| ICAL.design.vcard3.param.type.multiValueSeparateDQuote !== false
	) {
		// https://github.com/mozilla-comm/ical.js/blob/ba8e2522ffd30ffbe65197a96a487689d6e6e9a1/lib/ical/stringify.js#L121
		ICAL.design.vcard.param.type.multiValueSeparateDQuote = false
		ICAL.design.vcard3.param.type.multiValueSeparateDQuote = false

		return true
	}

	return false
}

/**
/**
* Check whether the ical.js design sets need updating (and if so, do it)
 *
 * @param {Array} vCard The ical.js vCard
 * @return {boolean} Whether or not the design set has been altered.
 */
export default function(vCard) {
	let madeChanges = false

	madeChanges |= setTypeMultiValueSeparateDQuote()
	madeChanges |= removePhoneNumberValueType()
	madeChanges |= addGroupedProperties(vCard)

	return madeChanges
}

/**
 * @param {string} original Name of the property whose settings should be copied
 * @param {string} alias Name of the new property
 * @return {boolean} Whether or not the design set has been altered.
 */
export function setPropertyAlias(original, alias) {
	let madeChanges = false
	original = original.toLowerCase()
	alias = alias.toLowerCase()

	if (ICAL.design.vcard.property[original]) {
		ICAL.design.vcard.property[alias] = ICAL.design.vcard.property[original]
		madeChanges = true
	}

	if (ICAL.design.vcard3.property[original]) {
		ICAL.design.vcard3.property[alias] = ICAL.design.vcard3.property[original]
		madeChanges = true
	}

	return madeChanges
}
