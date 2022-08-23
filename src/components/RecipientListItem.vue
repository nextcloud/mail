<template>
	<div class="multiselect__tag multiselect__tag--recipient">
		<ListItemIcon
			:no-margin="true"
			:title="option.label"
			:avatar-size="24" />
		<Button type="tertiary-no-background" @click.prevent="removeRecipient(option)">
			<template #icon>
				<Close :size="20" />
			</template>
		</Button>
	</div>
</template>

<script>
import ListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon'
import Button from '@nextcloud/vue/dist/Components/NcButton'
import Close from 'vue-material-design-icons/Close'
export default {
	name: 'RecipientListItem',
	components: {
		ListItemIcon,
		Button,
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

<style scoped>
.multiselect
	.multiselect__tags
	.multiselect__tags-wrap
	.multiselect__tag--recipient {
	padding: 0;
	border-radius: 25px;
	border-color: transparent;
	background-color: var(--color-background-dark);
}
.multiselect__tag--recipient .action-item--single {
	width: auto;
	min-width: 24px;
	height: 24px;
	min-height: 24px;
	position: absolute;
	right: 0;
}
.multiselect__tag--recipient .action-item--single .material-design-icon {
	height: 24px;
	width: 24px;
}
::v-deep .option {
	margin-left: 10px;
}
</style>
