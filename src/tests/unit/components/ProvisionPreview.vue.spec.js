/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import ProvisionPreview from '../../../components/settings/ProvisionPreview.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const templates = {
	email: '%USERID%@example.com',
	provisioningDomain: 'example.com',
	imapUser: '%USERID%',
	imapHost: 'imap.example.com',
	imapPort: 993,
	imapSslMode: 'ssl',
	smtpUser: '%USERID%',
	smtpHost: 'smtp.example.com',
	smtpPort: 587,
	smtpSslMode: 'tls',
	sieveEnabled: false,
	sieveUser: '',
	masterPasswordEnabled: false,
	masterUser: '',
}

function mountPreview(saveSentCopy) {
	return shallowMount(ProvisionPreview, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			data: { uid: 'user123', email: '' },
			templates: { ...templates, saveSentCopy },
		},
	})
}

describe('ProvisionPreview', () => {
	it('mentions that sent messages are not saved', () => {
		const view = mountPreview(false)

		expect(view.text()).toContain('Sent messages are not saved to the Sent folder')
	})

	it('does not mention the default of saving sent messages', () => {
		const view = mountPreview(true)

		expect(view.text()).not.toContain('Sent messages are not saved to the Sent folder')
	})
})
