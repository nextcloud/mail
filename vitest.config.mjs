/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

export default defineConfig({
	plugins: [vue()],
	test: {
		include: ['src/tests/unit/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
		setupFiles: ['./src/tests/setup.js'],
		globals: true,
		environment: 'jsdom',
		// Required for transforming CSS files
		pool: 'vmForks',
		server: {
			deps: {
				// emoji-mart-vue-fast ships as ESM inside a CJS package; inlining
				// lets Vite handle the transformation so vmForks can load it.
				inline: ['emoji-mart-vue-fast', '@nextcloud/vue'],
			},
		},
	},
})
