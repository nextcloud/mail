/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { readFileSync } from 'fs'
import { join } from 'path'

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
}

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
