<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<AppContent
		pane-config-key="mail"
		:show-details="isMessageShown"
		@update:showDetails="hideMessage">
		<OutboxMessageContent />
		<!-- List -->
		<template #list>
			<div slot="list" class="header__button">
				<NewMessageButtonHeader />
				<div class="outbox-retry">
					<div v-if="!sending" class="outbox-retry--info">
						{{
							n('mail', '{count} failed message', '{count} failed messages', failedMessages.length, {count: failedMessages.length})
						}}
					</div>
					<div v-else class="outbox-retry--info">
						{{
							n('mail', 'Retrying send message', 'Retrying send {count} messages', failedMessages.length, {count: failedMessages.length})
						}}
					</div>
					<span
						class="outbox-retry--btn"
						:class="{sending: sending}"
						@click="sendFailedMessages">
						{{ t('mail', 'Retry') }}
					</span>
					<NcButton
						class="outbox-retry--btn"
						:title="t('mail', 'Send failed messages')"
						:disabled="sending"
						@click="sendFailedMessages">
						<template #icon>
							<EmailFastOutlineIcon :size="22" />
						</template>
					</NcButton>
				</div>
				<AppContentList>
					<Error
						v-if="error"
						:error="t('mail', 'Could not open outbox')"
						message=""
						role="alert" />
					<LoadingSkeleton
						v-else-if="loading" />
					<EmptyMailbox v-else-if="messages.length === 0" />
					<div v-else class="outbox-container">
						<OutboxMessageListItem
							v-for="message in messages"
							:key="message.id"
							:message="message" />
					</div>
				</AppContentList>
			</div>
		</template>
	</AppContent>
</template>

<script>
import { NcAppContent as AppContent, NcAppContentList as AppContentList } from '@nextcloud/vue'
import LoadingSkeleton from './LoadingSkeleton'
import Error from './Error'
import EmptyMailbox from './EmptyMailbox'
import OutboxMessageContent from './OutboxMessageContent'
import OutboxMessageListItem from './OutboxMessageListItem'
import NewMessageButtonHeader from './NewMessageButtonHeader'
import logger from '../logger'

export default {
	name: 'Outbox',
	components: {
		AppContent,
		AppContentList,
		Error,
		LoadingSkeleton,
		EmptyMailbox,
		OutboxMessageListItem,
		OutboxMessageContent,
		NewMessageButtonHeader,
	},
	data() {
		return {
			error: false,
			loading: false,
			refreshInterval: undefined,
			sending: false,
		}
	},
	computed: {
		isMessageShown() {
			return !!this.$route.params.messageId
		},
		currentMessage() {
			if (!this.isMessageShown) {
				return null
			}

			return this.$store.getters['outbox/getMessage'](this.$route.params.messageId)
		},
		messages() {
			return this.$store.getters['outbox/getAllMessages']
		},
		failedMessages() {
			return this.messages.filter((message) => {
				return message.failed
			})
		},
	},
	created() {
		// Reload outbox contents every 60 seconds
		this.refreshInterval = setInterval(async () => {
			// TODO cancel if sending available?
			await this.fetchMessages()
		}, 60000)
	},
	async mounted() {
		await this.fetchMessages()
	},
	destroyed() {
		clearInterval(this.refreshInterval)
	},
	methods: {
		hideMessage() {
			this.$router.push({
				name: 'outbox',
			})
		},
		async fetchMessages() {
			this.loading = true
			this.error = false

			try {
				await this.$store.dispatch('outbox/fetchMessages')
			} catch (error) {
				this.error = true
				logger.error('Failed to fetch outbox messages', { error })
			}

			this.loading = false
		},
		sendFailedMessages() {
			if (this.sending) {
				return false
			}
			this.sending = true
			const promises = []
			this.failedMessages.map(async (msg) => {
				// sending imitation
				const message = Object.assign({}, msg)
				message.pending = true
				this.$store.commit('outbox/updateMessage', { message })

				promises.push(this.$store.dispatch('outbox/sendMessage', { id: message.id })
					.then(() => {
						this.$store.commit('outbox/deleteMessage', { message })
					})
					.catch(() => {
						message.pending = false
						this.$store.commit('outbox/updateMessage', { message })
					}))
				return msg
			})
			Promise.all(promises).then((res) => {
				this.sending = false
			})

		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep(.button-vue--vue-secondary) {
	box-shadow: none;
}
.header__button {
	display: flex;
	flex: 1px 0 0;
	flex-direction: column;
	height: calc(100vh - var(--header-height));

}

.outbox-retry {
	display: flex;
	align-items: center;
	justify-content: space-between;
	border-right: 1px solid var(--color-border);

	.outbox-retry--info {
		margin: 4px;
		font-weight: bold;
		color: var(--color-primary-light-text);
		padding-left: 8px;
	}

	.outbox-retry--btn {
		cursor:pointer;
		color: var(--color-primary-element);
		font-weight: bold;
		padding:0 8px;

		&.sending {
			opacity: 0.5;
		}
	}
}
</style>
