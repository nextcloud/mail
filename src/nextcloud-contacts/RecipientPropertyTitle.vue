<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="property property--title">
		<div class="property__label">
			<RecipientPropertyTitleIcon :icon="icon" />
		</div>

		<h6 class="property__value">
			{{ readableName }}
		</h6>
	</div>
</template>

<script>
import RecipientPropertyTitleIcon from './RecipientPropertyTitleIcon.vue'

export default {
	name: 'RecipientPropertyTitle',
	components: {
		RecipientPropertyTitleIcon,
	},
	props: {
		icon: {
			type: String,
			default: '',
			required: true,
		},
		readableName: {
			type: String,
			default: '',
			required: true,
		},
		isReadOnly: {
			type: Boolean,
			required: true,
		},
		property: {
			type: Object,
			default: () => {},
		},
		isMultiple: {
			type: Boolean,
			default: false,
		},
		bus: {
			type: Object,
			required: false,
		},
	},
}
</script>

<style lang="scss" scoped>
h3 {
	font-size: 22px;
	margin: 0;
}

.property {
	margin-top: 12px;
	margin-bottom: 6px;
	display: flex;
	align-items: center;

	// Left align icon and title on mobile
	@media (max-width: 1024px) {
		&__label {
			width: unset;
		}
	}

	&__value {
		font-weight: bold;
	}
}
</style>
