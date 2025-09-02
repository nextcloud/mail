<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="mail">
		<Navigation v-if="hasAccounts" />
		<AppContent>
			<div class="setup"
				:class="{ 'setup--themed': isThemed, }"
				:style="{ 'backgroundImage': isThemed ? undefined: backgroundImgSrc, }">
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
							class="setup__form-content__form"
							@account-created="onAccountCreated" />
					</template>
				</EmptyContent>
				<EmptyContent v-else :name="t('mail', 'To add a mail account, please contact your administrator.')">
					<template #icon>
						<div class="setup__form-content__svg-wrapper" v-html="FluidMail" />
					</template>
				</EmptyContent>
			</div>
		</AppContent>
	</NcContent>
</template>

<script>
import { generateFilePath } from '@nextcloud/router'
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
			backgroundImgSrc: this.isDarkTheme
				? 'url("' + generateFilePath('mail', 'img', 'welcome-connection-dark.png') + '")'
				: 'url("' + generateFilePath('mail', 'img', 'welcome-connection-light.png') + '")',
			isThemed: this.isDarkTheme
				? window.getComputedStyle(document.body).getPropertyValue('--color-primary-element') !== '#0091f2'
				: window.getComputedStyle(document.body).getPropertyValue('--color-primary-element') !== '#00679e',
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

<style lang="scss" scoped>
@use '../../css/fluid';

.setup {
	/* make sure the background image covers everything */
	min-height: 100%;
	min-width: 100%;

	@include fluid.background;

	/* put the contents to the center */
	display: flex;
	align-items: center;
	justify-content: center;

	/** fallback gradient when the theme color isn't standard blue */
	&--themed {
		@include fluid.gradient-background;
	}

	&__form-content {
		/** make it as narrow as possible */
		flex-grow: 0;

		background-color: var(--color-main-background-blur);
		padding: calc(3 * var(--default-grid-baseline));
		border-radius: var(--border-radius-container);
		box-shadow: 0 0 10px var(--color-box-shadow);

		/* overrides for custom icon size and full opacity */
		:deep(.empty-content__icon) {
			width: 128px !important;
			height: 128px !important;
			opacity: 1 !important;

			.setup__form-content__svg-wrapper {
				width: 128px;
				height: 128px;

				svg {
					width: 128px !important;
					height: 128px !important;
					max-width: 128px !important;
					max-height: 128px !important;
				}
			}
		}
	}
}

</style>
