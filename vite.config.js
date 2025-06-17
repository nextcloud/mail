/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import path from 'path'
import { createAppConfig } from '@nextcloud/vite-config'
import { createRequire } from 'node:module'
import ckeditor5 from '@ckeditor/vite-plugin-ckeditor5'

const require = createRequire(import.meta.url)

export default createAppConfig({
	main: path.join(__dirname, 'src', 'main.js'),
	autoredirect: path.join(__dirname, 'src/autoredirect.js'),
	oauthpopup: path.join(__dirname, 'src/main-oauth-popup.js'),
	settings: path.join(__dirname, 'src/main-settings'),
	htmlresponse: path.join(__dirname, 'src/html-response.js'),
}, {
	// Move all css to a single chunk (mail-style.css)
	inlineCSS: false,
	config: {
		build: {
			cssCodeSplit: false,
			minify: false,
		},
		plugins: [
			ckeditor5({ theme: require.resolve('@ckeditor/ckeditor5-theme-lark') }),
		],
	},
})
