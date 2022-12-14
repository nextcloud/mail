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
								<NcButton type="secondary" @click="deleteCertificate(certificate.id)">
									<template #icon>
										<DeleteIcon :size="20" />
									</template>
									{{ t('mail', 'Delete') }}
								</NcButton>
							</td>
						</tr>
					</tbody>
				</table>

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

				<fieldset>
					<label for="certificate">{{ t('mail', 'Certificate') }}</label>
					<input
						id="certificate"
						ref="certificate"
						type="file"
						@change="certificate = $event.target.files[0]">
				</fieldset>

				<fieldset>
					<label for="private-key">{{ t('mail', 'Private key') }}</label>
					<input
						id="private-key"
						ref="privateKey"
						type="file"
						@change="privateKey = $event.target.files[0]">
				</fieldset>

				<div class="certificate-modal__import__hints">
					<p>{{ t('mail', 'Only PEM encoded certificates and private keys are supported. PKCS #12 certificates (.p12 files) can\'t be imported and need to be converted.') }}</p>
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
import { NcButton, NcModal } from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import logger from '../../logger'
import moment from '@nextcloud/moment'
import DeleteIcon from 'vue-material-design-icons/Delete'

export default {
	name: 'SMimeCertificateModal',
	components: {
		NcModal,
		NcButton,
		DeleteIcon,
	},
	data() {
		return {
			moment,
			showImportScreen: false,
			loading: false,
			certificate: undefined,
			privateKey: undefined,
		}
	},
	computed: {
		...mapGetters({
			certificates: 'getSMimeCertificates',
		}),
		inputFormIsValid() {
			return !!this.certificate
		},
	},
	async mounted() {
		await this.$store.dispatch('fetchSMimeCertificates')
	},
	methods: {
		async deleteCertificate(id) {
			await this.$store.dispatch('deleteSMimeCertificate', id)
		},
		async uploadCertificate() {
			const certificate = this.$refs.certificate.files[0]
			const privateKey = this.$refs.privateKey.files[0]

			this.loading = true
			try {
				await this.$store.dispatch('createSMimeCertificate', {
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
			this.showImportScreen = false
			this.certificate = undefined
			this.privateKey = undefined
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
			margin-top: 12px;
		}
	}

	&__import {
		display: flex;
		flex-direction: column;
		gap: 10px;

		input {
			display: flex;
			width: 100%;
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
