<template>
	<div class="flex_row">
		<div>
			<label :for="templateid + '-test-subject'">{{ t('mail', 'Test Subject') }}</label>
			<div class="wrapper">
				<Multiselect
					:id="templateid + '-test-subject'"
					v-model="test.testSubject"
					:options="Object.keys(supportedsievestructure.supportedTestSubjects)"
					:searchable="false"
					@select="onSelectSubject" />
			</div>
		</div>
		<template v-if="expectedParameters">
			<div v-if="showAddressParts">
				<label :for="templateid + '-addres-part'">{{ t('mail', 'Addresparts') }}</label>
				<div class="wrapper">
					<Multiselect
						:id="templateid + '-addres-part'"
						v-model="addressPartValue"
						:options="addressParts"
						:searchable="false"
						:allow-empty="expectedParameters.addresspart.optional"
						:multiple="expectedParameters.addresspart.multiple" />
				</div>
			</div>
			<div v-if="showMatchTypes">
				<label :for="templateid + '-match-type'">{{ t('mail', 'Matchtype(s)') }}</label>
				<div class="wrapper">
					<Multiselect
						:id="templateid + '-match-type'"
						v-model="matchTypeValue"
						:options="matchtypes"
						:searchable="false"
						:allow-empty="expectedParameters.matchtype.optional"
						:multiple="expectedParameters.matchtype.multiple" />
				</div>
			</div>
			<div v-if="showEnvelopeParts">
				<label :for="templateid + '-envelope-part'">{{ t('mail', 'EnvelopeParts') }}</label>
				<div class="wrapper">
					<Multiselect
						:id="templateid + '-envelope-part'"
						v-model="envelopePartValue"
						:options="supportedsievestructure.envelopeParts"
						:searchable="false"
						:allow-empty="expectedParameters.envelopepart.optional"
						:multiple="expectedParameters.envelopepart.multiple" />
				</div>
			</div>
			<div v-if="showHeaders">
				<label :for="templateid + '-headers'">{{ t('mail', 'Header(s)') }}</label>
				<div class="wrapper">
					<Multiselect
						:id="templateid + '-headers'"
						v-model="headerValue"
						:options="supportedsievestructure.headers"
						:searchable="true"
						:allow-empty="expectedParameters.headers.optional"
						:multiple="expectedParameters.headers.multiple"
						:taggable="true"
						tag-placeholder="Add this as new header"
						@tag="addCustomHeader" />
				</div>
			</div>
			<div v-if="showKeylist" class="flex_column">
				<label>{{ t('mail', 'Keylist') }}</label>
				<Multiselect
					:id="templateid + '-keys'"
					v-model="test.parameters.keylist"
					:options="test.parameters.keylist"
					:searchable="true"
					:allow-empty="false"
					:multiple="true"
					:taggable="true"
					tag-placeholder="Add this as new key"
					@tag="addKey" />
			</div>
			<div v-if="expectedParameters.size">
				<label :for="templateid + '-size'">{{ t('mail', 'Size') }}</label>
				<div class="wrapper">
					<input :id="templateid + '-size'" v-model="test.parameters.size[0]">
				</div>
			</div>
		</template>
	</div>
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import Vue from 'vue'

export default {
	name: 'SieveFilterTest',
	components: {
		Multiselect,
	},
	props: {
		test: {
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
	},
	data() {
		return {
			expectedParameters: null,
		}
	},
	computed: {
		addressParts() {
			return Object.values(this.supportedsievestructure.supportedAddressParts)
				.filter((item) => {
					return item.usages.includes(this.test.testSubject)
				})
				.map((a) => a.name)
		},
		matchtypes() {
			return Object.values(this.supportedsievestructure.supportedMatchTypes)
				.filter((item) => {
					return item.usages.includes(this.test.testSubject)
				})
				.map((a) => a.name)
		},
		envelopePartValue: {
			get() {
				return this.getValueForParameter('envelopepart')
			},
			set(value) {
				this.setValueForParameter('envelopepart', value)
			},
		},
		addressPartValue: {
			get() {
				return this.getValueForParameter('addresspart')
			},
			set(value) {
				this.setValueForParameter('addresspart', value)
			},
		},
		matchTypeValue: {
			get() {
				return this.getValueForParameter('matchtype')
			},
			set(value) {
				this.setValueForParameter('matchtype', value)
			},
		},
		headerValue: {
			get() {
				return this.getValueForParameter('headers')
			},
			set(value) {
				this.setValueForParameter('headers', value)
			},
		},
		showAddressParts() {
			let x = this.expectedParameters.addresspart !== undefined
			if (x) {
				x = x && this.addressParts.length > 0
			}
			return x
		},
		showEnvelopeParts() {
			let x = this.expectedParameters.envelopepart !== undefined
			if (x) {
				x = x && this.supportedsievestructure.envelopeParts.length > 0
			}
			return x
		},
		showKeylist() {
			const x = this.expectedParameters.keylist !== undefined
			return x
		},
		showMatchTypes() {
			let x = this.expectedParameters.matchtype !== undefined
			if (x) {
				x = x && this.matchtypes.length > 0
			}
			return x
		},
		showHeaders() {
			let x = this.expectedParameters.headers !== undefined
			if (x) {
				x = x && this.supportedsievestructure.headers.length > 0
			}
			return x
		},
	},
	mounted() {
		this.setExpectedParameters(this.test.testSubject)
	},
	methods: {
		getValueForParameter(parameter) {
			return this.expectedParameters[parameter].multiple
				? this.test.parameters[parameter]
				: this.test.parameters[parameter][0]
		},
		setValueForParameter(parameter, value) {
			if (this.expectedParameters[parameter].multiple) {
				this.test.parameters[parameter] = []
				if (Array.isArray(value)) {
					value.forEach((val, index) => {
						Vue.set(this.test.parameters[parameter], index, val)
					})
				}
			} else {
				Vue.set(this.test.parameters[parameter], 0, value)
			}
		},
		addCustomHeader(val) {
			this.supportedsievestructure.headers.push(val)
			this.test.parameters.headers.push(val)
		},
		addKey(val) {
			this.test.parameters.keylist.push(val)
		},
		removeKey(index) {
			this.test.parameters.keylist.splice(index, 1)
		},
		onSelectSubject(val) {
			this.setExpectedParameters(val)
		},
		setExpectedParameters(val) {
			if (this.supportedsievestructure.supportedTestSubjects[val].parameters) {
				const parameters = Object.assign({})
				const a = this.supportedsievestructure.supportedTestSubjects[val].parameters.split(' ')
				a.forEach((element) => {
					element = element.substring(1)
					let multiple = false
					let optional = false
					if (element.indexOf('*') === 0) {
						element = element.substring(1)
						multiple = true
					}
					if (element.indexOf('?') === 0) {
						element = element.substring(1)
						optional = true
					}
					parameters[element] = { multiple, optional }
					if (!this.test.parameters[element]) {
						Vue.set(this.test.parameters, element, [])
					}
					if (!Array.isArray(this.test.parameters[element])) {
						const val = this.test.parameters.element
						Vue.set(this.test.parameters[element], 0, val)
					} else {
						if (this.test.parameters[element].length === 0 && !multiple) {
							Vue.set(this.test.parameters[element], 0, '')
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
.keylist {
	min-width: 200px;
}
input {
	margin: 0;
}
</style>
