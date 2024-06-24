<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcEmptyContent :name="error"
		:description="message"
		class="mail-error"
		:class="{ 'mail-error--auto-margin': autoMargin }">
		<template #icon>
			<AlertCircleIcon :size="24" />
		</template>
		<template v-if="data && data.debug" #action>
			<NcButton :aria-label="t('mail', 'Report this bug')"
				:href="reportUrl">
				{{ t('mail', 'Report this bug') }}
			</NcButton>
		</template>
	</NcEmptyContent>
</template>

<script>
import { getReportUrl } from '../util/CrashReport.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
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
	height: 100%;
	display: flex;
	&--auto-margin {
		margin: auto 0;
	}
}
</style>
