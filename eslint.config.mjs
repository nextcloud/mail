/*
* SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
* SPDX-License-Identifier: AGPL-3.0-or-later
*/

import { recommended } from '@nextcloud/eslint-config'
import pluginVitest from 'eslint-plugin-vitest-globals'
import { defineConfig } from 'eslint/config'

export default defineConfig([
	...recommended,
	{
		plugins: {
			vitest: pluginVitest,
		},
		languageOptions: {
			globals: {
				...pluginVitest.environments.env.globals,
				__webpack_public_path__: 'writable',
			},
		},
		rules: {
			'no-console': 'error',
			'no-unused-vars': 'warn',

			// 'jsdoc/no-undefined-types': 'error',
			'jsdoc/require-jsdoc': 'off',
			// 'jsdoc/require-param': 'off',

			'perfectionist/sort-enums': 'error',
			'perfectionist/sort-interfaces': 'error',
			'perfectionist/sort-object-types': 'error',

			'@typescript-eslint/no-unused-vars': 'off',
			'@typescript-eslint/no-explicit-any': 'off',

			'vue/multi-word-component-names': 'off',

			// 'sort-imports': ['error', { ignoreDeclarationSort: true }],

			// // Relax some rules for now. Can be improved later one (baseline).
			//
			// // JSDocs are welcome but lint:fix should not create empty ones
			// 'jsdoc/require-jsdoc': 'off',
			// 'jsdoc/require-param': 'off',
			// Forbid empty JSDocs
			// TODO: Enable this rule once @nextcloud/eslint-config was updated and pulls the
			//       newest version of eslint-plugin-jsdoc (is a recent feature/rule).
			// 'jsdoc/no-blank-blocks': 'error',
		},
	},
])
