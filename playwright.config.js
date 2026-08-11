/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const { defineConfig, devices } = require('@playwright/test')
const path = require('path')

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
	testDir: './src/tests/e2e',
	fullyParallel: false,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	reporter: [
		['list'],
		['html'],
	],
	use: {
		baseURL: 'https://localhost',
		trace: 'on-first-retry',
	},
	projects: [
		{
			name: 'chromium',
			testMatch: '**/*.spec.js',
			use: {
				...devices['Desktop Chrome'],
				ignoreHTTPSErrors: true,
			},
		},
	],
})
