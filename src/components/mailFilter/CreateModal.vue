<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal size="normal"
		:close-on-click-outside="false"
		@close="closeModal">
		<form class="modal__content" @submit.prevent="createFilter">
			<h2>{{ t('mail', 'Create a new mail filter') }}</h2>

			<p class="intro-text">
				{{ t('mail', 'Choose the headers you want to use to create your filter. In the next step, you will be able to refine the filter conditions and specify the actions to be taken on messages that match your criteria.') }}
			</p>

			<div class="headers-list">
				<NcCheckboxRadioSwitch v-for="header in headers"
					:key="header.key"
					v-model="header.enable"
					type="switch">
					{{ header.label }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcButton type="primary"
				native-type="submit">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<IconCheck v-else :size="20" />
				</template>
				{{ t('mail', 'Create mail filter') }}
			</NcButton>
		</form>
	</NcModal>
</template>

<script>
import {
	NcButton,
	NcLoadingIcon,
	NcCheckboxRadioSwitch,
	NcModal,
} from '@nextcloud/vue'
import { mapStores } from 'pinia'
import useMailFilterStore from '../../store/mailFilterStore.ts'
import useMainStore from '../../store/mainStore.js'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { MailFilterConditionField } from '../../models/mailFilter.ts'

export default {
	name: 'CreateModal',
	components: {
		NcModal,
		NcCheckboxRadioSwitch,
		IconCheck,
		NcLoadingIcon,
		NcButton,

	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		envelope: {
			type: Object,
			required: true,
		},
		loading: {
			type: Boolean,
			required: false,
		},
	},
	data() {
		return {
			currentFilter: null,
			headers: [],
		}
	},
	computed: {
		...mapStores(useMailFilterStore, useMainStore),
	},
	async mounted() {
		this.prepareHeaders()
	},
	methods: {
		prepareHeaders() {
			this.headers = []

			this.headers.push({
				field: MailFilterConditionField.Subject,
				value: this.envelope.subject,
				label: t('mail', 'Subject') + ': ' + this.envelope.subject,
				enable: true,
				key: btoa('subject' + this.envelope.subject),
			})

			for (const from of this.envelope.from) {
				this.headers.push({
					field: MailFilterConditionField.From,
					value: from.email,
					label: t('mail', 'Sender') + ': ' + from.email,
					enable: true,
					key: btoa('from' + from.email),
				})
			}

			for (const to of this.envelope.to) {
				this.headers.push({
					field: MailFilterConditionField.To,
					value: to.email,
					label: t('mail', 'Recipient') + ': ' + to.email,
					enable: true,
					key: btoa('to' + to.email),
				})
			}
		},
		createFilter() {
			const headers = structuredClone(this.headers).filter((header) => header.enable)
			this.$emit('create-filter', headers)
		},
		closeModal() {
			this.headers = []
			this.$emit('close')
		},
	},
}
</script>

<style lang="scss" scoped>
.modal__content {
	margin: 20px;
}

h2, .intro-text, .headers-list {
	margin-bottom: calc(var(--default-grid-baseline) * 2);
}
</style>
