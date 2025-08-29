<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="mail">
		<Navigation v-if="hasAccounts" />
		<AppContent>
			<EmptyContent v-if="allowNewMailAccounts"
				class="setup__form-content"
				:name="t('mail', 'Connect your mail account')">
				<template #icon>
					<div class="setup__form-content__svg-wrapper" v-html="FluidMail" />
				</template>
				<template #action>
					<AccountForm :display-name="displayName"
						:email="email"
						:error.sync="error"
						@account-created="onAccountCreated" />
				</template>
			</EmptyContent>
			<EmptyContent v-else :name="t('mail', 'To add a mail account, please contact your administrator.')">
				<template #icon>
					<IconMail />
				</template>
			</EmptyContent>
		</AppContent>
	</NcContent>
</template>

<script>
import { NcContent, NcAppContent as AppContent, NcEmptyContent as EmptyContent } from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'

import AccountForm from '../components/AccountForm.vue'
import FluidMail from '../../img/mail-fluid.svg'
import Navigation from '../components/Navigation.vue'
import logger from '../logger.js'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'Setup',
	components: {
		AppContent,
		AccountForm,
		NcContent,
		EmptyContent,
		Navigation,
	},
	data() {
		return {
			displayName: loadState('mail', 'prefill_displayName'),
			email: loadState('mail', 'prefill_email'),
			FluidMail,
			allowNewMailAccounts: loadState('mail', 'allow-new-accounts', true),
			error: null,
		}
	},
	computed: {
		...mapStores(useMainStore),
		hasAccounts() {
			return this.mainStore.getAccounts.length > 1
		},
	},
	methods: {
		onAccountCreated() {
			logger.info('account successfully created, redirecting …')
			this.$router.push({
				name: 'home',
			})
		},
	},
}
</script>

<style scoped>
.setup__form-content {
	min-height: 100%;
}

/* overrides for custom icon size and full opacity */
:deep(.empty-content__icon) {
	width: 128px !important;
	height: 128px !important;
	opacity: 1 !important;

	.setup__form-content__svg-wrapper {
		width: 128px;
		height: 128px;
	}

	:deep(svg) {
		width: 128px !important;
		height: 128px !important;
		max-width: 128px !important;
		max-height: 128px !important;
	}
}

</style>
