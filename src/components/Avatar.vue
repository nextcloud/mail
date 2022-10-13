<!--
  - @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<BaseAvatar v-if="loading || !hasAvatar"
		:display-name="displayName"
		:size="size"
		:disable-tooltip="disableTooltip" />
	<BaseAvatar v-else
		:display-name="displayName"
		:url="avatarUrl"
		:size="size"
		:disable-tooltip="disableTooltip" />
</template>

<script>
import BaseAvatar from '@nextcloud/vue/dist/Components/NcAvatar'
import { fetchAvatarUrlMemoized } from '../service/AvatarService'
import logger from '../logger'

export default {
	name: 'Avatar',
	components: {
		BaseAvatar,
	},
	props: {
		displayName: {
			type: String,
			required: true,
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
		if (this.email !== '') {
			try {
				this.avatarUrl = await fetchAvatarUrlMemoized(this.email)
			} catch {
				logger.debug('Could not fetch avatar', { email: this.email })
			}
		}

		this.loading = false
	},
}
</script>
