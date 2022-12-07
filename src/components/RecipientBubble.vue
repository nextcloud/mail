<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<Popover ref="popover" trigger="click" class="contact-popover">
		<UserBubble slot="trigger"
			:display-name="label"
			:avatar-image="avatarUrlAbsolute"
			@click="onClickOpenContactDialog">
			<span class="user-bubble-email">{{ email }}</span>
		</UserBubble>
		<div class="contact-wrapper">
			<ButtonVue v-if="contactsWithEmail && contactsWithEmail.length > 0" type="tertiary-no-background" class="contact-existing">
				<template #icon>
					<IconDetails :size="20" />
				</template>
				{{ t('mail', 'Contacts with this address') }}:
				<span>
					{{ contactsWithEmailComputed }}
				</span>
			</ButtonVue>
			<div v-if="selection === ContactSelectionStateEnum.select" class="contact-menu">
				<ButtonVue type="tertiary-no-background" @click="onClickReply">
					<template #icon>
						<IconReply :size="20" />
					</template>
					{{ t('mail', 'Reply') }}
				</ButtonVue>
				<ButtonVue type="tertiary-no-background" @click="selection = ContactSelectionStateEnum.existing">
					<template #icon>
						<IconUser :size="20" />
					</template>
					{{ t('mail', 'Add to Contact') }}
				</ButtonVue>
				<ButtonVue type="tertiary-no-background" @click="selection = ContactSelectionStateEnum.new">
					<template #icon>
						<IconAdd :size="20" />
					</template>
					{{ t('mail', 'New Contact') }}
				</ButtonVue>
				<ButtonVue type="tertiary-no-background" @click="onClickCopyToClipboard">
					<template #icon>
						<IconClipboard :size="20" />
					</template>
					{{ t('mail', 'Copy to clipboard') }}
				</ButtonVue>
			</div>
			<div v-else class="contact-input-wrapper">
				<Multiselect
					v-if="selection === ContactSelectionStateEnum.existing"
					id="contact-selection"
					ref="contact-selection-label"
					v-model="selectedContact"
					:options="selectableContacts"
					:taggable="true"
					label="label"
					track-by="label"
					:multiple="false"
					:placeholder="t('name', 'Contact name …')"
					:clear-on-select="false"
					:show-no-options="false"
					:preserve-search="true"
					@search-change="onAutocomplete" />

				<input v-else-if="selection === ContactSelectionStateEnum.new" v-model="newContactName">
			</div>
			<div v-if="selection !== ContactSelectionStateEnum.select">
				<ButtonVue type="tertiary-no-background" @click="selection = ContactSelectionStateEnum.select">
					<template #icon>
						<IconClose :size="20" />
					</template>
					{{ t('mail', 'Go back') }}
				</ButtonVue>

				<ButtonVue
					v-close-popover
					:disabled="addButtonDisabled"
					type="tertiary-no-background"
					@click="onClickAddToContact">
					<template #icon>
						<IconCheck :size="20" />
					</template>
					{{ t('mail', 'Add') }}
				</ButtonVue>
			</div>
		</div>
	</Popover>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

import { NcUserBubble as UserBubble, NcPopover as Popover, NcMultiselect as Multiselect, NcButton as ButtonVue } from '@nextcloud/vue'

import IconReply from 'vue-material-design-icons/Reply'
import IconAdd from 'vue-material-design-icons/Plus'
import IconClose from 'vue-material-design-icons/Close'
import IconClipboard from 'vue-material-design-icons/ClipboardText'
import IconDetails from 'vue-material-design-icons/Information'
import IconCheck from 'vue-material-design-icons/Check'
import IconUser from 'vue-material-design-icons/Account'
import { fetchAvatarUrlMemoized } from '../service/AvatarService'
import { addToContact, findMatches, newContact, autoCompleteByName } from '../service/ContactIntegrationService'
import uniqBy from 'lodash/fp/uniqBy'
import debouncePromise from 'debounce-promise'
import { showError, showSuccess } from '@nextcloud/dialogs'

const debouncedSearch = debouncePromise(autoCompleteByName, 500)

const ContactSelectionStateEnum = Object.freeze({ new: 1, existing: 2, select: 3 })

export default {
	name: 'RecipientBubble',
	components: {
		ButtonVue,
		UserBubble,
		Popover,
		Multiselect,
		IconReply,
		IconUser,
		IconAdd,
		IconClose,
		IconClipboard,
		IconDetails,
		IconCheck,
	},
	props: {
		email: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			avatarUrl: undefined,
			loadingContacts: false,
			contactsWithEmail: [],
			autoCompleteContacts: [],
			selectedContact: '',
			newContactName: '',
			ContactSelectionStateEnum,
			selection: ContactSelectionStateEnum.select,
			isContactPopoverOpen: false,
		}
	},
	computed: {
		avatarUrlAbsolute() {
			if (!this.avatarUrl) {
				return
			}
			if (this.avatarUrl.startsWith('http')) {
				return this.avatarUrl
			}

			// Make it an absolute URL because the user bubble component doesn't work with relative URLs
			return window.location.protocol + '//' + window.location.host + generateUrl(this.avatarUrl)
		},
		selectableContacts() {
			return this.autoCompleteContacts
				.map((contact) => ({ ...contact, label: contact.label }))
		},
		contactsWithEmailComputed() {
			let additional = ''
			if (this.contactsWithEmail && this.contactsWithEmail.length > 3) {
				additional = ` + ${this.contactsWithEmail.length - 3}`
			}
			return this.contactsWithEmail.slice(0, 3).map(e => e.label).join(', ').concat(additional)
		},
		addButtonDisabled() {
			return !((this.selection === ContactSelectionStateEnum.existing && this.selectedContact)
					|| (this.selection === ContactSelectionStateEnum.new && this.newContactName.trim() !== ''))
		},
	},
	async mounted() {
		try {
			this.avatarUrl = await fetchAvatarUrlMemoized(this.email)
		} catch (error) {
			console.debug('no avatar for ' + this.email, {
				error,
			})
		}
		this.newContactName = this.label
	},
	methods: {
		async onClickCopyToClipboard() {
			try {
				await navigator.clipboard.writeText(this.email)
				showSuccess(t('mail', 'Copied email address to clipboard'))
			} catch (e) {
				showError(t('mail', 'Could not copy email address to clipboard'))
			}
		},
		onClickReply() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'mailto',
				},
				query: {
					to: this.email,
				},
			})
		},
		onClickOpenContactDialog() {
			if (this.contactsWithEmail.length === 0) { // TODO fix me
				findMatches(this.email).then(res => {
					if (res && res.length > 0) {
						this.contactsWithEmail = res
					}
				})
			}
		},
		onClickAddToContact() {
			if (this.selection === ContactSelectionStateEnum.new) {
				if (this.newContactName !== '') {
					newContact(this.newContactName.trim(), this.email).then(res => console.debug('ContactIntegration', res))
				}
			} else if (this.selection === ContactSelectionStateEnum.existing) {
				if (this.selectedContact) {
					addToContact(this.selectedContact.id, this.email).then(res => console.debug('ContactIntegration', res))
				}
			}
		},
		onAutocomplete(term) {
			if (term === undefined || term === '') {
				return
			}
			debouncedSearch(term).then((results) => {
				this.autoCompleteContacts = uniqBy('id')(this.autoCompleteContacts.concat(results))
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.user-bubble__title {
	max-width: 30vw;
}
.user-bubble-email {
	margin: 10px;
}

.contact-menu {
	display: flex;
	flex-wrap: wrap;
}
.contact-popover {
	display: inline-block;
}
.contact-wrapper {
	padding:10px;
	min-width: 300px;

	a {
		opacity: 0.7;
	}
	a:hover {
		opacity: 1;
	}
}
.contact-input-wrapper {
	margin-top: 10px;
    margin-bottom: 10px;
	input,
	.multiselect {
		width: 100%;
	}
}

.contact-existing {
	font-size: small !important;
}
:deep(.button-vue__text) {
	font-weight: normal !important;
}
</style>
