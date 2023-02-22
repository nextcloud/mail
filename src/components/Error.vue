<!--
  - @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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
	<NcEmptyContent
		:title="error"
		:description="message"
		class="mail-error"
		:class="{ 'mail-error--auto-margin': autoMargin }">
		<template #icon>
			<AlertCircleIcon :size="24" />
		</template>
		<template v-if="data && data.debug" #action>
			<NcButton :href="reportUrl">
				{{ t('mail', 'Report this bug') }}
			</NcButton>
		</template>
	</NcEmptyContent>
</template>

<script>
import { getReportUrl } from '../util/CrashReport'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent'
import NcButton from '@nextcloud/vue/dist/Components/NcButton'
import AlertCircleIcon from 'vue-material-design-icons/AlertCircle.vue'

export default {
	name: 'Error',
	components: {
		NcEmptyContent,
		NcButton,
		AlertCircleIcon,
	},
	props: {
		error: {
			type: String,
			required: true,
		},
		message: {
			type: String,
			required: true,
		},
		data: {
			type: Object,
			default: () => undefined,
		},
		autoMargin: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		reportUrl() {
			return getReportUrl(this.data)
		},
	},
}
</script>

<style lang="scss" scoped>
.mail-error {
	&--auto-margin {
		margin: auto 0;
	}
}
</style>
