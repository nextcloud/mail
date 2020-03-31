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

import logger from '../logger'

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
			return this.account.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders')
		},
	},
	data() {
		return {
			timeoutID: '',
			key: 'collapse-' + this.account.id,
			classes: ['collapse-folders'],
			text: this.account.collapsed ? t('mail', 'Show all folders') : t('mail', 'Collapse folders'),
			action: () => this.$store.commit('toggleAccountCollapsed', this.account.id),
		}
	},
	methods: {
		toggleCollapse() {
			this.$store.commit('toggleAccountCollapsed', this.account.id)
		},
	},
	mounted() {
		this.$el.ondragenter = () => {
			logger.debug('drag enter on expand-collapse component')

			// this.style.background = '#F5F5F5'
			clearTimeout(this.timeoutID)
			this.timeoutID = setTimeout(() => {
				logger.debug('expanding folder list for drag and drop')

				this.$store.commit('toggleAccountCollapsed', this.account.id)
				this.timeoutID = undefined
			}, 800)
		}
		this.$el.ondragleave = () => {
			logger.debug('drag leave on expand-collapse component')

			clearTimeout(this.timeoutID)
		}
	}
}
</script>

<style lang="scss" scoped>
::v-deep .app-navigation-entry__title {
	color: var(--color-text-maxcontrast);
}
</style>
