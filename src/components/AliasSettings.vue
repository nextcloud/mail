<!--
  - @copyright 2020 Patrick Bender <patrick@bender-it-services.de>
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
	<div>
		<ul class="aliases-list">
			<li v-for="curAlias in aliases" :key="curAlias.id">
				<strong>{{ curAlias.name }}</strong> &lt;{{ curAlias.alias }}&gt;

				<button class="icon-delete" @click="deleteAlias(curAlias)" />
			</li>
		</ul>

		<div>
			<input
				v-if="addMode"
				id="alias-name"
				v-model="alias.aliasName"
				type="text"
				:placeholder="t('mail', 'Name')"
				:disabled="loading">

			<input
				v-if="addMode"
				id="alias"
				ref="email"
				v-model="alias.alias"
				type="email"
				:placeholder="t('mail', 'Mail Address')"
				:disabled="loading">
		</div>

		<div>
			<button v-if="!addMode" class="primary icon-add" @click="enabledAddMode">
				{{ t('mail', 'Add alias') }}
			</button>

			<button
				v-if="addMode"
				class="primary"
				:class="loading ? 'icon-loading-small-dark' : 'icon-checkmark-white'"
				:disabled="loading"
				@click="saveAlias">
				{{ t('mail', 'Save') }}
			</button>
		</div>
	</div>
</template>

<script>
import logger from '../logger'
import Vue from 'vue'

export default {
	name: 'AliasSettings',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			addMode: false,
			loading: false,
			alias: { aliasName: this.account.name, alias: '' },
		}
	},
	computed: {
		aliases() {
			return this.account.aliases
		},
	},
	methods: {
		enabledAddMode() {
			this.addMode = true
			Vue.nextTick(this.focusEmailField)
		},
		focusEmailField() {
			this.$refs.email.focus()
		},
		async deleteAlias(alias) {
			this.loading = true
			await this.$store.dispatch('deleteAlias', { account: this.account, aliasToDelete: alias })
			logger.info('alias deleted')
			this.loading = false
		},
		async saveAlias() {
			this.loading = true
			await this.$store.dispatch('createAlias', { account: this.account, aliasToAdd: this.alias })
			logger.info('alias added')
			this.alias = { aliasName: this.account.name, alias: '' }
			this.loading = false
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
input {
	width: 195px;
}
.aliases-list {
	margin: 0.5rem 0rem;
}
.icon-delete {
	vertical-align: bottom;
	background-image: var(--icon-delete-000);
	background-color: var(--color-main-background);
	border: none;
	opacity: 0.7;
	&:hover,
	&:focus {
		opacity: 1;
	}
}
.icon-add {
	background-image: var(--icon-add-fff);
}
</style>
