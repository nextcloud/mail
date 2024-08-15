<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppContentDetails>
		<!-- nothing selected or contact not found -->
		<EmptyContent v-if="!contact"
			class="empty-content"
			:name="t('contacts', 'No contact selected')"
			:description="t('contacts', 'Select a contact on the list to begin')">
			<template #icon>
				<IconContact :size="20" />
			</template>
		</EmptyContent>

		<!-- TODO: add empty content while this.loadingData === true -->
		<template v-else>
			<!-- contact header -->
			<DetailsHeader>
				<!-- avatar and upload photo -->
				<template #avatar>
					<ContactAvatar :contact="contact"
						:is-read-only="isReadOnly"
						:reload-bus="reloadBus"
						@update-local-contact="updateLocalContact" />
				</template>

				<!-- fullname -->
				<template #title>
					<div v-if="isReadOnly" class="contact-title">
						{{ contact.fullName }}
					</div>
					<input v-else
						id="contact-fullname"
						ref="fullname"
						v-model="contact.fullName"
						:placeholder="t('contacts', 'Name')"
						type="text"
						autocomplete="off"
						autocorrect="off"
						spellcheck="false"
						name="fullname"
						@click="selectInput">
				</template>

				<!-- org, title -->
				<template #subtitle>
					<template v-if="isReadOnly">
						<span v-html="formattedSubtitle" />
					</template>
					<template v-else>
						<input id="contact-title"
							v-model="contact.title"
							:placeholder="t('contacts', 'Title')"
							type="text"
							autocomplete="off"
							autocorrect="off"
							spellcheck="false"
							name="title">
						<input id="contact-org"
							v-model="contact.org"
							:placeholder="t('contacts', 'Company')"
							type="text"
							autocomplete="off"
							autocorrect="off"
							spellcheck="false"
							name="org">
					</template>
				</template>

				<div v-if="!loadingData" class="contact-details-wrapper">
					<div v-for="(properties, name) in groupedProperties"
						:key="name">
						<ContactDetailsProperty v-for="(property, index) in properties"
							:key="`${index}-${contact.key}-${property.name}`"
							:is-first-property="index===0"
							:is-last-property="index === properties.length - 1"
							:property="property"
							:contact="contact"
							:local-contact="localContact"
							:contacts="contacts"
							:bus="bus"
							:is-read-only="isReadOnly" />
					</div>
					<!-- addressbook change select - no last property because class is not applied here,
					empty property because this is a required prop on regular property-select. But since
					we are hijacking this... (this is supposed to be used with a ICAL.property, but to avoid code
					duplication, we created a fake propModel and property with our own options here) -->
					<PropertySelect :prop-model="addressbookModel"
						:options="addressbooksOptions"
						:value.sync="addressbook"
						:is-first-property="true"
						:is-last-property="true"
						:property="{}"
						:hide-actions="true"
						:is-read-only="isReadOnly"
						class="property--addressbooks property--last" />

					<!-- Groups always visible -->
					<PropertyGroups :prop-model="groupsModel"
						:value.sync="localContact.groups"
						:contact="contact"
						:is-read-only="isReadOnly"
						class="property--groups property--last"
						@update:value="updateGroups" />
				</div>
			</Detailsheader>
		</template>
	</AppContentDetails>
</template>

<script>
import {
	NcAppContentDetails as AppContentDetails,
	NcEmptyContent as EmptyContent,
	isMobile,
} from '@nextcloud/vue'
import IconContact from 'vue-material-design-icons/AccountMultiple.vue'
import mitt from 'mitt'
import DetailsHeader from './DetailsHeaderRecipient.vue'
import { loadState } from '@nextcloud/initial-state'

const { profileEnabled } = loadState('user_status', 'profileEnabled', false)

export default {
	name: 'RecipientDetails',

	components: {
		AppContentDetails,
		DetailsHeader,
		EmptyContent,
		IconContact,

	},

	mixins: [isMobile],

	props: {
		contactKey: {
			type: String,
			default: undefined,
		},
		contacts: {
			type: Array,
			default: () => [],
		},
		reloadBus: {
			type: Object,
			required: true,
		},
		desc: {
			type: String,
			required: false,
			default: '',
		},
	},

	data() {
		return {
			loadingData: true,
			// if true, the local contact have been fixed and requires a push
			fixed: false,
			contactDetailsSelector: '.contact-details',
			excludeFromBirthdayKey: 'x-nc-exclude-from-birthday-calendar',

			// communication for ContactDetailsAddNewProp and ContactDetailsProperty
			bus: mitt(),
			showMenuPopover: false,
			profileEnabled,

		}
	},

	computed: {
		// store getter
		addressbooks() {
			return this.$store.getters.getAddressbooks
		},
		contact() {
			return this.$store.getters.getContact(this.contactKey)
		},

		/**
		 * Conflict message
		 *
		 * @return {string|boolean}
		 */
		conflict() {
			if (this.contact.conflict) {
				return t('contacts', 'The contact you were trying to edit has changed. Please manually refresh the contact. Any further edits will be discarded.')
			}
			return false
		},

		/**
		 * Contact properties copied and sorted by rfcProps.fieldOrder
		 *
		 * @return {Array}
		 */
		sortedProperties() {
			return this.localContact.properties
				.slice(0)
				.sort((a, b) => {
					const nameA = a.name.split('.').pop()
					const nameB = b.name.split('.').pop()
					return rfcProps.fieldOrder.indexOf(nameA) - rfcProps.fieldOrder.indexOf(nameB)
				})
		},

		/**
		 * Contact properties filtered and grouped by rfcProps.fieldOrder
		 *
		 * @return {object}
		 */
		groupedProperties() {
			return this.sortedProperties
				.reduce((list, property) => {
					// If there is no component to display this prop, ignore it
					if (!this.canDisplay(property)) {
						return list
					}

					// Init if needed
					if (!list[property.name]) {
						list[property.name] = []
					}

					list[property.name].push(property)
					return list
				}, {})
		},

		/**
		 * Fake model to use the propertySelect component
		 *
		 * @return {object}
		 */
		addressbookModel() {
			return {
				readableName: t('contacts', 'Address book'),
				icon: 'icon-address-book',
				options: this.addressbooksOptions,
			}
		},

		/**
		 * Usable addressbook object linked to the local contact
		 *
		 * @param {string} [addressbookId] set the addressbook id
		 * @return {string}
		 */
		addressbook: {
			get() {
				return this.contact.addressbook.id
			},
			set(addressbookId) {
				// Only move when the address book actually changed to prevent a conflict.
				if (this.contact.addressbook.id !== addressbookId) {
					this.moveContactToAddressbook(addressbookId)
				}
			},
		},

		/**
		 * Fake model to use the propertyGroups component
		 *
		 * @return {object}
		 */
		groupsModel() {
			return {
				readableName: t('contacts', 'Contact groups'),
				icon: 'icon-contacts-dark',
			}
		},
	},
	methods: {
		updateGroups(value) {
			this.newGroupsValue = value
		},
		/**
		 *  Update this.localContact and set this.fixed
		 *
		 * @param {Contact} contact the contact to clone
		 */
		async updateLocalContact(contact) {
			// create empty contact and copy inner data
			const localContact = Object.assign(
				Object.create(Object.getPrototypeOf(contact)),
				contact,
			)

			this.fixed = validate(localContact)

			this.localContact = localContact
			this.newGroupsValue = [...this.localContact.groups]
		},
	},
}
</script>

<style lang="scss" scoped>
// List of all properties
.contact-details-wrapper {
	display: inline;
	align-items: flex-start;
	padding-bottom: 20px;
	gap: 15px;
	float: left;
}
@media only screen and (max-width: 600px) {
	.contact-details-wrapper {
		display: block;
	}
}

section.contact-details {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

#qrcode-modal {
	:deep(.modal-container) {
		display: flex;
		padding: 10px;
		background-color: #fff;
		.qrcode {
			max-width: 100%;
		}
	}
}

:deep(.v-select.select) {
	min-width: 0;
	flex: 1 auto;
}

:deep(.v-select.select .vs__selected-options), :deep(.vs__search) {
	min-height: unset;
	margin: 0 !important;
}

:deep(.vs__selected) {
	height: calc(var(--default-clickable-area) - var(--default-grid-baseline)) !important;
	margin: calc(var(--default-grid-baseline) / 2);
}

#pick-addressbook-modal {
	:deep(.modal-container) {
		display: flex;
		overflow: visible;
		flex-wrap: wrap;
		justify-content: space-evenly;
		margin-bottom: 20px;
		padding: 10px;
		background-color: #fff;
		.multiselect {
			flex: 1 1 100%;
			width: 100%;
			margin-bottom: 20px;
		}
	}
}

.action-item {
	background-color: var(--color-primary-element-light);
	border-radius: var(--border-radius-rounded);
}

:deep(.button-vue--vue-tertiary:hover),
:deep(.button-vue--vue-tertiary:active) {
	background-color: var(--color-primary-element-light-hover) !important;
}

.related-resources {
	display:inline-grid;
	margin-top: 88px;
	flex-direction: column;
	margin-bottom: -30px;
}
@media only screen and (max-width: 1600px) {
	.related-resources {
		float: left;
		display: inline-grid;
		margin-left: 80px;
		flex-direction: column;
		margin-bottom: 0;
		margin-top: 40px;
	}
}

.last-edit {
	display: inline-flex;
}
// forcing the size only for contacts app to fit the text size of the contacts app
:deep(.related-resources__header h5) {
	font-size: medium;
	opacity: .7;
	color: var(--color-primary-element);
}

.address-book {
	min-width: 260px !important;
}

.empty-content {
	height: 100%;
}
.contact-title {
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
</style>
