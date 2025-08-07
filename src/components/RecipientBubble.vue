<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Popover popup-role="dialog" class="contact-popover">
		<template #trigger="{ attrs }">
			<UserBubble v-bind="attrs"
				:display-name="label"
				:size="26"
				:avatar-image="avatarUrlAbsolute"
				@click="onClickOpenContactDialog" />
		</template>
		<template>
			<div class="contact-wrapper">
				<p class="contact-popover__email">
					{{ email }}
				</p>
				<ButtonVue v-if="contactsWithEmail && contactsWithEmail.length > 0"
					type="tertiary-no-background"
					:aria-label="t('mail', 'Contacts with this address')"
					class="contact-existing">
					<template #icon>
						<IconDetails :size="20" />
					</template>
					{{ t('mail', 'Contacts with this address') }}: {{ contactsWithEmailComputed }}
				</ButtonVue>
				<div v-if="selection === ContactSelectionStateEnum.select" class="contact-menu">
					<ButtonVue :aria-label="t('mail', 'Reply')"
						type="tertiary-no-background"
						@click="onClickReply">
						<template #icon>
							<IconReply :size="20" />
						</template>
						{{ t('mail', 'Reply') }}
					</ButtonVue>
					<ButtonVue type="tertiary-no-background"
						:aria-label="t('mail', 'Add to Contact')"
						@click="selection = ContactSelectionStateEnum.existing">
						<template #icon>
							<IconUser :size="20" />
						</template>
						{{ t('mail', 'Add to Contact') }}
					</ButtonVue>
					<ButtonVue type="tertiary-no-background"
						:aria-label="t('mail', 'New Contact')"
						@click="selection = ContactSelectionStateEnum.new">
						<template #icon>
							<IconAdd :size="20" />
						</template>
						{{ t('mail', 'New Contact') }}
					</ButtonVue>
					<ButtonVue type="tertiary-no-background"
						:aria-label="t('mail', 'Copy to clipboard')"
						@click="onClickCopyToClipboard">
						<template #icon>
							<IconClipboard :size="20" />
						</template>
						{{ t('mail', 'Copy to clipboard') }}
					</ButtonVue>
				</div>
				<div v-else class="contact-input-wrapper">
					<NcSelect v-if="selection === ContactSelectionStateEnum.existing"
						id="contact-selection"
						ref="contact-selection-label"
						v-model="selectedContact"
						:options="selectableContacts"
						:taggable="true"
						track-by="label"
						:multiple="false"
						:placeholder="t('name', 'Contact name …')"
						:clear-search-on-select="true"
						:show-no-options="false"
						:append-to-body="false"
						@search="onAutocomplete" />

					<input v-else-if="selection === ContactSelectionStateEnum.new" v-model="newContactName">
				</div>
				<div v-if="selection !== ContactSelectionStateEnum.select">
					<ButtonVue type="tertiary-no-background"
						:aria-label="t('mail', 'Go back')"
						@click="selection = ContactSelectionStateEnum.select">
						<template #icon>
							<IconClose :size="20" />
						</template>
						{{ t('mail', 'Go back') }}
					</ButtonVue>

					<ButtonVue v-close-popover
						:disabled="addButtonDisabled"
						type="tertiary-no-background"
						:aria-label="t('mail', 'Add')"
						@click="onClickAddToContact">
						<template #icon>
							<IconCheck :size="20" />
						</template>
						{{ t('mail', 'Add') }}
					</ButtonVue>
				</div>
			</div>
		</template>
	</Popover>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

import { NcUserBubble as UserBubble, NcPopover as Popover, NcSelect, NcButton as ButtonVue } from '@nextcloud/vue'

import IconReply from 'vue-material-design-icons/ReplyOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconClose from 'vue-material-design-icons/CloseOutline.vue'
import IconClipboard from 'vue-material-design-icons/ClipboardTextOutline.vue'
import IconDetails from 'vue-material-design-icons/InformationOutline.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconUser from 'vue-material-design-icons/AccountOutline.vue'
import { fetchAvatarUrlMemoized } from '../service/AvatarService.js'
import { addToContact, findMatches, newContact, autoCompleteByName } from '../service/ContactIntegrationService.js'
import uniqBy from 'lodash/fp/uniqBy.js'
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
		NcSelect,
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
			selectedContact: null,
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

.contact-menu {
	display: flex;
	flex-wrap: wrap;
}

.contact-popover {
	display: flex;

	&__email {
		text-align: center;
	}
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
	input {
		width: 100%;
	}
}

.contact-existing {
	font-size: small !important;
}

:deep(.button-vue__text) {
	font-weight: normal !important;
}

:deep(.vs__dropdown-menu) {
	// Make the dropdown scrollable
	max-height: 100px;
}
</style>
