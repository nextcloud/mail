<!--
  - @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<div :class="[message.hasHtmlBody ? 'mail-message-body mail-message-body-html' : 'mail-message-body']"
		role="region"
		:aria-label="t('mail','Message body')">
		<div v-if="message.itineraries.length > 0" class="message-itinerary">
			<Itinerary :entries="message.itineraries" :message-id="message.messageId" />
		</div>
		<MessageHTMLBody v-if="message.hasHtmlBody"
			:url="htmlUrl"
			:message="message"
			:full-height="fullHeight" />
		<MessageEncryptedBody v-else-if="isEncrypted"
			:body="message.body"
			:from="from"
			:message="message" />
		<MessagePlainTextBody v-else
			:body="message.body"
			:signature="message.signature"
			:message="message" />
		<Popover v-if="message.flags.hasAttachments" class="attachment-popover">
			<Actions slot="trigger">
				<ActionButton icon="icon-public icon-attachment">
					{{ t('mail', 'Attachments') }}
				</ActionButton>
			</Actions>
			<MessageAttachments v-close-popover="true" :attachments="message.attachments" :envelope="envelope" />
		</Popover>
		<div id="reply-composer" />
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import { generateUrl } from '@nextcloud/router'
import Popover from '@nextcloud/vue/dist/Components/Popover'

import { html, plain } from '../util/text'
import { isPgpgMessage } from '../crypto/pgp'
import Itinerary from './Itinerary'
import MessageAttachments from './MessageAttachments'
import MessageEncryptedBody from './MessageEncryptedBody'
import MessageHTMLBody from './MessageHTMLBody'
import MessagePlainTextBody from './MessagePlainTextBody'

export default {
	name: 'Message',
	components: {
		Actions,
		ActionButton,
		Itinerary,
		MessageAttachments,
		MessageEncryptedBody,
		MessageHTMLBody,
		MessagePlainTextBody,
		Popover,
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
	},
}
</script>

<style lang="scss" scoped>
.v-popover > .trigger > .action-item {
	border-radius: 22px;
	background-color: var(--color-background-darker);
}
</style>
