<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
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
	<ListItem
		class="outbox-message"
		:class="{ selected }"
		:title="title"
		:details="sendAt"
		@click="openModal">
		<template #icon>
			<div
				class="account-color"
				:style="{'background-color': accountColor}" />
			<Avatar :display-name="avatarDisplayName" :email="avatarEmail" />
		</template>
		<template #subtitle>
			{{ message.subject }}
		</template>
		<template slot="actions">
			<ActionButton
				icon="icon-checkmark"
				:close-after-click="true"
				@click="sendMessage">
				{{ t('mail', 'Send now') }}
				<template #icon>
					<Send
						:title="t('mail', 'Send now')"
						:size="20" />
				</template>
			</ActionButton>
			<ActionButton
				icon="icon-delete"
				:close-after-click="true"
				@click="deleteMessage">
				{{ t('mail', 'Delete') }}
			</ActionButton>
		</template>
	</ListItem>
</template>

<script>
import ListItem from '@nextcloud/vue/dist/Components/ListItem'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Avatar from './Avatar'
import { calculateAccountColor } from '../util/AccountColor'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import OutboxAvatarMixin from '../mixins/OutboxAvatarMixin'
import moment from '@nextcloud/moment'
import logger from '../logger'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { matchError } from '../errors/match'
import { html, plain } from '../util/text'
import Send from 'vue-material-design-icons/Send'

export default {
	name: 'OutboxMessageListItem',
	components: {
		ListItem,
		Avatar,
		ActionButton,
		Send,
	},
	mixins: [
		OutboxAvatarMixin,
	],
	props: {
		message: {
			type: Object,
			required: true,
		},
	},
	computed: {
		selected() {
			return this.$route.params.messageId === this.message.id
		},
		accountColor() {
			const account = this.$store.getters.getAccount(this.message.accountId)
			return calculateAccountColor(account?.emailAddress ?? '')
		},
		title() {
			const recipientToString = recipient => recipient.label
			const recipients = this.message.to.map(recipientToString)
				.concat(this.message.cc.map(recipientToString))
				.concat(this.message.bcc.map(recipientToString))
			const formatter = new Intl.ListFormat(getLanguage(), { type: 'conjunction' })
			return formatter.format(recipients)
		},
		sendAt() {
			if (!this.message.sendAt) {
				return ''
			}
			return moment.unix(this.message.sendAt).fromNow()
		},
	},
	methods: {
		async deleteMessage() {
			try {
				await this.$store.dispatch('outbox/deleteMessage', {
					id: this.message.id,
				})
				showSuccess(t('mail', 'Message deleted'))
			} catch (error) {
				showError(await matchError(error, {
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			}
		},
		async sendMessage(data) {
			logger.debug('sending message', { data })
			try {
				await this.$store.dispatch('outbox/sendMessage', { id: this.message.id, force: true })
				showSuccess(t('mail', 'Message sent'))
			} catch (error) {
				showError(t('mail', 'Could not send message'))
			}
		},
		async openModal() {
			await this.$store.dispatch('showMessageComposer', {
				type: 'outbox',
				data: {
					...this.message,
					body: this.message.isHtml ? html(this.message.body) : plain(this.message.body),
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.outbox-message {
	list-style: none;
	&.active {
		background-color: var(--color-background-dark);
		border-radius: 16px;
	}

	.account-color {
		position: absolute;
		left: 0;
		width: 2px;
		height: 69px;
		z-index: 1;
	}
}
</style>
