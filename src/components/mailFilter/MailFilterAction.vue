<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="mail-filter-row">
		<div class="mail-filter-column">
			<NcSelect id="mail-filter-action"
				:value="action.type"
				:required="true"
				:label-outside="true"
				:options="availableTypes"
				@input="updateAction({ type: $event })" />
		</div>
		<div class="mail-filter-column">
			<component :is="componentInstance"
				v-if="componentInstance"
				:action="action"
				:account="account"
				@update-action="updateAction" />
		</div>
		<div class="mail-filter-column">
			<NcButton aria-label="Delete action"
				type="tertiary-no-background"
				@click="deleteAction">
				{{ t('mail', 'Delete action') }}
				<template #icon>
					<DeleteIcon :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>
<script>
import MailFilterActionFileinto from './MailFilterActionFileinto.vue'
import MailFilterActionAddflag from './MailFilterActionAddflag.vue'
import { NcButton, NcSelect, NcTextField } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'MailFilterAction',
	components: {
		NcSelect,
		NcTextField,
		NcButton,
		MailFilterActionFileinto,
		MailFilterActionAddflag,
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
				'addflag',
				'fileinto',
				'keep',
				'stop',
			],
		}
	},
	computed: {
		componentInstance() {
			if (this.action.type === 'fileinto') {
				return MailFilterActionFileinto
			} else if (this.action.type === 'addflag') {
				return MailFilterActionAddflag
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
	align-items: baseline;
}
</style>
