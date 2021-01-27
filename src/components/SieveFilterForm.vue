<template>
	<div class="section">
		<textarea
			id="sieve-text-area"
			v-model="active.script"
			v-shortkey.avoid
			rows="20"
			:disabled="loading" />
		<p v-if="errorMessage">
			{{ t('mail', 'Oh Snap!') }}
			{{ errorMessage }}
		</p>
		<button
			class="primary"
			:class="loading ? 'icon-loading-small-dark' : 'icon-checkmark-white'"
			:disabled="loading"
			@click="saveActiveScript">
			{{ t('mail', 'Save sieve script') }}
		</button>
	</div>
</template>

<script>
import { getActiveScript, updateActiveScript } from '../service/SieveService'

export default {
	name: 'SieveFilterForm',
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			active: {},
			loading: false,
			errorMessage: '',
		}
	},
	async mounted() {
		this.active = await getActiveScript(this.account.id)
	},
	methods: {
		async saveActiveScript() {
			this.loading = true
			this.errorMessage = ''

			try {
				await updateActiveScript(this.account.id, this.active)
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
