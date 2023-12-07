<!--
  - @copyright 2020 Greta Doçi <gretadoci@gmail.com>
  -
  - @author 2020 Greta Doçi <gretadoci@gmail.com>
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
	<div id="priority">
		<div class="app-content-list-item">
			<h2>{{ name }}</h2>
		</div>
	</div>
</template>
<script>
export default {
	name: 'SectionTitle',
	props: {
		name: {
			type: String,
			required: true,
		},
	},
}
</script>

<style scoped>
#priority {
	display: inline-block;
}
.app-content-list-item {
	opacity: .8;
}
.app-content-list-item:hover {
	background-color: transparent;
	opacity: 0.8;
}
h2 {
	font-weight: normal;
	font-size: 17px;
	margin-bottom: 2px;
}
</style>
