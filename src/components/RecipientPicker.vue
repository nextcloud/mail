<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect id="to"
			  ref="toLabel"
			  :value="value"
			  :options="options"
			  :get-option-key="(option) => option.email"
			  :taggable="true"
			  :aria-label-combobox="t('mail', 'Select recipient')"
			  :filter-by="(option, label, search) => filterOption(option, label, search)"
			  :multiple="true"
			  :close-on-select="true"
			  :clear-search-on-select="true"
			  :loading="loading"
			  :reducible="true"
			  :clearable="true"
			  :no-wrap="false"
			  :append-to-body="false"
			  :create-option="createRecipientOption"
			  :clear-search-on-blur="() => searchTerm.includes('@')"
			  @input="onInput"
			  @option:selecting="selectRecipient"
			  @search:blur="selectRecipient"
			  @search="onAutocomplete($event)">
		<template #search="{ events, attributes }">
			<input :placeholder="t('mail', 'Contact or email address …')"
				   type="search"
				   class="vs__search"
				   v-bind="attributes"
				   v-on="events">
		</template>
		<template #selected-option-container="{option}">
			<RecipientListItem :option="option"
							   class="vs__selected selected"
							   @remove-recipient="removeRecipient(option)" />
		</template>
		<template #option="option">
			<div>
				<ListItemIcon :no-margin="true"
							  :name="option.label"
							  :subname="getSubnameForRecipient(option)"
							  :icon-class="!option.id ? 'icon-user' : null"
							  :url="option.photo" />
			</div>
		</template>
	</NcSelect>
</template>

<script>
import {mapGetters} from 'pinia'
import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'
import Avatar from './Avatar.vue'
import useMainStore from '../store/mainStore.js'
import RecipientListItem from "./RecipientListItem.vue";
import uniqBy from "lodash/fp/uniqBy";
import { NcActions as Actions, NcActionButton as ActionButton, NcActionCheckbox as ActionCheckbox, NcActionInput as ActionInput, NcActionRadio as ActionRadio, NcButton as ButtonVue, NcSelect, NcListItemIcon as ListItemIcon, NcIconSvgWrapper } from '@nextcloud/vue'

export default {
	name: 'RecipientPicker',
	components: {
		RecipientListItem,
		NcSelect,
		Avatar,
		IconArrowDown,
		IconArrowUp,
		ListItemIcon
	},
	props: {
		value: {
			type: Array,
			required: true,
		},
		options: {
			type: Array,
			required: true,
		},
		loading: {
			type: Boolean,
			required: true,
		}
	},
	data() {
		return {
			searchTerm: '',
		}
	},
	computed: {
		...mapGetters(useMainStore, ['composerMessage']),
		recipients() {
			return Array.isArray(this.composerMessage.data.to) ? this.composerMessage.data.to : []
		},
	},
	watch: {
		recipients: {
			immediate: true,
			handler() {
				this.expandedRecipients = this.recipients.map(() => false)
			},
		},
	},
	methods: {
		selectRecipient(option) {
			if ((option === null || option === undefined) && this.searchTerm !== '') {
				if (!this.searchTerm.includes('@')) {
					return
				}
				option = { email: this.searchTerm, label: this.searchTerm }
				this.searchTerm = ''
			}

			if (this.value.some((recipient) => recipient.email === option?.email) || !option) {
				return
			}

			this.$emit('select-recipient', option)
		},

		onAutocomplete(term) {
			if (term === undefined || term === '') {
				return
			}

			this.$emit('autocomplete', term)
		},

		onInput(input) {
			this.$emit('input', input)
		},

		filterOption(option, label, search) {
			if (this.value.some((item) => item.email === option.email)) {
				return false // skip option if already selected
			}

			const searchInLowerCase = search.toLocaleLowerCase()

			return (label || '').toLocaleLowerCase().includes(searchInLowerCase)
				|| (option?.email || '').toLocaleLowerCase().includes(searchInLowerCase)
		},

		/**
		 * Create a new option for the to, cc and bcc selects.
		 *
		 * @param {string} value The string (email) typed by the user
		 * @return {{email: string, label: string}} The new option
		 */
		createRecipientOption(value) {
			return { email: value, label: value }
		},

		/**
		 * Return the subname for recipient suggestion.
		 *
		 * Empty if label and email are the same or
		 * if the suggestion is a group.
		 *
		 * @param {{email: string, label: string}} option object
		 * @return {string}
		 */
		getSubnameForRecipient(option) {
			if (option.source && option.source === 'groups') {
				return ''
			}

			if (option.label === option.email) {
				return ''
			}

			return option.email
		},

		removeRecipient(option) {
			this.$emit('remove-recipient', option)
		},
	},
}
</script>
<style scoped lang="scss">
.recipient-info {
	display: inline;
	width: 100%;

	&__single {
		width: 370px;
		display: inline-block;
	}

	&__avatar {
		margin-top: 20px;
		display: inline;
		float: inline-start;
		padding: 20px;
	}

	&__details {
		max-width: 100%;
	}

	&__multiple {
		margin-top: 10px;
		display: flex;
		flex-direction: column;
	}

	&__item {
		margin-bottom: 10px;
	}

	&__expand-toggle {
		cursor: pointer;
		display: flex;
		gap: 5px;
	}

	&__header {
		display: contents;
	}

	&__name {
		margin-top: 50px;
	}

	&__show-less {
		margin-top: 40px;
	}
}
</style>
