<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog
		:name="t('mail', 'Choose target folder')"
		:buttons="saveButtons"
		@closing="onClose">
		<NcInputField
			v-model="filterName"
			:label="t('mail', 'Search')" />
		<NcBreadcrumbs v-if="!filtering">
			<NcBreadcrumb
				v-for="(box, index) in mailboxCrumbs"
				:key="box.databaseId"
				:name="getMailboxTitle(box)"
				@click="onClickCrumb(index)" />
		</NcBreadcrumbs>
		<div v-if="filteredMailboxes.length > 0">
			<ul>
				<NcListItem
					v-for="box in filteredMailboxes"
					:key="box.databaseId"
					compact
					:name="getMailboxTitle(box)"
					@click.prevent="onClickMailbox(box)">
					<template #icon>
						<IconInbox v-if="box.specialRole === 'inbox'" :size="20" />
						<IconDraft
							v-else-if="box.specialRole === 'drafts'"
							:size="20" />
						<IconSent
							v-else-if="box.specialRole === 'sent'"
							:size="20" />
						<IconArchive
							v-else-if="box.specialRole === 'archive'"
							:size="20" />
						<IconTrash
							v-else-if="box.specialRole === 'trash'"
							:size="20" />
						<IconFolder
							v-else
							:size="20" />
					</template>
				</NcListItem>
			</ul>
		</div>
		<NcEmptyContent v-else>
			<template #icon>
				<IconFolder />
			</template>
			<template #description>
				<p v-if="filterName == ''">
					{{ t('mail', 'No more submailboxes in here') }}
				</p>
				<p v-else>
					{{ t('mail', 'No results') }}
				</p>
			</template>
		</NcEmptyContent>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcBreadcrumb, NcBreadcrumbs, NcDialog, NcEmptyContent, NcInputField, NcListItem } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import IconArchive from 'vue-material-design-icons/ArchiveArrowDownOutline.vue'
import IconFolder from 'vue-material-design-icons/FolderOutline.vue'
import IconInbox from 'vue-material-design-icons/HomeOutline.vue'
import IconDraft from 'vue-material-design-icons/PencilOutline.vue'
import IconSent from 'vue-material-design-icons/SendOutline.vue'
import IconTrash from 'vue-material-design-icons/TrashCanOutline.vue'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator.js'
import useMainStore from '../store/mainStore.js'
import { mailboxHasRights } from '../util/acl.js'

export default {
	name: 'MailboxPicker',
	components: {
		NcDialog,
		NcEmptyContent,
		NcBreadcrumbs,
		NcBreadcrumb,
		NcInputField,
		NcListItem,
		IconInbox,
		IconDraft,
		IconSent,
		IconArchive,
		IconTrash,
		IconFolder,
	},

	props: {
		account: {
			type: Object,
			required: true,
		},

		selected: {
			type: Number,
			required: false,
			default: undefined,
		},

		loading: {
			type: Boolean,
			required: false,
			default: false,
		},

		labelSelect: {
			type: String,
			default: t('mail', 'Choose'),
		},

		labelSelectLoading: {
			type: String,
			default: t('mail', 'Choose'),
		},

		pickedMailbox: {
			type: Object,
			required: false,
			default: () => undefined,
		},

		allowRoot: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			selectedMailboxId: undefined,
			filterName: '',
			mailboxCrumbs: [
				{
					databaseId: undefined,
					specialUse: [],
					displayName: '/',
				},
			],
		}
	},

	computed: {
		...mapStores(useMainStore),

		filtering() {
			return this.filterName !== ''
		},

		mailboxes() {
			if (this.filtering) {
				const actualFilter = this.filterName.toLowerCase().trim()
				const mailboxes = []
				for (const mailbox of this.mainStore.getRecursiveMailboxIterator(this.account.accountId)) {
					const mailboxName = translateMailboxName(mailbox)
					if (mailboxName.toLowerCase().includes(actualFilter)) {
						mailboxes.push(mailbox)
					}
				}
				return mailboxes
			} else if (!this.selectedMailboxId) {
				return this.mainStore.getMailboxes(this.account.accountId)
			} else {
				return this.mainStore.getSubMailboxes(this.selectedMailboxId)
			}
		},

		filteredMailboxes() {
			if (this.pickedMailbox) {
				return this.mailboxes.filter((mailbox) => mailbox.databaseId !== this.pickedMailbox.databaseId && mailboxHasRights(mailbox, 'k'))
			}
			return this.mailboxes.filter((mailbox) => mailboxHasRights(mailbox, 'i'))
		},

		saveButtons() {
			return [
				{
					variant: 'primary',
					disabled: this.loading || (!this.allowRoot && !this.selectedMailboxId),
					callback: this.onSelect,
					label: this.loading ? this.labelSelectLoading : this.labelSelect,
				},
			]
		},
	},

	methods: {
		translateMailboxPath(mailbox) {
			const fullname = []

			let parent = mailbox
			while (parent) {
				fullname.push(translateMailboxName(parent))
				parent = this.mainStore.getParentMailbox(parent.databaseId)
			}

			return fullname.reverse().join(' / ')
		},

		getMailboxTitle(mailbox) {
			if (this.filtering) {
				return this.translateMailboxPath(mailbox)
			} else {
				return translateMailboxName(mailbox)
			}
		},

		onClickCrumb(index) {
			this.filterName = ''
			this.selectedMailboxId = this.mailboxCrumbs[index].databaseId
			this.$emit('update:selected', this.selectedMailboxId)
			this.mailboxCrumbs = this.mailboxCrumbs.slice(0, index + 1)
		},

		rebuildBreadcrumb(mailbox) {
			const newBreadcrumb = []

			let parent = mailbox
			while (parent) {
				newBreadcrumb.push(parent)
				parent = this.mainStore.getParentMailbox(parent.databaseId)
			}

			newBreadcrumb.push(this.mailboxCrumbs[0])

			this.mailboxCrumbs = newBreadcrumb.reverse()
		},

		onClickMailbox(mailbox) {
			if (this.filtering) {
				this.filterName = ''
				this.rebuildBreadcrumb(mailbox)
			} else {
				this.mailboxCrumbs.push(mailbox)
			}

			this.selectedMailboxId = mailbox.databaseId
			this.$emit('update:selected', this.selectedMailboxId)
		},

		async onSelect() {
			return new Promise((resolve) => {
				this.$emit('select', this.selectedMailboxId, () => resolve())
			})
		},

		onClose() {
			this.$emit('close')
		},
	},
}
</script>
