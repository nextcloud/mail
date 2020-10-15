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
			required: false,
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
	width: 300px;
}
.vue-treeselect__control-arrow-container {
	display: none;
}
.vue-treeselect--searchable .vue-treeselect__input-container {
	padding-left: 0;
}
input.vue-treeselect__input {
	margin: 0;
}
</style>
