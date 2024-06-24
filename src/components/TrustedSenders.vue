<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div v-for="sender in sortedSenders"
			:key="sender.email">
			{{ sender.email }}
			{{ senderType(sender.type) }}
			<ButtonVue type="tertiary"
				class="button"
				:aria-label="t('mail','Remove')"
				@click="removeSender(sender)">
				{{ t('mail','Remove') }}
			</ButtonVue>
		</div>
		<span v-if="!sortedSenders.length"> {{ t('mail', 'No senders are trusted at the moment.') }}</span>
	</div>
</template>

<script>

import { fetchTrustedSenders, trustSender } from '../service/TrustedSenderService.js'
import { NcButton as ButtonVue } from '@nextcloud/vue'
import prop from 'lodash/fp/prop.js'
import sortBy from 'lodash/fp/sortBy.js'
import logger from '../logger.js'
import { showError } from '@nextcloud/dialogs'

const sortByEmail = sortBy(prop('email'))

export default {
	name: 'TrustedSenders',
	components: {
		ButtonVue,
	},

	data() {
		return {
			list: [],
		}
	},
	computed: {
		sortedSenders() {
			return sortByEmail(this.list)
		},
	},
	async mounted() {
		this.list = await fetchTrustedSenders()
	},
	methods: {
		async removeSender(sender) {
			// Remove the item immediately
			this.list = this.list.filter(s => s.id !== sender.id)
			try {
				await trustSender(
					sender.email,
					sender.type,
					false,
				)
			} catch (error) {
				logger.error(`Could not remove trusted sender ${sender.email}`, {
					error,
				})
				showError(t('mail', 'Could not remove trusted sender {sender}', {
					sender: sender.email,
				}))
				// Put the sender back
				this.list.push(sender)
			}
		},
		senderType(type) {
			switch (type) {
			case 'individual':
				return t('mail', 'individual')
			case 'domain':
				return t('mail', 'domain')
			}
			return type
		},
	},
}
</script>

<style lang="scss" scoped>
.button-vue:deep() {
	display: inline-block !important;
}
</style>
