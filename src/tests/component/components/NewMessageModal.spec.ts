/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OneSender } from './NewMessageModal.story.ts'

import { expect, test } from '@playwright/test'

test('shows the only account as sender', async ({ page, mount }) => {
	const component = await mount<typeof OneSender>('components/NewMessageModal/OneSender')

	const fromInput = component.getByRole('combobox', { name: 'Select account' }).locator('..')
	await expect(fromInput).toContainText('Jane Doe <jane@example.org>')
	await expect(page).toHaveScreenshot()
})
