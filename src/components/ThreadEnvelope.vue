<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="envelope">
		<div class="envelope--header"
			:class="{'list-item-style' : expanded }">
			<Avatar v-if="envelope.from && envelope.from[0]"
				:email="envelope.from[0].email"
				:display-name="envelope.from[0].label"
				:disable-tooltip="true"
				:size="40" />
			<div
				v-if="isImportant"
				class="app-content-list-item-star icon-important"
				:data-starred="isImportant ? 'true' : 'false'"
				@click.prevent="onToggleImportant"
				v-html="importantSvg" />
			<div
				v-if="envelope.flags.flagged"
				class="app-content-list-item-star icon-starred"
				:data-starred="envelope.flags.flagged ? 'true' : 'false'"
				@click.prevent="onToggleFlagged" />
			<div
				v-if="envelope.flags.$junk"
				class="app-content-list-item-star icon-junk"
				:data-starred="envelope.flags.$junk ? 'true' : 'false'"
				@click.prevent="onToggleJunk" />
			<router-link
				:to="route"
				event=""
				class="left"
				:class="{seen: envelope.flags.seen}"
				@click.native.prevent="$emit('toggleExpand', $event)">
				<div class="sender">
					{{ envelope.from && envelope.from[0] ? `${envelope.from[0].label} <${envelope.from[0].email}>` : '' }}
				</div>
				<div v-if="hasChangedSubject" class="subject">
					<span class="preview">
						<!-- TODO: instead of subject it should be shown the first line of the message #2666 -->
						{{ cleanSubject }}
					</span>
				</div>
				<div class="recipients" v-if="expanded">
					To: <span class="main-recipient">{{ envelope.to && envelope.to[0] ? `${envelope.to[0].label} <${envelope.to[0].email}>` : '' }}</span>
					<span v-if="envelope.cc.length" class="cc-recipients">
						Cc: <span v-for="ccRec in envelope.cc"
							:key="`cc-${ccRec.email}`"
							class="cc-recipient">{{ ccRec.label !== ccRec.email ? `${ccRec.label} <${ccRec.email}>` : ccRec.email }}</span>
					</span>
				</div>
				<div v-for="tag in tags"
					:key="tag.id"
					class="tag-group">
					<div class="tag-group__bg"
						:style="{'background-color': tag.color}" />
					<span class="tag-group__label"
						:style="{color: tag.color}">{{ tag.displayName }} </span>
				</div>
			</router-link>
			<div class="right">
				<Moment class="timestamp" :timestamp="envelope.dateInt" />
				<button
					:class="{
						'icon-reply-all-white': hasMultipleRecipients,
						'icon-reply-white': !hasMultipleRecipients,
						primary: expanded,
					}"
					class="button"
					@click="onReply">
					<span class="action-label"> {{ t('mail', 'Reply') }}</span>
				</button>
				<MenuEnvelope class="app-content-list-item-menu"
					:envelope="envelope"
					:with-reply="false"
					:with-select="false"
					:with-show-source="true"
					@delete="$emit('delete',envelope.databaseId)" />
			</div>
		</div>
		<Loading v-if="loading" />
		<Message v-else-if="message"
			:envelope="envelope"
			:message="message"
			:full-height="fullHeight" />
		<Error v-else-if="error"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage"
			:data="error"
			role="alert" />
	</div>
</template>
<script>
import Error from './Error'
import Loading from './Loading'
import logger from '../logger'
import Message from './Message'
import MenuEnvelope from './MenuEnvelope'
import Moment from './Moment'
import Avatar from './Avatar'
import importantSvg from '../../img/important.svg'
import { buildRecipients as buildReplyRecipients } from '../ReplyBuilder'

export default {
	name: 'ThreadEnvelope',
	components: {
		Error,
		Loading,
		MenuEnvelope,
		Moment,
		Message,
		Avatar,
	},
	props: {
		envelope: {
			required: true,
			type: Object,
		},
		mailboxId: {
			required: false,
			type: [
				String,
				Number,
			],
			default: undefined,
		},
		threadSubject: {
			required: true,
			type: String,
		},
		expanded: {
			required: false,
			type: Boolean,
			default: false,
		},
		fullHeight: {
			required: false,
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			loading: false,
			error: undefined,
			message: undefined,
			importantSvg,
			seenTimer: undefined,
		}
	},
	computed: {
		account() {
			return this.$store.getters.getAccount(this.envelope.accountId)
		},
		hasMultipleRecipients() {
			if (!this.account) {
				console.error('account is undefined', {
					accountId: this.envelope.accountId,
				})
			}
			const recipients = buildReplyRecipients(this.envelope, {
				label: this.account.name,
				email: this.account.emailAddress,
			})
			return recipients.to.concat(recipients.cc).length > 1
		},
		route() {
			return {
				name: 'message',
				params: {
					mailboxId: this.mailboxId || this.envelope.mailboxId,
					threadId: this.envelope.databaseId,
				},
			}
		},
		isImportant() {
			return this.$store.getters
				.getEnvelopeTags(this.envelope.databaseId)
				.find((tag) => tag.imapLabel === '$label1')
		},
		tags() {
			return this.$store.getters.getEnvelopeTags(this.envelope.databaseId).filter((tag) => tag.imapLabel !== '$label1')
		},
		hasChangedSubject() {
			return this.cleanSubject !== this.threadSubject
		},
		cleanSubject() {
			return this.envelope.subject.replace(/((?:[\t ]*(?:R|RE|F|FW|FWD):[\t ]*)*)/i, '')
		},
	},
	watch: {
		expanded(expanded) {
			if (expanded) {
				this.fetchMessage()
			} else {
				this.message = undefined
			}
		},
	},
	async mounted() {
		if (this.expanded) {
			await this.fetchMessage()

			// Only one envelope is expanded at the time of mounting so we can
			// assume that this is the relevant envelope to be scrolled to.
			this.$nextTick(() => this.scrollToCurrentEnvelope())
		}
	},
	beforeDestroy() {
		if (this.seenTimer !== undefined) {
			logger.info('Navigating away before seenTimer delay, will not mark message as seen/read')
			clearTimeout(this.seenTimer)
		}
	},
	methods: {
		async fetchMessage() {
			this.loading = true

			logger.debug(`fetching thread message ${this.envelope.databaseId}`)

			try {
				this.message = await this.$store.dispatch('fetchMessage', this.envelope.databaseId)
				logger.debug(`message ${this.envelope.databaseId} fetched`, { message: this.message })

				if (!this.envelope.flags.seen) {
					logger.info('Starting timer to mark message as seen/read')
					this.seenTimer = setTimeout(() => {
						this.$store.dispatch('toggleEnvelopeSeen', { envelope: this.envelope })
						this.seenTimer = undefined
					}, 2000)
				}

				this.loading = false
			} catch (error) {
				logger.error('Could not fetch message', { error })
			}

			// Fetch itineraries if they haven't been included in the message data
			if (this.message && !this.message.itineraries) {
				await this.fetchItineraries()
			}
		},
		async fetchItineraries() {
			// Sanity check before actually making the request
			if (!this.message.hasHtmlBody && this.message.attachments.length === 0) {
				return
			}

			logger.debug(`Fetching itineraries for message ${this.envelope.databaseId}`)

			try {
				const itineraries = await this.$store.dispatch('fetchItineraries', this.envelope.databaseId)
				logger.debug(`Itineraries of message ${this.envelope.databaseId} fetched`, { itineraries })
			} catch (error) {
				logger.error(`Could not fetch itineraries of message ${this.envelope.databaseId}`, { error })
			}
		},
		scrollToCurrentEnvelope() {
			// Account for global navigation bar and thread header
			const globalHeader = document.querySelector('#header').clientHeight
			const threadHeader = document.querySelector('#mail-thread-header').clientHeight
			const top = this.$el.getBoundingClientRect().top - globalHeader - threadHeader
			window.scrollTo({ top })
		},
		onReply() {
			this.$store.dispatch('showMessageComposer', {
				reply: {
					mode: this.hasMultipleRecipients ? 'replyAll' : 'reply',
					data: this.envelope,
				},
			})
		},
		onToggleImportant() {
			this.$store.dispatch('toggleEnvelopeImportant', this.envelope)
		},
		onToggleFlagged() {
			this.$store.dispatch('toggleEnvelopeFlagged', this.envelope)
		},
		onToggleJunk() {
			this.$store.dispatch('toggleEnvelopeJunk', this.envelope)
		},
	},
}
</script>

<style lang="scss" scoped>
	.sender {
		font-weight: bold;
	}
	.sender,
	.recipients {
		margin-left: 8px;
	}

	.recipients {
		display: block;
		font-size: .8em;
		line-height: 1.5em;
		margin-top: 5px;
	}

	.cc-recipients {
		margin-left: 5px;
	}

	.cc-recipient,
	.main-recipient {
		opacity: 0.5;
		display: inline-block;
	}

	.cc-recipient {
		position: relative;

		&:after {
			content: ',';
			margin-right: 5px;
		}

		&:last-child {
			&:after {
				content: '';
			}
		}
	}

	.right {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: flex-end;
		margin-left: 10px;
		height: 44px;

		.app-content-list-item-menu {
			margin-left: 4px;
		}

		.timestamp {
			margin-right: 10px;
			font-size: small;
			white-space: nowrap;
			margin-bottom: 0;
		}
	}
	.button {
		color: var(--color-main-background);
		&:not(.active):not(.primary) {
			display: none;

			&.primary {
				background-color: var(--color-primary);
				opacity: 1;
				margin-bottom: 0;

			}
		}
	}
	.avatardiv {
		display: inline-block;
		margin-bottom: -23px;
	}
	.subject {
		margin-left: 8px;
		cursor: default;
	}

	.envelope {
		display: flex;
		flex-direction: column;
    	border-radius: 0;
		margin-left: 10px;
		margin-right: 10px;
		background-color: var(--color-main-background);
		padding-bottom: 20px;
		border: 1px solid #e5e5e5;

		& + .envelope {
			margin-top: -20px;
		}

		&:last-of-type {
			margin-bottom: 10px;
			padding-bottom: 0;
		}
	}

	.envelope--header {
		position: relative;
		display: flex;
		padding: 10px;
		border-radius: 0;

		&:hover {
			background-color: var(--color-background-hover);
			border-radius: 0;
		}
	}
	.left {
		flex-grow: 1;
	}
	.icon-important {
		::v-deep path {
			fill: #ffcc00;
			stroke: var(--color-main-background);
			cursor: pointer;
		}

		&.app-content-list-item-star {
			background-image: none;
			position: absolute;
			opacity: 1;
			width: 16px;
			height: 16px;
			margin-left: -1px;
			display: flex;

			&:hover,
			&:focus {
				opacity: 0.5;
			}
		}
	}
	.app-content-list-item-star.icon-starred {
		display: inline-block;
		position: absolute;
		margin-top: -2px;
		margin-left: 27px;
		cursor: pointer;
	}
	.app-content-list-item-star.icon-junk {
		display: inline-block;
		position: absolute;
		margin-top: -2px;
		margin-left: 27px;
		cursor: pointer;
		opacity: .2;
	}
	.left:not(.seen) {
		font-weight: bold;
	}
	.tag-group__label {
		margin: 0 7px;
		z-index: 2;
		font-size: calc(var(--default-font-size) * 0.8);
		font-weight: bold;
		padding-left: 2px;
		padding-right: 2px;
	}
	.tag-group__bg {
		position: absolute;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		opacity: 15%;
	}
	.tag-group {
		display: inline-block;
		border: 1px solid transparent;
		border-radius: var(--border-radius-pill);
		position: relative;
		margin: 0 1px;
		overflow: hidden;
		left: 4px;
	}
	.envelope--header.list-item-style {
		border-radius: 0;
	}
</style>
