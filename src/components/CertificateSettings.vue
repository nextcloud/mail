<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="certificate-settings">
		<NcSelect v-model="alias"
			class="certificate-settings__alias"
			:options="aliases"
			:searchable="false"
			:placeholder="t('mail', 'Select an alias')"
			:aria-label-combobox="t('mail','Select an alias')"
			label="name"
			@input="savedCertificate = null" />
		<NcSelect v-if="alias !== null"
			v-model="savedCertificate"
			class="certificate-settings__certificate"
			:options="smimeCertOptions"
			:aria-label-combobox="t('mail', 'Select certificates')"
			:searchable="false" />
		<NcButton type="primary"
			class="certificate-settings__submit"
			:disabled="certificate === null"
			:aria-label="t('mail', 'Update Certificate')"
			@click="updateSmimeCertificate">
			{{ t('mail', 'Update Certificate') }}
		</NcButton>
		<NcNoteCard v-if="alias && !savedCertificate.isChainVerified"
			type="warning">
			<p>{{ t('mail', 'The selected certificate is not trusted by the server. Recipients might not be able to verify your signature.') }}</p>
		</NcNoteCard>
	</div>
</template>

<script>
import { NcSelect, NcButton, NcNoteCard } from '@nextcloud/vue'
import { compareSmimeCertificates } from '../util/smime.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import Logger from '../logger.js'
import moment from '@nextcloud/moment'
import useMainStore from '../store/mainStore.js'
import { mapStores, mapState } from 'pinia'

export default {
	name: 'CertificateSettings',
	components: {
		NcSelect,
		NcButton,
		NcNoteCard,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			alias: null,
			certificate: null,
		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, {
			smimeCertificates: 'getSmimeCertificates',
		}),
		savedCertificate: {
			get() {
				if (this.certificate) {
					return this.certificate
				}
				const saved = this.smimeCertOptions.find(certificate => this.alias.smimeCertificateId === certificate.id)
				return saved || this.noCertificateOption
			},
			set(newVal) {
				this.certificate = newVal
			},
		},
		accountSmimeCertificate() {
			return {
				id: -1,
				alias: this.account.emailAddress,
				name: this.account.name,
				provisioned: !!this.account.provisioningId,
				smimeCertificateId: this.account.smimeCertificateId,
			}
		},
		aliases() {
			const aliases = this.account.aliases.map((alias) => {
				return {
					id: alias.id,
					alias: alias.alias,
					name: alias.name,
					provisioned: !!alias.provisioningId,
					smimeCertificateId: alias.smimeCertificateId,
					isAccountCertificate: false,
				}
			})
			aliases.push({ ...this.accountSmimeCertificate, isAccountCertificate: true })
			return aliases
		},
		smimeCertOptions() {
			// Only show certificates that are at least valid until tomorrow
			const now = (new Date().getTime() / 1000) + 3600 * 24
			const certs = this.smimeCertificates
				.filter((cert) => {
					return cert.hasKey
						&& cert.emailAddress === this.alias.alias
						&& cert.info.notAfter >= now
						&& cert.info.purposes.sign
						&& cert.info.purposes.encrypt
						// TODO: select a separate certificate for encryption?!
				})
				.map(this.mapCertificateToOption)
				.sort(compareSmimeCertificates)
			certs.push(this.noCertificateOption)

			return certs
		},
		/**
		 * The select option for no certificate
		 *
		 * @return {{label: string, isChainVerified: boolean}}
		 */
		noCertificateOption() {
			return {
				label: this.t('mail', 'No certificate'),
				isChainVerified: true,
			}
		},
	},

	methods: {
		async updateSmimeCertificate() {
			if (this.alias.isAccountCertificate) {
				await this.mainStore.updateAccountSmimeCertificate({
					account: this.account,
					smimeCertificateId: this.certificate.id,
				}).then(() => {
					showSuccess(t('mail', 'Certificate updated'))
				}).catch((error) => {
					Logger.error('could not update account Smime ceritificate', { error })
					showError(t('mail', 'Could not update certificate'))
				},
				)
			} else {
				await this.mainStore.updateAlias({
					account: this.account,
					aliasId: this.alias.id,
					alias: this.alias.alias,
					name: this.alias.name,
					smimeCertificateId: this.certificate.id,
				}).then(() => {
					showSuccess(t('mail', 'Certificate updated'))
				}).catch((error) => {
					Logger.error('could not update alias Smime ceritificate', { error })
					showError(t('mail', 'Could not update certificate'))
				},
				)
			}

		},
		/**
		 * Map an S/MIME certificate from the db to a NcSelect option.
		 *
		 * @param {object} cert S/MIME certificate
		 * @return {object} NcSelect option
		 */
		mapCertificateToOption(cert) {
			const label = this.t('mail', '{commonName} - Valid until {expiryDate}', {
				commonName: cert.info.commonName ?? cert.info.emailAddress,
				expiryDate: moment.unix(cert.info.notAfter).format('LL'),
			})
			return {
				...cert,
				label,
				isChainVerified: cert.info.isChainVerified,
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.certificate-settings {
	&__alias,
	&__certificate {
		width: 100%;
	}

	&__alias + &__certificate {
		margin-top: 5px
	}

	&__submit {
		margin-top: 1rem;
	}
}
</style>
