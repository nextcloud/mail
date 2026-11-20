/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showSuccess } from '@nextcloud/dialogs'
import { shallowMount } from '@vue/test-utils'
import AliasForm from '../../../components/AliasForm.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import { copyToClipboard } from '../../../util/clipboard.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showSuccess: vi.fn(),
}))
vi.mock('../../../util/clipboard.js', () => ({
	copyToClipboard: vi.fn(),
}))

const EMAIL_ADDRESS = 'jane@example.com'

function mountAliasForm() {
	return shallowMount(AliasForm, {
		global: {
			mixins: [Nextcloud],
		},
		props: {
			account: { id: 1 },
			alias: { id: 2, name: 'Jane', alias: EMAIL_ADDRESS },
		},
	})
}

describe('AliasForm', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('copies the alias email address and confirms it', async () => {
		copyToClipboard.mockResolvedValue(true)
		const view = mountAliasForm()

		await view.vm.copyEmailAddress()

		expect(copyToClipboard).toHaveBeenCalledWith(EMAIL_ADDRESS, expect.any(String))
		expect(showSuccess).toHaveBeenCalled()
	})

	it('does not confirm the copy when the address was shown in a prompt instead', async () => {
		copyToClipboard.mockResolvedValue(false)
		const view = mountAliasForm()

		await view.vm.copyEmailAddress()

		expect(copyToClipboard).toHaveBeenCalledWith(EMAIL_ADDRESS, expect.any(String))
		expect(showSuccess).not.toHaveBeenCalled()
	})
})
