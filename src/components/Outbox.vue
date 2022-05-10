<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
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
			<AppContentList>
				<Error
					v-if="error"
					:error="t('mail', 'Could not open outbox')"
					message=""
					role="alert" />
				<Loading
					v-else-if="loading"
					:hint="t('mail', 'Loading messages …')" />
				<EmptyMailbox v-else-if="messages.length === 0" />
				<OutboxMessageListItem
					v-for="message in messages"
					v-else
					:key="message.id"
					:message="message" />
			</AppContentList>
		</template>
	</AppContent>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppContentList from '@nextcloud/vue/dist/Components/AppContentList'
import Loading from './Loading'
import Error from './Error'
import EmptyMailbox from './EmptyMailbox'
import OutboxMessageContent from './OutboxMessageContent'
import OutboxMessageListItem from './OutboxMessageListItem'
import logger from '../logger'

export default {
	name: 'Outbox',
	components: {
		AppContent,
		AppContentList,
		Error,
		Loading,
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
	},
	created() {
		// Reload outbox contents every 60 seconds
		this.refreshInterval = setInterval(async() => {
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
	},
}
</script>

<style lang="scss" scoped>
</style>
