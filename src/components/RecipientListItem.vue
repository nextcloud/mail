<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="isInternal?'ncselect__tag--recipient' :'ncselect__tag--recipient external'" :title="option.email">
		<ListItemIcon :no-margin="true"
			:name="option.label || option.displayName || option.email"
			:url="option.photo"
			:avatar-size="24" />
		<Close class="delete-recipient"
			:size="20"
			@click.prevent="removeRecipient(option)" />
	</div>
</template>

<script>
import { NcListItemIcon as ListItemIcon } from '@nextcloud/vue'
import Close from 'vue-material-design-icons/Close.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'RecipientListItem',
	components: {
		ListItemIcon,
		Close,
	},
	props: {
		option: {
			type: Object,
			required: true,
		},
	},
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
			this.$emit('remove-recipient', option, field)
		},
	},
}
</script>

<style scoped lang="scss">
.external {
	background-color: var(--color-error) !important;
	:deep(.option__lineone){
		color: var(--color-primary-text) !important;
	}
}

.ncselect__tag--recipient {
	padding: 0 ;
	border-radius: 25px;
	border-color: transparent;
	background-color: var(--color-background-dark);
	height: 24px;
	max-width: 100%;
	display: flex;

	& > span.option {
		margin-inline-start: 0
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
