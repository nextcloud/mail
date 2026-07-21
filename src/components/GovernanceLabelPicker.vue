<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSelect
		v-if="governanceAvailable && labels.length"
		class="governance-label-picker"
		:style="{ '--governance-label-color': labelColor(selectedLabel) ?? 'var(--color-main-text)' }"
		:model-value="selectedLabel"
		:options="options"
		label="name"
		:searchable="false"
		:clearable="false"
		placement="bottom-end"
		:aria-label-combobox="t('mail', 'Sensitivity label')"
		@option:selected="selectLabel">
		<template #selected-option="option">
			<span class="governance-label-picker__selected">
				<IconSecurity :size="16" />
				{{ option.name }}
			</span>
		</template>

		<template #option="option">
			<span class="governance-label-picker__option">
				<span
					v-if="option.color"
					class="governance-label-picker__dot"
					:style="{ backgroundColor: labelColor(option) }" />
				<MinusIcon v-else :size="16" />
				{{ option.name }}
			</span>
		</template>
	</NcSelect>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { NcSelect } from '@nextcloud/vue'
import MinusIcon from 'vue-material-design-icons/Minus.vue'
import IconSecurity from 'vue-material-design-icons/Security.vue'
import logger from '../logger.js'
import { getGovernanceLabels } from '../service/GovernanceLabelService.js'

export default {
	name: 'GovernanceLabelPicker',
	components: {
		NcSelect,
		IconSecurity,
		MinusIcon,
	},

	props: {
		value: {
			type: String,
			default: null,
		},
	},

	data() {
		return {
			governanceAvailable: loadState('mail', 'governance-labels-available', false),
			labels: [],
		}
	},

	computed: {
		noLabelOption() {
			return {
				id: null,
				name: t('mail', 'No label'),
				color: null,
			}
		},

		options() {
			return [...this.labels, this.noLabelOption]
		},

		selectedLabel() {
			return this.labels.find((label) => label.id === this.value) ?? this.noLabelOption
		},
	},

	async mounted() {
		if (!this.governanceAvailable) {
			return
		}

		try {
			this.labels = await getGovernanceLabels()
		} catch (error) {
			logger.error('Could not fetch governance labels', { error })
		}
	},

	methods: {
		labelColor(option) {
			// governance stores hex colors without the leading '#'
			return option.color ? `#${option.color}` : null
		},

		selectLabel(option) {
			this.$emit('input', option.id)
		},
	},
}
</script>

<style lang="scss" scoped>
.governance-label-picker {
	// NcSelect forces min-width: 260px, size to the label instead
	&.v-select.select {
		min-width: fit-content;
	}

	:deep(.vs__dropdown-toggle) {
		border: none;
		background-color: color-mix(in srgb, var(--governance-label-color) 12%, transparent);
	}

	:deep(.vs__selected),
	:deep(.vs__open-indicator) {
		color: var(--governance-label-color);
		fill: var(--governance-label-color);
	}

	// keep the selected label visible while the menu is open,
	// vue-select only hides it to make room for the search input
	&.vs--open :deep(.vs__selected) {
		position: static;
		opacity: 1;
	}

	// the select is not searchable, collapse the hidden search input:
	// it otherwise stretches the toggle while open and the floating
	// menu copies that inflated width
	:deep(.vs__search) {
		flex-grow: 0;
		width: 0;
		min-width: 0;
		margin: 0;
		padding: 0;
		border: none;
	}

	// __selected/__option also render inside the dropdown menu, which is
	// appended to <body>; keep these selectors flat (no ancestor) so they
	// still match there
	&__selected,
	&__option {
		display: flex;
		align-items: center;
		justify-content: flex-start;
		width: 100%;
		gap: var(--default-grid-baseline);
		text-align: start;
	}

	&__dot {
		display: block;
		flex-shrink: 0;
		width: calc(var(--default-grid-baseline) * 3);
		height: calc(var(--default-grid-baseline) * 3);
		border-radius: 50%;
	}
}
</style>
