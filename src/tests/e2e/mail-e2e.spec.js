/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { test, expect } from '@playwright/test'
import { login } from './login.js'

test.beforeEach(async ({ page }) => {
	await login(page)
})

test('render setup page', async ({ page }) => {
	await page.goto('./index.php/apps/mail')

	await expect(page.getByText('Connect your mail account')).toBeVisible();
	await expect(page.locator('#account-form')).toBeVisible();
})

// we might need these in the future

/*
test('open Mail app and load inbox', async ({ page }) => {
	await page.goto('./index.php/apps/mail')

	// Assert that the mail interface is rendered
	await expect(page.getByRole('complementary', { name: 'New message' }).first()).toBeVisible()
	await expect(page.getByRole('link', { name: 'Priority inbox' })).toBeVisible()
	await expect(page.getByRole('link', { name: 'All inboxes' })).toBeVisible()
})

test('compose and send an email', async ({ page }) => {
	await page.goto('./index.php/apps/mail')

	// Open compose modal
	await page.getByRole('complementary', { name: 'New message' }).first().click()

	// Fill in email fields
	await page.getByRole('combobox', { name: 'Select recipient' }).fill('test@example.com')
	await page.getByRole('textbox', { name: 'Subject' }).fill('Test Subject')
	await page.getByRole('textbox', { name: 'Rich Text Editor' }).fill('This is a test message.')

	// Click send
	await page.getByRole('button', { name: 'Send' }).click()

	// Assert that a confirmation or notification appears
	await expect(page.getByText('Message sent')).toBeVisible()
})
*/
