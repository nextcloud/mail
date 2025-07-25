<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<Modal @close="onClose">
		<div ref="content" class="modal-content">
			<h2 class="oc-dialog-title">
				{{ t('mail', 'Choose target folder') }}
			</h2>
			<span class="crumbs">
				<div @click.prevent="onClickHome">
					<IconInbox :size="16" />
				</div>
				<div v-for="(box, index) in mailboxCrumbs"
					:key="box.databaseId"
					class="level">
					<IconBreadcrumb :size="16" />
					<a @click.prevent="onClickCrumb(index)">
						{{ getMailboxTitle(box) }}
					</a>
				</div>
			</span>
			<div class="mailbox-list">
				<ul v-if="filteredMailboxes.length > 0">
					<li v-for="box in filteredMailboxes "
						:key="box.databaseId"
						@click.prevent="onClickMailbox(box)">
						<IconInbox v-if="box.specialRole === 'inbox'" :size="16" />
						<IconDraft v-else-if="box.specialRole === 'drafts'"
							:size="16" />
						<IconSent v-else-if="box.specialRole === 'sent'"
							:size="16" />
						<IconArchive v-else-if="box.specialRole === 'archive'"
							:size="16" />
						<IconTrash v-else-if="box.specialRole === 'trash'"
							:size="16" />
						<IconFolder v-else
							:size="16" />
						<div class="mailbox-title">
							{{ getMailboxTitle(box) }}
						</div>
					</li>
				</ul>
				<IconFolder v-else :size="65" />
				<div class="empty-icon empty" />
				<h2>{{ t('mail', 'No more submailboxes in here') }}</h2>
			</div>
			<div class="buttons">
				<ButtonVue type="primary"
					:disabled="loading || (!allowRoot && !selectedMailboxId)"
					:aria-label="loading ? labelSelectLoading : labelSelect"
					@click="onSelect">
					<template #icon>
						<IconLoading v-if="loading" :size="16" />
					</template>
					{{ loading ? labelSelectLoading : labelSelect }}
				</ButtonVue>
			</div>
		</div>
	</Modal>
</template>
<script>
import { NcModal as Modal, NcLoadingIcon as IconLoading, NcButton as ButtonVue } from '@nextcloud/vue'
import IconBreadcrumb from 'vue-material-design-icons/ChevronRight.vue'
import IconInbox from 'vue-material-design-icons/HomeOutline.vue'
import IconDraft from 'vue-material-design-icons/PencilOutline.vue'
import IconSent from 'vue-material-design-icons/SendOutline.vue'
import IconArchive from 'vue-material-design-icons/ArchiveArrowDownOutline.vue'
import IconTrash from 'vue-material-design-icons/DeleteOutline.vue'
import IconFolder from 'vue-material-design-icons/FolderOutline.vue'

import { translate as t } from '@nextcloud/l10n'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator.js'
import { mailboxHasRights } from '../util/acl.js'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'MailboxPicker',
	components: {
		ButtonVue,
		Modal,
		IconInbox,
		IconDraft,
		IconSent,
		IconArchive,
		IconTrash,
		IconFolder,
		IconBreadcrumb,
		IconLoading,
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
			mailboxCrumbs: [],
		}
	},
	computed: {
		...mapStores(useMainStore),
		mailboxes() {
			if (!this.selectedMailboxId) {
				return this.mainStore.getMailboxes(this.account.accountId)
			} else {
				return this.mainStore.getSubMailboxes(this.selectedMailboxId)
			}
		},
		filteredMailboxes() {
			if (this.pickedMailbox) {
				return this.mailboxes.filter(mailbox => mailbox.databaseId !== this.pickedMailbox.databaseId && mailboxHasRights(mailbox, 'k'))
			}
			return this.mailboxes.filter(mailbox => mailboxHasRights(mailbox, 'i'))
		},
	},
	methods: {
		getMailboxIcon(mailbox) {
			return mailbox.specialRole ? 'icon-' + mailbox.specialRole : 'icon-folder'
		},
		getMailboxTitle(mailbox) {
			return translateMailboxName(mailbox)
		},
		onClickHome() {
			this.selectedMailboxId = undefined
			this.$emit('update:selected', undefined)
			this.mailboxCrumbs = []
		},
		onClickCrumb(index) {
			this.selectedMailboxId = this.mailboxCrumbs[index].databaseId
			this.$emit('update:selected', this.selectedMailboxId)
			this.mailboxCrumbs = this.mailboxCrumbs.slice(0, index + 1)
		},
		onClickMailbox(mailbox) {
			this.selectedMailboxId = mailbox.databaseId
			this.$emit('update:selected', this.selectedMailboxId)
			this.mailboxCrumbs.push(mailbox)
		},
		onSelect() {
			this.$emit('select', this.selectedMailboxId)
		},
		onClose() {
			this.$emit('close')
		},
	},
}
</script>

<style lang="scss" scoped>
:deep(.modal-container) {
	width: calc(100vw - 120px) !important;
	height: calc(100vh - 120px) !important;
	max-width: 600px !important;
	max-height: 500px !important;
}

.modal-content {
	display: flex;
	box-sizing: border-box;
	width: 100%;
	height: 100%;
	flex-direction: column;
	padding: calc(var(--default-grid-baseline) * 4);
}

.crumbs {
	display: inline-flex;
	padding-inline-end: 0;
	flex-wrap: wrap;

	.level {
		display: inline-flex;
		height: 44px;
		min-width: 0px;
		flex: 0 0 auto;
		order: 1;
		padding-inline-end: calc(var(--default-grid-baseline) * 2);
		background-position: right center;
		background-size: auto 24px;
		margin-top: calc(var(--default-grid-baseline) * -2.5);
	}

	a {
		position: relative;
		padding: calc(var(--default-grid-baseline) * 3);
		opacity: 0.5;
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
		flex: 0 0 auto;
		min-width: 0px;
		max-width: 200px;

		&:hover {
			opacity: 0.7;
		}
	}

	a.icon-home {
		width: 0px;
		background-position: left center;
	}
}

.mailbox-list {
	display: inline-block;
	width: 100%;
	height: 100%;
	overflow-y: auto;
	flex: 1;

	li {
		display: flex;
		cursor: pointer;

		&:hover {
			background-color: var(--color-background-hover);
		}

		&:not(:last-child) {
			border-bottom: var(--border-width-input) solid var(--color-border);
		}
	}

	h2 {
		width: 100%;
		color: var(--color-text-maxcontrast);
		text-align: center;
		margin-top: 80px;
		opacity: 0.4;
		background-size: 64px;
		height: 64px;
	}

	.mailbox-icon {
		width: 24px;
		height: 24px;
		padding: calc(var(--default-grid-baseline) * 3);
		opacity: 0.9;
		background-size: 24px;
	}

	.mailbox-title {
		padding: calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 3) calc(var(--default-grid-baseline) * 3) 0;
		flex: 1;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
}

.buttons {
	display: flex;
	justify-content: flex-end;
	padding-top: calc(var(--default-grid-baseline) * 2);

	.spinner {
		margin-inline-end: var(--default-grid-baseline);
	}
}

.material-design-icon {
	opacity: .7;
	margin-inline-end: calc(var(--default-grid-baseline) * 1.5);
}
</style>
