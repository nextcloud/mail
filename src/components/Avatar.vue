<!--
  - @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<BaseAvatar v-if="loading || !hasAvatar" :display-name="displayName" :size="40" />
	<BaseAvatar v-else
		:display-name="displayName"
		:url="avatarUrl"
		:size="40" />
</template>

<script>
import BaseAvatar from '@nextcloud/vue/dist/Components/Avatar'

import { fetchAvatarUrlMemoized } from '../service/AvatarService'

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
	mounted() {
		fetchAvatarUrlMemoized(this.email).then((url) => {
			this.avatarUrl = url
			this.loading = false
		})
	},
}
</script>
