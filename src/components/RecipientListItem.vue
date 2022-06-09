<template>
	<div class="multiselect__tag multiselect__tag--recipient">
		<ListItemIcon
			:no-margin="true"
			:title="option.label"
			:url="option.photo"
			:avatar-size="24" />
		<Close
			class="delete-recipient"
			:size="20"
			@click.prevent="removeRecipient(option)" />
	</div>
</template>

<script>
import { NcListItemIcon as ListItemIcon } from '@nextcloud/vue'
import Close from 'vue-material-design-icons/Close'
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
	padding: 0;
	border-radius: 25px;
	border-color: transparent;
	background-color: var(--color-background-dark);
	height: 24px;

	& > span.option {
		margin-left: 0
	}
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
	margin-left: 10px;
}
</style>
