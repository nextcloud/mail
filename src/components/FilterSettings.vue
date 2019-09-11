<template>
	<div>
		<div>
			<h2>{{ t('mail', 'Filters') }}</h2>

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

		<div>
			<label>
				Use custom script
				<input v-model="customScriptEnabled" type="checkbox" />
			</label>

			<div v-show="customScriptEnabled">
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
			customScriptEnabled: false,
			customScript: '',
			activeScriptName: '',
			isSavingScript: false,
			scriptNames: [],
			scripts: [
				{
					[SIEVE_CUSTOM_NAME]: '',
				},
			],
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

			if (this.customScriptEnabled && name !== SIEVE_CUSTOM_NAME) {
				this.customScriptEnabled = false
			} else if (name === SIEVE_CUSTOM_NAME) {
				this.customScript = this.scripts[SIEVE_CUSTOM_NAME]
				this.customScriptEnabled = true
			}
		},
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

				if (this.activeScriptName === SIEVE_CUSTOM_NAME) {
					getScript(this.accountId, SIEVE_CUSTOM_NAME).then(script => {
						this.scripts[SIEVE_CUSTOM_NAME] = script
						this.customScript = script
						this.customScriptEnabled = true
					})
				}
			})
		},
	},
}
</script>
