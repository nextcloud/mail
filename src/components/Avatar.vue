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
	<BaseAvatar v-if="loading || !hasAvatar"
				:displayName="displayName"/>
	<BaseAvatar v-else
				:displayName="displayName"
				:url="avatarUrl"/>
</template>

<script>
	import _ from 'lodash'
	import {Avatar as BaseAvatar} from 'nextcloud-vue'

	import {fetchAvatarUrlMemoized} from '../service/AvatarService'

	export default {
		name: 'Avatar',
		props: {
			displayName: {
				type: String,
				required: true,
			},
			email: {
				type: String,
			}
		},
		data() {
			return {
				loading: true,
				avatarUrl: undefined,
			}
		},
		computed: {
			hasAvatar() {
				return !_.isUndefined(this.avatarUrl)
			}
		},
		components: {
			BaseAvatar
		},
		mounted () {
			fetchAvatarUrlMemoized(this.email)
				.then(url => {
					this.avatarUrl = url
					this.loading = false
				})
		}
	}
</script>
