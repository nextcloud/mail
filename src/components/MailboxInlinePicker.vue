<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<Treeselect ref="Treeselect"
		v-model="selected"
		:options="mailboxes"
		:multiple="false"
		:clearable="false"
		:disabled="disabled" />
</template>
<script>
import Treeselect from '@riophae/vue-treeselect'
import '@riophae/vue-treeselect/dist/vue-treeselect.css'
import { mailboxHasRights } from '../util/acl.js'

import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'MailboxInlinePicker',
	components: {
		Treeselect,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		value: {
			type: Number,
			default: undefined,
		},
	},
	data() {
		return {
			selected: this.value,
		}
	},
	computed: {
		...mapStores(useMainStore),
		mailboxes() {
			return this.getMailboxes()
		},
	},
	watch: {
		selected(val) {
			if (val !== this.value) {
				this.$emit('input', val)
				this.selected = val
			}
		},
	},
	methods: {
		getMailboxes(mailboxId) {
			let mailboxes = []
			if (!mailboxId) {
				mailboxes = this.mainStore.getMailboxes(this.account.accountId)
			} else {
				mailboxes = this.mainStore.getSubMailboxes(mailboxId)
			}
			mailboxes = mailboxes.filter(mailbox => mailboxHasRights(mailbox, 'i'))
			return mailboxes.map((mailbox) => {
				 return {
					id: mailbox.databaseId,
					label: mailbox.displayName,
					children: mailbox.mailboxes.length > 0 ? this.getMailboxes(mailbox.databaseId) : '',
				}
			})
		},
	},
}
</script>
<style>
.vue-treeselect__control {
	padding: 0;
	border: 0;
	width: 250px;
}

.vue-treeselect__control-arrow-container {
	display: none;
}

.vue-treeselect--searchable .vue-treeselect__input-container {
	padding-inline-start: 0;
	background-color: var(--color-main-background)
}

input.vue-treeselect__input {
	margin: 0;
	padding: 0;
	border: 1px solid var(--color-border-maxcontrast) !important;
}

.vue-treeselect__menu {
	background: var(--color-main-background);
}

.vue-treeselect--single .vue-treeselect__option--selected {
	background: var(--color-primary-element-light);
	border-radius: var(--border-radius-large);
}

.vue-treeselect__option.vue-treeselect__option--highlight,
.vue-treeselect__option:hover,
.vue-treeselect__option:focus {
	border-radius: var(--border-radius-large);
	}

.vue-treeselect__placeholder, .vue-treeselect__single-value {
	line-height: 34px;
	color: var(--color-main-text);
}

</style>
