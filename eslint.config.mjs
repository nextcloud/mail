/*
* SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
* SPDX-License-Identifier: AGPL-3.0-or-later
*/

import { defineConfig } from 'eslint/config'

// TODO: replace with recommended when migrating to Vue 3
import { recommended } from '@nextcloud/eslint-config'
import perfectionistPlugin from 'eslint-plugin-perfectionist'
import importPlugin from "eslint-plugin-import";

export default defineConfig([
	...recommended,
	{
		plugins: {
			perfectionist: perfectionistPlugin,
			import: importPlugin
		},
		rules: {
			"comma-dangle": "error",

			"jsdoc/no-undefined-types": "error",
			"jsdoc/require-jsdoc": "off",
			// 'jsdoc/require-param': 'off',

			"perfectionist/sort-enums": "error",
			"perfectionist/sort-interfaces": "error",
			"perfectionist/sort-object-types": "error",

			"sort-imports": ["error", {
				"ignoreDeclarationSort": true,
			}],

			"import/order": ["error", {
				"groups": ["builtin", "external", "internal"],

				"alphabetize": {
					"order": "asc",
					"caseInsensitive": true,
				},
			}],


			// // Relax some rules for now. Can be improved later one (baseline).
			// 'no-console': 'off',
			// 'no-unused-vars': 'off',
			// '@typescript-eslint/no-unused-vars': 'off',
			// '@typescript-eslint/no-explicit-any': 'off',
			// 'vue/multi-word-component-names': 'off',
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
