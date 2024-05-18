<!--
  - @copyright 2020 Greta Doci <gretadoci@gmail.com>
  -
  - @author 2020 Greta Doci <gretadoci@gmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		<ButtonVue type="primary"
			@click="openModal = true">
			<template #icon>
				<IconAdd :size="20" />
			</template>
			{{ t('mail', 'Add trusted sender') }}
		</ButtonVue>
		<NcModal v-if="openModal"
			:container="null"
			:name="t('mail', 'Add trusted sender')"
			@close="openModal = false">
			<div class="content">
				<h2>{{ t('mail', 'Add trusted sender') }}</h2>
				<NcTextField class="input" :label="t('mail', 'Sender')" :value.sync="sender" />
				<ButtonVue type="primary"
					:disabled="sender.length === 0"
					@click="trustSender">
					{{ t('mail', 'Add') }}
				</ButtonVue>
			</div>
		</NcModal>
	</div>
</template>

<script>

import { fetchTrustedSenders, trustSender } from '../service/TrustedSenderService.js'
import { NcButton as ButtonVue, NcModal, NcTextField } from '@nextcloud/vue'
import prop from 'lodash/fp/prop.js'
import sortBy from 'lodash/fp/sortBy.js'
import logger from '../logger.js'
import { showError } from '@nextcloud/dialogs'
import IconAdd from 'vue-material-design-icons/Plus.vue'

const sortByEmail = sortBy(prop('email'))

export default {
	name: 'TrustedSenders',
	components: {
		ButtonVue,
		IconAdd,
		NcModal,
		NcTextField,
	},

	data() {
		return {
			list: [],
			openModal: false,
			sender: '',
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
		async trustSender() {
			const type = this.checkType()
			try {
				await trustSender(
					this.sender,
					type,
					true,
				).then(async () => {
					this.list = await fetchTrustedSenders()
					this.sender = ''
					this.openModal = false

				})
			} catch (error) {
				logger.error(`Could not trust sender ${this.sender}`, {
					error,
				})
				showError(t('mail', 'Could not trust sender {sender}', {
					sende: this.sender,
				}))
			}
		},
		checkType() {
			const parts = this.sender.split('@')
			if (parts.length !== 2) {
				return 'domain'
			}
			// remove '@'' from domain if added by mistake
			if (parts[0].length === 0) {
				this.sender = parts[1]
				return 'domain'
			}
			return 'individual'
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
.content{
	margin: 50px;
}
.input{
	margin-bottom: 10px;
}
</style>
