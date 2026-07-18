<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="oidc-admin-settings">
		<p>
			{{
				t(
					'mail',
					'Configure OpenID Connect providers so users whose email domain matches can sign in to their mail account with your identity provider (XOAUTH2), instead of storing a password.',
				)
			}}
		</p>

		<OidcProviderForm
			v-for="provider in providers"
			:key="provider.id"
			:setting="provider"
			:redirect-url="redirectUrl"
			:submit="saveProvider"
			:remove="deleteProvider" />

		<OidcProviderForm
			v-if="addNew"
			:key="formKey"
			:setting="{}"
			:redirect-url="redirectUrl"
			:submit="createNewProvider" />

		<ButtonVue
			v-else
			type="secondary"
			@click="addNew = true">
			<template #icon>
				<IconAdd :size="20" />
			</template>
			{{ t('mail', 'Add OIDC provider') }}
		</ButtonVue>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import ButtonVue from '@nextcloud/vue/components/NcButton'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import OidcProviderForm from './OidcProviderForm.vue'
import logger from '../../logger.js'
import {
	createOidcProvider,
	deleteOidcProvider,
	updateOidcProvider,
} from '../../service/OidcIntegrationService.js'

export default {
	name: 'OidcAdminSettings',
	components: {
		ButtonVue,
		IconAdd,
		OidcProviderForm,
	},

	data() {
		return {
			providers: loadState('mail', 'oidc_providers', []),
			redirectUrl: loadState('mail', 'oidc_redirect_url', ''),
			addNew: false,
			formKey: Math.random(),
		}
	},

	methods: {
		t,

		async saveProvider(provider) {
			try {
				const updated = await updateOidcProvider(provider)
				const index = this.providers.findIndex((p) => p.id === updated.id)
				if (index !== -1) {
					this.providers.splice(index, 1, updated)
				}
				showSuccess(t('mail', 'Saved OIDC provider for "{domain}"', { domain: provider.emailDomain }))
			} catch (error) {
				showError(t('mail', 'Could not save OIDC provider'))
				logger.error('Could not save OIDC provider', { error })
				throw error
			}
		},

		async createNewProvider(provider) {
			try {
				const created = await createOidcProvider(provider)
				this.providers.push(created)
				this.addNew = false
				this.formKey = Math.random()
				showSuccess(t('mail', 'Saved OIDC provider for "{domain}"', { domain: provider.emailDomain }))
			} catch (error) {
				showError(t('mail', 'Could not save OIDC provider'))
				logger.error('Could not create OIDC provider', { error })
				throw error
			}
		},

		async deleteProvider(id) {
			const provider = this.providers.find((p) => p.id === id)
			try {
				await deleteOidcProvider(id)
				this.providers = this.providers.filter((p) => p.id !== id)
				showSuccess(t('mail', 'Deleted OIDC provider for "{domain}"', { domain: provider.emailDomain }))
			} catch (error) {
				showError(t('mail', 'Could not delete OIDC provider'))
				logger.error('Could not delete OIDC provider', { error })
				throw error
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.oidc-admin-settings {
	> p {
		margin-bottom: calc(var(--default-grid-baseline, 4px) * 2);
	}
}
</style>
