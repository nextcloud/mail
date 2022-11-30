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
	<ListItem
		class="outbox-message"
		:class="{ selected }"
		:title="title"
		@click="openModal">
		<template #icon>
			<Avatar :display-name="avatarDisplayName" :email="avatarEmail" />
		</template>
		<template #subtitle>
			{{ subjectForSubtitle }}
		</template>
		<template #indicator>
			<div class="indicator">
				<IconAlertCircleOutline v-if="message.failed && !message.pending"
					:title="details"
					:size="20"
					class="failed-icon error" />
				<NcLoadingIcon v-else-if="message.failed && message.pending"
					:title="details"
					:size="20"
					class="failed-icon pending" />
				<div v-else-if="counter > 0 && !message?.aborted" class="failed-icon countdown">
					<svg>
						<circle r="12" cx="20" cy="20" />
					</svg>
					{{ counter }}
				</div>
			</div>
		</template>
		<template v-if="!message.pending" slot="actions">
			<ActionButton
				:close-after-click="true"
				@click="sendMessageNow">
				{{ t('mail', 'Send now') }}
				<template #icon>
					<Send
						:title="t('mail', 'Send now')"
						:size="20" />
				</template>
			</ActionButton>
			<ActionButton
				:close-after-click="true"
				@click="deleteMessage">
				<template #icon>
					<IconDelete :size="20" />
				</template>
				{{ t('mail', 'Delete') }}
			</ActionButton>
		</template>
	</ListItem>
</template>

<script>
import { NcListItem as ListItem, NcActionButton as ActionButton } from '@nextcloud/vue'
import Avatar from './Avatar'
import IconDelete from 'vue-material-design-icons/Delete'
import IconAlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import OutboxAvatarMixin from '../mixins/OutboxAvatarMixin'
import moment from '@nextcloud/moment'
import logger from '../logger'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { matchError } from '../errors/match'
import { html, plain } from '../util/text'
import Send from 'vue-material-design-icons/Send'
import { UNDO_DELAY } from '../store/constants'

export default {
	name: 'OutboxMessageListItem',
	components: {
		ListItem,
		Avatar,
		ActionButton,
		IconDelete,
		IconAlertCircleOutline,
		NcLoadingIcon,
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
	data() {
		return {
			counter: 0,
		}
	},
	computed: {
		selected() {
			return this.$route.params.messageId === this.message.id
		},
		title() {
			const recipientToString = recipient => recipient.label
			const recipients = this.message.to.map(recipientToString)
				.concat(this.message.cc.map(recipientToString))
				.concat(this.message.bcc.map(recipientToString))
			const formatter = new Intl.ListFormat(getLanguage(), { type: 'conjunction' })
			return formatter.format(recipients)
		},
		details() {
			if (this.message.failed) {
				return this.t('mail', 'Message could not be sent')
			}
			if (!this.message.sendAt) {
				return ''
			}
			return moment.unix(this.message.sendAt).fromNow()
		},
		/**
		 * Subject of message or "No Subject".
		 *
		 * @return {string}
		 */
		subjectForSubtitle() {
			// We have to use || here (instead of ??) because the subject might be '', null
			// or undefined.
			return this.message.subject || this.t('mail', 'No subject')
		},
	},
	watch: {
		counter() {
			if (this.counter > 0 && !this.message?.aborted) {
				setTimeout(() => {
					this.counter--
				}, 1000)
			} else {
				this.$store.commit('outbox/updateMessage', {
					message: {
						...this.message,
						failed: true,
						pending: false,
					},
				})
				this.counter = 0
			}
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
		async sendMessageNow() {
			const message = {
				...this.message,
				pending: true,
				failed: false,
				sendAt: (new Date().getTime() + UNDO_DELAY) / 1000,
			}
			this.$store.commit('outbox/updateMessage', { message })
			this.counter = UNDO_DELAY / 1000
			await this.$store.dispatch('outbox/updateMessage', { message, id: message.id })
			await this.$store.dispatch('outbox/sendMessageWithUndo', { id: message.id }).catch(() => {
				message.pending = false
				message.failed = true
				this.$store.commit('outbox/updateMessage', { message })
			})
		},
		async openModal() {
			if (this.message.pending) {
				return
			}
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

	.indicator {
		padding: 0 8px;
	}

	&.active {
		background-color: var(--color-background-dark);
		border-radius: 16px;
	}

	.failed-icon {
		position: absolute;
		top:0;
		bottom:0;
		right: 20px;
		display: flex;
		align-items: center;

		&.error::v-deep svg {
			fill: var(--color-error);
		}

		&.pending {
			animation:spin 0.4s linear infinite;
			::v-deep svg {
				color: var(--color-primary-element-light)
			}
		}
	}

	.account-color {
		position: absolute;
		left: 0;
		width: 2px;
		height: 69px;
		z-index: 1;
	}

	.countdown {
		width: 20px;
		justify-content: center;
		font-weight: bold;
		font-size: 12px;
		color: var(--color-primary-element);

		svg {
			position: absolute;
			top: 12px;
			right: -10px;
			width: 40px;
			height: 40px;
			transform: rotateY(-180deg) rotateZ(-90deg);
		}
		svg circle {
			stroke-dasharray: 80px;
			stroke-dashoffset: 0;
			stroke-linecap: round;
			stroke-width: 2px;
			stroke: var(--color-primary-element-light);
			fill: none;
			animation: countdown 10s linear infinite forwards;
		}
	}

	@keyframes spin {
		0% {
			transform: rotate(0deg)
		}
		100% {
			transform: rotate(360deg)
		}
	}

	@keyframes countdown {
		from {
			stroke-dashoffset: 0px;
		}
		to {
			stroke-dashoffset: 80px;
		}
	}
}
</style>
