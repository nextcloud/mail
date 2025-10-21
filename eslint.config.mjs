/*
* SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
* SPDX-License-Identifier: AGPL-3.0-or-later
*/

import { recommendedVue2 } from '@nextcloud/eslint-config'
import pluginImport from 'eslint-plugin-import'
import pluginPerfectionist from 'eslint-plugin-perfectionist'
import pluginVitest from 'eslint-plugin-vitest-globals'
import { defineConfig } from 'eslint/config'

export default defineConfig([
	...recommendedVue2,
	{
		plugins: {
			perfectionist: pluginPerfectionist,
			import: pluginImport,
			vitest: pluginVitest,
		},
		languageOptions: {
			globals: {
				...pluginVitest.environments.env.globals,
				__webpack_public_path__: 'writable',
			},
		},
		rules: {
			'no-console': 'warn',
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
			// 'import/order': ['error', { groups: ['builtin', 'external', 'internal'], alphabetize: { order: 'asc', caseInsensitive: true } }],

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
