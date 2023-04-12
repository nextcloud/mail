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
		<ButtonVue
			type="primary"
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
import { getActiveScript, updateActiveScript } from '../service/SieveService'
import ButtonVue from '@nextcloud/vue/dist/Components/NcButton'
import IconLoading from '@nextcloud/vue/dist/Components/NcLoadingIcon'
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
