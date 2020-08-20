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
			<DashboardWidgetItem :item="getWidgetItem(item)">
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
			<div class="empty-content">
				<img class="empty-content__image" :src="emptyImage">
				<p class="empty-content__text">
					{{ t('mail', 'No messages found yet') }}
				</p>
				<a v-if="accounts.length === 0" :href="accountSetupUrl" class="button">{{ t('mail', 'Set up an account') }}</a>
			</div>
		</template>
	</DashboardWidget>
</template>

<script>
import { fetchEnvelopes } from '../service/MessageService'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl, imagePath } from '@nextcloud/router'
import Avatar from '../components/Avatar'
import { DashboardWidget, DashboardWidgetItem } from '@nextcloud/vue-dashboard'
import orderBy from 'lodash/fp/orderBy'
import prop from 'lodash/fp/prop'

const accounts = loadState('mail', 'mail-accounts')
const orderByDateInt = orderBy(prop('dateInt'), 'desc')

export default {
	name: 'Dashboard',
	components: {
		Avatar,
		DashboardWidget,
		DashboardWidgetItem,
	},
	data() {
		return {
			messages: [],
			accounts,
			fetchedAccounts: 0,
			emptyImage: imagePath('mail', 'newsletter.svg'),
			accountSetupUrl: generateUrl('/apps/mail/#/setup'),
		}
	},
	computed: {
		loading() {
			return this.fetchedAccounts < this.accounts.length
		},
		importantMessages() {
			if (!this.messages) {
				return []
			}
			return orderByDateInt(this.messages).slice(0, 7)
		},
		getWidgetItem() {
			return (item) => {
				const { uid, accountId, mailbox } = item
				return {
					targetUrl: generateUrl(`/apps/mail/#/accounts/${accountId}/folders/${mailbox}/message/${accountId}-${mailbox}-${uid}`),
					mainText: item.from ? item.from[0].label : '',
					subText: item.subject,
					message: item,
				}
			}
		},
	},
	mounted() {
		// TODO: check if there is a more sane way to query this and if other mailboxes should be fetched as well
		this.accounts.forEach((account) => {
			fetchEnvelopes(account.accountId, btoa('INBOX'), 'is:important', undefined, 10).then((messages) => {
				messages = messages.map((message) => ({ ...message, accountId: account.accountId, mailbox: btoa('INBOX') }))
				this.messages = this.messages !== null ? [...this.messages, ...messages] : messages
				this.fetchedAccounts++
			})
		})
	},
}
</script>

<style lang="scss" scoped>
.empty-content {
	text-align: center;
	margin-top: 50px;
}
.empty-content__image {
	width: 80%;
	margin: auto;
	margin-bottom: 10px;
}
.empty-content__text {
	color: var(--color-text-maxcontrast);
	text-align: center;
	margin-bottom: 10px;
}
</style>
