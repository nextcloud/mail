<template>
	<div>
		<h2>{{ t('mail', 'Filters') }}</h2>

		<label>
			{{ t('mail', 'Sieve script') }}:
			<select v-model="script">
				<option v-for="script in scripts" :key="script" :value="script">{{ script }}</option>
			</select>
		</label>

		<input
			type="submit"
			class="primary"
			:value="t('mail', 'Save')"
			:disabled="canChangeScript"
			@click="onScriptChange"
		/>
	</div>
</template>

<script>
import Logger from '../logger'
import {getScripts, setActiveScript} from '../service/FiltersService'

export default {
	name: 'FilterSettings',

	data: () => ({
		script: '',
		activeScript: '',
		scripts: [],
	}),

	computed: {
		canChangeScript() {
			return this.script === this.activeScript
		},
	},

	mounted() {
		this.getScripts()
	},

	methods: {
		onScriptChange() {
			setActiveScript(this.$route.params.accountId, this.script).then(successful => {
				if (successful) {
					this.activeScript = this.script
				} else {
					// TODO: display error
					Logger.error('an error occurred changing active sieve script')
				}
			})
		},

		getScripts() {
			getScripts(this.$route.params.accountId).then(scripts => {
				const {active, entries} = scripts
				this.script = this.activeScript = active || this.activeScript
				this.scripts = entries || this.scripts
			})
		},
	},
}
</script>
