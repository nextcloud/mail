<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<AppNavigationItem
		id="navigation-outbox"
		key="navigation-outbox"
		:title="t('mail', 'Outbox')"
		:to="to">
		<template #icon>
			<IconLoading v-if="sending"
				class="outbox-sending-icon"
				:size="20" />
			<IconOutbox v-else
				class="outbox-opacity-icon"
				:size="20" />
		</template>
		<template #counter>
			<CounterBubble v-if="count">
				{{ count }}
			</CounterBubble>
		</template>
	</AppNavigationItem>
</template>

<script>
import { NcAppNavigationItem as AppNavigationItem, NcCounterBubble as CounterBubble } from '@nextcloud/vue'
import IconOutbox from 'vue-material-design-icons/InboxArrowUp'
import IconLoading from 'vue-material-design-icons/Loading'

const RETRY_COUNT = 5
const RETRY_TIMEOUT = 10000

export default {
	name: 'NavigationOutbox',
	components: {
		AppNavigationItem,
		CounterBubble,
		IconOutbox,
		IconLoading,
	},
	data() {
		return {
			retry: true,
			sending: false,
			attempts: 0,
			failedMessaged: [],
		}
	},
	computed: {
		count() {
			return this.$store.getters['outbox/getAllMessages'].length
		},
		to() {
			return {
				name: 'outbox',
			}
		},
	},
	watch: {
		attempts() {
			if (this.attempts <= RETRY_COUNT && this.retry) {
				setTimeout(() => {
					this.retrySendMessages()
				}, RETRY_TIMEOUT)

			} else {

				this.retry = false
				this.attempts = 0
			}
		},
	},
	created() {
		this.failedMessaged = this.$store.getters['outbox/getAllMessages'].filter(message => {
			return message.failed
		})

		setTimeout(() => {
			this.retrySendMessages()
		}, RETRY_TIMEOUT)
	},
	methods: {
		retrySendMessages() {
			const promises = []
			if (this.sending) {
				return
			}
			this.sending = true
			this.attempts++
			this.failedMessaged.map(async (message) => {
				if (!message.pending) {
					promises.push(this.$store.dispatch('outbox/sendMessage', { id: message.id }).catch((err) => {
						console.log(err)
					}))
				}
				return message
			})
			Promise.all(promises).then(() => {
				this.sending = false
			})
		},

	},
}
</script>

<style lang="scss" scoped>
:deep(.counter-bubble__counter) {
	margin-right: 43px;
}
.outbox-opacity-icon {
	opacity: .7;

	&:hover {
		opacity: 1;
	}
}
.outbox-sending-icon {
	animation:spin 0.4s linear infinite;
}
@keyframes spin {
	0% {
		transform: rotate(0deg)
	}
	100% {
		transform: rotate(360deg)
	}
}
</style>
