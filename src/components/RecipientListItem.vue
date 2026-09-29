<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="isInternal ? 'ncselect__tag--recipient' : 'ncselect__tag--recipient external'" :title="option.email">
		<NcListItemIcon
			:noMargin="true"
			:name="option.label || option.displayName || option.email"
			:url="option.photo"
			:avatarSize="24" />
		<Close
			class="delete-recipient"
			:size="20"
			@click.prevent="removeRecipient(option)" />
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import NcListItemIcon from '@nextcloud/vue/components/NcListItemIcon'
import Close from 'vue-material-design-icons/Close.vue'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'RecipientListItem',
	components: {
		NcListItemIcon,
		Close,
	},

	props: {
		option: {
			type: Object,
			required: true,
		},
	},

	emits: ['removeRecipient'],

	data() {
		return {
			isInternal: true,
		}
	},

	computed: {
		...mapStores(useMainStore),
	},

	async mounted() {
		if (this.mainStore.getPreference('internal-addresses', 'false') === 'true') {
			this.isInternal = this.mainStore.isInternalAddress(this.option.email)
		}
	},

	methods: {
		removeRecipient(option, field) {
			this.$emit('removeRecipient', option, field)
		},
	},
}
</script>

<style scoped lang="scss">
.external {
	// NcSelect styles .vs__selected with a four-class selector
	background-color: var(--color-error) !important;
	:deep(.option__lineone) {
		color: var(--color-error-text);
	}
}

.delete-recipient {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	height: 24px;
	width: 24px;
	min-width: 24px;
	margin-inline-start: 6px;
	border-radius: 50%;
	flex-shrink: 0;

	&:hover {
		background: var(--color-background-darker);
	}
}

.option {
	flex-shrink: 1;
	overflow: hidden;
	width: unset;
}
</style>
