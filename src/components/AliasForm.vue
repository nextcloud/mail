<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="alias-form">
		<form v-if="showForm"
			:id="formId"
			class="alias-form__form"
			@submit.prevent="updateAlias">
			<input v-model="changeName"
				type="text"
				class="alias-form__form__input"
				required>
			<input v-model="changeAlias"
				:disabled="alias.provisioned"
				type="email"
				class="alias-form__form__input"
				required>
		</form>
		<div v-else>
			<strong>{{ alias.name }}</strong> &lt;{{ alias.alias }}&gt;
		</div>

		<div class="alias-form__actions">
			<template v-if="showForm">
				<NcButton type="tertiary-no-background"
					:aria-label="t('mail', 'Update alias')"
					native-type="submit"
					:form="formId"
					:name="t('mail', 'Update alias')">
					<template #icon>
						<IconLoading v-if="loading" :size="20" />
						<IconCheck v-else :size="20" />
					</template>
				</NcButton>
			</template>
			<template v-else>
				<!-- Extra buttons -->
				<slot />

				<NcButton v-if="enableUpdate"
					type="tertiary-no-background"
					:aria-label="t('mail', 'Rename alias')"
					:name="t('mail', 'Show update alias form')"
					@click.prevent="showForm = true">
					<template #icon>
						<IconRename :size="20" />
					</template>
				</NcButton>
				<NcButton v-if="enableDelete && !alias.provisioned"
					type="tertiary-no-background"
					:aria-label="t('mail', 'Delete alias')"
					:name="t('mail', 'Delete alias')"
					@click.prevent="deleteAlias">
					<template #icon>
						<IconLoading v-if="loading" :size="20" />
						<IconDelete v-else :size="20" />
					</template>
				</NcButton>
			</template>
		</div>
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconDelete from 'vue-material-design-icons/TrashCanOutline.vue'
import IconRename from 'vue-material-design-icons/PencilOutline.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'

export default {
	name: 'AliasForm',
	components: {
		NcButton,
		IconRename,
		IconLoading,
		IconDelete,
		IconCheck,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		alias: {
			type: Object,
			required: true,
		},
		enableUpdate: {
			type: Boolean,
			default: true,
		},
		enableDelete: {
			type: Boolean,
			default: true,
		},
		onUpdateAlias: {
			type: Function,
			default: async (aliasId, { alias, name }) => {},
		},
		onDelete: {
			type: Function,
			default: async (aliasId) => {},
		},
	},
	data() {
		return {
			changeAlias: this.alias.alias,
			changeName: this.alias.name,
			showForm: false,
			loading: false,
		}
	},
	computed: {
		formId() {
			return `alias-form-${this.alias.id}`
		},
	},
	methods: {
		/**
		 * Call alias update event handler of parent.
		 *
		 * @return {Promise<void>}
		 */
		async updateAlias() {
			this.loading = true
			await this.onUpdateAlias(this.alias.id, {
				alias: this.changeAlias,
				name: this.changeName,
			})
			this.showForm = false
			this.loading = false
		},
		/**
		 * Call alias deletion event handler of parent.
		 *
		 * @return {Promise<void>}
		 */
		async deleteAlias() {
			this.loading = true
			await this.onDelete(this.alias.id)
			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
$form-gap: 10px;

.alias-form {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: $form-gap;
	flex-wrap: wrap;
	width: 100%;

	&__form {
		display: flex;
		flex: 1 auto;
		gap: 10px; // Gap between inputs

		&--expand {
			// Prevent the submit button from being wrapped to the next line on normal sized screens
			flex-basis: calc(100% - 44px - $form-gap);
		}

		&__input {
			flex: 1 auto;
		}
	}

	&__actions {
		display: flex;
	}
}
</style>
