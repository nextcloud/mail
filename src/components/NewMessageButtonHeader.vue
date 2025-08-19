<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="header">
		<ButtonVue :aria-label="t('mail', 'New message')"
			type="secondary"
			button-id="mail_new_message"
			:wide="true"
			@click="onNewMessage">
			<template #icon>
				<IconAdd :size="20" />
			</template>
			{{ t('mail', 'New message') }}
		</ButtonVue>
		<ButtonVue v-if="showRefresh && currentMailbox"
			:aria-label="t('mail', 'Refresh')"
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
		</ButtonVue>
	</div>
</template>

<script>
import { NcButton as ButtonVue } from '@nextcloud/vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconRefresh from 'vue-material-design-icons/Refresh.vue'
import IconLoading from '@nextcloud/vue/components/NcLoadingIcon'
import logger from '../logger.js'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'NewMessageButtonHeader',
	components: {
		ButtonVue,
		IconAdd,
		IconRefresh,
		IconLoading,
	},
	props: {
		showRefresh: {
			default: true,
		},
	},
	data() {
		return {
			refreshing: false,
		}
	},
	computed: {
		...mapStores(useMainStore),
		currentMailbox() {
			if (this.$route.name === 'message' || this.$route.name === 'mailbox') {
				return this.mainStore.getMailbox(this.$route.params.mailboxId)
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
				await this.mainStore.syncEnvelopes({ mailboxId: this.currentMailbox.databaseId })
				logger.debug('Current folder is sync\'ing ')
			} catch (error) {
				logger.error('could not sync current folder', { error })
			} finally {
				this.refreshing = false
			}
		},
		async onNewMessage() {
			await this.mainStore.startComposerSession({
				isBlankMessage: true,
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
	gap: var(--default-grid-baseline);
}

.refresh__button {
	background-color: transparent;
}
</style>
