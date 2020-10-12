<template>
	<div class="flex_row">
		<div>
			<label :for="templateid + '-actionname'">{{ t('mail', 'Action') }}</label>
			<div class="wrapper">
				<Multiselect
					:id="templateid + '-actionname'"
					v-model="action.action"
					:options="Object.keys(supportedsievestructure.supportedAction)"
					:searchable="false"
					@select="onSelectAction" />
			</div>
		</div>
		<template v-if="expectedParameters">
			<div v-if="showAddress" class="flex_column">
				<label :for="templateid + '-address'">{{ t('mail', 'Mailaddress') }}</label>
				<input :id="templateid + '-address'" v-model="action.parameters.address[0]" class="mailaddress" >
			</div>
			<div v-if="showMailbox" class="flex_column">
				<label :for="templateid + '-mailbox-picker'">{{ t('mail', 'Mailbox') }}</label>
				<MailboxPicker
					:id="templateid + '-mailbox-picker'"
					v-model="action.parameters.mailbox[0]"
					:accountid="accountid" />
			</div>
		</template>
	</div>
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import MailboxPicker from './MailboxPicker'
import Vue from 'vue'
import logger from '../logger'

export default {
	name: 'SieveFilterAction',
	components: {
		Multiselect,
		MailboxPicker,
	},
	props: {
		action: {
			type: Object,
			required: true,
		},
		supportedsievestructure: {
			type: Object,
			required: true,
		},
		templateid: {
			type: String,
			required: true,
		},
		accountid: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			expectedParameters: null,
		}
	},
	computed: {
		showAddress() {
			var x = this.expectedParameters.address !== undefined
			return x
		},
		showMailbox() {
			var x = this.expectedParameters.mailbox !== undefined
			return x
		},
	},
	mounted() {
		this.setExpectedParameters(this.action.action)
	},
	methods: {
		onSelectAction(action) {
			this.setExpectedParameters(action)
		},
		setExpectedParameters(val) {
			if (this.supportedsievestructure.supportedAction[val].parameters) {
				logger.info('action: paremeters found')
				var parameters = Object.assign({})
				const a = this.supportedsievestructure.supportedAction[val].parameters.split(' ')
				a.forEach((element) => {
					element = element.substring(1)
					var multiple = false
					var optional = false
					const ast = element.indexOf('*')
					if (element.indexOf('*') == 0) {
						element = element.substring(1)
						multiple = true
					}
					if (element.indexOf('?') == 0) {
						element = element.substring(1)
						optional = true
					}
					parameters[element] = {multiple: multiple, optional: optional}
					if (!this.action.parameters[element]) {
						Vue.set(this.action.parameters, element, [])
					}
					if (!Array.isArray(this.action.parameters[element])) {
						const val = this.action.parameters.element
						Vue.set(this.action.parameters[element], 0, val)
					} else {
						if (this.action.parameters[element].length == 0 && (!multiple || element == 'keylist')) {
							Vue.set(this.action.parameters[element], 0, '')
						}
					}
				})
				Vue.set(this, 'expectedParameters', Object.assign({}, parameters))
			} else {
				Vue.set(this, 'expectedParameters', Object.assign({}))
			}
		},
	},
}
</script>
<style scoped>
input {
	margin: 0;
}
input.mailaddress {
	width: 300px;
}
</style>
