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

<style scoped>
.toggle {
	position: fixed;
	display: inline-block;
	left: 0;
	width: 44px;
	height: 44px;
	z-index: 1500;
	cursor: pointer;
	transform: rotate(180deg);
	background-color: var(--color-main-background);
	opacity: 1;
}
</style>
