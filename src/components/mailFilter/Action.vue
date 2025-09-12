<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="mail-filter-row">
		<div class="mail-filter-column">
			<NcSelect class="mail-filter__action"
				:value="currentAction"
				:required="true"
				:label-outside="true"
				:options="availableTypes"
				@input="updateAction({ type: $event.id })" />
		</div>
		<div class="mail-filter-column mail-filter-column--grow">
			<component :is="componentInstance"
				v-if="componentInstance"
				:action="action"
				:account="account"
				@update-action="updateAction" />
		</div>
		<div class="mail-filter-column">
			<NcButton :aria-label="t('mail', 'Delete action')"
				type="tertiary-no-background"
				@click="deleteAction">
				<template #icon>
					<DeleteIcon :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>
<script>
import ActionFileinto from './ActionFileinto.vue'
import ActionAddflag from './ActionAddflag.vue'
import ActionStop from './ActionStop.vue'
import { NcButton, NcSelect, NcTextField } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/TrashCanOutline.vue'

export default {
	name: 'Action',
	components: {
		NcSelect,
		NcTextField,
		NcButton,
		ActionFileinto,
		ActionAddflag,
		ActionStop,
		DeleteIcon,
	},
	props: {
		action: {
			type: Object,
			required: true,
		},
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			availableTypes: [
				{
					id: 'addflag',
					label: this.t('mail', 'Add flag'),
				},
				{
					id: 'fileinto',
					label: this.t('mail', 'Move into folder'),
				},
				{
					id: 'stop',
					label: this.t('mail', 'Stop'),
				},
			],
		}
	},
	computed: {
		currentAction() {
			return this.availableTypes.find(type => type.id === this.action.type)
		},
		componentInstance() {
			if (this.action.type === 'fileinto') {
				return ActionFileinto
			} else if (this.action.type === 'addflag') {
				return ActionAddflag
			} else if (this.action.type === 'stop') {
				return ActionStop
			}
			return null
		},
	},
	methods: {
		updateAction(properties) {
			this.$emit('update-action', { ...this.action, ...properties })
		},
		deleteAction() {
			this.$emit('delete-action', this.action)
		},
	},
}
</script>
<style lang="scss" scoped>
.mail-filter-row {
	display: flex;
	gap: 5px;
	align-items: flex-end;
	margin-bottom: var(--default-grid-baseline);

	.mail-filter-column {
		&--grow {
			flex-grow: 1;
		}
	}
}

.mail-filter {
	&__action {
		color: red;
		margin-bottom: 0; /* unset default grid padding */
	}
}

:deep(.vue-treeselect__control) {
	width: 100%; /* todo: fix MailboxInlinePicker.vue styling instead */
}
</style>
