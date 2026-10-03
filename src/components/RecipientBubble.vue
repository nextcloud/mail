<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcPopover popup-role="dialog" class="contact-popover">
		<template #trigger="{ attrs }">
			<NcUserBubble
				v-bind="attrs"
				:display-name="label"
				:avatar-image="avatarUrlAbsolute"
				:size="size"
				@click="onClickOpenContactDialog" />
		</template>
		<template>
			<div class="contact-wrapper">
				<p class="contact-popover__email">
					{{ email }}
				</p>
				<NcButton
					v-if="contactsWithEmail && contactsWithEmail.length > 0"
					variant="tertiary-no-background"
					:aria-label="t('mail', 'Contacts with this address')"
					class="contact-existing">
					<template #icon>
						<IconDetails :size="20" />
					</template>
					{{ t('mail', 'Contacts with this address') }}: {{ contactsWithEmailComputed }}
				</NcButton>
				<div v-if="selection === ContactSelectionStateEnum.select" class="contact-menu">
					<NcButton
						:aria-label="t('mail', 'Reply')"
						variant="tertiary-no-background"
						@click="onClickReply">
						<template #icon>
							<IconReply :size="20" />
						</template>
						{{ t('mail', 'Reply') }}
					</NcButton>
					<NcButton
						variant="tertiary-no-background"
						:aria-label="t('mail', 'Add to Contact')"
						@click="selection = ContactSelectionStateEnum.existing">
						<template #icon>
							<IconUser :size="20" />
						</template>
						{{ t('mail', 'Add to Contact') }}
					</NcButton>
					<NcButton
						variant="tertiary-no-background"
						:aria-label="t('mail', 'New Contact')"
						@click="selection = ContactSelectionStateEnum.new">
						<template #icon>
							<IconAdd :size="20" />
						</template>
						{{ t('mail', 'New Contact') }}
					</NcButton>
					<NcButton
						variant="tertiary-no-background"
						:aria-label="t('mail', 'Copy to clipboard')"
						@click="onClickCopyToClipboard">
						<template #icon>
							<IconClipboard :size="20" />
						</template>
						{{ t('mail', 'Copy to clipboard') }}
					</NcButton>
					<NcButton
						v-if="canBeAddedAsAlias"
						variant="tertiary-no-background"
						:aria-label="t('mail', 'Add as alias')"
						@click="onClickStartAddAlias">
						<template #icon>
							<IconAlias :size="20" />
						</template>
						{{ t('mail', 'Add as alias') }}
					</NcButton>
				</div>
				<div v-else class="contact-input-wrapper">
					<NcSelect
						v-if="selection === ContactSelectionStateEnum.existing"
						id="contact-selection"
						ref="contact-selection-label"
						v-model="selectedContact"
						:options="selectableContacts"
						:taggable="true"
						track-by="label"
						:multiple="false"
						:placeholder="t('name', 'Contact name …')"
						:clear-search-on-select="true"
						:show-no-options="false"
						:append-to-body="false"
						@search="onAutocomplete" />

					<input v-else-if="selection === ContactSelectionStateEnum.new" v-model="newContactName">

					<input
						v-else-if="selection === ContactSelectionStateEnum.alias"
						v-model="newAliasName"
						:aria-label="t('mail', 'Alias name')"
						:placeholder="t('mail', 'Alias name')"
						@keydown.enter.prevent="$refs.confirmAddButton?.$el?.click()">
				</div>
				<div v-if="selection !== ContactSelectionStateEnum.select">
					<NcButton
						variant="tertiary-no-background"
						:aria-label="t('mail', 'Go back')"
						@click="selection = ContactSelectionStateEnum.select">
						<template #icon>
							<IconClose :size="20" />
						</template>
						{{ t('mail', 'Go back') }}
					</NcButton>

					<NcButton
						ref="confirmAddButton"
						v-close-popover
						:disabled="addButtonDisabled"
						variant="tertiary-no-background"
						:aria-label="t('mail', 'Add')"
						@click="onClickConfirmAdd">
						<template #icon>
							<IconCheck :size="20" />
						</template>
						{{ t('mail', 'Add') }}
					</NcButton>
				</div>
			</div>
		</template>
	</NcPopover>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcPopover, NcSelect, NcUserBubble } from '@nextcloud/vue'
import debouncePromise from 'debounce-promise'
import uniqBy from 'lodash/fp/uniqBy.js'
import { mapStores } from 'pinia'
import IconUser from 'vue-material-design-icons/AccountOutline.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClipboard from 'vue-material-design-icons/ClipboardTextOutline.vue'
import IconClose from 'vue-material-design-icons/CloseOutline.vue'
import IconAlias from 'vue-material-design-icons/EmailPlusOutline.vue'
import IconDetails from 'vue-material-design-icons/InformationOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconReply from 'vue-material-design-icons/ReplyOutline.vue'
import logger from '../logger.js'
import { fetchAvatarUrlMemoized } from '../service/AvatarService.js'
import { addToContact, autoCompleteByName, findMatches, newContact } from '../service/ContactIntegrationService.js'
import useMainStore from '../store/mainStore.js'
import { isCompatibleAliasDomain } from '../util/emailAddress.js'

const debouncedSearch = debouncePromise(autoCompleteByName, 500)

const ContactSelectionStateEnum = Object.freeze({ new: 1, existing: 2, select: 3, alias: 4 })

export default {
	name: 'RecipientBubble',
	components: {
		NcButton,
		NcUserBubble,
		NcPopover,
		NcSelect,
		IconReply,
		IconUser,
		IconAdd,
		IconClose,
		IconClipboard,
		IconDetails,
		IconCheck,
		IconAlias,
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

		size: {
			type: Number,
			default: 26,
		},

		account: {
			type: Object,
			default: null,
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
			newAliasName: '',
			ContactSelectionStateEnum,
			selection: ContactSelectionStateEnum.select,
			isContactPopoverOpen: false,
		}
	},

	computed: {
		activeAccount() {
			return this.account || null
		},

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
			return this.contactsWithEmail.slice(0, 3).map((e) => e.label).join(', ').concat(additional)
		},

		...mapStores(useMainStore),

		canBeAddedAsAlias() {
			if (!this.email) {
				return false
			}
			const activeAccount = this.activeAccount
			if (!activeAccount || activeAccount.isUnified || activeAccount.provisioningId) {
				return false
			}
			const emailClean = this.email.trim().toLowerCase()
			if (!emailClean.includes('@')) {
				return false
			}
			if (activeAccount.emailAddress?.toLowerCase() === emailClean) {
				return false
			}
			const isExistingAlias = (activeAccount.aliases || []).some((a) => (typeof a === 'string' ? a : a?.alias)?.toLowerCase() === emailClean)
			if (isExistingAlias) {
				return false
			}
			return isCompatibleAliasDomain(emailClean, activeAccount)
		},

		addButtonDisabled() {
			if (this.selection === ContactSelectionStateEnum.alias) {
				return !this.newAliasName.trim()
			}
			return !((this.selection === ContactSelectionStateEnum.existing && this.selectedContact)
				|| (this.selection === ContactSelectionStateEnum.new && this.newContactName.trim() !== ''))
		},
	},

	async mounted() {
		try {
			this.avatarUrl = await fetchAvatarUrlMemoized(this.email)
		} catch (error) {
			logger.debug('no avatar for ' + this.email, {
				error,
			})
		}
		this.newContactName = this.label
	},

	methods: {
		onClickStartAddAlias() {
			this.newAliasName = this.activeAccount?.name || this.label || ''
			this.selection = ContactSelectionStateEnum.alias
		},

		async onClickConfirmAdd() {
			if (this.addButtonDisabled) {
				return
			}
			if (this.selection === ContactSelectionStateEnum.alias) {
				await this.onClickAddAsAlias()
			} else {
				this.onClickAddToContact()
			}
		},

		async onClickCopyToClipboard() {
			try {
				await navigator.clipboard.writeText(this.email)
				showSuccess(t('mail', 'Copied email address to clipboard'))
			} catch (e) {
				logger.error('could not copy email address to clipboard', { error: e })
				showError(t('mail', 'Could not copy email address to clipboard'))
			}
		},

		async onClickAddAsAlias() {
			const activeAccount = this.activeAccount
			if (!activeAccount) {
				return
			}
			try {
				await this.mainStore.createAlias({
					account: activeAccount,
					alias: this.email,
					name: this.newAliasName.trim() || activeAccount.name,
				})
				showSuccess(t('mail', 'Alias added successfully'))
				this.selection = ContactSelectionStateEnum.select
			} catch (error) {
				logger.error('Failed to create alias', { error })
				showError(t('mail', 'Could not add alias'))
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
				findMatches(this.email).then((res) => {
					if (res && res.length > 0) {
						this.contactsWithEmail = res
					}
				})
			}
		},

		onClickAddToContact() {
			if (this.selection === ContactSelectionStateEnum.new) {
				if (this.newContactName !== '') {
					newContact(this.newContactName.trim(), this.email).then((res) => logger.debug('ContactIntegration', { res }))
				}
			} else if (this.selection === ContactSelectionStateEnum.existing) {
				if (this.selectedContact) {
					addToContact(this.selectedContact.id, this.email).then((res) => logger.debug('ContactIntegration', { res }))
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
