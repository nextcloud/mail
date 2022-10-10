<template>
	<div class="section">
		<textarea
			id="sieve-text-area"
			v-model="script"
			v-shortkey.avoid
			rows="20"
			:disabled="loading" />
		<p v-if="errorMessage">
			{{ t('mail', 'Oh Snap!') }}
			{{ errorMessage }}
		</p>
		<ButtonVue
			class="primary"
			:disabled="loading"
			@click="saveActiveScript">
			<template #icon>
				<IconLoading v-if="loading" :size="20" />
				<IconCheck v-else :size="20" />
			</template>
			{{ t('mail', 'Save sieve script') }}
		</ButtonVue>
	</div>
</template>

<script>
import { NcButton as ButtonVue, NcLoadingIcon as IconLoading } from '@nextcloud/vue'
import IconCheck from 'vue-material-design-icons/Check'
export default {
	name: 'SieveFilterForm',
	components: {
		ButtonVue,
		IconLoading,
		IconCheck,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			script: '',
			loading: true,
			errorMessage: '',
		}
	},
	computed: {
		scriptData() {
			return this.$store.getters.getActiveSieveScript(this.account.id)
		},
	},
	watch: {
		scriptData: {
			immediate: true,
			handler(scriptData) {
				if (!scriptData) {
					return
				}

				this.script = scriptData.script
				this.loading = false
			},
		},
	},
	methods: {
		async saveActiveScript() {
			this.loading = true
			this.errorMessage = ''

			try {
				await this.$store.dispatch('updateActiveSieveScript', {
					accountId: this.account.id,
					scriptData: {
						...this.scriptData,
						script: this.script,
					},
				})
			} catch (error) {
				this.errorMessage = error.message
			}

			this.loading = false
		},
	},
}
</script>

<style lang="scss" scoped>
.section {
	display: block;
	padding: 0;
	margin-bottom: 23px;
}

textarea {
	width: 100%;
}

.primary {
	padding-left: 26px;
	background-position: 6px;
	color: var(--color-main-background);

	&:after {
		 left: 14px;
	 }
}
</style>
