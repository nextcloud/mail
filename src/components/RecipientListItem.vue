<template>
	<div class="multiselect__tag multiselect__tag--recipient">
		<ListItemIcon
			:no-margin="true"
			:title="option.label"
			:avatar-size="24" />
		<Actions>
			<ActionButton @click.prevent="removeRecipient(option)">
				<template #icon>
					<Close :size="20" />
				</template>
			</ActionButton>
		</Actions>
	</div>
</template>

<script>
import ListItemIcon from '@nextcloud/vue/dist/Components/ListItemIcon'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Close from 'vue-material-design-icons/Close'
export default {
	name: 'RecipientListItem',
	components: {
		ListItemIcon,
		Actions,
		ActionButton,
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
	padding: 0 25px 0 0;
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
</style>
