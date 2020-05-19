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
			<ActionCheckbox
				:checked="account.showSubscribedOnly"
				:disabled="savingShowOnlySubscribed"
				@update:checked="changeShowSubscribedOnly"
			>
				{{ t('mail', 'Show only subscribed folders') }}
			</ActionCheckbox>
			<ActionInput icon="icon-add" @submit="createFolder">
				{{ t('mail', 'Add folder') }}
			</ActionInput>
			<ActionButton v-if="!isFirst" icon="icon-triangle-n" @click="changeAccountOrderUp">
				{{ t('mail', 'Move Up') }}
			</ActionButton>
			<ActionButton v-if="!isLast" icon="icon-triangle-s" @click="changeAccountOrderDown">
				{{ t('mail', 'Move down') }}
			</ActionButton>
			<ActionButton v-if="!account.provisioned" icon="icon-delete" @click="removeAccount">
				{{ t('mail', 'Remove account') }}
			</ActionButton>
		</template>
	</AppNavigationItem>
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationIconBullet from '@nextcloud/vue/dist/Components/AppNavigationIconBullet'
import ActionRouter from '@nextcloud/vue/dist/Components/ActionRouter'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import {generateUrl} from '@nextcloud/router'

import {calculateAccountColor} from '../util/AccountColor'
import logger from '../logger'

export default {
	name: 'NavigationAccount',
	components: {
		AppNavigationItem,
		AppNavigationIconBullet,
		ActionRouter,
		ActionButton,
		ActionCheckbox,
		ActionInput,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		isFirst: {
			type: Boolean,
			default: false,
		},
		isLast: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			menuOpen: false,
			loading: {
				delete: false,
			},
			savingShowOnlySubscribed: false,
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
			const name = e.target.elements[1].value
			logger.info('creating folder ' + name)
			this.menuOpen = false
			this.$store
				.dispatch('createFolder', {account: this.account, name})
				.then(() => logger.info(`folder ${name} created`))
				.catch((error) => {
					logger.error('could not create folder', {error})
					throw error
				})
		},
		removeAccount() {
			const id = this.account.id
			logger.info('delete account', {account: this.account})
			OC.dialogs.confirmDestructive(
				t(
					'mail',
					'The account for {email} and cached email data will be removed from Nextcloud, but not from your email provider.',
					{email: this.account.emailAddress}
				),
				t('mail', 'Remove account {email}', {email: this.account.emailAddress}),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('mail', 'Remove {email}', {email: this.account.emailAddress}),
					confirmClasses: 'error',
					cancel: t('mail', 'Cancel'),
				},
				(result) => {
					if (result) {
						return this.$store
							.dispatch('deleteAccount', this.account)
							.then(() => {
								this.loading.delete = true
							})
							.then(() => {
								logger.info(`account ${id} deleted, redirecting …`)

								// TODO: update store and handle this more efficiently
								location.href = generateUrl('/apps/mail')
							})
							.catch((error) => logger.error('could not delete account', {error}))
					}
					this.loading.delete = false
				}
			)
		},
		changeAccountOrderUp() {
			this.$store
				.dispatch('moveAccount', {account: this.account, up: true})
				.catch((error) => logger.error('could not move account up', {error}))
		},
		changeAccountOrderDown() {
			this.$store
				.dispatch('moveAccount', {account: this.account})
				.catch((error) => logger.error('could not move account down', {error}))
		},
		changeShowSubscribedOnly(onlySubscribed) {
			this.savingShowOnlySubscribed = true
			this.$store
				.dispatch('patchAccount', {
					account: this.account,
					data: {
						showSubscribedOnly: onlySubscribed,
					},
				})
				.then(() => {
					this.savingShowOnlySubscribed = false
					logger.info('show only subscribed folders updated to ' + onlySubscribed)
				})
				.catch((error) => {
					logger.error('could not update subscription mode', {error})
					this.savingShowOnlySubscribed = false
					throw error
				})
		},
	},
}
</script>
