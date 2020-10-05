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
	<div>
		<div class="envelope--header">
			<Avatar v-if="envelope.from && envelope.from[0]"
				:email="envelope.from[0].email"
				:display-name="envelope.from[0].label"
				:disable-tooltip="true"
				:size="40" />
			<router-link
				:to="route"
				event=""
				class="left"
				@click.native.prevent="$emit('toggleExpand', $event)">
				<span class="sender">{{ envelope.from && envelope.from[0] ? envelope.from[0].label : '' }}</span>
				<div class="subject">
					<span class="preview">
						<!-- TODO: instead of subject it should be shown the first line of the message #2666 -->
						{{ envelope.subject }}
					</span>
				</div>
			</router-link>
			<div class="right">
				<Moment class="timestamp" :timestamp="envelope.dateInt" />
				<div
					class="button"
					:class="{
						'icon-reply-all-white': hasMultipleRecipients,
						'icon-reply-white': !hasMultipleRecipients,
						primary: expanded,
					}"
					@click="hasMultipleRecipients ? replyAll() : replyMessage()">
					<span class="action-label">{{ t('mail', 'Reply') }}</span>
				</div>
				<Actions class="app-content-list-item-menu" menu-align="right">
					<ActionButton v-if="hasMultipleRecipients" icon="icon-reply" @click="replyMessage">
						{{ t('mail', 'Reply to sender only') }}
					</ActionButton>
					<ActionButton icon="icon-forward" @click="forwardMessage">
						{{ t('mail', 'Forward') }}
					</ActionButton>
					<ActionButton icon="icon-important" @click.prevent="onToggleImportant">
						{{
							envelope.flags.important ? t('mail', 'Mark unimportant') : t('mail', 'Mark important')
						}}
					</ActionButton>
					<ActionButton icon="icon-starred" @click.prevent="onToggleFlagged">
						{{
							envelope.flags.flagged ? t('mail', 'Mark unfavorite') : t('mail', 'Mark favorite')
						}}
					</ActionButton>
					<ActionButton icon="icon-mail" @click="onToggleSeen">
						{{ envelope.flags.seen ? t('mail', 'Mark unread') : t('mail', 'Mark read') }}
					</ActionButton>

					<ActionButton icon="icon-junk" @click="onToggleJunk">
						{{ envelope.flags.junk ? t('mail', 'Mark not spam') : t('mail', 'Mark as spam') }}
					</ActionButton>
					<ActionButton
						:icon="sourceLoading ? 'icon-loading-small' : 'icon-details'"
						:disabled="sourceLoading"
						@click="onShowSource">
						{{ t('mail', 'View source') }}
					</ActionButton>
					<ActionLink v-if="debug"
						icon="icon-download"
						:download="threadingFileName"
						:href="threadingFile">
						{{ t('mail', 'Download thread data for debugging') }}
					</ActionLink>
					<ActionButton icon="icon-delete" @click.prevent="onDelete">
						{{ t('mail', 'Delete') }}
					</ActionButton>
				</Actions>
				<Modal v-if="showSource" @close="onCloseSource">
					<div class="section">
						<h2>{{ t('mail', 'Message source') }}</h2>
						<pre class="message-source">{{ rawMessage }}</pre>
					</div>
				</Modal>
			</div>
		</div>
		<Loading v-if="loading" />
		<Message v-else-if="message" :envelope="envelope" :message="message" />
		<Error v-else-if="error"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage"
			:data="error" />
	</div>
</template>
<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import axios from '@nextcloud/axios'
import Error from './Error'
import Loading from './Loading'
import logger from '../logger'
import Message from './Message'
import Moment from './Moment'
import Avatar from './Avatar'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'
import { generateUrl } from '@nextcloud/router'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import { Base64 } from 'js-base64'

export default {
	name: 'ThreadEnvelope',
	components: {
		Actions,
		ActionButton,
		ActionLink,
		Error,
		Loading,
		Moment,
		Message,
		Modal,
		Avatar,
	},
	props: {
		envelope: {
			required: true,
			type: Object,
		},
		mailboxId: {
			required: false,
			type: [
				String,
				Number,
			],
			default: undefined,
		},
		expanded: {
			required: false,
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			debug: window?.OC?.debug || false,
			loading: false,
			error: undefined,
			message: undefined,
			rawMessage: '',
			sourceLoading: false,
			showSource: false,
		}
	},
	computed: {
		threadingFile() {
			return `data:text/plain;base64,${Base64.encode(JSON.stringify({
				subject: this.envelope.subject,
				messageId: this.envelope.messageId,
				inReplyTo: this.envelope.inReplyTo,
				references: this.envelope.references,
				threadRootId: this.envelope.threadRootId,
			}, null, 2))}`
		},
		threadingFileName() {
			return `${this.envelope.databaseId}.json`
		},
		route() {
			return {
				name: 'message',
				params: {
					mailboxId: this.mailboxId || this.envelope.mailboxId,
					threadId: this.envelope.databaseId,
				},
			}
		},
		hasMultipleRecipients() {
			const account = this.$store.getters.getAccount(this.envelope.accountId)
			if (!account) {
				console.error('account is undefined', {
					accountId: this.envelope.accountId,
				})
			}
			const recipients = buildReplyRecipients(this.envelope, {
				label: account.name,
				email: account.emailAddress,
			})
			return recipients.to.concat(recipients.cc).length > 1
		},
	},
	watch: {
		expanded(expanded) {
			if (expanded) {
				this.fetchMessage()
			} else {
				this.message = undefined
			}
		},
	},
	mounted() {
		if (this.expanded) {
			this.fetchMessage()
		}
	},
	methods: {
		async fetchMessage() {
			this.loading = true

			logger.debug(`fetching thread message ${this.envelope.databaseId}`)

			try {
				const message = this.message = await this.$store.dispatch('fetchMessage', this.envelope.databaseId)
				logger.debug(`message ${this.envelope.databaseId} fetched`, { message })

				if (!this.envelope.flags.seen) {
					this.$store.dispatch('toggleEnvelopeSeen', this.envelope)
				}

				this.loading = false
			} catch (error) {
				logger.error('Could not fetch message', { error })
			}
		},
		replyMessage() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'reply',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.$route.params.threadId,
				},
			})
		},
		replyAll() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'replyAll',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.$route.params.threadId,
				},
			})
		},
		forwardMessage() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: this.$route.params.mailboxId,
					threadId: 'new',
					filter: this.$route.params.filter ? this.$route.params.filter : undefined,
				},
				query: {
					messageId: this.$route.params.threadId,
				},
			})
		},
		onToggleSeen() {
			this.$store.dispatch('toggleEnvelopeSeen', this.envelope)
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.envelope)
		},
		onDelete() {
			this.$emit('delete', this.envelope.databaseId)
			this.$store.dispatch('deleteMessage', {
				id: this.envelope.databaseId,
			})
		},
		async onShowSource() {
			this.sourceLoading = true

			try {
				const resp = await axios.get(
					generateUrl('/apps/mail/api/messages/{id}/source', {
						id: this.envelope.databaseId,
					})
				)

				this.rawMessage = resp.data.source
				this.showSource = true
			} finally {
				this.sourceLoading = false
			}
		},
		onCloseSource() {
			this.showSource = false
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.envelope)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.envelope)
		},
	},
}
</script>

<style lang="scss" scoped>
	.sender {
		font-weight: bold;
		margin-left: 8px;
	}

	.right {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
		margin-left: 10px;
		margin-right: 22px;
		height: 44px;

		.app-content-list-item-menu {
			margin-left: 4px;
		}

		.timestamp {
			margin-right: 10px;
			font-size: small;
			white-space: nowrap;
			margin-bottom: 0;
		}
	}
	.button {
		color: var(--color-main-background);
		&:not(.active):not(.primary) {
			display: none;

			&.primary {
				background-color: var(--color-primary);
				opacity: 1;
				margin-bottom: 0;

			}
		}
	}
	.avatardiv {
		display: inline-block;
		margin-bottom: -23px;
	}
	.subject {
		margin-left: 8px;
		cursor: default;
	}
	.envelope--header {
		display: flex;
		padding: 10px;
		margin-bottom: 3px;
		border-radius: var(--border-radius);

		&:hover {
			background-color: var(--color-background-hover);
		}
	}
	.left {
		flex-grow: 1;
	}
	::v-deep .modal-container {
		overflow-y: scroll !important;
	}

</style>
