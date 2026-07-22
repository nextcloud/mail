<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcListItem
			v-for="sender in sortedSenders"
			:key="sender.email">
			<template #name>
				{{ sender.email }}
			</template>
			<template #icon>
				<IconDomain v-if="sender.type === 'domain'" :size="20" :title="senderType(sender.type)" />
				<IconEmail v-if="sender.type === 'individual'" :size="20" :title="senderType(sender.type)" />
			</template>
			<template #extra-actions>
				<NcActionButton
					:title="t('mail', 'Remove')"
					:aria-label="t('mail', 'Remove')"
					@click="removeSender(sender)">
					<template #icon>
						<IconDelete :size="20" />
					</template>
				</NcActionButton>
			</template>
		</NcListItem>
		<span v-if="!sortedSenders.length"> {{ t('mail', 'No senders are trusted at the moment.') }}</span>
	</div>
</template>

<script>

import { showError } from '@nextcloud/dialogs'
import { NcActionButton, NcListItem } from '@nextcloud/vue'
import prop from 'lodash/fp/prop.js'
import sortBy from 'lodash/fp/sortBy.js'
import IconDomain from 'vue-material-design-icons/Domain.vue'
import IconEmail from 'vue-material-design-icons/EmailOutline.vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import logger from '../logger.js'
import { fetchTrustedSenders, trustSender } from '../service/TrustedSenderService.js'

const sortByEmail = sortBy(prop('email'))

export default {
	name: 'TrustedSenders',
	components: {
		NcActionButton,
		NcListItem,
		IconDelete,
		IconDomain,
		IconEmail,
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
			this.list = this.list.filter((s) => s.id !== sender.id)
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
