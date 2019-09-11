<template>
	<div>
		<h2>{{ t('mail', 'Filters') }}</h2>

		<div>
			<label>
				{{ t('mail', 'Mode') }}:
				<select v-model="filterMode">
					<option v-for="mode in filterModes" :key="mode" :value="mode">
						{{ mode }}
					</option>
				</select>
			</label>
		</div>

		<div v-show="filterMode === filterModes.available">
			<label>
				{{ t('mail', 'Active script') }}:
				<select v-model="selectedScriptName">
					<option v-for="scriptName in scriptNames" :key="scriptName" :value="scriptName">
						{{ scriptName }}
					</option>
				</select>
			</label>

			<input
				type="submit"
				class="primary"
				:value="t('mail', 'Save')"
				:disabled="canChangeScriptName"
				@click="onSetActiveScriptName"
			/>
		</div>

		<div v-show="filterMode === filterModes.custom">
			<textarea v-model="customScript" style="display: block"></textarea>

			<input
				type="submit"
				class="primary"
				:value="t('mail', 'Save')"
				:disabled="canSaveCustomScript"
				@click="onSaveCustomScript"
			/>
		</div>
	</div>
</template>

<script>
import Logger from '../logger'
import {SIEVE_NAME, SIEVE_CUSTOM_NAME} from '../store/constants'
import {getScript, getScripts, setActiveScript, saveCustomScript} from '../service/FiltersService'

export default {
	name: 'FilterSettings',

	data() {
		return {
			accountId: this.$route.params.accountId,
			selectedScriptName: '',
			customScript: '',
			activeScriptName: '',
			isSavingScript: false,
			filterMode: '',
			scriptNames: [],
			scripts: {
				[SIEVE_CUSTOM_NAME]: '',
			},
			filterModes: {
				simple: 'Simple',
				custom: 'Custom',
				available: 'From available',
			},
		}
	},

	computed: {
		canChangeScriptName() {
			return this.selectedScriptName === this.activeScriptName
		},

		canSaveCustomScript() {
			return this.customScript === this.scripts[SIEVE_CUSTOM_NAME] || !this.customScript || this.isSavingScript
		},
	},

	watch: {
		activeScriptName(name) {
			if (this.selectedScriptName !== name) {
				this.selectedScriptName = name
			}

			if (name === SIEVE_CUSTOM_NAME) {
				if (!this.scripts[SIEVE_CUSTOM_NAME]) {
					getScript(this.accountId, SIEVE_CUSTOM_NAME).then(script => {
						this.scripts[SIEVE_CUSTOM_NAME] = script
						this.customScript = script
					})
				}

				if (this.customScript !== this.scripts[SIEVE_CUSTOM_NAME]) {
					this.customScript = this.scripts[SIEVE_CUSTOM_NAME]
				}
			}
		},
	},

	created() {
		this.filterMode = this.filterModes.simple
	},

	mounted() {
		this.getScriptNames()
	},

	methods: {
		onSetActiveScriptName() {
			setActiveScript(this.accountId, this.selectedScriptName).then(successful => {
				if (successful) {
					this.activeScriptName = this.selectedScriptName
				} else {
					// TODO: display error
					Logger.error('an error occurred changing active sieve script')
				}
			})
		},

		onSaveCustomScript() {
			this.isSavingScript = true

			saveCustomScript(this.accountId, this.customScript)
				.then(resp => {
					if (resp.status === 'error') {
						// TODO: display error
						Logger.error(`error saving custom script: ${resp.message}`)
					} else {
						if (!this.scriptNames.includes(SIEVE_CUSTOM_NAME)) {
							this.scriptNames.push(SIEVE_CUSTOM_NAME)
						}

						this.activeScriptName = SIEVE_CUSTOM_NAME
					}
				})
				.finally(() => {
					this.isSavingScript = false
				})
		},

		getScriptNames() {
			getScripts(this.accountId).then(scripts => {
				const {active, entries} = scripts

				this.activeScriptName = active || this.activeScriptName
				this.scriptNames = entries || this.scriptNames

				if (!['', SIEVE_NAME, SIEVE_CUSTOM_NAME].includes(this.activeScriptName)) {
					this.filterMode = this.filterModes.available
				} else if (this.activeScriptName === SIEVE_CUSTOM_NAME) {
					this.filterMode = this.filterModes.custom
				}
			})
		},
	},
}
</script>
