/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue2'

export default defineConfig({
	plugins: [vue()],
	test: {
		include: ['src/tests/unit/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		setupFiles: ['./src/tests/setup.js'],
		globals: true,
		environment: 'jsdom',
		// Required for transforming CSS files
		pool: 'vmForks',
	},
});
