<!--
  - @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
	<DashboardWidget :items="importantMessages"
		:show-more-url="''"
		:loading="loading"
		@hide="() => {}"
		@markDone="() => {}">
		<template v-slot:default="{ item }">
			<DashboardWidgetItem :target-url="itemTargetUrl(item)" :main-text="itemMainText(item)" :sub-text="itemSubText(item)">
				<template v-slot:avatar>
					<Avatar v-if="item.from"
						:email="item.from[0].email"
						:display-name="item.from[0].label"
						:disable-tooltip="true"
						:size="44" />
				</template>
			</DashboardWidgetItem>
		</template>
		<template v-slot:empty-content>
			<EmptyContent id="mail--empty-content" icon="icon-checkmark">
				<template #desc>
					{{ t('mail', 'No message found yet') }}
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
import { DashboardWidget, DashboardWidgetItem } from '@nextcloud/vue-dashboard'
import orderBy from 'lodash/fp/orderBy'
import prop from 'lodash/fp/prop'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

import Avatar from '../components/Avatar'
import { fetchEnvelopes } from '../service/MessageService'
import logger from '../logger'
import { fetchAll } from '../service/MailboxService'

const accounts = loadState('mail', 'mail-accounts')
const orderByDateInt = orderBy(prop('dateInt'), 'desc')

export default {
	name: 'Dashboard',
	components: {
		Avatar,
		DashboardWidget,
		DashboardWidgetItem,
		EmptyContent,
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
		const accountInboxes = await Promise.all(this.accounts.map(async(account) => {
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

		await Promise.all(inboxes.map(async(mailbox) => {
			const messages = await fetchEnvelopes(mailbox.databaseId, 'is:important', undefined, 10)
			this.messages = this.messages !== null ? [...this.messages, ...messages] : messages
			this.fetchedAccounts++
		}))

		this.loading = false
	},
	methods: {
		itemMainText(item) {
			return item.from ? item.from[0].label : ''
		},
		itemSubText(item) {
			return item.subject
		},
		itemTargetUrl(item) {
			return generateUrl(`/apps/mail/box/priority/thread/${item.databaseId}`)
		},
	},
}
</script>

<style lang="scss">
#mail--empty-content {
	text-align: center;
	margin-top: 5vh;
}
.no-account {
	margin-top: 5vh;
	margin-right: 5px;
}
</style>
