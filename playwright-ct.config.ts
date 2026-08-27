/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig, devices } from '@playwright/test'

const GALLERY_URL = 'http://localhost:5173/src/tests/component/gallery/index.html'

export default defineConfig({
	testDir: 'src/tests/component',
	snapshotDir: 'src/tests/component/snapshots',
	forbidOnly: !!process.env.CI,
	fullyParallel: true,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? 'blob' : 'html',

	use: {
		...devices['Desktop Chrome'],
		baseURL: GALLERY_URL,
		serviceWorkers: 'block',
		reuseContext: true,
		trace: 'retain-on-failure',
	},
	webServer: {
		command: 'npx vite --config vite-ct.config.ts',
		url: GALLERY_URL,
		reuseExistingServer: !process.env.CI,
	},
})
