<template>
	<Modal @close="onClose">
		<div ref="content" class="modal-content">
			<h2 class="oc-dialog-title">
				{{ t('mail', 'Choose target folder') }}
			</h2>
			<span class="crumbs">
				<div class="level icon-breadcrumb">
					<a class="icon-home" @click.prevent="onClickHome" />
				</div>
				<div
					v-for="(box, index) in mailboxCrumbs"
					:key="box.databaseId"
					class="level icon-breadcrumb">
					<a @click.prevent="onClickCrumb(index)">
						{{ getMailboxTitle(box) }}
					</a>
				</div>
			</span>
			<div class="mailbox-list">
				<ul v-if="filteredMailboxes.length > 0">
					<li
						v-for="box in filteredMailboxes "
						:key="box.databaseId"
						@click.prevent="onClickMailbox(box)">
						<div :class="['mailbox-icon', getMailboxIcon(box)]" />
						<div class="mailbox-title">
							{{ getMailboxTitle(box) }}
						</div>
					</li>
				</ul>
				<div v-else class="empty">
					<div class="empty-icon icon-folder" />
					<h2>{{ t('mail', 'No more subfolders in here') }}</h2>
				</div>
			</div>
			<div class="buttons">
				<button class="primary" :disabled="loading || (!allowRoot && !selectedMailboxId)" @click="onSelect">
					<span v-if="loading" class="icon-loading-small spinner" />
					{{ loading ? labelSelectLoading : labelSelect }}
				</button>
			</div>
		</div>
	</Modal>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'

import { translate as t } from '@nextcloud/l10n'
import { translate as translateMailboxName } from '../i18n/MailboxTranslator'

export default {
	name: 'MailboxPicker',
	components: {
		Modal,
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
		mailboxes() {
			if (!this.selectedMailboxId) {
				return this.$store.getters.getMailboxes(this.account.accountId)
			} else {
				return this.$store.getters.getSubMailboxes(this.selectedMailboxId)
			}
		},
		filteredMailboxes() {
			if (this.pickedMailbox) {
				return this.mailboxes.filter(mailbox => mailbox.databaseId !== this.pickedMailbox.databaseId)
			}
			return this.mailboxes
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
::v-deep .modal-container {
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
	padding: 15px;
}

.crumbs {
	display: inline-flex;
	padding-left: 12px;
	padding-right: 0px;
	flex-wrap: wrap;

	.level {
		display: inline-flex;
		height: 44px;
		min-width: 0px;
		flex: 0 0 auto;
		order: 1;
		padding-right: 7px;
		background-position: right center;
		background-size: auto 24px;
	}

	a {
		position: relative;
		padding: 12px;
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
			border-bottom: 1px solid var(--color-border);
		}
	}

	.empty {
		width: 100%;
		color: var(--color-text-maxcontrast);
		text-align: center;
		margin-top: 80px;
	}

	.empty-icon {
		opacity: 0.4;
		background-size: 64px;
		height: 64px;
		width: 64px;
		margin: 0 auto 15px;
	}

	.mailbox-icon {
		width: 24px;
		height: 24px;
		padding: 14px;
		opacity: 0.9;
		background-size: 24px;
	}

	.mailbox-title {
		padding: 14px 14px 14px 0;
		flex: 1;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
}

.buttons {
	display: flex;
	justify-content: flex-end;
	padding-top: 10px;

	.spinner {
		margin-right: 5px;
	}
}
</style>
