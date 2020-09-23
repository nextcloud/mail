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
	<UserBubble :display-name="label"
		:avatar-image="avatarUrlAbsolute"
		@click="onClick">
		<span class="user-bubble-email">{{ email }}</span>
	</UserBubble>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import UserBubble from '@nextcloud/vue/dist/Components/UserBubble'

import { fetchAvatarUrlMemoized } from '../service/AvatarService'

export default {
	name: 'RecipientBubble',
	components: {
		UserBubble,
	},
	props: {
		email: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			avatarUrl: undefined,
		}
	},
	computed: {
		avatarUrlAbsolute() {
			if (!this.avatarUrl) {
				return
			}
			if (this.avatarUrl.startsWith('http')) {
				return this.avatarUrl
			}

			// Make it an absolute URL because the user bubble component doesn't work with relative URLs
			return window.location.protocol + '//' + window.location.host + generateUrl(this.avatarUrl)
		},
	},
	async mounted() {
		try {
			this.avatarUrl = await fetchAvatarUrlMemoized(this.email)
		} catch (error) {
			console.debug('no avatar for ' + this.email, {
				error,
			})
		}
	},
	methods: {
		onClick() {
			this.$router.push({
				name: 'message',
				params: {
					mailboxId: 'priority', // TODO: figure out current mailbox
					threadId: 'new',
				},
				query: {
					to: this.email,
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.user-bubble-email {
	margin: 10px;
}
</style>
