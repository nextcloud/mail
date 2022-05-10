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
	<AppContentDetails>
		<div id="emptycontent">
			<div class="icon icon-mail" />
			<h2>{{ t('mail', 'No message selected') }}</h2>
			<p />
		</div>
	</AppContentDetails>
</template>

<script>
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'

export default {
	name: 'NoMessageSelected',
	components: {
		AppContentDetails,
	},
}
</script>

<style scoped>
.icon {
	height: 64px;
	width: 64px;
	margin: 0 auto 15px;
	background-size: 64px;
	opacity: 0.4;
}

h2,
p {
	text-align: center;
}
</style>
