<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="multiselect__tag multiselect__tag--recipient" :title="option.email">
		<ListItemIcon :no-margin="true"
			:name="option.label"
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
	methods: {
		removeRecipient(option, field) {
			this.$emit('remove-recipient', option, field)
		},
	},
}
</script>

<style scoped lang="scss">
.multiselect
	.multiselect__tags
	.multiselect__tags-wrap
	.multiselect__tag--recipient {
	padding: 0 ;
	border-radius: 25px;
	border-color: transparent;
	background-color: var(--color-background-dark);
	height: 24px;
	width: 100%;

	& > span.option {
		margin-left: 0
	}
}
.vs__selected{
	padding: 0 !important;
}
.multiselect__tag--recipient .action-item--single {
	width: auto;
	min-width: 24px;
	height: 24px;
	position: absolute;
	right: -7px;
}
.multiselect__tag--recipient .action-item--single .material-design-icon {
	height: 24px;
	width: 24px;
}
.delete-recipient {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	height: 24px;
	width: 24px;
	min-width: 24px;
	margin-left: 6px;
	border-radius: 50%;

	&:hover {
		background: var(--color-background-darker);
	}
}
:deep(.option) {
	margin: 7px;
}
.close-icon {
	margin-right: 3px;
}
</style>
