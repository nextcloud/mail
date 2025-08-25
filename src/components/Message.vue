<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :class="[message.hasHtmlBody ? 'mail-message-body mail-message-body-html' : 'mail-message-body']"
		role="region"
		:aria-label="t('mail','Message body')">
		<PhishingWarning v-if="message.phishingDetails.warning" :phishing-data="message.phishingDetails.checks" />
		<div v-if="message.smime.isSigned && !message.smime.signatureIsValid"
			class="invalid-signature-warning">
			<LockOffIcon :size="20"
				fill-color="red"
				class="invalid-signature-warning__icon" />
			<p>
				{{ t('mail', 'Warning: The S/MIME signature of this message is  unverified. The sender might be impersonating someone!') }}
			</p>
		</div>
		<div v-if="itineraries.length > 0" class="message-itinerary">
			<Itinerary :entries="itineraries" :message-id="message.messageId" />
		</div>
		<div v-if="hasCurrentUserPrincipalAndCollections && message.scheduling.length > 0" class="message-imip">
			<Imip v-for="scheduling in message.scheduling"
				:key="scheduling.id"
				:scheduling="scheduling" />
		</div>
		<MessageHTMLBody v-if="message.hasHtmlBody"
			:url="htmlUrl"
			:message="message"
			:full-height="fullHeight"
			@load="$emit('load', $event)"
			@translate="$emit('translate')" />
		<MessageEncryptedBody v-else-if="isEncrypted || isPgpMimeEncrypted"
			:body="message.body"
			:from="from"
			:message="message" />
		<MessagePlainTextBody v-else
			:body="message.body"
			:signature="message.signature"
			:message="message"
			@translate="$emit('translate')" />
		<MessageAttachments :attachments="message.attachments" :envelope="envelope" />
		<div id="reply-composer" />
		<div class="reply-buttons">
			<div v-if="smartReplies.length > 0" class="reply-buttons__suggested">
				<NcAssistantButton v-for="(reply,index) in smartReplies"
					:key="index"
					class="reply-buttons__suggested__button"
					type="secondary"
					@click="onReply(reply)">
					{{ reply }}
				</NcAssistantButton>
			</div>
			<NcButton type="primary"
				class="reply-buttons__notsuggested"
				@click="onReply('')">
				<template #icon>
					<ReplyIcon />
				</template>
				{{ replyButtonLabel }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcAssistantButton } from '@nextcloud/vue'

import { html, plain } from '../util/text.js'
import { isPgpgMessage } from '../crypto/pgp.js'
import Itinerary from './Itinerary.vue'
import MessageAttachments from './MessageAttachments.vue'
import PhishingWarning from './PhishingWarning.vue'
import MessageEncryptedBody from './MessageEncryptedBody.vue'
import MessageHTMLBody from './MessageHTMLBody.vue'
import MessagePlainTextBody from './MessagePlainTextBody.vue'
import Imip from './Imip.vue'
import LockOffIcon from 'vue-material-design-icons/LockOffOutline.vue'
import ReplyIcon from 'vue-material-design-icons/ReplyOutline.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'Message',
	components: {
		Itinerary,
		MessageAttachments,
		MessageEncryptedBody,
		MessageHTMLBody,
		MessagePlainTextBody,
		PhishingWarning,
		Imip,
		LockOffIcon,
		ReplyIcon,
		NcButton,
		NcAssistantButton,
	},
	props: {
		envelope: {
			required: true,
			type: Object,
		},
		message: {
			required: true,
			type: Object,
		},
		fullHeight: {
			required: false,
			type: Boolean,
			default: false,
		},
		smartReplies: {
			required: false,
			type: Array,
			default: () => [],
		},
		replyButtonLabel: {
			required: true,
			type: String,
		},
	},
	computed: {
		...mapStores(useMainStore),
		from() {
			return this.message.from.length === 0 ? '?' : this.message.from[0].label || this.message.from[0].email
		},
		htmlUrl() {
			return generateUrl('/apps/mail/api/messages/{id}/html', {
				id: this.envelope.databaseId,
			})
		},
		isEncrypted() {
			return isPgpgMessage(this.message.hasHtmlBody ? html(this.message.body) : plain(this.message.body))
		},
		isPgpMimeEncrypted() {
			return this.message.isPgpMimeEncrypted
		},
		itineraries() {
			return this.message.itineraries ?? []
		},
		hasCurrentUserPrincipalAndCollections() {
			return this.mainStore.hasCurrentUserPrincipalAndCollections
		},
	},
	methods: {
		onReply(replyBody) {
			this.$emit('reply', replyBody)
		},
	},
}
</script>

<style lang="scss" scoped>
.v-popover > .trigger > .action-item {
	border-radius: 22px;
	background-color: var(--color-background-darker);
}

.message-imip {
	padding: 5px 10px;
}

.invalid-signature-warning {
	display: flex;
	align-items: center;
	gap: 5px;

	border: solid 2px var(--color-border);
	border-radius: var(--border-radius-large);
	border-color: var(--color-warning);

	margin: 5px 10px;
	padding: 10px;

	&__icon {
		// Fix alignment with message
		margin-top: -5px;
	}
}

.reply-buttons {
	margin: 0 calc(var(--default-grid-baseline) * 4) calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 14);
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	justify-content: space-between;
	align-items: center;

	&__suggested {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;

		&__button {
			box-sizing: border-box;

			:deep(.button-vue__text) {
				font-weight: normal;
			}
		}
	}

	&__notsuggested {
		margin-inline-start: auto;
	}
}

@media screen and (max-width: 600px) {
	.reply-buttons {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;

		&__suggested {
			display: flex;
			flex-wrap: wrap;
			gap: 5px;
		}

		&__notsuggested {
			margin-inline-start: 0;
		}
	}
}
</style>
