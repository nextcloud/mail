<!--
  - @copyright 2020 Patrick Bender <patrick@bender-it-services.de>
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
		<ul class="aliases-list">
			<li v-for="alias in aliases" :key="alias.id">
				<AliasForm :account="account" :alias="alias" />
			</li>
			<li v-if="showForm">
				<form id="createAliasForm" @submit.prevent="createAlias">
					<input v-model="newName"
						type="text"
						:placeholder="t('mail', 'Name')"
						required>
					<input v-model="newAlias"
						type="email"
						:placeholder="t('mail', 'Email address')"
						required>
				</form>
			</li>
		</ul>

		<div v-if="!account.provisioningId">
			<ButtonVue v-if="!showForm" type="primary" @click="showForm = true">
				{{ t('mail', 'Add alias') }}
			</ButtonVue>

			<ButtonVue v-if="showForm"
				native-type="submit"
				type="primary"
				form="createAliasForm"
				:disabled="loading">
				<template #icon>
					<IconLoading v-if="loading" :size="20" />
					<IconCheck v-else :size="20" />
				</template>
				{{ t('mail', 'Create alias') }}
			</ButtonVue>
			<ButtonVue v-if="showForm"
				type="tertiary-no-background"
				class="button-text"
				@click="resetCreate">
				{{ t("mail", "Cancel") }}
			</ButtonVue>
		</div>
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check'
import logger from '../logger'
import AliasForm from './AliasForm'

export default {
	name: 'AliasSettings',
	components: { AliasForm, ButtonVue, IconLoading, IconCheck },
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			newAlias: '',
			newName: this.account.name,
			showForm: false,
			loading: false,
		}
	},
	computed: {
		aliases() {
			return this.account.aliases
		},
	},
	methods: {
		async createAlias() {
			this.loading = true

			await this.$store.dispatch('createAlias', {
				account: this.account,
				alias: this.newAlias,
				name: this.newName,
			})

			logger.debug('created alias', {
				accountId: this.account.id,
				alias: this.newAlias,
				name: this.newName,
			})

			this.resetCreate()
			this.loading = false
		},
		resetCreate() {
			this.newAlias = ''
			this.newName = this.account.name
			this.showForm = false
		},
	},
}
</script>

<style lang="scss" scoped>
.primary {
	padding-left: 26px;
	background-position: 6px;
	color: var(--color-main-background);

	&:after {
		left: 14px;
	}
}

.button-text {
	background-color: transparent;
	border: none;
	color: var(--color-text-maxcontrast);
	font-weight: normal;

	&:hover,
	&:focus {
		color: var(--color-main-text);
	}
}

input {
	width: 195px;
}
.button-vue:deep() {
	display: inline-block !important;
	margin-top: 4px !important;
}
</style>
