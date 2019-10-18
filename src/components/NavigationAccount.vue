<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<AppNavigationItem
		v-if="visible"
		:id="id"
		:key="id"
		:icon="iconError"
		:menu-open.sync="menuOpen"
		:title="account.emailAddress"
		:to="settingsRoute"
	>
		<!-- Color dot -->
		<AppNavigationIconBullet v-if="bulletColor" slot="icon" :color="bulletColor" />

		<!-- Actions -->
		<template #actions>
			<ActionRouter :to="settingsRoute" icon="icon-settings">
				{{ t('mail', 'Edit account') }}
			</ActionRouter>
			<ActionButton icon="icon-delete" @click="deleteAccount">
				{{ t('mail', 'Delete account') }}
			</ActionButton>
			<ActionInput icon="icon-add" @submit="createFolder">
				{{ t('mail', 'Add folder') }}
			</ActionInput>
		</template>
	</AppNavigationItem>
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationIconBullet from '@nextcloud/vue/dist/Components/AppNavigationIconBullet'
import ActionRouter from '@nextcloud/vue/dist/Components/ActionRouter'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'

import {calculateAccountColor} from '../util/AccountColor'
import Logger from '../logger'

export default {
	name: 'NavigationAccount',
	components: {
		AppNavigationItem,
		AppNavigationIconBullet,
		ActionRouter,
		ActionButton,
		ActionInput,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			menuOpen: false,
		}
	},
	computed: {
		visible() {
			return this.account.isUnified !== true && this.account.visible !== false
		},
		settingsRoute() {
			return {
				name: 'accountSettings',
				params: {
					accountId: this.account.id,
				},
			}
		},
		id() {
			return 'account-' + this.account.id
		},
		bulletColor() {
			return this.account.error ? undefined : calculateAccountColor(this.account.emailAddress)
		},
		iconError() {
			return this.account.error ? 'icon-error' : undefined
		},
	},
	methods: {
		createFolder(e) {
			const name = e.target.elements[0].value
			Logger.info('creating folder ' + name)
			this.menuOpen = false
			this.$store
				.dispatch('createFolder', {account: this.account, name})
				.then(() => Logger.info(`folder ${name} created`))
				.catch(error => {
					Logger.error('could not create folder', {error})
					throw error
				})
		},
		deleteAccount() {
			this.$store
				.dispatch('deleteAccount', this.account)
				.catch(error => Logger.error('could not delete account', {error}))
		},
	},
}
</script>
