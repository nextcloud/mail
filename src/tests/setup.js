/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* global readTestDataRaw */

import { readFileSync } from 'fs'
import { join } from 'path'
import { vi } from 'vitest'

global.appName = 'mail'

global._nc_l10n_locale = 'en'
global._nc_l10n_language = 'en_US'

global.OC = {
	getLocale: () => 'en',
	getLanguage: () => 'en_US',
	L10N: {
		translate: (app, string) => {
			if (app !== 'mail') {
				throw new Error('tried to translate a string for an app other than Mail')
			}
			return string
		},
	},
	isUserAdmin: () => false,
	config: {
		version: '9999.0.0',
	},
}

global.t = vi.fn().mockImplementation((msg) => msg)
global.n = vi.fn().mockImplementation((msg) => msg)

/**
 * @param {string} path Path to file relative to src/tests/data/
 * @return {Buffer} File contents
 */
global.readTestDataRaw = function(path) {
	path = join('src', 'tests', 'data', path)
	return readFileSync(path)
}

/**
 * @param {string} path Path to file relative to src/tests/data/
 * @return {string} File contents
 */
global.readTestData = function(path) {
	return readTestDataRaw(path).toString('utf-8')
}

/**
 * Convert a Buffer to an ArrayBuffer
 *
 * https://stackoverflow.com/a/12101012
 *
 * @param {Buffer} buffer
 * @return {ArrayBuffer}
 */
global.toArrayBuffer = function(buffer) {
	const arrayBuffer = new ArrayBuffer(buffer.length)
	const view = new Uint8Array(arrayBuffer)
	for (let i = 0; i < buffer.length; ++i) {
		view[i] = buffer[i]
	}
	return arrayBuffer
}
