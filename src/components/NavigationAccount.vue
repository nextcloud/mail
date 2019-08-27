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
	<AppNavigationItem v-if="visible" :item="data" :menu-open.sync="menuOpen" />
</template>

<script>
import AppNavigationItem from 'nextcloud-vue/dist/Components/AppNavigationItem'

import {calculateAccountColor} from '../util/AccountColor'
import Logger from '../logger'

export default {
	name: 'NavigationAccount',
	components: {
		AppNavigationItem,
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
		data() {
			const route = {
				name: 'accountSettings',
				params: {
					accountId: this.account.id,
				},
			}

			const isError = this.account.error
			return {
				id: 'account' + this.account.id,
				key: 'account' + this.account.id,
				text: this.account.emailAddress,
				bullet: isError ? undefined : calculateAccountColor(this.account.name), // TODO
				icon: isError ? 'icon-error' : undefined,
				router: route,
				utils: {
					actions: [
						{
							icon: 'icon-settings',
							text: t('mail', 'Edit'),
							action: () => {
									this.$router.push(route) // eslint-disable-line
							},
						},
						{
							icon: 'icon-delete',
							text: t('mail', 'Delete'),
							action: () => {
								this.$store
									.dispatch('deleteAccount', this.account)
									.catch(error => Logger.error('could not delete account', {error}))
							},
						},
						{
							icon: 'icon-add',
							text: t('mail', 'Add folder'),
							input: 'text',
							action: e => {
								this.createFolder(e)
							},
						},
					],
				},
			}
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
	},
}
</script>
