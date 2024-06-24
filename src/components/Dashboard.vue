<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<DashboardWidget :items="importantMessages"
		:show-more-url="''"
		:loading="loading">
		<template #default="{ item }">
			<DashboardWidgetItem :class="{unread: itemUnread(item)}"
				:target-url="itemTargetUrl(item)"
				:main-text="itemMainText(item)"
				:sub-text="itemSubText(item)">
				<template #avatar>
					<Avatar v-if="item.from && item.from.length"
						:email="item.from[0].email"
						:display-name="item.from[0].label"
						:disable-tooltip="true"
						:size="44" />
				</template>
			</DashboardWidgetItem>
		</template>
		<template #empty-content>
			<EmptyContent id="mail--empty-content" :name="t('mail', 'No message found yet')">
				<template #icon>
					<IconCheck :size="65" />
				</template>
				<template #action>
					<div class="no-account">
						<a v-if="accounts.length === 0" :href="accountSetupUrl" class="button">{{ t('mail', 'Set up an account') }}</a>
					</div>
				</template>
			</EmptyContent>
		</template>
	</DashboardWidget>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl, imagePath } from '@nextcloud/router'
import DashboardWidget from '@nextcloud/vue/dist/Components/NcDashboardWidget.js'
import DashboardWidgetItem from '@nextcloud/vue/dist/Components/NcDashboardWidgetItem.js'
import orderBy from 'lodash/fp/orderBy.js'
import prop from 'lodash/fp/prop.js'
import EmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import IconCheck from 'vue-material-design-icons/Check.vue'

import Avatar from '../components/Avatar.vue'
import { fetchEnvelopes } from '../service/MessageService.js'
import logger from '../logger.js'
import { fetchAll } from '../service/MailboxService.js'

const accounts = loadState('mail', 'mail-accounts')
const orderByDateInt = orderBy(prop('dateInt'), 'desc')

export default {
	name: 'Dashboard',
	components: {
		Avatar,
		DashboardWidget,
		DashboardWidgetItem,
		EmptyContent,
		IconCheck,
	},
	props: {
		query: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			messages: [],
			accounts,
			loading: true,
			fetchedAccounts: 0,
			emptyImage: imagePath('mail', 'newsletter.svg'),
			accountSetupUrl: generateUrl('/apps/mail/#/setup'),
		}
	},
	computed: {
		importantMessages() {
			if (!this.messages) {
				return []
			}
			return orderByDateInt(this.messages).slice(0, 7)
		},
	},
	async mounted() {
		const accountInboxes = await Promise.all(this.accounts.map(async (account) => {
			logger.debug('account', {
				account,
			})

			const mailboxes = await fetchAll(account.accountId)

			logger.debug('mailboxes', {
				mailboxes,
			})

			return mailboxes.filter(mb => mb.specialRole === 'inbox')
		}))
		const inboxes = accountInboxes.flat()

		logger.debug(`found ${inboxes.length} inboxes`, {
			inboxes,
		})

		await Promise.all(inboxes.map(async (mailbox) => {
			const messages = await fetchEnvelopes(mailbox.accountId, mailbox.databaseId, this.query, undefined, 10)
			messages.forEach(message => { message.id = message.databaseId })
			this.messages = this.messages !== null ? [...this.messages, ...messages] : messages
			this.fetchedAccounts++
		}))

		this.loading = false
	},
	methods: {
		itemMainText(item) {
			return item.from && item.from.length ? item.from[0].label : ''
		},
		itemSubText(item) {
			return item.subject
		},
		itemTargetUrl(item) {
			return generateUrl(`/apps/mail/box/priority/thread/${item.databaseId}`)
		},
		itemUnread(item) {
			return !item.flags.seen
		},
	},
}
</script>

<style lang="scss" scoped>
#mail--empty-content {
	text-align: center;
	margin-top: 5vh;
	height: 100%;
	display: flex;
}
.no-account {
	margin-top: 5vh;
	margin-right: 5px;
}
.unread :deep(.item__details) {
	font-weight: bold;
}
</style>
