<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="calendar-picker-option">
		<div class="calendar-picker-option__color-indicator"
			:style="{ backgroundColor: color }" />

		<span class="calendar-picker-option__label">
			{{ displayname }}
		</span>
		<span :class="displayIcon ? 'icon-caret-dark' : ''" />
	</div>
</template>

<script>
export default {
	name: 'CalendarPickerOption',
	props: {
		color: {
			type: String,
			required: true,
		},
		displayname: {
			type: String,
			required: true,
		},
		displayIcon: {
		  type: Boolean,
			default: false,
		},
	},
}
</script>

<style lang="scss" scoped>
.calendar-picker-option {
	width: 100%;
	display: flex;
	align-items: center;

	&__color-indicator {
		width: 12px;
		height: 12px;
		border-radius: 50%;
		border: none;
		margin-inline-end: calc(var(--default-grid-baseline) * 2);
		flex-basis: calc(var(--default-grid-baseline) * 3);
		flex-shrink: 0;
	}

	&__label {
		overflow: hidden;
		text-overflow: ellipsis;
		flex-grow: 1;
		max-width: 80px;
	}
}

</style>
