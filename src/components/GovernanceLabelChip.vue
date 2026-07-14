<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<span v-if="label"
		class="governance-label-chip"
		:style="{ '--governance-label-color': `#${label.color}` }"
		:title="label.description">
		<IconSecurity :size="16" />
		{{ label.name }}
	</span>
</template>

<script>
import IconSecurity from 'vue-material-design-icons/Security.vue'
import logger from '../logger.js'
import { getGovernanceLabels } from '../service/GovernanceLabelService.js'

export default {
	name: 'GovernanceLabelChip',
	components: {
		IconSecurity,
	},

	props: {
		labelId: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			label: null,
		}
	},

	async mounted() {
		try {
			const labels = await getGovernanceLabels()
			this.label = labels.find((label) => label.id === this.labelId) ?? null
		} catch (error) {
			logger.error('Could not fetch governance labels', { error })
		}
	},
}
</script>

<style lang="scss" scoped>
.governance-label-chip {
	display: inline-flex;
	align-items: center;
	gap: var(--default-grid-baseline);
	padding: 0 calc(var(--default-grid-baseline) * 2);
	border-radius: var(--border-radius-pill);
	color: var(--governance-label-color);
	background-color: color-mix(in srgb, var(--governance-label-color) 12%, transparent);
	white-space: nowrap;
}
</style>
