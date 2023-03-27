<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcModal @close="$emit('close')">
		<div class="certificate-modal">
			<div v-if="!showImportScreen" class="certificate-modal__list">
				<h2>{{ t('mail', 'S/MIME certificates') }}</h2>

				<table class="certificate-modal__list__table">
					<thead>
						<tr>
							<th>{{ t('mail', 'Certificate name') }}</th>
							<th>{{ t('mail', 'E-mail address') }}</th>
							<th>{{ t('mail', 'Valid until') }}</th>
							<th />
						</tr>
					</thead>
					<tbody>
						<tr v-for="certificate in certificates" :key="certificate.id">
							<td>{{ certificate.info.commonName }}</td>
							<td>{{ certificate.info.emailAddress }}</td>
							<td>{{ moment.unix(certificate.info.notAfter).format('LL') }}</td>
							<td>
								<NcButton type="tertiary-no-background" @click="deleteCertificate(certificate.id)">
									<template #icon>
										<DeleteIcon
											:title="t('mail', 'Delete certificate')"
											:size="20" />
									</template>
								</NcButton>
							</td>
						</tr>
					</tbody>
				</table>
				<NcEmptyContent v-if="certificates.length === 0"
					class="certificate__empty"
					:title="t('mail', 'No certificate imported yet')" />
				<div class="certificate-modal__list__actions">
					<NcButton type="primary" @click="showImportScreen = true">
						{{ t('mail', 'Import certificate') }}
					</NcButton>
				</div>
			</div>
			<form
				v-else
				class="certificate-modal__import"
				@submit.prevent="uploadCertificate">
				<h2>{{ t('mail', 'Import S/MIME certificate') }}</h2>

				<fieldset class="certificate-modal__import__type">
					<div>
						<input
							id="certificate-type-pkcs12"
							v-model="certificateType"
							name="certificate-type"
							type="radio"
							:value="TYPE_PKCS12">
						<label for="certificate-type-pkcs12">
							{{ t('mail', 'PKCS #12 Certificate') }}
						</label>
					</div>

					<div>
						<input
							id="certificate-type-pem"
							v-model="certificateType"
							name="certificate-type"
							type="radio"
							:value="TYPE_PEM">
						<label for="certificate-type-pem">
							{{ t('mail', 'PEM Certificate') }}
						</label>
					</div>
				</fieldset>

				<fieldset>
					<label for="certificate">{{ t('mail', 'Certificate') }}</label>
					<input
						id="certificate"
						ref="certificate"
						type="file"
						required
						@change="certificate = $event.target.files[0]">
				</fieldset>

				<fieldset v-if="certificateType === TYPE_PEM">
					<label for="private-key">{{ t('mail', 'Private key (optional)') }}</label>
					<input
						id="private-key"
						ref="privateKey"
						type="file"
						@change="privateKey = $event.target.files[0]">
				</fieldset>

				<fieldset v-if="certificateType === TYPE_PKCS12">
					<label for="password">{{ t('mail', 'Password') }}</label>
					<NcPasswordField :value.sync="password" :label="t('mail', 'Password')" />
				</fieldset>

				<div class="certificate-modal__import__hints">
					<p v-if="certificateType === TYPE_PEM">
						{{ t('mail', 'The private key is only required if you intend to send signed and encrypted emails using this certificate.') }}
					</p>
				</div>

				<div class="certificate-modal__import__actions">
					<NcButton
						type="tertiary-no-background"
						@click="resetImportForm">
						{{ t('mail', 'Back') }}
					</NcButton>
					<NcButton
						type="primary"
						native-type="submit"
						:disabled="loading || !inputFormIsValid">
						{{ t('mail', 'Submit') }}
					</NcButton>
				</div>
			</form>
		</div>
	</NcModal>
</template>

<script>
import { mapGetters } from 'vuex'
import { NcButton, NcModal, NcPasswordField, NcEmptyContent } from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import logger from '../../logger'
import moment from '@nextcloud/moment'
import DeleteIcon from 'vue-material-design-icons/Delete'
import { convertPkcs12ToPem, InvalidPkcs12CertificateError } from '../../util/pkcs12'

const TYPE_PKCS12 = 'pkcs12'
const TYPE_PEM = 'pem'

export default {
	name: 'SmimeCertificateModal',
	components: {
		NcModal,
		NcButton,
		NcPasswordField,
		NcEmptyContent,
		DeleteIcon,
	},
	data() {
		return {
			TYPE_PKCS12,
			TYPE_PEM,

			moment,
			showImportScreen: false,
			loading: false,
			certificateType: TYPE_PKCS12,
			certificate: undefined,
			privateKey: undefined,
			password: '',
		}
	},
	computed: {
		...mapGetters({
			certificates: 'getSmimeCertificates',
		}),
		inputFormIsValid() {
			return !!this.certificate
		},
	},
	async mounted() {
		// Refresh S/MIME certificates for good measure
		await this.$store.dispatch('fetchSmimeCertificates')
	},
	methods: {
		async deleteCertificate(id) {
			await this.$store.dispatch('deleteSmimeCertificate', id)
		},
		async uploadCertificate() {
			let certificate = this.$refs.certificate.files[0]
			let privateKey
			if (this.certificateType === TYPE_PKCS12) {
				try {
					const result = convertPkcs12ToPem(await certificate.arrayBuffer(), this.password)
					certificate = new Blob([result.certificate])
					privateKey = new Blob([result.privateKey])
				} catch (error) {
					if (error.name === InvalidPkcs12CertificateError.name) {
						logger.error('PKCS #12 certificate contains multiple certs or keys', { error })
						showError(t('mail', 'The provided PKCS #12 certificate must contain at least one certificate and exactly one private key.'))
					} else {
						logger.debug('Is probably not a PKCS #12 certificate or the password is wrong', { error })
						showError(t('mail', 'Failed to import the certificate. Please check the password.'))
					}

					return
				}
			} else if (this.certificateType === TYPE_PEM) {
				privateKey = this.$refs.privateKey.files[0]
			} else {
				return
			}

			this.loading = true
			try {
				await this.$store.dispatch('createSmimeCertificate', {
					certificate,
					privateKey,
				})
				showSuccess(t('mail', 'Certificate imported successfully'))
				this.resetImportForm()
			} catch (error) {
				logger.error(
					`Failed to import a S/MIME certificate: ${error.response?.data?.data}`,
					{ error },
				)
				if (privateKey) {
					showError(t('mail', 'Failed to import the certificate. Please make sure that the private key matches the certificate and is not protected by a passphrase.'))
				} else {
					showError(t('mail', 'Failed to import the certificate'))
				}
			} finally {
				this.loading = false
			}
		},
		resetImportForm() {
			this.certificateType = TYPE_PKCS12
			this.showImportScreen = false
			this.certificate = undefined
			this.privateKey = undefined
			this.password = ''
		},
	},
}
</script>

<style lang="scss" scoped>
.certificate-modal {
	padding: 20px;

	&__list {
		table {
			width: 100%;

			th {
				color: var(--color-text-maxcontrast);
			}

			th, td {
				padding: 2.5px;
			}

			// Disable default hover style
			tr:hover {
				background-color: unset;
			}
		}

		&__actions {
			margin: 12px;
			float: right;
		}
	}

	&__import {
		display: flex;
		flex-direction: column;
		gap: 10px;

		input[type=file] {
			display: flex;
			width: 100%;
		}

		&__type {
			display: flex;
			gap: 0 20px;
			flex-wrap: wrap;

			> div {
				display: flex;
				gap: 5px;
				align-items: center;
			}
		}

		&__hints {
			color: var(--color-text-maxcontrast);
		}

		&__actions {
			display: flex;
			justify-content: space-between;
			gap: 15px;
		}
	}
}
</style>
