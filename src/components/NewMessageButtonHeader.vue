<template>
	<div class="header">
		<Button
			type="primary"
			class="new-message-button"
			:disabled="$store.getters.showMessageComposer"
			button-id="mail_new_message"
			role="complementary"
			:wide="true"
			@click="onNewMessage">
			<template #icon>
				<IconAdd
					:size="20" />
			</template>
			{{ t('mail', 'New message') }}
		</Button>
		<Button v-if="currentMailbox"
			type="tertiary-no-background"
			class="refresh__button"
			:disabled="refreshing"
			@click="refreshMailbox">
			<template #icon>
				<IconRefresh v-if="!refreshing"
					:size="20" />
				<IconLoading v-if="refreshing"
					:size="20" />
			</template>
		</Button>
	</div>
</template>

<script>
import { NcButton as Button } from '@nextcloud/vue'
import IconAdd from 'vue-material-design-icons/Plus'
import IconRefresh from 'vue-material-design-icons/Refresh'
import IconLoading from '@nextcloud/vue/dist/Components/NcLoadingIcon'
import logger from '../logger'

export default {
	name: 'NewMessageButtonHeader',
	components: {
		Button,
		IconAdd,
		IconRefresh,
		IconLoading,
	},
	data() {
		return {
			refreshing: false,
		}
	},
	computed: {
		currentMailbox() {
			if (this.$route.name === 'message' || this.$route.name === 'mailbox') {
				return this.$store.getters.getMailbox(this.$route.params.mailboxId)
			}
			return undefined
		},
	},
	methods: {
		async refreshMailbox() {
			if (this.refreshing === true) {
				logger.debug('already sync\'ing mailbox.. aborting')
				return
			}
			this.refreshing = true
			try {
				await this.$store.dispatch('syncEnvelopes', { mailboxId: this.currentMailbox.databaseId })
				logger.debug('Current mailbox is sync\'ing ')
			} catch (error) {
				logger.error('could not sync current mailbox', { error })
			} finally {
				this.refreshing = false
			}
		},
		onNewMessage() {
			this.$store.dispatch('showMessageComposer', {

			})
		},
	},
}
</script>

<style lang="scss" scoped>
.header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: calc(var(--default-grid-baseline, 4px) * 2);
	gap: 4px;
	height: 61px;
}
.refresh__button {
	background-color: transparent;
}
.new-message-button {
	background-image: var(--gradient-primary-background);
}
</style>
