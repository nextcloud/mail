/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import vue from '@vitejs/plugin-vue2'
import { defineConfig } from 'vite'

export default defineConfig({
	plugins: [vue()],
	server: { port: 5173 },
})
