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
				<ul v-if="mailboxes.length > 0">
					<li
						v-for="box in mailboxes"
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
				<button class="primary" :disabled="!isMoveable" @click="onMove">
					<span v-if="moving" class="icon-loading-small spinner" />
					{{ moving ? t('mail', 'Moving') : t('mail', 'Move') }}
				</button>
			</div>
		</div>
	</Modal>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'

import { translate as translateMailboxName } from '../i18n/MailboxTranslator'

export default {
	name: 'MoveModal',
	components: {
		Modal,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		envelopes: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			moving: false,
			hasMovedEnvelopes: false,
			destMailboxId: undefined,
			mailboxCrumbs: [],
		}
	},
	computed: {
		isMoveable() {
			return !this.moving && this.destMailboxId
		},
		mailboxes() {
			if (!this.destMailboxId) {
				return this.$store.getters.getMailboxes(this.account.accountId)
			} else {
				return this.$store.getters.getSubMailboxes(this.destMailboxId)
			}
		},
	},
	methods: {
		onClose() {
			this.$emit('close')
			if (this.hasMovedEnvelopes) {
				this.$store.dispatch('syncEnvelopes', { mailboxId: this.destMailboxId })
			}
		},
		onClickHome() {
			this.destMailboxId = undefined
			this.mailboxCrumbs = []
		},
		onClickCrumb(index) {
			this.destMailboxId = this.mailboxCrumbs[index].databaseId
			this.mailboxCrumbs = this.mailboxCrumbs.slice(0, index + 1)
		},
		onClickMailbox(mailbox) {
			this.destMailboxId = mailbox.databaseId
			this.mailboxCrumbs.push(mailbox)
		},
		onMove() {
			if (!this.isMoveable) {
				return
			}

			this.moving = true

			const envelopeIds = this.envelopes
				.filter((envelope) => envelope.mailboxId !== this.destMailboxId)
				.map((envelope) => envelope.databaseId)

			if (envelopeIds.length === 0) {
				this.$emit('close')
				return
			}

			const promises = envelopeIds.map(async(id) => {
				await this.$store.dispatch('moveMessage', {
					id,
					destMailboxId: this.destMailboxId,
				})
				this.hasMovedEnvelopes = true
			})

			Promise.all(promises).then(() => {
				this.$store.dispatch('syncEnvelopes', { mailboxId: this.destMailboxId })
			}).finally(() => {
				this.moving = false
				this.$emit('close')
			})
		},
		getMailboxIcon(mailbox) {
			return mailbox.specialRole ? 'icon-' + mailbox.specialRole : 'icon-folder'
		},
		getMailboxTitle(mailbox) {
			return translateMailboxName(mailbox)
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
