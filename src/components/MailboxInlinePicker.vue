<template>
	<Treeselect
		ref="Treeselect"
		v-model="selected"
		:options="mailboxes"
		:multiple="false"
		:clearable="false"
		:disabled="disabled" />
</template>
<script>
import Treeselect from '@riophae/vue-treeselect'
import '@riophae/vue-treeselect/dist/vue-treeselect.css'

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
				mailboxes = this.$store.getters.getMailboxes(this.account.accountId)
			} else {
				mailboxes = this.$store.getters.getSubMailboxes(mailboxId)
			}
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
	padding-left: 0;
	background-color: var(--color-main-background)
}
input.vue-treeselect__input {
	margin: 0;
	padding: 0;
}
.vue-treeselect__menu {
	background: var(--color-main-background);
}
.vue-treeselect--single .vue-treeselect__option--selected {
	background: var(--color-primary-light);
	border-radius: var(--border-radius-large);
}
.vue-treeselect__option.vue-treeselect__option--highlight,
.vue-treeselect__option:hover,
.vue-treeselect__option:focus {
	border-radius: var(--border-radius-large);
	}
.vue-treeselect__placeholder, .vue-treeselect__single-value {
	line-height: 44px;
}

</style>
