<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- If not in the rfcProps then we don't want to display it -->
	<component :is="componentInstance"
		ref="component"
		:select-type.sync="selectType"
		:prop-model="propModel"
		:value.sync="value"
		:is-first-property="isFirstProperty"
		:property="property"
		:is-last-property="isLastProperty"
		:class="{
			'property--last': isLastProperty,
			[`property-${propName}`]: true,
		}"
		:local-contact="localContact"
		:prop-name="propName"
		:prop-type="propType"
		:options="sortedModelOptions"
		:bus="bus"
		:is-read-only="isReadOnly" />
</template>

<script>
import ICAL from 'ical.js'
import Contact from './contact.js'
import rfcProps from './rfcPropsRecipient.js'

import RecipientPropertyText from './RecipientPropertyText.vue'

export default {
	name: 'RecipientDetailsProperty',

	props: {
		property: {
			type: ICAL.Property,
			default: true,
		},

		/**
		 * Is it the first property of its kind
		 */
		isFirstProperty: {
			type: Boolean,
			default: false,
		},
		/**
		 * Is it the last property of its kind
		 */
		isLastProperty: {
			type: Boolean,
			default: false,
		},

		contact: {
			type: Contact,
			default: null,
		},
		localContact: {
			type: Contact,
			default: null,
		},
		contacts: {
			type: Array,
			default: () => [],
		},
		bus: {
			type: Object,
			required: true,
		},
		isReadOnly: {
			type: Boolean,
			required: true,
		},
	},

	computed: {
		// dynamically load component based on property type
		componentInstance() {
			if (this.propType && this.propType !== 'unknown') {
				return RecipientPropertyText
			}
			return RecipientPropertyText
		},

		// eslint-disable-next-line no-mixed-spaces-and-tabs
 		// rfc properties list
		properties() {
			return rfcProps.properties
		},

		/**
		 * Return the type of the prop e.g. FN
		 *
		 * @return {string}
		 */
		propName() {
			// ! is this a ITEMXX.XXX property??
			if (this.propGroup[1]) {
				return this.propGroup[1]
			}

			return this.property.name
		},

		/**
		 * Return the type or property
		 *
		 * @see src/models/rfcProps
		 * @return {string}
		 */
		propType() {
			// if we have a force type set, use it!
			if (this.propModel && this.propModel.force) {
				return this.propModel.force
			}

			return this.property.getDefaultType()
		},

		/**
		 * RFC template matching this property
		 *
		 * @see src/models/rfcProps
		 * @return {object}
		 */
		propModel() {
			return this.properties[this.propName]
		},

		/**
		 * Remove duplicate name amongst options
		 * but make sure to include the selected one
		 * in the final list
		 *
		 * @return {object[]}
		 */
		sortedModelOptions() {
			if (!this.propModel.options) {
				return []
			}

			if (typeof this.propModel.options === 'function') {
				return this.propModel.options({
					contact: this.contact,
					$store: this.$store,
					selectType: this.selectType,
				})
			} else {
				return this.propModel.options.reduce((list, option) => {
					if (!list.find(search => search.name === option.name)) {
						list.push(option)
					}
					return list
				}, this.selectType ? [this.selectType] : [])
			}
		},

		/**
		 * Return the id and type of a property group
		 * e.g ITEMXX.tel => ['ITEMXX', 'tel']
		 *
		 * @return {Array}
		 */
		propGroup() {
			return this.property.name.split('.')
		},

		/**
		 * Return the associated X-ABLABEL if any
		 *
		 * @return {ICAL.Property}
		 */
		propLabel() {
			return this.localContact.vCard.getFirstProperty(`${this.propGroup[0]}.x-ablabel`)
		},

		/**
		 * Returns the closest match to the selected type
		 * or return the default selected as a new object if
		 * none exists
		 *
		 * @return Object|null
		 */
		 selectType: {
			get() {
				// ! if ABLABEL is present, this is a priority
				if (this.propLabel) {
					return {
						id: this.propLabel.name,
						name: this.propLabel.getFirstValue(),
					}
				}
				if (this.type) {
					// vcard 3.0 save pref alongside TYPE
					const selectedType = this.type
						.filter(type => type !== 'pref')
						.join(',')
					if (selectedType.trim() !== '') {
						return {
							id: selectedType,
							name: selectedType,
						}
					}
				}
				return null
			},
			set(data) {
				// Skip setting type if select is cleared
				if (!data) {
					return
				}

				// if a custom label exists and this is the one we selected
				if (this.propLabel && data.id === this.propLabel.name) {
					this.propLabel.setValue(data.name)
					// only one can coexist
					this.type = []
				} else {
					// ical.js take types as arrays
					this.type = data.id.split(',')
					// only one can coexist
					this.localContact.vCard.removeProperty(`${this.propGroup[0]}.x-ablabel`)

					// checking if there is any other property in this group
					const groups = this.localContact.jCal[1]
						.map(prop => prop[0])
						.filter(name => name.startsWith(`${this.propGroup[0]}.`))
					if (groups.length === 1) {
						// then this prop is the latest of its group
						// -> converting back to simple prop
						// eslint-disable-next-line vue/no-mutating-props
						this.property.jCal[0] = this.propGroup[1]
					}
				}
			},

		},
		// property value(s)
		value: {
			get() {
				if (this.property.isMultiValue) {
					// differences between values types :x;x;x;x;x and x,x,x,x,x
					return this.property.isStructuredValue
						? this.property.getValues()[0]
						: this.property.getValues()
				}
				if (this.propName === 'x-managersname') {
					if (this.property.getParameter('uid')) {
						return this.property.getParameter('uid') + '~' + this.contact.addressbook.id
					}
					// Try to find the matching contact by display name
					// TODO: this only *shows* the display name but doesn't assign the missing UID
					const displayName = this.property.getFirstValue()
					const other = this.otherContacts(this.contact).find(contact => contact.displayName === displayName)
					return other?.key
				}
				return this.property.getFirstValue()
			},
			set(data) {
				if (this.property.isMultiValue) {
					// differences between values types :x;x;x;x;x and x,x,x,x,x
					this.property.isStructuredValue
						? this.property.setValues([data])
						: this.property.setValues(data)
				} else {
					if (this.propName === 'x-managersname') {
						const manager = this.$store.getters.getContact(data)
						this.property.setValue(manager.displayName)
						this.property.setParameter('uid', manager.uid)
					} else {
						this.property.setValue(data)
					}
				}
			},
		},

		// property meta type
		type: {
			get() {
				const type = this.property.getParameter('type')
				// ensure we have an array
				if (type) {
					return Array.isArray(type) ? type : [type]
				}
				return null
			},
			set(data) {
				this.property.setParameter('type', data)
			},
		},

		// property meta pref
		pref: {
			get() {
				return this.property.getParameter('pref')
			},
			set(data) {
				this.property.setParameter('pref', data)
			},
		},
	},

	created() {
		this.bus.on('focus-prop', this.onFocusProp)
	},

	destroyed() {
		this.bus.off('focus-prop', this.onFocusProp)
	},

	methods: {
		/**
		 * Focus first input element of the new prop
		 *
		 * @param {string} id the id of the property
		 */
		onFocusProp(id) {
			if (id === this.propName && this.isLastProperty) {
				this.$nextTick(() => {
					const inputs = this.$refs.component.$el.querySelectorAll('input, textarea')
					if (inputs === undefined || inputs.length === 0) {
						console.warn('no input to focus found')
					} else {
						inputs[0].focus()
					}
				})
			}
		},
	},
}
</script>
