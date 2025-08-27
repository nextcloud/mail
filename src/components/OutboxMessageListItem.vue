<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ListItem class="outbox-message"
		:class="{ selected }"
		:name="title"
		:details="details"
		@click="openModal">
		<template #icon>
			<Avatar :display-name="avatarDisplayName"
				:email="avatarEmail"
				:fetch-avatar="false"
				:avatar="message.avatar" />
		</template>
		<template #subname>
			{{ subjectForSubtitle }}
		</template>
		<template #actions>
			<ActionButton v-if="message.status === statusImapSentMailboxFail()"
				:close-after-click="true"
				@click="sendMessageNow">
				{{ t('mail', 'Copy to "Sent" Folder') }}
				<template #icon>
					<Copy :title="t('mail', 'Copy to Sent Folder')"
						:size="20" />
				</template>
			</ActionButton>
			<ActionButton v-if="message.status !== statusImapSentMailboxFail() && message.status !== statusSmtpError()"
				:close-after-click="true"
				@click="sendMessageNow">
				{{ t('mail', 'Send now') }}
				<template #icon>
					<Send :title="t('mail', 'Send now')"
						:size="20" />
				</template>
			</ActionButton>
			<ActionButton :close-after-click="true"
				@click="deleteMessage">
				<template #icon>
					<IconDelete :size="24" />
				</template>
				{{ t('mail', 'Delete') }}
			</ActionButton>
		</template>
	</ListItem>
</template>

<script>
import { NcListItem as ListItem, NcActionButton as ActionButton } from '@nextcloud/vue'
import Avatar from './Avatar.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import OutboxAvatarMixin from '../mixins/OutboxAvatarMixin.js'
import moment from '@nextcloud/moment'
import logger from '../logger.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { matchError } from '../errors/match.js'
import Send from 'vue-material-design-icons/SendOutline.vue'
import Copy from 'vue-material-design-icons/ContentCopy.vue'
import {
	STATUS_RAW,
	STATUS_IMAP_SENT_MAILBOX_FAIL,
	STATUS_SMTP_ERROR,
	UNDO_DELAY,
} from '../store/constants.js'
import useOutboxStore from '../store/outboxStore.js'
import useMainStore from '../store/mainStore.js'
import { mapStores } from 'pinia'

export default {
	name: 'OutboxMessageListItem',
	components: {
		ListItem,
		Avatar,
		ActionButton,
		IconDelete,
		Send,
		Copy,
	},
	mixins: [
		OutboxAvatarMixin,
	],
	props: {
		message: {
			type: Object,
			required: true,
		},
	},
	computed: {
		...mapStores(useOutboxStore, useMainStore),
		selected() {
			return this.$route.params.messageId === this.message.id
		},
		title() {
			const recipientToString = recipient => recipient.label
			const recipients = this.message.to.map(recipientToString)
				.concat(this.message.cc.map(recipientToString))
				.concat(this.message.bcc.map(recipientToString))
			const formatter = new Intl.ListFormat(getLanguage(), { type: 'conjunction' })
			return formatter.format(recipients)
		},
		details() {
			if (this.message.status === STATUS_IMAP_SENT_MAILBOX_FAIL) {
				return this.t('mail', 'Could not copy to "Sent" folder')
			} else if (this.message.status === STATUS_SMTP_ERROR) {
				return this.t('mail', 'Mail server error')
			} else if (this.message.status !== STATUS_RAW) {
				return this.t('mail', 'Message could not be sent')
			}
			if (!this.message.sendAt) {
				return ''
			}
			return moment.unix(this.message.sendAt).fromNow()
		},
		/**
		 * Subject of message or "No Subject".
		 *
		 * @return {string}
		 */
		subjectForSubtitle() {
			// We have to use || here (instead of ??) because the subject might be '', null
			// or undefined.
			return this.message.subject || this.t('mail', 'No subject')
		},
	},
	methods: {
		statusImapSentMailboxFail() {
			return STATUS_IMAP_SENT_MAILBOX_FAIL
		},
		statusSmtpError() {
			return STATUS_SMTP_ERROR
		},
		async deleteMessage() {
			try {
				await this.outboxStore.deleteMessage({
					id: this.message.id,
				})
				showSuccess(t('mail', 'Message deleted'))
			} catch (error) {
				showError(await matchError(error, {
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			}
		},
		async sendMessageNow() {
			const message = {
				...this.message,
				failed: false,
				sendAt: (new Date().getTime() + UNDO_DELAY) / 1000,
			}
			await this.outboxStore.updateMessage({ message, id: message.id })
			try {
				if (this.message.status !== STATUS_IMAP_SENT_MAILBOX_FAIL) {
					await this.outboxStore.sendMessageWithUndo({ id: message.id })
				} else {
					await this.outboxStore.copyMessageToSentMailbox({ id: message.id })
				}
			} catch (error) {
				logger.error('Could not send or copy message', { error })
				if (error.data !== undefined) {
					await this.outboxStore.updateMessage({ message: error.data[0], id: message.id })
				}

			}
		},
		async openModal() {
			if (this.message.status === STATUS_IMAP_SENT_MAILBOX_FAIL) {
				return
			}
			await this.mainStore.startComposerSession({
				type: 'outbox',
				data: { ...this.message },
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.outbox-message {
	list-style: none;
	&.active {
		background-color: var(--color-background-dark);
		border-radius: 16px;
	}

	.account-color {
		position: absolute;
		inset-inline-start: 0;
		width: 2px;
		height: 69px;
		z-index: 1;
	}
}
</style>
