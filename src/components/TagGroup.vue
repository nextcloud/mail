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
	<div class="tag-group">
		<Tag v-for="tag in tags"
			:key="tag.id"
			class="tag-group__tag"
			:label="tag.displayName"
			:color="tag.color"
			:small="true" />
	</div>
</template>

<script>
import Tag from './Tag.vue'

export default {
	name: 'TagGroup',
	components: {
		Tag
	},
	props: {
		tags: {
			required: true,
			type: Array,
		},
	},
}
</script>

<style scoped lang="scss">
.tag-group {
	padding: 0 6px;

	&__tag {
		margin: 1px;
	}
}
</style>
