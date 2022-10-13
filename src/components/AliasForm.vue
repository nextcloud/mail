<!--
  - @copyright 2021 Daniel Kesselberg <mail@danielkesselberg.de>
  -
  - @author 2021 Daniel Kesselberg <mail@danielkesselberg.de>
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
	<div>
		<form v-if="showForm" class="alias-form" @submit.prevent="updateAlias">
			<div>
				<input v-model="changeName"
					type="text"
					required>
				<input v-model="changeAlias"
					type="email"
					:disabled="alias.provisioned"
					required>
			</div>
			<div class="button-group">
				<ButtonVue
					type="tertiary-no-background"
					native-type="submit"
					:title="t('mail', 'Update alias')">
					<template #icon>
						<IconLoading v-if="loading" :size="20" />
						<IconCheck v-else :size="20" />
					</template>
				</ButtonVue>
			</div>
		</form>
		<div v-else class="alias-item">
			<p><strong>{{ alias.name }}</strong> &lt;{{ alias.alias }}&gt;</p>
			<div class="button-group">
				<ButtonVue
					type="tertiary-no-background"
					:title="t('mail', 'Show update alias form')"
					@click="showForm = true">
					<template #icon>
						<IconRename :size="20" />
					</template>
				</ButtonVue>
				<ButtonVue v-if="!alias.provisioned"
					type="tertiary-no-background"
					:title="t('mail', 'Delete alias')"
					@click="deleteAlias">
					<template #icon>
						<IconLoading v-if="loading" :size="20" />
						<IconDelete v-else :size="20" />
					</template>
				</ButtonVue>
			</div>
		</div>
	</div>
</template>

<script>
import logger from '../logger'
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconDelete from 'vue-material-design-icons/Delete'
import IconRename from 'vue-material-design-icons/Pencil'
import IconCheck from 'vue-material-design-icons/Check'
export default {
	name: 'AliasForm',
	components: {
		ButtonVue,
		IconRename,
		IconLoading,
		IconDelete,
		IconCheck,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		alias: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			changeAlias: this.alias.alias,
			changeName: this.alias.name,
			showForm: false,
			loading: false,
		}
	},
	methods: {
		async updateAlias(e) {
			this.loading = true

			await this.$store.dispatch('updateAlias', {
				account: this.account,
				aliasId: this.alias.id,
				alias: this.changeAlias,
				name: this.changeName,
			})

			logger.debug('updated alias', {
				accountId: this.account.id,
				aliasId: this.alias.id,
				alias: this.changeAlias,
				name: this.changeName,
			})

			this.showForm = false
			this.loading = false
		},
		async deleteAlias() {
			this.loading = true

			await this.$store.dispatch('deleteAlias', {
				account: this.account,
				aliasId: this.alias.id,
			})

			logger.debug('deleted alias', {
				accountId: this.account.id,
				aliasId: this.alias.id,
				alias: this.alias.alias,
				name: this.alias.name,
			})

			this.showForm = false
			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
.alias-form, .alias-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.button-group {
	display: flex;
	align-items: center;
}

.icon {
	background-color: var(--color-main-background);
	border: none;
	opacity: 0.7;

	&:hover, &:focus {
		opacity: 1;
	}
}

.icon-checkmark {
	background-image: var(--icon-checkmark-000);
}

.icon-delete {
	background-image: var(--icon-delete-000);
}

.icon-rename {
	background-image: var(--icon-rename-000);
}
</style>
