<!--
  - @copyright 2023 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
  - @author 2023 Hamza Mahjoubi <hamzamahjoubi221@gmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div>
		<Select
			:options="aliases"
			:value="alias"
			:placeholder="t('mail', 'Select an alias')"
			label="name"
			@select="handleAlias" />
		<Select
			v-if="alias !== null"
			v-model="savedCertificate"
			:options="smimeCertOptions"
			:searchable="false"
			label="label"
			@select="selectCertificate" />
		<Button
			type="primary"
			:disabled="certificate === null"
			:aria-label="t('mail', 'Update Certificate')"
			@click="updateSmimeCertificate">
			{{ t('mail', 'Update Certificate') }}
		</Button>
	</div>
</template>

<script>
import { NcSelect as Select, NcButton as Button } from '@nextcloud/vue'
import { compareSmimeCertificates } from '../util/smime.js'
import { mapGetters } from 'vuex'
import { showError, showSuccess } from '@nextcloud/dialogs'
import Logger from '../logger.js'
import moment from '@nextcloud/moment'

export default {
	name: 'CertificateSettings',
	components: {
		Select,
		Button,
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
		...mapGetters({
			smimeCertificates: 'getSmimeCertificates',
		}),
		savedCertificate: {
			get() {
				if (this.certificate) {
					return this.certificate
				}
				const saved = this.smimeCertOptions.find(certificate => this.alias.smimeCertificateId === certificate.id)
				return saved || { label: t('mail', 'No certificate') }
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
						&& cert.purposes.sign
						&& cert.purposes.encrypt
						// TODO: select a separate certificate for encryption?!
				})
				.map(this.mapCertificateToOption)
				.sort(compareSmimeCertificates)
			certs.push({ label: t('mail', 'No certificate') })

			return certs
		},
	},

	methods: {
		selectCertificate(certificate) {
			this.certificate = certificate
		},
		handleAlias(alias) {
			this.alias = alias
			this.savedCertificate = null
		},
		async updateSmimeCertificate() {
			if (this.alias.isAccountCertificate) {
				await this.$store.dispatch('updateAccountSmimeCertificate', {
					account: this.account,
					smimeCertificateId: this.certificate.id,
				}).then(() => {
					showSuccess(t('mail', 'Certificate updated'))
				}).catch((error) => {
					Logger.error('could not update account Smime ceritificate', { error })
					showError(t('mail', 'Could not update certificate'))
				}
				)
			} else {
				await this.$store.dispatch('updateAlias', {
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
				}
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
			return { ...cert, label }
		},
	},
}
</script>

<style lang="scss" scoped>
.multiselect--single {
  width: 100%;
  margin-bottom: 4px;
}

.button-vue {
	margin-top: 4px !important;
}
</style>
