<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppContent pane-config-key="mail"
		:show-details="isMessageShown"
		@update:showDetails="hideMessage">
		<OutboxMessageContent />
		<!-- List -->
		<template #list>
			<AppContentList>
				<Error v-if="error"
					:error="t('mail', 'Could not open outbox')"
					message=""
					role="alert" />
				<LoadingSkeleton v-else-if="loading" />
				<EmptyMailbox v-else-if="messages.length === 0" />
				<OutboxMessageListItem v-for="message in messages"
					v-else
					:key="message.id"
					:message="message" />
			</AppContentList>
		</template>
	</AppContent>
</template>

<script>
import { NcAppContent as AppContent, NcAppContentList as AppContentList } from '@nextcloud/vue'
import LoadingSkeleton from './LoadingSkeleton.vue'
import Error from './Error.vue'
import EmptyMailbox from './EmptyMailbox.vue'
import OutboxMessageContent from './OutboxMessageContent.vue'
import OutboxMessageListItem from './OutboxMessageListItem.vue'
import logger from '../logger.js'
import useOutboxStore from '../store/outboxStore.js'
import { mapStores } from 'pinia'

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
	},
	data() {
		return {
			error: false,
			loading: false,
			refreshInterval: undefined,
		}
	},
	computed: {
		...mapStores(useOutboxStore),
		isMessageShown() {
			return !!this.$route.params.messageId
		},
		currentMessage() {
			if (!this.isMessageShown) {
				return null
			}

			return this.outboxStore.getMessage(this.$route.params.messageId)
		},
		messages() {
			return this.outboxStore.getAllMessages
		},
	},
	created() {
		// Reload outbox contents every 60 seconds
		this.refreshInterval = setInterval(async () => {
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
				await this.outboxStore.fetchMessages()
			} catch (error) {
				this.error = true
				logger.error('Failed to fetch outbox messages', { error })
			}

			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
:deep(.button-vue--vue-secondary) {
	box-shadow: none;
}
</style>
