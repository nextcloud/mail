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
				@click="removeSender(sender)">
				{{ t('mail','Remove') }}
			</ButtonVue>
		</div>
		<span v-if="!sortedSenders.length"> {{ t('mail', 'No senders are trusted at the moment.') }}</span>
	</div>
</template>

<script>

import { fetchTrustedSenders, trustSender } from '../service/TrustedSenderService'
import { NcButton as ButtonVue } from '@nextcloud/vue'
import prop from 'lodash/fp/prop'
import sortBy from 'lodash/fp/sortBy'
import logger from '../logger'
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
					false
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
