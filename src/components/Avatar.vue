<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAvatar v-if="loading || !hasAvatar"
		:display-name="displayName"
		:size="size"
		:disable-tooltip="disableTooltip" />
	<NcAvatar v-else
		:display-name="displayName"
		:url="avatarUrl"
		:size="size"
		:disable-tooltip="disableTooltip" />
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import { generateUrl } from '@nextcloud/router'
import { fetchAvatarUrlMemoized } from '../service/AvatarService.js'
import logger from '../logger.js'

export default {
	name: 'Avatar',
	components: {
		NcAvatar,
	},
	props: {
		displayName: {
			type: String,
			required: true,
		},
		avatar: {
			type: Object,
			default: null,
		},
		fetchAvatar: {
			type: Boolean,
			default: false,
		},
		email: {
			type: String,
			required: true,
		},
		disableTooltip: {
			type: Boolean,
			default: false,
		},
		size: {
			type: Number,
			default: 40,
		},
	},
	data() {
		return {
			loading: true,
			avatarUrl: undefined,
		}
	},
	computed: {
		hasAvatar() {
			return this.avatarUrl !== undefined
		},
	},
	async mounted() {
		if (this.avatar) {
			this.avatarUrl = this.avatar.isExternal
				? generateUrl('/apps/mail/api/avatars/image/{email}', {
					email: this.email,
				})
				: this.avatar.url
		} else if (this.fetchAvatar) {
			if (this.email !== '') {
				try {
					this.avatarUrl = await fetchAvatarUrlMemoized(this.email)
				} catch {
					logger.debug('Could not fetch avatar', { email: this.email })
				}
			}
		}
		this.loading = false
	},
}
</script>
