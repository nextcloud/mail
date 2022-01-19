<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
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
	<Popover trigger="click" class="contact-popover">
		<UserBubble slot="trigger"
			:display-name="label"
			:avatar-image="avatarUrlAbsolute"
			@click="onClickOpenContactDialog">
			<span class="user-bubble-email">{{ email }}</span>
		</UserBubble>
		<div class="contact-wrapper">
			<div v-if="contactsWithEmail && contactsWithEmail.length > 0" class="contact-existing">
				<span class="icon-details">
					{{ t('mail', 'Contacts with this address') }}:
				</span>
				<span>
					{{ contactsWithEmailComputed }}
				</span>
			</div>
			<div v-if="selection === ContactSelectionStateEnum.select">
				<a class="icon-reply" @click="onClickReply">
					<span class="action-label">{{ t('mail', 'Reply') }}</span>
				</a>
				<a class="icon-user" @click="selection = ContactSelectionStateEnum.existing">
					<span class="action-label">{{ t('mail', 'Add to Contact') }}</span>
				</a>
				<a class="icon-add" @click="selection = ContactSelectionStateEnum.new">
					<span class="action-label">{{ t('mail', 'New Contact') }}</span>
				</a>
				<a class="icon-clippy" @click="onClickCopyToClipboard">
					<span class="action-label">{{ t('mail', 'Copy to clipboard') }}</span>
				</a>
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
				<a class="icon-close" type="button" @click="selection = ContactSelectionStateEnum.select">
					{{ t('mail', 'Go back') }}
				</a>
				<a
					v-close-popover
					:disabled="addButtonDisabled"
					class="icon-checkmark"
					type="button"
					@click="onClickAddToContact">
					{{ t('mail', 'Add') }}
				</a>
			</div>
		</div>
	</Popover>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import UserBubble from '@nextcloud/vue/dist/Components/UserBubble'
import Popover from '@nextcloud/vue/dist/Components/Popover'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

import { fetchAvatarUrlMemoized } from '../service/AvatarService'
import { addToContact, findMatches, newContact, autoCompleteByName } from '../service/ContactIntegrationService'
import uniqBy from 'lodash/fp/uniqBy'
import debouncePromise from 'debounce-promise'

const debouncedSearch = debouncePromise(autoCompleteByName, 500)

const ContactSelectionStateEnum = Object.freeze({ new: 1, existing: 2, select: 3 })

export default {
	name: 'RecipientBubble',
	components: {
		UserBubble,
		Popover,
		Multiselect,
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
		onClickCopyToClipboard() {
			this.$copyText(this.email)
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
.icon-clippy,
.icon-user,
.icon-reply,
.icon-checkmark,
.icon-close,
.icon-add {
	height: 44px;
	min-width: 44px;
	margin: 0;
	padding: 9px 18px 10px 32px;
}
@media only screen and (min-width: 600px) {
	.icon-clippy,
	.icon-user,
	.icon-reply,
	.icon-checkmark,
	.icon-close,
	.icon-add {
		background-position: 12px center;
	}
}
.icon-add {
	display: revert;
    vertical-align: revert;
}
.contact-existing {
	margin-bottom: 10px;
	font-size: small;
	.icon-details {
		padding-left: 34px;
		background-position: 10px center;
		text-align: left;
	}
}

</style>
