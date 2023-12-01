<!--
  - @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<div class="tag"
		:class="{ 'tag--small': small }"
		:style="{ 'background-color': convertHex(color, 0.2) }">
		<span class="tag__label">{{ label }}</span>
	</div>
</template>

<script>

export default {
	name: 'Tag',
	props: {
		label: {
			required: true,
			type: String,
		},
		color: {
			required: true,
			type: String,
		},
		small: {
			default: false,
			type: Boolean,
		},
	},
	methods: {
		convertHex(color, opacity) {
			if (color.length === 4) {
				const r = parseInt(color.substring(1, 2), 16)
				const g = parseInt(color.substring(2, 3), 16)
				const b = parseInt(color.substring(3, 4), 16)
				return `rgba(${r}, ${g}, ${b}, ${opacity})`
			} else {
				const r = parseInt(color.substring(1, 3), 16)
				const g = parseInt(color.substring(3, 5), 16)
				const b = parseInt(color.substring(5, 7), 16)
				return `rgba(${r}, ${g}, ${b}, ${opacity})`
			}
		},
	},
}
</script>

<style scoped lang="scss">
.tag {
	display: inline-block;
	border-radius: var(--border-radius-pill);
	padding: 4px 12px;

	&__label {
		font-size: var(--default-font-size);
		font-weight: bold;
	}

	&--small {
		padding: 0 8px;

		.tag__label {
			font-size: calc(var(--default-font-size) * 0.8);
		}
	}
}
</style>
