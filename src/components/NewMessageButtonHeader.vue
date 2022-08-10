<template>
	<div class="header">
		<Button
			class="app-navigation-new"
			:disabled="$store.getters.showMessageComposer"
			button-id="mail_new_message"
			role="complementary"
			@click="onNewMessage">
			<template #icon>
				<IconAdd
					:size="20" />
			</template>
			{{ t('mail', 'New message') }}
		</Button>
		<Button v-if="currentMailbox"
			:disabled="refreshing"
			@click="refreshMailbox">
			<template #icon>
				<IconRefresh
					:size="20" />
			</template>
		</Button>
	</div>
</template>

<script>
import Button from '@nextcloud/vue/dist/Components/Button'
import IconAdd from 'vue-material-design-icons/Plus'
import IconRefresh from 'vue-material-design-icons/Refresh'
import logger from '../logger'

export default {
	name: 'NewMessageButtonHeader',
	components: {
		Button,
		IconAdd,
		IconRefresh,
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
.app-navigation-new {
	flex-grow: 1;
}
.header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 8px 8px 8px 48px;
	gap: 4px;
	height: 61px;
	border-right: 1px solid var(--color-border)
}
</style>
