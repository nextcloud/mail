<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="action">
		<div class="action__type">
			<NcSelect class="action__type__column action__type__column__select"
				:value="currentAction"
				:required="true"
				:label-outside="true"
				:options="availableTypes"
				:clearable="false"
				@input="updateAction({ type: $event.id })" />
			<component :is="componentInstance"
				v-if="componentInstance"
				class="action__type__column"
				:action="action"
				:account="account"
				@update-action="updateAction" />
		</div>
		<NcButton :aria-label="t('mail', 'Delete action')"
			class="action__delete"
			type="tertiary-no-background"
			@click="deleteAction">
			<template #icon>
				<DeleteIcon :size="20" />
			</template>
		</NcButton>
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
.action {
	display: flex;
	margin-bottom: calc(var(--default-grid-baseline) * 2);
	&__type {
		display: flex;
		gap: var(--default-grid-baseline);
		width: 100%;
		&__column {
			flex: 0 1 auto;
			&__select {
				margin: 0
			}
		}
	}
	&__delete {
		width: 30px;
	}
}

:deep(.vs__dropdown-toggle) {
	height: 100%;
}
</style>
