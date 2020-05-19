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
	<AppNavigationItem :title="title" @click="toggleCollapse" />
</template>

<script>
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'

export default {
	name: 'NavigationAccountExpandCollapse',
	components: {
		AppNavigationItem,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	computed: {
		id() {
			return 'collapse-' + this.account.id
		},
		title() {
			if (this.account.collapsed && this.account.showSubscribedOnly) {
				return t('mail', 'Show all subscribed folders')
			} else if (this.account.collapsed && !this.account.showSubscribedOnly) {
				return t('mail', 'Show all folders')
			}
			return t('mail', 'Collapse folders')
		},
	},
	methods: {
		toggleCollapse() {
			this.$store.commit('toggleAccountCollapsed', this.account.id)
		},
	},
}
</script>

<style lang="scss" scoped>
::v-deep .app-navigation-entry__title {
	color: var(--color-text-maxcontrast);
}
</style>
