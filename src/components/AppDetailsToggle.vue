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
	<div v-if="isMobile"
		class="toggle icon-confirm"
		tabindex="0"
		@click="$emit('close')" />
</template>

<script>
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'

export default {
	name: 'AppDetailsToggle',
	mixins: [isMobile],
}
</script>

<style lang="scss" scoped>
.toggle {
	position: fixed;
	width: 44px;
	height: 44px;
	padding: 14px;
	cursor: pointer;
	opacity: .6;
	font-size: 16px;
	line-height: 17px;
	transform: rotate(180deg);
	background-color: var(--color-main-background);
	z-index: 1100; // needs to be above the main content(1000) and below the navigation(1800)

	&:active,
	&:hover,
	&:focus {
		opacity: 1;
	}

	// Hide app navigation toggle in case we are on mobile and have the back arrow visible
	&::v-deep + .app-navigation--close .app-navigation-toggle {
		display: none;
	}
}
</style>
