<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
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
	<div :class="[message.hasHtmlBody ? 'mail-message-body mail-message-body-html' : 'mail-message-body']"
		role="region"
		:aria-label="t('mail','Message body')">
		<div v-if="itineraries.length > 0" class="message-itinerary">
			<Itinerary :entries="itineraries" :message-id="message.messageId" />
		</div>
		<div v-if="message.scheduling.length > 0" class="message-imip">
			<Imip
				v-for="scheduling in message.scheduling"
				:key="scheduling.id"
				:scheduling="scheduling" />
		</div>
		<MessageHTMLBody v-if="message.hasHtmlBody"
			:url="htmlUrl"
			:message="message"
			:full-height="fullHeight"
			@load="$emit('load', $event)" />
		<MessageEncryptedBody v-else-if="isEncrypted"
			:body="message.body"
			:from="from"
			:message="message" />
		<MessagePlainTextBody v-else
			:body="message.body"
			:signature="message.signature"
			:message="message" />
		<MessageAttachments :attachments="message.attachments" :envelope="envelope" />
		<div id="reply-composer" />
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

import { html, plain } from '../util/text'
import { isPgpgMessage } from '../crypto/pgp'
import Itinerary from './Itinerary'
import MessageAttachments from './MessageAttachments'
import MessageEncryptedBody from './MessageEncryptedBody'
import MessageHTMLBody from './MessageHTMLBody'
import MessagePlainTextBody from './MessagePlainTextBody'
import Imip from './Imip'

export default {
	name: 'Message',
	components: {
		Itinerary,
		MessageAttachments,
		MessageEncryptedBody,
		MessageHTMLBody,
		MessagePlainTextBody,
		Imip,
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
	},
	computed: {
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
		itineraries() {
			return this.message.itineraries ?? []
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
</style>
