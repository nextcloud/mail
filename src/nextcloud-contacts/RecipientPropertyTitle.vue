<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="property property--title">
		<div class="property__label">
			<PropertyTitleIcon :icon="icon" />
		</div>

		<h3 class="property__value">
			{{ readableName }}
		</h3>
	</div>
</template>

<script>
import PropertyTitleIcon from 'vue-material-design-icons/OpenInNew.vue'

export default {
	name: 'RecipientPropertyTitle',
	components: {
		PropertyTitleIcon,
	},
	props: {
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
