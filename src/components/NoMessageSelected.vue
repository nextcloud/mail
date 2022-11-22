<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<AppContentDetails>
		<NcEmptyContent
			:title="t('mail', 'No message selected')">
			<template #icon>
				<IconMail :size="65" />
			</template>
		</NcEmptyContent>
	</AppContentDetails>
</template>

<script>
import { NcAppContentDetails as AppContentDetails, NcEmptyContent } from '@nextcloud/vue'
import IconMail from 'vue-material-design-icons/Email'

export default {
	name: 'NoMessageSelected',
	components: {
		AppContentDetails,
		NcEmptyContent,
		IconMail,
	},
}
</script>
